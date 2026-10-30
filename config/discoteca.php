<?php

return [
    // Lista cerrada de géneros (05 §2, I6): el gráfico no parte "Rock" y "rock".
    'genres' => ['Rock', 'Pop', 'Hip hop', 'Electrónica', 'Jazz', 'Clásica', 'Salsa', 'Reggaetón', 'Vallenato', 'Otro'],

    // Clave de los usuarios del seeder de demostración (05 §6); en producción es obligatoria.
    'demo_password' => env('SEED_DEMO_PASSWORD'),
];
