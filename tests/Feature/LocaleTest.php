<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_los_mensajes_de_validacion_salen_en_espanol(): void
    {
        $this->actingAs(User::factory()->create())->post('/artists', ['genre' => 'Rock']);

        $mensaje = session('errors')->first('name');
        $this->assertStringContainsString('nombre', $mensaje);
        $this->assertStringContainsString('obligatorio', $mensaje);
    }

    public function test_el_login_fallido_sale_en_espanol(): void
    {
        $this->from('/login')->post('/login', ['email' => 'nadie@x.com', 'password' => 'otra-clave']);

        $this->assertStringContainsString('credenciales', session('errors')->first('email'));
    }

    public function test_el_correo_de_reset_sale_en_espanol_y_dice_que_vence_a_los_15_minutos(): void
    {
        Notification::fake();
        $ana = User::factory()->create(['email' => 'ana@x.com']);

        $this->post('/forgot-password', ['email' => 'ana@x.com']);

        Notification::assertSentTo($ana, ResetPassword::class, function (ResetPassword $n) use ($ana) {
            $correo = $n->toMail($ana);
            $texto = implode(' ', [...$correo->introLines, ...$correo->outroLines]);
            $this->assertStringContainsString('contraseña', mb_strtolower($correo->subject));
            $this->assertStringContainsString('Restablecer contraseña', $correo->actionText);
            $this->assertStringContainsString('15 minutos', $texto);
            $this->assertStringNotContainsString('Reset', $correo->subject.$texto);

            return true;
        });
    }

    public function test_el_401_de_la_api_sale_en_espanol(): void
    {
        $this->get('/api/v1/artists')->assertUnauthorized()->assertExactJson(['message' => 'No autenticado.']);
    }
}
