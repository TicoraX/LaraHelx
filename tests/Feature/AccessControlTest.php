<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_invitado_va_al_login(): void
    {
        $this->get('/artists')->assertRedirect('/login');
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_el_autenticado_no_ve_login(): void
    {
        $this->actingAs(User::factory()->create())->get('/login')->assertRedirect('/dashboard');
    }

    public function test_el_autenticado_no_ve_register(): void
    {
        $this->actingAs(User::factory()->create())->get('/register')->assertRedirect('/dashboard');
    }

    public function test_un_artista_ajeno_da_404_en_show_edit_update_y_destroy(): void
    {
        $ajeno = Artist::factory()->create();
        $this->actingAs(User::factory()->create());

        $this->get("/artists/{$ajeno->id}")->assertNotFound();
        $this->get("/artists/{$ajeno->id}/edit")->assertNotFound();
        $this->put("/artists/{$ajeno->id}", ['name' => 'Robado', 'genre' => 'Rock'])->assertNotFound();
        $this->delete("/artists/{$ajeno->id}")->assertNotFound();

        $this->assertModelExists($ajeno);
        $this->assertNotSame('Robado', $ajeno->refresh()->name);
    }

    public function test_el_ajeno_y_el_inexistente_responden_con_el_mismo_cuerpo(): void
    {
        $ajeno = Artist::factory()->create();
        $this->actingAs(User::factory()->create());

        $deAjeno = $this->get("/artists/{$ajeno->id}")->assertNotFound()->getContent();
        $deInexistente = $this->get('/artists/'.Str::ulid())->assertNotFound()->getContent();

        $this->assertSame($deInexistente, $deAjeno);
        $this->assertStringNotContainsString($ajeno->name, $deAjeno);
    }

    public function test_un_put_ajeno_con_datos_invalidos_da_404_y_no_422(): void
    {
        $ajeno = Artist::factory()->create();
        $this->actingAs(User::factory()->create());

        $this->put("/artists/{$ajeno->id}", ['name' => '', 'genre' => 'no-existe'])
            ->assertNotFound()
            ->assertSessionDoesntHaveErrors();
    }
}
