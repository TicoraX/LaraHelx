<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_login_aterriza_en_el_dashboard(): void
    {
        $user = User::factory()->create(['email' => 'ana@x.com', 'password' => 'clave-segura-1']);

        $this->post('/login', ['email' => 'ana@x.com', 'password' => 'clave-segura-1'])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_quien_se_registro_en_minusculas_entra_con_mayusculas(): void
    {
        $this->post('/register', [
            'name' => 'Ana Pérez',
            'email' => 'ana@x.com',
            'password' => 'clave-segura-1',
            'password_confirmation' => 'clave-segura-1',
        ]);
        $this->post('/logout');

        $this->post('/login', ['email' => 'Ana@X.com', 'password' => 'clave-segura-1'])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticated();
    }

    public function test_el_login_fallido_da_el_mismo_mensaje_generico_con_o_sin_cuenta(): void
    {
        User::factory()->create(['email' => 'ana@x.com', 'password' => 'clave-segura-1']);

        $claveMala = $this->from('/login')->post('/login', ['email' => 'ana@x.com', 'password' => 'otra-clave']);
        $claveMala->assertRedirect('/login')->assertSessionHasErrors('email');
        $mensajeClaveMala = session('errors')->first('email');

        $sinCuenta = $this->from('/login')->post('/login', ['email' => 'nadie@x.com', 'password' => 'otra-clave']);
        $sinCuenta->assertRedirect('/login')->assertSessionHasErrors(['email' => $mensajeClaveMala]);

        $this->assertGuest();
        $this->assertSame(__('auth.failed'), $mensajeClaveMala);
    }

    public function test_el_logout_invalida_la_sesion(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['dato' => 'de-la-sesion-vieja'])
            ->post('/logout')
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertFalse(session()->has('dato'));
        $this->get('/dashboard')->assertRedirect('/login');
    }
}
