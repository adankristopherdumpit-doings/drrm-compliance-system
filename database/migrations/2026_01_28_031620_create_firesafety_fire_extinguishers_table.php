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
        Schema::create('firesafety_fire_extinguishers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('firesafety_school_information');
            $table->string('code');
            $table->enum('status', ['active', 'expired', 'maintenance', 'missing']);
            $table->date('date_checked');
            $table->string('evaluation_result');
            $table->timestamps();
        });
    }
};
