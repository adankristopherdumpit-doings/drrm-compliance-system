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
        Schema::table('firesafety_alarm_systems', function (Blueprint $table) {
            $table->date('next_test_due')->nullable()->after('last_test');
        });
    }

    public function down()
    {
        Schema::table('firesafety_alarm_systems', function (Blueprint $table) {
            $table->dropColumn('next_test_due');
        });
    }
};
