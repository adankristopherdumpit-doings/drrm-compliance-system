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
        Schema::create('firesafety_school_information', function (Blueprint $table) {
            $table->id();
            $table->string('school_name');
            $table->string('school_id')->unique();
            $table->string('school_head');
            $table->string('school_drrm_coordinator');
            $table->string('status')->default('unconfigured');
            $table->timestamps();
        });
    }
};
