<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Album extends Model
{
    use HasUlids;

    protected $fillable = ['title', 'year', 'genre', 'cover_path'];

    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }
}
