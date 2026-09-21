<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('category_song', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained();
            $table->foreignId('song_id')->constrained();

            $table->primary(['category_id', 'song_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_song');
    }
};
