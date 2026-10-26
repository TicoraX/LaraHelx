<?php

namespace Tests\Feature\Api;

use App\Models\Artist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArtistIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_sin_token_y_sin_accept_responde_401_en_json(): void
    {
        $this->get('/api/v1/artists')
            ->assertUnauthorized()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonStructure(['message']);
    }

    public function test_con_token_lista_solo_mis_artistas_paginados_de_a_15(): void
    {
        $ana = User::factory()->create();
        Artist::factory()->count(16)->for($ana)->create();
        $ajeno = Artist::factory()->create(['name' => 'Artista ajeno']);
        $token = $ana->createToken('postman')->plainTextToken;

        $respuesta = $this->withToken($token)->get('/api/v1/artists')
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.total', 16)
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonStructure(['data', 'links' => ['next'], 'meta']);

        $this->assertNotContains($ajeno->id, $respuesta->json('data.*.id'));
        $this->withToken($token)->get('/api/v1/artists?page=2')->assertJsonCount(1, 'data');
    }

    public function test_cada_artista_sigue_el_contrato_del_recurso(): void
    {
        $ana = User::factory()->create();
        $artista = Artist::factory()->for($ana)->create([
            'name' => 'Soda Stereo', 'country' => 'Argentina', 'genre' => 'Rock', 'bio' => null, 'photo_path' => null,
        ]);
        $artista->albums()->createMany([
            ['title' => 'Signos', 'year' => 1986, 'genre' => 'Rock'],
            ['title' => 'Canción animal', 'year' => 1990, 'genre' => 'Rock'],
        ]);

        $item = $this->withToken($ana->createToken('postman')->plainTextToken)
            ->get('/api/v1/artists')
            ->assertOk()
            ->json('data.0');

        $this->assertSame(
            ['id', 'name', 'country', 'genre', 'bio', 'photo_url', 'albums_count', 'created_at', 'updated_at'],
            array_keys($item),
        );
        $this->assertSame($artista->id, $item['id']);
        $this->assertSame(26, strlen($item['id']));
        $this->assertSame(2, $item['albums_count']);
        $this->assertNull($item['photo_url']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{6}Z$/', $item['created_at']);
    }

    public function test_un_token_invalido_responde_401(): void
    {
        $this->withToken('1|no-existe')->get('/api/v1/artists')->assertUnauthorized();
    }

    public function test_una_ruta_inexistente_de_la_api_responde_404_sin_nombre_de_clase(): void
    {
        $this->get('/api/v1/no-existe')
            ->assertNotFound()
            ->assertExactJson(['message' => 'No encontrado']);
    }
}
