<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TokenUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_crear_muestra_el_valor_una_sola_vez(): void
    {
        $ana = User::factory()->create();

        $this->actingAs($ana)->post('/profile/tokens', ['token_name' => 'Postman'])->assertRedirect('/profile');
        $plano = $ana->tokens()->sole()->id.'|';

        $this->actingAs($ana)->get('/profile')->assertSee($plano)->assertSee('no podrás verlo de nuevo')->assertSee('data-copy="token-nuevo"', false);
        $this->actingAs($ana)->get('/profile')->assertDontSee($plano);
    }

    public function test_la_lista_muestra_nombre_y_fecha(): void
    {
        $ana = User::factory()->create();
        $ana->createToken('Mi laptop');

        $this->actingAs($ana)->get('/profile')->assertSee('Mi laptop')->assertSee(now()->timezone('America/Bogota')->format('d/m/Y'));
    }

    public function test_revocar_un_token_propio(): void
    {
        $ana = User::factory()->create();
        $token = $ana->createToken('Postman')->accessToken;

        $this->actingAs($ana)->delete("/profile/tokens/{$token->id}")->assertRedirect('/profile');

        $this->assertSame(0, $ana->tokens()->count());
    }

    public function test_revocar_uno_ajeno_da_404(): void
    {
        $token = User::factory()->create()->createToken('suyo')->accessToken;

        $this->actingAs(User::factory()->create())->delete("/profile/tokens/{$token->id}")->assertNotFound();

        $this->assertDatabaseHas('personal_access_tokens', ['id' => $token->id]);
    }

    public function test_el_nombre_es_obligatorio(): void
    {
        $this->actingAs(User::factory()->create())->post('/profile/tokens', ['token_name' => ''])
            ->assertSessionHasErrors('token_name');
    }
}
