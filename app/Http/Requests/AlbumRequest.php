<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

/** Crear y editar álbum (05 §3). I3: el artista tiene que ser del usuario. */
class AlbumRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'artist_id' => ['required', 'ulid', Rule::exists('artists', 'id')->where('user_id', $this->user()->id)],
            'title' => ['required', 'string', 'max:160'],
            // APP_TIMEZONE=UTC: si cambia la zona, se revisa la regla (05 §3).
            'year' => ['required', 'integer', 'between:1900,'.(now()->year + 1)],
            'genre' => ['required', Rule::in(config('discoteca.genres'))],
            'cover' => ['nullable', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(2048)
                ->dimensions(Rule::dimensions()->maxWidth(2000)->maxHeight(2000))],
        ];
    }

    /** Texto de 03 §2: no revela si el artista existe en otra cuenta. */
    public function messages(): array
    {
        return ['artist_id.exists' => 'El artista seleccionado no es válido.'];
    }
}
