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

    public function test_la_de_albumes_entrega_la_tabla_que_datatables_mejora(): void
    {
        $ana = User::factory()->create();
        $soda = Artist::factory()->for($ana)->create(['name' => 'Soda Stereo']);
        $signos = $soda->albums()->create(['title' => 'Signos', 'year' => 1986, 'genre' => 'Rock']);
        Artist::factory()->create()->albums()->create(['title' => 'Album ajeno', 'year' => 2000, 'genre' => 'Pop']);

        $this->actingAs($ana)->get('/albums')
            ->assertOk()
            ->assertSee('<table class="dataTable" data-datatable', false)
            ->assertSeeInOrder(['Signos', 'Soda Stereo', '1986', 'Rock'])
            ->assertSee(route('albums.show', $signos))
            ->assertSee('data-confirm="Vas a eliminar Signos. No se puede deshacer."', false)
            ->assertDontSee('Album ajeno');
    }

    public function test_albumes_sin_artistas_lleva_a_crear_un_artista(): void
    {
        $this->actingAs(User::factory()->create())->get('/albums')
            ->assertOk()
            ->assertSee('Primero crea un artista')
            ->assertSee(route('artists.create'))
            ->assertDontSee(route('albums.create'))
            ->assertDontSee('<table', false);
    }

    public function test_albumes_vacios_con_artistas_ofrece_crear_el_primero(): void
    {
        $ana = User::factory()->create();
        Artist::factory()->for($ana)->create();

        $this->actingAs($ana)->get('/albums')
            ->assertOk()
            ->assertSee('Todavía no tienes álbumes')
            ->assertSee(route('albums.create'));
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
