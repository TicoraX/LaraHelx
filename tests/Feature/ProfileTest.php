<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $ana;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->ana = User::factory()->create(['name' => 'Ana', 'email' => 'ana@x.com']);
    }

    private function guardar(array $datos)
    {
        return $this->actingAs($this->ana)->from('/profile')
            ->put('/user/profile-information', ['name' => $this->ana->name, 'email' => $this->ana->email, ...$datos]);
    }

    public function test_edita_el_nombre(): void
    {
        $this->guardar(['name' => 'Ana María'])->assertRedirect('/profile')->assertSessionHas('status');

        $this->assertSame('Ana María', $this->ana->fresh()->name);
    }

    public function test_edita_el_correo(): void
    {
        $this->guardar(['email' => 'nueva@x.com'])->assertSessionHasNoErrors();

        $this->assertSame('nueva@x.com', $this->ana->fresh()->email);
    }

    public function test_edita_la_foto_y_borra_la_anterior(): void
    {
        $this->guardar(['photo' => UploadedFile::fake()->image('a.jpg', 300, 300)])->assertSessionHasNoErrors();
        $primera = $this->ana->fresh()->photo_path;
        Storage::disk('public')->assertExists($primera);

        $this->guardar(['photo' => UploadedFile::fake()->image('b.png', 300, 300)])->assertSessionHasNoErrors();

        Storage::disk('public')->assertMissing($primera);
        Storage::disk('public')->assertExists($this->ana->fresh()->photo_path);
    }

    public function test_un_correo_de_otra_cuenta_da_error_y_no_se_guarda(): void
    {
        User::factory()->create(['email' => 'otra@x.com']);

        $this->guardar(['name' => 'Cambiado', 'email' => 'otra@x.com'])
            ->assertSessionHasErrorsIn('updateProfileInformation', 'email');

        $this->assertSame(['Ana', 'ana@x.com'], [$this->ana->fresh()->name, $this->ana->fresh()->email]);
    }

    public function test_el_correo_nuevo_se_guarda_en_minusculas(): void
    {
        $this->guardar(['email' => 'ANA.Nueva@X.com']);

        $this->assertSame('ana.nueva@x.com', $this->ana->fresh()->email);
    }

    public function test_la_pantalla_muestra_los_tres_bloques(): void
    {
        $this->actingAs($this->ana)->get('/profile')->assertOk()
            ->assertSee('Mis datos')->assertSee('Cambiar contraseña')->assertSee('Tokens de API')
            ->assertSee('No tienes tokens de API');
    }
}
