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
    Schema::create('firesafety_evacuationplans', function (Blueprint $table) {
        $table->id();
        $table->foreignId('school_id')->constrained('firesafety_school_information');
        $table->string('plan_no');
        $table->text('exits');
        $table->text('routes');
        $table->text('areas');
        $table->enum('status', ['approved', 'draft', 'review']);
        $table->timestamps();
    });
}
};
