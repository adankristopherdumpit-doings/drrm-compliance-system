<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fire_safety_extinguisher_room_coverage', function (Blueprint $table) {
            $table->id();

            $table->foreignId('extinguisher_id')
                ->constrained('firesafety_fire_extinguishers')
                ->onDelete('cascade');

            $table->foreignId('room_id')
                ->constrained('fire_safety_rooms')
                ->onDelete('cascade');

            $table->timestamps();

            $table->unique(['extinguisher_id', 'room_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fire_safety_extinguisher_room_coverage');
    }
};

