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
        Schema::create('firesafety_alarm_systems', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('firesafety_school_information');
            $table->foreignId('building_id')->nullable()->constrained('firesafety_buildings');
            $table->string('code');
            $table->string('location');
            $table->string('alarm_type');
            $table->enum('status', ['active', 'offline', 'maintenance']); // or your status values
            $table->date('last_test')->nullable();
            $table->date('next_test_due')->nullable(); // ADD THIS
            $table->string('manufacturer')->nullable(); // ADD THIS
            $table->date('installation_date')->nullable(); // ADD THIS
            $table->text('notes')->nullable(); // ADD THIS
            $table->timestamps();
        });
    }
};
