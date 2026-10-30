<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArtistCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $ana;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->ana = User::factory()->create();
    }

    private function datos(array $extra = []): array
    {
        return ['name' => 'Soda Stereo', 'country' => 'Argentina', 'genre' => 'Rock', 'bio' => 'Trío de Buenos Aires.', ...$extra];
    }

    public function test_crear_un_artista_con_foto_lleva_a_su_detalle(): void
    {
        $respuesta = $this->actingAs($this->ana)->post('/artists', $this->datos([
            'photo' => UploadedFile::fake()->image('cerati.jpg', 400, 400),
        ]));

        $artista = $this->ana->artists()->sole();
        $respuesta->assertRedirect("/artists/{$artista->id}")->assertSessionHas('status', 'Artista creado');
        $this->assertSame(['Soda Stereo', 'Argentina', 'Rock'], [$artista->name, $artista->country, $artista->genre]);
        Storage::disk('public')->assertExists($artista->photo_path);
    }

    public function test_ver_el_detalle_muestra_sus_datos_foto_y_albumes(): void
    {
        $artista = Artist::factory()->for($this->ana)->create($this->datos(['photo_path' => 'artists/foto.jpg']));
        $artista->albums()->create(['title' => 'Signos', 'year' => 1986, 'genre' => 'Rock']);

        $this->actingAs($this->ana)->get("/artists/{$artista->id}")
            ->assertOk()
            ->assertSeeInOrder(['Soda Stereo', 'Argentina', 'Rock', 'Trío de Buenos Aires.', 'Signos', '1986'])
            ->assertSee('/storage/artists/foto.jpg');
    }

    public function test_editar_cambia_los_datos_y_conserva_la_foto_si_no_llega_otra(): void
    {
        $artista = Artist::factory()->for($this->ana)->create($this->datos(['photo_path' => 'artists/foto.jpg']));

        $this->actingAs($this->ana)->get("/artists/{$artista->id}/edit")
            ->assertOk()
            ->assertSee('value="Soda Stereo"', false)
            ->assertSee('/storage/artists/foto.jpg');

        // Mismo nombre: el unique ignora al propio artista.
        $this->put("/artists/{$artista->id}", $this->datos(['country' => 'Chile', 'bio' => null]))
            ->assertRedirect("/artists/{$artista->id}")
            ->assertSessionHas('status', 'Artista actualizado');

        $artista->refresh();
        $this->assertSame(['Chile', null, 'artists/foto.jpg'], [$artista->country, $artista->bio, $artista->photo_path]);
    }

    public function test_editar_con_foto_nueva_borra_la_vieja_despues_de_guardar(): void
    {
        $vieja = UploadedFile::fake()->image('vieja.jpg')->store('artists', 'public');
        $artista = Artist::factory()->for($this->ana)->create($this->datos(['photo_path' => $vieja]));

        $this->actingAs($this->ana)->put("/artists/{$artista->id}", $this->datos([
            'photo' => UploadedFile::fake()->image('nueva.png'),
        ]))->assertRedirect("/artists/{$artista->id}");

        $nueva = $artista->refresh()->photo_path;
        $this->assertNotSame($vieja, $nueva);
        Storage::disk('public')->assertExists($nueva);
        Storage::disk('public')->assertMissing($vieja);
    }

    public function test_borrar_elimina_sus_albumes_su_foto_y_sus_portadas(): void
    {
        $disco = Storage::disk('public');
        $foto = UploadedFile::fake()->image('foto.jpg')->store('artists', 'public');
        $portada = UploadedFile::fake()->image('signos.jpg')->store('covers', 'public');
        $otra = UploadedFile::fake()->image('otra.jpg')->store('covers', 'public');
        $artista = Artist::factory()->for($this->ana)->create(['photo_path' => $foto]);
        $artista->albums()->createMany([
            ['title' => 'Signos', 'year' => 1986, 'genre' => 'Rock', 'cover_path' => $portada],
            ['title' => 'Doble vida', 'year' => 1988, 'genre' => 'Rock'],
        ]);
        Artist::factory()->for($this->ana)->create()->albums()->create(['title' => 'Otro', 'year' => 2000, 'genre' => 'Pop', 'cover_path' => $otra]);

        $this->actingAs($this->ana)->delete("/artists/{$artista->id}")
            ->assertRedirect('/artists')
            ->assertSessionHas('status', 'Artista eliminado');

        $this->assertModelMissing($artista);
        $this->assertSame(['Otro'], $this->ana->albums()->pluck('title')->all());
        $disco->assertMissing([$foto, $portada]);
        $disco->assertExists($otra);
    }

    /** @return array<string, array{UploadedFile}> */
    public static function fotosInvalidas(): array
    {
        return [
            'no es imagen' => [UploadedFile::fake()->create('cerati.pdf', 10, 'application/pdf')],
            'mas de 2 MB' => [UploadedFile::fake()->image('grande.jpg', 400, 400)->size(2049)],
            'mas de 2000 px' => [UploadedFile::fake()->image('ancha.jpg', 2001, 100)],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('fotosInvalidas')]
    public function test_rechaza_una_foto_invalida_sin_crear_nada(UploadedFile $foto): void
    {
        $this->actingAs($this->ana)->from('/artists/create')
            ->post('/artists', $this->datos(['photo' => $foto]))
            ->assertRedirect('/artists/create')
            ->assertSessionHasErrors('photo');

        $this->assertSame(0, $this->ana->artists()->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_evil_php_jpg_se_guarda_con_nombre_aleatorio_y_extension_de_imagen(): void
    {
        $this->actingAs($this->ana)->post('/artists', $this->datos([
            'photo' => UploadedFile::fake()->image('evil.php.jpg'),
        ]))->assertSessionHasNoErrors();

        $ruta = $this->ana->artists()->sole()->photo_path;
        $this->assertMatchesRegularExpression('#^artists/[A-Za-z0-9]{40}\.(jpg|png|webp)$#', $ruta);
        $this->assertStringNotContainsString('evil', $ruta);
    }

    public function test_un_genero_fuera_de_la_lista_da_error_de_validacion(): void
    {
        $this->actingAs($this->ana)->post('/artists', $this->datos(['genre' => 'rock']))
            ->assertSessionHasErrors('genre');

        $this->assertSame(0, $this->ana->artists()->count());
    }

    public function test_si_la_edicion_falla_la_foto_vieja_sigue_ahi(): void
    {
        $vieja = UploadedFile::fake()->image('vieja.jpg')->store('artists', 'public');
        $artista = Artist::factory()->for($this->ana)->create($this->datos(['photo_path' => $vieja]));
        // Simula una falla de la BD al guardar la fila.
        Artist::updating(fn () => throw new \RuntimeException('falla de escritura'));

        $this->withoutExceptionHandling()->expectException(\RuntimeException::class);
        try {
            $this->actingAs($this->ana)->put("/artists/{$artista->id}", $this->datos([
                'photo' => UploadedFile::fake()->image('nueva.png'),
            ]));
        } finally {
            Storage::disk('public')->assertExists($vieja);
            $this->assertSame($vieja, $artista->refresh()->photo_path);
        }
    }
}
