<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\ArtistRequest as WebArtistRequest;
use Illuminate\Support\Arr;

/** Mismas reglas que la web sin `photo`: la API no maneja archivos (05 §3). */
class ArtistRequest extends WebArtistRequest
{
    public function rules(): array
    {
        return Arr::except(parent::rules(), 'photo');
    }
}
