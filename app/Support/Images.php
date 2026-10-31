<?php

namespace App\Support;

use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use RuntimeException;
use Throwable;

/** Fotos y portadas en el disco público: validación, reemplazo y borrado (05 §3, I7). */
final class Images
{
    /** @return array<int, mixed> Reglas de 05 §3: el tipo se decide por el contenido, no por la extensión del cliente. */
    public static function rules(): array
    {
        return ['nullable', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(2048)
            ->dimensions(Rule::dimensions()->maxWidth(2000)->maxHeight(2000))];
    }

    /**
     * Guarda el archivo nuevo, ejecuta $save con la ruta resultante (la nueva o la vieja) y solo después
     * borra la vieja. Si $save lanza, la imagen vieja queda intacta, la nueva se borra y la excepción
     * sigue (03 §4). Al crear, $old es null. store() genera el nombre (I7).
     *
     * @template T
     *
     * @param  Closure(?string): T  $save
     * @return T
     */
    public static function replace(?UploadedFile $file, ?string $old, string $dir, Closure $save): mixed
    {
        $new = $file?->store($dir, 'public');
        try {
            $saved = $save($new ?? $old);
        } catch (Throwable $e) {
            self::delete($new);
            throw $e;
        }
        if ($new && $old) {
            self::delete($old);
        }

        return $saved;
    }

    /**
     * Miniatura cuadrada como data URI para el PDF: dompdf decodifica la imagen entera y una portada de
     * 2000 px tarda segundos (F8). Lanza si el archivo no es una imagen legible.
     */
    public static function thumbnail(string $path, int $size): string
    {
        $original = imagecreatefromstring(Storage::disk('public')->get($path))
            ?: throw new RuntimeException("No se pudo leer la imagen {$path}");
        $small = imagescale($original, $size, $size);
        ob_start();
        imagepng($small);

        return 'data:image/png;base64,'.base64_encode(ob_get_clean());
    }

    /** @param  string|array<int, string>|null  $paths */
    public static function delete(string|array|null $paths): void
    {
        Storage::disk('public')->delete($paths ?? []);
    }
}
