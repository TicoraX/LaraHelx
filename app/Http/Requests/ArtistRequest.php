<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

/** Crear y editar artista (05 §3): mismas reglas, ignore() es null al crear. */
class ArtistRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120',
                Rule::unique('artists')->where('user_id', $this->user()->id)->ignore($this->route('artist'))],
            'country' => ['nullable', 'string', 'max:80'],
            'genre' => ['required', Rule::in(config('discoteca.genres'))],
            'bio' => ['nullable', 'string', 'max:1000'],
            // El tipo se decide por el contenido (fileinfo), no por la extensión del cliente.
            'photo' => ['nullable', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(2048)
                ->dimensions(Rule::dimensions()->maxWidth(2000)->maxHeight(2000))],
        ];
    }
}
