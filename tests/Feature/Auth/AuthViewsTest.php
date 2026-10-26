<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class AuthViewsTest extends TestCase
{
    public function test_login_registro_olvide_y_reset_usan_vistas_propias(): void
    {
        $this->get('/login')->assertOk()->assertViewIs('auth.login')->assertSee('Iniciar sesión');
        $this->get('/register')->assertOk()->assertViewIs('auth.register')->assertSee('Crear cuenta');
        $this->get('/forgot-password')->assertOk()->assertViewIs('auth.forgot-password')->assertSee('Olvidé mi contraseña');
        $this->get('/reset-password/token-de-prueba?email=ana@x.com')
            ->assertOk()
            ->assertViewIs('auth.reset-password')
            ->assertSee('Contraseña nueva')
            ->assertSee('value="token-de-prueba"', false)
            ->assertSee('value="ana@x.com"', false);
    }
}
