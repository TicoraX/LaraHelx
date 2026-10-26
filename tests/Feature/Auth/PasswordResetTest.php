<?php

namespace Tests\Feature\Auth;

use App\Http\Responses\PasswordResetLinkResponse;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->user = User::factory()->create(['email' => 'ana@x.com', 'password' => 'clave-vieja-1']);
    }

    public function test_olvide_responde_igual_con_un_correo_existente_y_uno_inexistente(): void
    {
        $existe = $this->from('/forgot-password')->post('/forgot-password', ['email' => 'ana@x.com']);
        $noExiste = $this->from('/forgot-password')->post('/forgot-password', ['email' => 'nadie@x.com']);

        foreach ([$existe, $noExiste] as $response) {
            $response->assertRedirect('/forgot-password')
                ->assertSessionHas('status', PasswordResetLinkResponse::MESSAGE)
                ->assertSessionHasNoErrors();
        }
        Notification::assertSentTo($this->user, ResetPassword::class);
        Notification::assertCount(1);
    }

    public function test_el_reset_cambia_la_contrasena_y_la_vieja_deja_de_servir(): void
    {
        $this->resetear($this->pedirToken(), 'clave-nueva-1')->assertRedirect('/login');

        $this->assertFalse($this->entra('clave-vieja-1'));
        $this->assertTrue($this->entra('clave-nueva-1'));
    }

    public function test_el_enlace_vencido_a_los_16_minutos_no_cambia_la_contrasena(): void
    {
        $token = $this->pedirToken();

        $this->travel(16)->minutes();
        $this->resetear($token, 'clave-nueva-1')->assertSessionHasErrors('email');

        $this->assertFalse($this->entra('clave-nueva-1'));
        $this->assertTrue($this->entra('clave-vieja-1'));
    }

    public function test_el_enlace_usado_no_sirve_dos_veces(): void
    {
        $token = $this->pedirToken();
        $this->resetear($token, 'clave-nueva-1')->assertRedirect('/login');

        $this->resetear($token, 'clave-otra-1')->assertSessionHasErrors('email');

        $this->assertFalse($this->entra('clave-otra-1'));
        $this->assertTrue($this->entra('clave-nueva-1'));
    }

    private function pedirToken(): string
    {
        $this->post('/forgot-password', ['email' => 'ana@x.com']);

        $token = null;
        Notification::assertSentTo($this->user, ResetPassword::class, function (ResetPassword $n) use (&$token) {
            $token = $n->token;

            return true;
        });

        return $token;
    }

    private function resetear(string $token, string $password)
    {
        return $this->from("/reset-password/{$token}")->post('/reset-password', [
            'token' => $token,
            'email' => 'ana@x.com',
            'password' => $password,
            'password_confirmation' => $password,
        ]);
    }

    private function entra(string $password): bool
    {
        $this->post('/login', ['email' => 'ana@x.com', 'password' => $password]);
        $entro = auth()->check();
        if ($entro) {
            $this->post('/logout');
        }

        return $entro;
    }
}
