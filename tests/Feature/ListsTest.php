<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListsTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_de_artistas_entrega_la_tabla_que_datatables_mejora(): void
    {
        $ana = User::factory()->create();
        $soda = Artist::factory()->for($ana)->create(['name' => 'Soda Stereo', 'country' => 'Argentina', 'genre' => 'Rock']);
        $soda->albums()->createMany([
            ['title' => 'Signos', 'year' => 1986, 'genre' => 'Rock'],
            ['title' => 'Doble vida', 'year' => 1988, 'genre' => 'Rock'],
        ]);
        Artist::factory()->for($ana)->create(['name' => 'Solista'])->albums()->create(['title' => 'Uno', 'year' => 2000, 'genre' => 'Pop']);
        Artist::factory()->for($ana)->create(['name' => 'Sin discos']);
        Artist::factory()->create(['name' => 'Artista ajeno']);

        $this->actingAs($ana)->get('/artists')
            ->assertOk()
            ->assertSee('<table class="dataTable" data-datatable', false)
            ->assertSeeInOrder(['Soda Stereo', 'Argentina', 'Rock', '2'])
            ->assertSee(route('artists.show', $soda))
            ->assertSee('data-confirm="Vas a eliminar a Soda Stereo y sus 2 álbumes. No se puede deshacer."', false)
            ->assertSee('data-confirm="Vas a eliminar a Solista y su álbum. No se puede deshacer."', false)
            ->assertSee('data-confirm="Vas a eliminar a Sin discos. No se puede deshacer."', false)
            ->assertDontSee('Artista ajeno');
    }

    public function test_sin_artistas_muestra_el_estado_vacio_con_cta(): void
    {
        $this->actingAs(User::factory()->create())->get('/artists')
            ->assertOk()
            ->assertSee('Todavía no tienes artistas')
            ->assertSee('Crear el primero')
            ->assertSee(route('artists.create'))
            ->assertDontSee('<table', false);
    }
}
