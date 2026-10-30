<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AlbumCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $ana;

    private Artist $soda;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->ana = User::factory()->create();
        $this->soda = Artist::factory()->for($this->ana)->create(['name' => 'Soda Stereo']);
    }

    private function datos(array $extra = []): array
    {
        return ['artist_id' => $this->soda->id, 'title' => 'Signos', 'year' => 1986, 'genre' => 'Rock', ...$extra];
    }

    public function test_crear_un_album_con_portada_lleva_a_su_detalle(): void
    {
        $respuesta = $this->actingAs($this->ana)->post('/albums', $this->datos([
            'cover' => UploadedFile::fake()->image('signos.jpg', 600, 600),
        ]));

        $album = $this->ana->albums()->sole();
        $respuesta->assertRedirect("/albums/{$album->id}")->assertSessionHas('status', 'Álbum creado');
        $this->assertSame([$this->soda->id, 'Signos', 1986, 'Rock'], [$album->artist_id, $album->title, $album->year, $album->genre]);
        $this->assertMatchesRegularExpression('#^covers/[A-Za-z0-9]{40}\.jpg$#', $album->cover_path);
        Storage::disk('public')->assertExists($album->cover_path);
    }

    public function test_ver_el_detalle_muestra_portada_artista_ano_y_genero(): void
    {
        $album = $this->soda->albums()->create(['title' => 'Signos', 'year' => 1986, 'genre' => 'Rock', 'cover_path' => 'covers/signos.jpg']);

        $this->actingAs($this->ana)->get("/albums/{$album->id}")
            ->assertOk()
            ->assertSeeInOrder(['Signos', 'Soda Stereo', '1986', 'Rock'])
            ->assertSee(route('artists.show', $this->soda))
            ->assertSee('/storage/covers/signos.jpg');
    }

    public function test_editar_cambia_artista_datos_y_portada_y_borra_la_vieja(): void
    {
        $vieja = UploadedFile::fake()->image('vieja.jpg')->store('covers', 'public');
        $album = $this->soda->albums()->create(['title' => 'Signos', 'year' => 1986, 'genre' => 'Rock', 'cover_path' => $vieja]);
        $cerati = Artist::factory()->for($this->ana)->create(['name' => 'Gustavo Cerati']);

        $this->actingAs($this->ana)->get("/albums/{$album->id}/edit")
            ->assertOk()
            ->assertSee('value="Signos"', false)
            ->assertSee('Gustavo Cerati');

        $this->put("/albums/{$album->id}", $this->datos([
            'artist_id' => $cerati->id, 'title' => 'Bocanada', 'year' => 1999,
            'cover' => UploadedFile::fake()->image('bocanada.png'),
        ]))->assertRedirect("/albums/{$album->id}")->assertSessionHas('status', 'Álbum actualizado');

        $album->refresh();
        $this->assertSame([$cerati->id, 'Bocanada', 1999], [$album->artist_id, $album->title, $album->year]);
        Storage::disk('public')->assertExists($album->cover_path);
        Storage::disk('public')->assertMissing($vieja);
    }

    public function test_borrar_elimina_el_album_y_su_portada_y_vuelve_a_la_lista(): void
    {
        $portada = UploadedFile::fake()->image('signos.jpg')->store('covers', 'public');
        $album = $this->soda->albums()->create(['title' => 'Signos', 'year' => 1986, 'genre' => 'Rock', 'cover_path' => $portada]);

        $this->actingAs($this->ana)->delete("/albums/{$album->id}")
            ->assertRedirect('/albums')
            ->assertSessionHas('status', 'Álbum eliminado');

        $this->assertModelMissing($album);
        $this->assertModelExists($this->soda);
        Storage::disk('public')->assertMissing($portada);
    }

    public function test_rechaza_un_artist_id_ajeno(): void
    {
        $ajeno = Artist::factory()->create();

        $this->actingAs($this->ana)->post('/albums', $this->datos(['artist_id' => $ajeno->id]))
            ->assertSessionHasErrors(['artist_id' => 'El artista seleccionado no es válido.']);

        $this->assertSame(0, $ajeno->albums()->count());
    }

    /** @return array<string, array{int}> */
    public static function anosFueraDeRango(): array
    {
        return ['antes de 1900' => [1899], 'despues del proximo ano' => [(int) date('Y') + 2]];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('anosFueraDeRango')]
    public function test_un_ano_fuera_de_rango_da_error_de_validacion(int $ano): void
    {
        $this->actingAs($this->ana)->post('/albums', $this->datos(['year' => $ano]))->assertSessionHasErrors('year');

        $this->assertSame(0, $this->ana->albums()->count());
    }

    public function test_un_genero_fuera_de_la_lista_da_error_de_validacion(): void
    {
        $this->actingAs($this->ana)->post('/albums', $this->datos(['genre' => 'Cumbia']))->assertSessionHasErrors('genre');

        $this->assertSame(0, $this->ana->albums()->count());
    }
}
