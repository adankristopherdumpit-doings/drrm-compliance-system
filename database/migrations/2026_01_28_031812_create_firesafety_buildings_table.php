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
        Schema::create('firesafety_buildings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('firesafety_school_information');
            $table->string('building_no');
            $table->integer('floors');
            $table->integer('rooms');
            $table->integer('capacity');
            $table->timestamps();
        });
    }
};
