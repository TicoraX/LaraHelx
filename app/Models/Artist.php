<?php

namespace App\Models;

use App\Support\Images;
use Database\Factories\ArtistFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Artist extends Model
{
    /** @use HasFactory<ArtistFactory> */
    use HasFactory, HasUlids;

    protected $fillable = ['name', 'country', 'genre', 'bio', 'photo_path'];

    /**
     * I4: las rutas se juntan antes; el DELETE con su cascada es atómico y los archivos se borran solo
     * si la fila ya no está. Si el DELETE falla, archivos y fila siguen ahí.
     */
    public function deleteWithImages(): void
    {
        $paths = $this->albums()->pluck('cover_path')->push($this->photo_path)->filter()->all();
        $this->delete();
        Images::delete($paths);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function albums(): HasMany
    {
        return $this->hasMany(Album::class);
    }
}
