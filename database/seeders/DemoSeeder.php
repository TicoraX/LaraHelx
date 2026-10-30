<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Datos de demostración (05 §6): discografías públicas, sin fotos. Idempotente: se puede correr
 * dos veces (el día de la demo corre en producción con --force) y deja los mismos conteos.
 */
class DemoSeeder extends Seeder
{
    private const CATALOGO = [
        ['Soda Stereo', 'Argentina', 'Rock', [['Signos', 1986], ['Canción Animal', 1990], ['Sueño Stereo', 1995]]],
        ['Shakira', 'Colombia', 'Pop', [['Pies Descalzos', 1995], ['¿Dónde Están los Ladrones?', 1998], ['Laundry Service', 2001]]],
        ['Carlos Vives', 'Colombia', 'Vallenato', [['Clásicos de la Provincia', 1993], ['La Tierra del Olvido', 1995]]],
        ['Queen', 'Reino Unido', 'Rock', [['A Night at the Opera', 1975], ['News of the World', 1977]]],
        ['Daft Punk', 'Francia', 'Electrónica', [['Discovery', 2001], ['Random Access Memories', 2013]]],
    ];

    public function run(): void
    {
        $password = config('discoteca.demo_password');
        if (! $password) {
            if (app()->isProduction()) {
                throw new RuntimeException('Falta SEED_DEMO_PASSWORD: en producción el seeder no usa una clave por defecto.');
            }
            $password = 'demo-local-2026';
        }

        $demo = User::updateOrCreate(['email' => 'demo@discoteca.test'], ['name' => 'Usuario Demo', 'password' => $password]);
        foreach (self::CATALOGO as [$nombre, $pais, $genero, $albumes]) {
            $artista = $demo->artists()->updateOrCreate(['name' => $nombre], ['country' => $pais, 'genre' => $genero]);
            foreach ($albumes as [$titulo, $anio]) {
                $artista->albums()->updateOrCreate(['title' => $titulo], ['year' => $anio, 'genre' => $genero]);
            }
        }

        // Un segundo usuario con un artista propio: la demo muestra que no se ve lo ajeno.
        User::updateOrCreate(['email' => 'otra@discoteca.test'], ['name' => 'Otra Persona', 'password' => $password])
            ->artists()->updateOrCreate(['name' => 'Juanes'], ['country' => 'Colombia', 'genre' => 'Rock']);
    }
}
