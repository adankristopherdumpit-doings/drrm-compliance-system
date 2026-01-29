<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('firesafety_fire_extinguishers', function (Blueprint $table) {
            // Add building_id column after school_id
            $table->foreignId('building_id')
                  ->nullable()
                  ->after('school_id')
                  ->constrained('firesafety_buildings')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('firesafety_fire_extinguishers', function (Blueprint $table) {
            $table->dropForeign(['building_id']);
            $table->dropColumn('building_id');
        });
    }
};
