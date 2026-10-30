<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_invitado_ve_crear_cuenta_e_iniciar_sesion(): void
    {
        $this->get('/')->assertOk()->assertSee('Crear cuenta')->assertSee('Iniciar sesión');
    }

    public function test_con_sesion_el_cta_lleva_al_dashboard(): void
    {
        $this->actingAs(User::factory()->create())->get('/')
            ->assertSee('href="'.route('dashboard').'"', false)
            ->assertDontSee('Crear cuenta');
    }

    public function test_cada_pantalla_usa_su_maestra(): void
    {
        $this->get('/')->assertSee('data-layout="guest"', false);
        $this->get('/login')->assertSee('data-layout="guest"', false);
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertSee('data-layout="app"', false);
    }
}
