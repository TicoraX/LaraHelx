<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sin user_id: el dueño de un álbum es el de su artista (05 §1).
        Schema::create('albums', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('artist_id')->index()->constrained()->cascadeOnDelete();
            $table->string('title', 160);
            $table->smallInteger('year');
            $table->string('genre', 30);
            $table->string('cover_path')->nullable();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE albums ADD CONSTRAINT albums_year_check CHECK (year BETWEEN 1900 AND 2100)');
    }

    public function down(): void
    {
        Schema::dropIfExists('albums');
    }
};
