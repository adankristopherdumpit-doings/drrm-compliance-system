<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('fire_safety_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('firesafety_school_information');
            $table->foreignId('building_id')->constrained('firesafety_buildings');
            $table->date('inspection_date');
            $table->string('inspection_type');
            $table->string('inspector');
            $table->text('notes')->nullable();
            $table->enum('status', ['scheduled', 'pending', 'completed', 'overdue', 'cancelled'])->default('scheduled');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('fire_safety_inspections');
    }
};
