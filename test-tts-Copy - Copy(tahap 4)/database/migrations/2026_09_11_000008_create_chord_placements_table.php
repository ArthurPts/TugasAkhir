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
        Schema::create('chord_placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lyric_line_id')->constrained('lyric_lines');
            $table->foreignId('chord_id')->constrained('chords');
            $table->integer('position');
            $table->decimal('start_time', 8, 3)->nullable();
            $table->integer('start_beat')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chord_placements');
    }
};
