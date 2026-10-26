<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artists', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('country', 80)->nullable();
            $table->string('genre', 30);
            $table->text('bio')->nullable();
            $table->string('photo_path')->nullable();
            $table->timestamps();

            // Cubre también las consultas por user_id (columna líder), 05 §1.
            $table->unique(['user_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artists');
    }
};
