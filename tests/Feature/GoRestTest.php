<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GoRestTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = 'https://gorest.co.in/public/v2';

    private User $ana;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.gorest.token' => 'token-de-prueba-123456']);
        $this->ana = User::factory()->create();
    }

    private function usuario(array $extra = []): array
    {
        return ['id' => 7771, 'name' => 'Luis', 'email' => 'luis@discoteca.test', 'gender' => 'male', 'status' => 'active', ...$extra];
    }

    private function datos(array $extra = []): array
    {
        return ['name' => 'Luis', 'email' => 'luis@discoteca.test', 'gender' => 'male', 'status' => 'active', ...$extra];
    }

    public function test_get_filtra_por_discoteca_test_con_per_page_100(): void
    {
        Http::fake([self::BASE.'/users*' => Http::response([$this->usuario()])]);

        $this->actingAs($this->ana)->get('/external/users')->assertOk()->assertSee('luis@discoteca.test');

        Http::assertSent(function (Request $r) {
            parse_str(parse_url($r->url(), PHP_URL_QUERY), $q);

            return $r->method() === 'GET' && str_starts_with($r->url(), self::BASE.'/users?')
                && $q === ['email' => 'discoteca.test', 'per_page' => '100'];
        });
    }

    public function test_post_crea_el_usuario(): void
    {
        Http::fake([self::BASE.'/users' => Http::response($this->usuario(), 201)]);

        $this->actingAs($this->ana)->post('/external/users', $this->datos())
            ->assertRedirect('/external/users')->assertSessionHas('status', 'Usuario creado en GoRest (id 7771)');

        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && $r->data() === $this->datos());
    }

    public function test_put_edita_el_usuario(): void
    {
        Http::fake([self::BASE.'/users/7771' => Http::response($this->usuario(['name' => 'Luis B']))]);

        $this->actingAs($this->ana)->put('/external/users/7771', $this->datos(['name' => 'Luis B']))
            ->assertRedirect('/external/users')->assertSessionHas('status', 'Usuario actualizado en GoRest (id 7771)');

        Http::assertSent(fn (Request $r) => $r->method() === 'PUT' && $r['name'] === 'Luis B');
    }

    public function test_delete_borra_el_usuario(): void
    {
        Http::fake([self::BASE.'/users/7771' => Http::response(null, 204)]);

        $this->actingAs($this->ana)->delete('/external/users/7771')
            ->assertRedirect('/external/users')->assertSessionHas('status', 'Usuario eliminado de GoRest (id 7771)');

        Http::assertSent(fn (Request $r) => $r->method() === 'DELETE');
    }

    public function test_cada_llamada_sale_con_bearer_y_accept_json(): void
    {
        Http::fake([self::BASE.'/users*' => Http::response([]), self::BASE.'/users' => Http::response($this->usuario(), 201)]);

        $this->actingAs($this->ana)->get('/external/users');
        $this->actingAs($this->ana)->post('/external/users', $this->datos());

        Http::assertSentCount(2);
        Http::assertSent(fn (Request $r) => $r->hasHeader('Authorization', 'Bearer token-de-prueba-123456') && $r->hasHeader('Accept', 'application/json'));
    }

    public function test_el_recuadro_tecnico_enmascara_el_token(): void
    {
        Http::fake([self::BASE.'/users*' => Http::response([])]);

        $this->actingAs($this->ana)->get('/external/users')
            ->assertSee('Bearer toke…3456')->assertDontSee('token-de-prueba-123456');
    }

    public static function erroresDeLaLista(): array
    {
        return [
            '401' => [401, 'El token de GoRest no es válido o no está configurado'],
            '403' => [403, 'Ese usuario lo creó otra cuenta de GoRest; solo puedes editar los que creaste aquí'],
            '404' => [404, 'Ese usuario ya no existe en GoRest'],
            '429' => [429, 'GoRest limitó las peticiones (429). Espera un momento y reintenta.'],
        ];
    }

    #[DataProvider('erroresDeLaLista')]
    public function test_mensaje_propio_por_codigo_de_estado(int $estado, string $mensaje): void
    {
        Http::fake([self::BASE.'/users*' => Http::response(['message' => 'texto cualquiera de GoRest'], $estado)]);

        $this->actingAs($this->ana)->get('/external/users')->assertOk()
            ->assertSee($mensaje)->assertSee('Reintentar')->assertDontSee('texto cualquiera');
    }

    public function test_los_422_salen_junto_a_cada_campo(): void
    {
        Http::fake([self::BASE.'/users' => Http::response([
            ['field' => 'email', 'message' => 'has already been taken'],
            ['field' => 'name', 'message' => "can't be blank"],
        ], 422)]);

        $this->actingAs($this->ana)->from('/external/users/create')->post('/external/users', $this->datos())
            ->assertRedirect('/external/users/create')
            ->assertSessionHasErrors(['email' => 'has already been taken', 'name' => "can't be blank"]);
    }

    public function test_timeout_muestra_no_pudimos_contactar_y_reintentar(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 28: timed out'));

        $this->actingAs($this->ana)->get('/external/users')->assertOk()
            ->assertSee('No pudimos contactar a GoRest')->assertSee('timed out')->assertSee('Reintentar');
    }

    public function test_una_respuesta_sin_id_es_un_error(): void
    {
        Http::fake([self::BASE.'/users' => Http::response(['name' => 'Luis'], 201)]);

        $this->actingAs($this->ana)->from('/external/users/create')->post('/external/users', $this->datos())
            ->assertRedirect('/external/users/create')
            ->assertSessionHasErrors(['gorest' => 'Respuesta inesperada de GoRest'])
            ->assertSessionMissing('status');
    }

    public function test_una_lista_vacia_muestra_el_estado_vacio(): void
    {
        Http::fake([self::BASE.'/users*' => Http::response([])]);

        $this->actingAs($this->ana)->get('/external/users')
            ->assertSee('Todavía no creaste usuarios externos')->assertSee('Crear uno');
    }

    public function test_el_correo_debe_terminar_en_discoteca_test(): void
    {
        Http::fake();

        $this->actingAs($this->ana)->post('/external/users', $this->datos(['email' => 'luis@gmail.com']))
            ->assertSessionHasErrors('email');

        Http::assertNothingSent();
    }
}
