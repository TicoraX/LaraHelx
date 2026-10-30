<?php

namespace Tests\Feature\Api;

use App\Models\Artist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArtistApiTest extends TestCase
{
    use RefreshDatabase;

    private User $ana;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ana = User::factory()->create();
        $this->token = $this->ana->createToken('postman')->plainTextToken;
    }

    public function test_ver_un_artista(): void
    {
        $artista = Artist::factory()->for($this->ana)->create(['name' => 'Soda Stereo']);
        $artista->albums()->create(['title' => 'Signos', 'year' => 1986, 'genre' => 'Rock']);

        $this->withToken($this->token)->get("/api/v1/artists/{$artista->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $artista->id)
            ->assertJsonPath('data.name', 'Soda Stereo')
            ->assertJsonPath('data.albums_count', 1);
    }

    public function test_crear_responde_201_con_location(): void
    {
        $respuesta = $this->withToken($this->token)
            ->post('/api/v1/artists', ['name' => 'Shakira', 'genre' => 'Pop', 'country' => 'Colombia'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Shakira')
            ->assertJsonPath('data.albums_count', 0);

        $artista = $this->ana->artists()->sole();
        $respuesta->assertHeader('Location', url("/api/v1/artists/{$artista->id}"));
    }

    public function test_put_reemplaza_y_pone_a_null_los_opcionales_omitidos(): void
    {
        $artista = Artist::factory()->for($this->ana)->create(['country' => 'Chile', 'bio' => 'Algo']);

        $this->withToken($this->token)->put("/api/v1/artists/{$artista->id}", ['name' => 'Nuevo nombre', 'genre' => 'Jazz'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Nuevo nombre')
            ->assertJsonPath('data.country', null);

        $this->assertNull($artista->fresh()->bio);
        $this->assertSame('Jazz', $artista->fresh()->genre);
    }

    public function test_delete_responde_204_y_borra(): void
    {
        $artista = Artist::factory()->for($this->ana)->create();

        $this->withToken($this->token)->delete("/api/v1/artists/{$artista->id}")->assertNoContent();

        $this->assertModelMissing($artista);
    }

    public function test_un_token_de_mas_de_30_dias_da_401(): void
    {
        $this->ana->tokens()->update(['created_at' => now()->subDays(31)]);

        $this->withToken($this->token)->get('/api/v1/artists')->assertUnauthorized();
    }

    public function test_el_ajeno_y_el_inexistente_dan_el_mismo_404(): void
    {
        $ajeno = Artist::factory()->create();
        $datos = ['name' => 'X', 'genre' => 'Rock'];

        $respuestas = [
            $this->withToken($this->token)->get("/api/v1/artists/{$ajeno->id}"),
            $this->withToken($this->token)->get('/api/v1/artists/01JBX3K8Q4V9W2M6N7P5R1S0TA'),
            $this->withToken($this->token)->put("/api/v1/artists/{$ajeno->id}", $datos),
            $this->withToken($this->token)->delete("/api/v1/artists/{$ajeno->id}"),
            $this->withToken($this->token)->put("/api/v1/artists/{$ajeno->id}", ['name' => '']),
        ];

        foreach ($respuestas as $r) {
            $r->assertNotFound()->assertExactJson(['message' => 'No encontrado']);
            $this->assertStringNotContainsString('App\Models', $r->getContent());
        }
        $this->assertModelExists($ajeno);
    }

    public function test_datos_invalidos_sin_accept_dan_422_en_json_por_campo(): void
    {
        $this->withToken($this->token)->post('/api/v1/artists', ['name' => '', 'genre' => 'Polka'])
            ->assertStatus(422)
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonValidationErrors(['name', 'genre']);
    }

    public function test_la_api_no_acepta_foto(): void
    {
        $this->withToken($this->token)->post('/api/v1/artists', ['name' => 'Sin foto', 'genre' => 'Rock', 'photo' => 'x'])->assertCreated();

        $this->assertNull($this->ana->artists()->sole()->photo_path);
    }
}
