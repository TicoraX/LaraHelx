<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToWriteFile;
use Tests\TestCase;

class ConfigTest extends TestCase
{
    public function test_no_existen_rutas_de_2fa_ni_de_passkeys(): void
    {
        $uris = collect(Route::getRoutes()->getRoutes())->map->uri();

        $this->assertNotEmpty($uris->filter(fn ($uri) => $uri === 'login'));
        $this->assertEmpty($uris->filter(fn ($uri) => str_contains($uri, 'two-factor') || str_contains($uri, 'passkey')));
    }

    public function test_el_disco_public_lanza_excepcion_si_falla_una_escritura(): void
    {
        // Escribir sobre una ruta que ya es carpeta falla en fopen: es la rama que 'throw' decide.
        $root = sys_get_temp_dir().'/discoteca-disco-'.uniqid();
        mkdir("{$root}/artists/x.jpg", recursive: true);
        config(['filesystems.disks.public.root' => $root]);
        Storage::forgetDisk('public');

        try {
            $this->expectException(UnableToWriteFile::class);
            Storage::disk('public')->put('artists/x.jpg', 'contenido');
        } finally {
            rmdir("{$root}/artists/x.jpg");
            rmdir("{$root}/artists");
            rmdir($root);
        }
    }

    public function test_env_example_declara_la_configuracion_del_equipo(): void
    {
        $env = file_get_contents(base_path('.env.example'));

        foreach ([
            '/^DB_CONNECTION=pgsql$/m',
            '/^QUEUE_CONNECTION=sync$/m',
            '/^SESSION_DRIVER=database$/m',
            '/^GOREST_TOKEN=$/m',
            '/^SEED_DEMO_PASSWORD=\S+$/m',
            '/^MAIL_FROM_ADDRESS="no-reply@helx\.page"$/m',
        ] as $linea) {
            $this->assertMatchesRegularExpression($linea, $env);
        }
    }

    public function test_ninguna_ruta_propia_es_un_closure(): void
    {
        $closures = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => $route->getAction('uses') instanceof \Closure)
            // Closures del framework: el health check y el disco local (route:cache los serializa).
            ->reject(fn ($route) => $route->uri() === 'up' || str_starts_with((string) $route->getName(), 'storage.local'))
            ->map->uri()
            ->values()
            ->all();

        $this->assertSame([], $closures);
    }
}
