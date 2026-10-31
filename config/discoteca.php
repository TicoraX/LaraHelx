<?php

return [
    // Lista cerrada de géneros (05 §2, I6): el gráfico no parte "Rock" y "rock".
    'genres' => ['Rock', 'Pop', 'Hip hop', 'Electrónica', 'Jazz', 'Clásica', 'Salsa', 'Reggaetón', 'Vallenato', 'Otro'],

    // Países en español desde ICU (extensión intl): solo códigos ISO de 2 letras, sin agrupaciones
    // como "Unión Europea" ni "Región desconocida". Evita países inventados o extintos.
    'countries' => collect(ResourceBundle::create('es', 'ICUDATA-region')->get('Countries'))
        ->filter(fn ($name, $code) => preg_match('/^[A-Z]{2}$/', $code) && ! in_array($code, ['EU', 'EZ', 'UN', 'QO', 'XA', 'XB', 'ZZ']))
        ->sort(fn ($a, $b) => strcoll($a, $b))->values()->all(),

    // Clave de los usuarios del seeder de demostración (05 §6); en producción es obligatoria.
    'demo_password' => env('SEED_DEMO_PASSWORD'),
];
