<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TokenApiTest extends TestCase
{
    use RefreshDatabase;

    private function pedir(array $datos = [])
    {
        return $this->postJson('/api/v1/tokens', ['email' => 'ana@x.com', 'password' => 'clave-1234', 'device_name' => 'Postman', ...$datos]);
    }

    public function test_emite_un_token_que_sirve_para_la_api(): void
    {
        $ana = User::factory()->create(['email' => 'ana@x.com', 'password' => 'clave-1234']);

        $token = $this->pedir()->assertCreated()->assertJsonStructure(['token'])->json('token');

        $this->assertSame('Postman', $ana->tokens()->sole()->name);
        $this->withToken($token)->get('/api/v1/artists')->assertOk();
    }

    public function test_metodo_no_permitido_en_la_api_responde_405_en_espanol(): void
    {
        $this->deleteJson('/api/v1/tokens')->assertStatus(405)
            ->assertExactJson(['message' => 'Método no permitido']);
    }

    public function test_el_mensaje_de_device_name_vacio_esta_en_espanol(): void
    {
        $this->postJson('/api/v1/tokens', [])->assertStatus(422)
            ->assertJsonValidationErrors('device_name')
            ->assertJsonPath('errors.device_name.0', fn ($m) => str_contains($m, 'nombre del dispositivo') && ! str_contains($m, 'device name'));
    }

    public function test_acepta_el_correo_con_mayusculas(): void
    {
        User::factory()->create(['email' => 'ana@x.com', 'password' => 'clave-1234']);

        $this->pedir(['email' => 'ANA@X.com'])->assertCreated();
    }

    public function test_credenciales_malas_dan_422_con_el_mismo_mensaje(): void
    {
        User::factory()->create(['email' => 'ana@x.com', 'password' => 'clave-1234']);

        $malaClave = $this->pedir(['password' => 'otra'])->assertStatus(422)->json('message');
        $noExiste = $this->pedir(['email' => 'nadie@x.com'])->assertStatus(422)->json('message');

        $this->assertSame('Las credenciales no son válidas', $malaClave);
        $this->assertSame($malaClave, $noExiste);
    }

    public function test_el_sexto_intento_en_un_minuto_da_429(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->pedir()->assertStatus(422);
        }

        $this->pedir()->assertStatus(429)->assertHeader('Retry-After')->assertJsonPath('message', 'Demasiados intentos.');
    }
}
