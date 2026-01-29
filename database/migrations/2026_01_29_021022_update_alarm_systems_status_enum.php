<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // ADD THIS LINE

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        DB::statement("ALTER TABLE firesafety_alarm_systems
            MODIFY COLUMN status
            ENUM('functional', 'broken', 'missing', 'not_installed', 'jammed', 'under_repair', 'online', 'offline', 'system_error', 'under_maintenance', 'decommissioned', 'active', 'maintenance')
            NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // You might want to add a down() method to revert
        DB::statement("ALTER TABLE firesafety_alarm_systems
            MODIFY COLUMN status
            ENUM('active', 'offline', 'maintenance')
            NOT NULL");
    }
};
