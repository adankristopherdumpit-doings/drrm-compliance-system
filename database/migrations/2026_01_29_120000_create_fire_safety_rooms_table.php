<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fire_safety_rooms', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')
                ->constrained('firesafety_school_information')
                ->onDelete('cascade');

            $table->foreignId('building_id')
                ->constrained('firesafety_buildings')
                ->onDelete('cascade');

            $table->string('room_code')->nullable(); // e.g. Rm-101
            $table->string('room_name'); // e.g. Room 101, Science Lab
            $table->enum('room_type', ['classroom', 'laboratory', 'auxiliary', 'office', 'storage', 'others'])
                ->default('classroom');
            $table->unsignedSmallInteger('floor_no')->nullable();

            $table->timestamps();

            $table->index(['school_id', 'building_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fire_safety_rooms');
    }
};

