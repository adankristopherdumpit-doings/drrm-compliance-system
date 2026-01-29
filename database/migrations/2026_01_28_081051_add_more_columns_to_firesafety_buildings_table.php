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
        Schema::table('firesafety_buildings', function (Blueprint $table) {
            $table->string('building_name')->nullable()->after('building_no');
            $table->integer('year_constructed')->nullable()->after('capacity');
            $table->integer('last_renovation')->nullable()->after('year_constructed');
            $table->integer('emergency_exits')->nullable()->after('last_renovation');
            $table->string('building_type')->nullable()->after('emergency_exits');
            $table->text('description')->nullable()->after('building_type');
            $table->text('features')->nullable()->after('description'); // Stores comma-separated safety features
        });
    }

    public function down()
    {
        Schema::table('firesafety_buildings', function (Blueprint $table) {
            $table->dropColumn([
                'building_name',
                'year_constructed',
                'last_renovation',
                'emergency_exits',
                'building_type',
                'description',
                'features'
            ]);
        });
    }
};
