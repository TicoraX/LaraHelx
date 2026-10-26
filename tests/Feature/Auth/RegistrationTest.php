<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_registro_aterriza_en_el_dashboard(): void
    {
        $this->post('/register', [
            'name' => 'Ana Pérez',
            'email' => 'ana@x.com',
            'password' => 'clave-segura-1',
            'password_confirmation' => 'clave-segura-1',
        ])->assertRedirect('/dashboard');

        $this->get('/dashboard')->assertOk()->assertSee('Hola, Ana Pérez');
    }

    public function test_registrar_un_correo_existente_con_mayusculas_da_error_de_validacion(): void
    {
        User::factory()->create(['email' => 'ana@x.com']);

        $this->from('/register')->post('/register', [
            'name' => 'Otra Ana',
            'email' => 'ANA@x.com',
            'password' => 'clave-segura-1',
            'password_confirmation' => 'clave-segura-1',
        ])->assertRedirect('/register')->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
