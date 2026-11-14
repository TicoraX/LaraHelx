<?php

namespace App\Http\Requests;

use App\Support\Images;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Crear y editar álbum: el artista tiene que ser del usuario. */
class AlbumRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'artist_id' => ['required', 'ulid', Rule::exists('artists', 'id')->where('user_id', $this->user()->id)],
            'title' => ['required', 'string', 'max:160'],
            // APP_TIMEZONE=UTC: si cambia la zona, se revisa la regla.
            'year' => ['required', 'integer', 'between:1900,'.(now()->year + 1)],
            'genre' => ['required', Rule::in(config('discoteca.genres'))],
            'cover' => Images::rules(),
        ];
    }

    /** No revela si el artista existe en otra cuenta. */
    public function messages(): array
    {
        return ['artist_id.exists' => 'El artista seleccionado no es válido.'];
    }
}
