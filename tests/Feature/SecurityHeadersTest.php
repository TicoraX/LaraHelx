<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_produccion_envia_las_cinco_cabeceras_de_seguridad(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->get('/')
            ->assertOk()
            ->assertHeader('Content-Security-Policy', "default-src 'self'; script-src 'self'; img-src 'self' data:; frame-ancestors 'none'")
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    }

    public function test_fuera_de_produccion_no_hay_csp_para_que_vite_dev_cargue(): void
    {
        $this->get('/')->assertOk()->assertHeaderMissing('Content-Security-Policy');
    }

    public function test_detras_de_caddy_las_redirecciones_respetan_https(): void
    {
        $this->get('/dashboard', ['X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'laravel.helx.page'])
            ->assertRedirect('https://laravel.helx.page/login');
    }
}
