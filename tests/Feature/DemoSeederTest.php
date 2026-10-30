<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Models\Artist;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_es_idempotente_al_correrlo_dos_veces(): void
    {
        $this->seed(DemoSeeder::class);
        $this->seed(DemoSeeder::class);

        $this->assertSame([2, 6, 12], [User::count(), Artist::count(), Album::count()]);
    }

    public function test_crea_el_usuario_demo_con_sus_datos_y_un_segundo_usuario(): void
    {
        config(['discoteca.demo_password' => 'clave-de-prueba-1']);
        $this->seed(DemoSeeder::class);

        $demo = User::where('email', 'demo@discoteca.test')->sole();
        $this->assertSame([5, 12], [$demo->artists()->count(), $demo->albums()->count()]);
        $this->assertTrue(password_verify('clave-de-prueba-1', $demo->password));
        $otra = User::where('email', 'otra@discoteca.test')->sole();
        $this->assertSame([1, 0], [$otra->artists()->count(), $otra->albums()->count()]);
        $this->assertSame(1986, $demo->albums()->where('albums.title', 'Signos')->value('albums.year'));
    }

    public function test_falla_en_produccion_sin_seed_demo_password(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config(['discoteca.demo_password' => null]);

        // run() directo: $this->seed() pasa por el comando db:seed, que pide confirmación en producción.
        try {
            $this->app->make(DemoSeeder::class)->run();
            $this->fail('El seeder debía fallar sin SEED_DEMO_PASSWORD');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('SEED_DEMO_PASSWORD', $e->getMessage());
            $this->assertSame(0, User::count());
        }
    }
}
