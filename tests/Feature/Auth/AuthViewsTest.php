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

    public function test_los_campos_de_contrasena_traen_mostrar_y_el_aviso_de_coincidencia(): void
    {
        $registro = $this->get('/register')->assertOk();

        // Un botón por campo, oculto hasta que app.js lo activa (sin JS no sirve).
        $this->assertSame(2, substr_count($registro->getContent(), 'data-toggle-password'));
        $registro->assertSee('aria-controls="password"', false)
            ->assertSee('aria-label="Mostrar contraseña"', false)
            ->assertSee('data-match="password"', false)
            ->assertSee('aria-live="polite"', false);
    }
}
