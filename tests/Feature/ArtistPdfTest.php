<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArtistPdfTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_se_descarga_como_pdf(): void
    {
        $ana = User::factory()->create();
        $artista = Artist::factory()->for($ana)->create(['name' => 'Soda Stereo']);

        $respuesta = $this->actingAs($ana)->get("/reports/artists/{$artista->id}")->assertOk();

        $this->assertStringStartsWith('application/pdf', $respuesta->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $respuesta->getContent());
    }

    public function test_se_llama_artista_slug_pdf(): void
    {
        $ana = User::factory()->create();
        $artista = Artist::factory()->for($ana)->create(['name' => 'Los Ángeles Azules']);

        $this->actingAs($ana)->get("/reports/artists/{$artista->id}")
            ->assertDownload('artista-los-angeles-azules.pdf');
    }

    public function test_la_de_un_artista_ajeno_da_404(): void
    {
        $ajeno = Artist::factory()->create();

        $this->actingAs(User::factory()->create())->get("/reports/artists/{$ajeno->id}")->assertNotFound();
    }

    public function test_con_una_portada_webp_de_2000_px_empieza_con_pdf_y_tarda_menos_de_3_s(): void
    {
        $ana = User::factory()->create();
        $artista = Artist::factory()->for($ana)->create();
        $artista->albums()->create([
            'title' => 'Portada grande', 'year' => 2020, 'genre' => 'Rock',
            'cover_path' => UploadedFile::fake()->image('c.webp', 2000, 2000)->store('covers', 'public'),
        ]);

        $inicio = microtime(true);
        $respuesta = $this->actingAs($ana)->get("/reports/artists/{$artista->id}")->assertOk();

        $this->assertStringStartsWith('%PDF', $respuesta->getContent());
        $this->assertLessThan(3.0, microtime(true) - $inicio);
    }
}
