<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** Recurso Artist de 05 §4. */
class ArtistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'country' => $this->country,
            'genre' => $this->genre,
            'bio' => $this->bio,
            'photo_url' => $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null,
            'albums_count' => $this->albums_count,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
