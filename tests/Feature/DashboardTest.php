<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_muestra_el_nombre(): void
    {
        $user = User::factory()->create(['name' => 'Ana Pérez']);

        $this->actingAs($user)->get('/dashboard')->assertOk()->assertSee('Hola, Ana Pérez');
    }

    public function test_muestra_la_foto_si_existe(): void
    {
        $user = User::factory()->create(['photo_path' => 'avatars/abc.jpg']);

        $this->actingAs($user)->get('/dashboard')->assertSee('storage/avatars/abc.jpg');
    }

    public function test_muestra_las_iniciales_si_no_hay_foto(): void
    {
        $user = User::factory()->create(['name' => 'Ana Pérez', 'photo_path' => null]);

        $this->actingAs($user)->get('/dashboard')->assertSee('class="galleta"', false)->assertSee('AP');
    }

    public function test_el_menu_esta_completo_y_el_logout_es_post(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/dashboard')->assertOk();

        foreach (['/dashboard', '/artists', '/albums', '/stats', '/external/users', '/profile'] as $path) {
            $response->assertSee('href="'.url($path).'"', false);
        }
        $response->assertSee('method="POST" action="'.route('logout').'"', false);
    }

    public function test_los_conteos_son_reales_y_no_incluyen_lo_ajeno(): void
    {
        $user = User::factory()->create();
        $albums = [['title' => 'Uno', 'year' => 2000, 'genre' => 'Rock'], ['title' => 'Dos', 'year' => 2001, 'genre' => 'Pop']];
        Artist::factory()->for($user)->count(3)->create()->each(fn ($a) => $a->albums()->createMany($albums));
        Artist::factory()->create()->albums()->createMany($albums);

        $this->actingAs($user)->get('/dashboard')
            ->assertSee('data-count="artists">3<', false)
            ->assertSee('data-count="albums">6<', false);
    }

    public function test_sin_artistas_muestra_el_estado_vacio(): void
    {
        $this->actingAs(User::factory()->create())->get('/dashboard')
            ->assertSee('Todavía no tienes artistas')
            ->assertSee('Crear tu primer artista')
            ->assertDontSee('data-count', false);
    }
}
