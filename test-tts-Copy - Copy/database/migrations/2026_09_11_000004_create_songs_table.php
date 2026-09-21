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
        Schema::create('songs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('title', 75);
            $table->foreignId('artist_id')->constrained('artists');
            $table->string('default_key', 45)->nullable();
            $table->smallInteger('bpm')->default(80);
            $table->integer('time_signature_numerator')->nullable();
            $table->integer('time_signature_denominator')->nullable();
            $table->enum('visibility', ['public', 'private'])->default('private');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('songs');
    }
};
