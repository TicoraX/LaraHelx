<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThrottleAndCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_sexto_login_fallido_en_un_minuto_da_429(): void
    {
        User::factory()->create(['email' => 'ana@x.com']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'ana@x.com', 'password' => 'mala'])->assertSessionHasErrors('email');
        }

        $this->post('/login', ['email' => 'ana@x.com', 'password' => 'mala'])->assertStatus(429);
    }

    public function test_el_septimo_post_a_forgot_password_da_429(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->post('/forgot-password', ['email' => 'nadie@x.com'])->assertSessionHas('status');
        }

        $this->post('/forgot-password', ['email' => 'nadie@x.com'])->assertStatus(429);
    }

    public function test_diez_get_login_seguidos_no_dan_429(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->get('/login')->assertOk();
        }
    }

    public function test_el_dashboard_responde_no_store(): void
    {
        $cache = $this->actingAs(User::factory()->create())->get('/dashboard')->assertOk()->headers->get('Cache-Control');

        $this->assertStringContainsString('no-store', $cache);
        $this->assertStringContainsString('private', $cache);
    }

    public function test_despues_del_logout_el_dashboard_redirige_al_login(): void
    {
        $this->actingAs(User::factory()->create())->post('/logout')->assertRedirect('/');

        $this->get('/dashboard')->assertRedirect('/login');
    }
}
