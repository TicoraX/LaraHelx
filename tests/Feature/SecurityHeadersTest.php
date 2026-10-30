<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

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

    public static function paginasDeError(): array
    {
        return [
            '403' => [403, 'No tienes acceso a esto'],
            '404' => [404, 'No encontramos esta página'],
            '413' => [413, 'El archivo es demasiado grande'],
            '419' => [419, 'Tu sesión expiró'],
            '500' => [500, 'Algo falló de nuestro lado'],
        ];
    }

    #[DataProvider('paginasDeError')]
    public function test_en_produccion_cada_error_usa_su_vista_propia_sin_trazas(int $codigo, string $titulo): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config(['app.debug' => false]);
        Route::get('/_falla', fn () => $codigo === 500 ? throw new RuntimeException('detalle-interno-secreto') : abort($codigo));

        $this->get('/_falla')->assertStatus($codigo)
            ->assertSee($titulo)
            ->assertSee('data-layout="guest"', false)
            ->assertDontSee('detalle-interno-secreto')->assertDontSee('RuntimeException')->assertDontSee('vendor/');
    }

    public function test_con_sesion_el_error_hereda_la_maestra_de_la_app(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->actingAs(User::factory()->create())->get('/no-existe')->assertNotFound()
            ->assertSee('data-layout="app"', false)->assertSee('Volver al dashboard');
    }
}
