<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class PasswordTest extends TestCase
{
    use RefreshDatabase;

    private User $ana;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ana = User::factory()->create(['password' => 'clave-vieja-1', 'remember_token' => 'token-viejo']);
    }

    private function cambiar(string $actual = 'clave-vieja-1')
    {
        return $this->actingAs($this->ana)->from('/profile')->put('/user/password', [
            'current_password' => $actual, 'password' => 'clave-nueva-2', 'password_confirmation' => 'clave-nueva-2',
        ]);
    }

    public function test_cambia_con_la_actual_correcta(): void
    {
        $this->cambiar()->assertRedirect('/profile')->assertSessionHas('status');

        $this->assertTrue(Hash::check('clave-nueva-2', $this->ana->fresh()->password));
    }

    public function test_rechaza_una_actual_incorrecta(): void
    {
        $this->cambiar('equivocada')->assertSessionHasErrorsIn('updatePassword', 'current_password');

        $this->assertTrue(Hash::check('clave-vieja-1', $this->ana->fresh()->password));
    }

    public function test_borra_las_otras_sesiones_y_deja_la_actual(): void
    {
        $fila = fn (string $id, int $user) => ['id' => $id, 'user_id' => $user, 'payload' => '', 'last_activity' => time()];
        $ajena = User::factory()->create();
        $actual = Str::random(40);
        DB::table('sessions')->insert([
            $fila('otra-1', $this->ana->id), $fila('otra-2', $this->ana->id),
            $fila($actual, $this->ana->id), $fila('de-otro', $ajena->id),
        ]);

        $this->withCookie(config('session.cookie'), $actual);
        $this->cambiar();

        $this->assertEqualsCanonicalizing([$actual, 'de-otro'], DB::table('sessions')->pluck('id')->all());
    }

    public function test_revoca_sus_tokens_de_api(): void
    {
        $this->ana->createToken('postman');
        $otro = User::factory()->create();
        $otro->createToken('suyo');

        $this->cambiar();

        $this->assertSame(0, $this->ana->tokens()->count());
        $this->assertSame(1, $otro->tokens()->count());
    }

    public function test_rota_el_remember_token(): void
    {
        $this->cambiar();

        $this->assertNotSame('token-viejo', $this->ana->fresh()->remember_token);
    }
}
