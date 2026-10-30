<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatsTest extends TestCase
{
    use RefreshDatabase;

    private function discos(Artist $artist, array $generos): void
    {
        foreach ($generos as $i => $genero) {
            $artist->albums()->create(['title' => "Disco $i", 'year' => 2000, 'genre' => $genero]);
        }
    }

    public function test_el_grafico_recibe_los_conteos_por_genero_del_usuario(): void
    {
        $ana = User::factory()->create();
        $this->discos(Artist::factory()->for($ana)->create(), ['Rock', 'Rock', 'Pop']);
        $this->discos(Artist::factory()->for($ana)->create(), ['Rock', 'Salsa']);

        $this->actingAs($ana)->get('/stats')->assertOk()
            ->assertViewHas('genres', ['Rock' => 3, 'Pop' => 1, 'Salsa' => 1])
            ->assertSee('<script type="application/json" id="stats-data">{"labels":["Rock","Pop","Salsa"],"values":[3,1,1]}</script>', false)
            ->assertSee('role="img"', false);
    }

    public function test_no_cuenta_albumes_ajenos(): void
    {
        $ana = User::factory()->create();
        $this->discos(Artist::factory()->for($ana)->create(), ['Jazz']);
        $this->discos(Artist::factory()->create(), ['Jazz', 'Jazz', 'Rock']);

        $this->actingAs($ana)->get('/stats')->assertViewHas('genres', ['Jazz' => 1]);
    }

    public function test_sin_albumes_muestra_el_estado_vacio(): void
    {
        $ana = User::factory()->create();
        Artist::factory()->for($ana)->create();

        $this->actingAs($ana)->get('/stats')->assertOk()
            ->assertSee('Agrega álbumes para ver tus estadísticas')
            ->assertDontSee('stats-data', false);
    }
}
