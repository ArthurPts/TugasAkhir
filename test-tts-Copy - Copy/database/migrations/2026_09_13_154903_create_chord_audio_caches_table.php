<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chord_audio_caches', function (Blueprint $table) {
            $table->id();
            $table->string('text_hash', 64); // sha1(text|voice), buat index cepat
            $table->string('text', 200);
            $table->string('voice', 100);
            $table->string('file_path', 255); // relative path di disk 'public'
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamps();

            $table->unique(['text_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chord_audio_caches');
    }
};