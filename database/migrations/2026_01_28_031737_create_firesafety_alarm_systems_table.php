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
            $table->string('code');
            $table->string('location');
            $table->string('alarm_type');
            $table->enum('status', ['active', 'offline', 'maintenance']);
            $table->date('last_test');
            $table->timestamps();
        });
    }
};
