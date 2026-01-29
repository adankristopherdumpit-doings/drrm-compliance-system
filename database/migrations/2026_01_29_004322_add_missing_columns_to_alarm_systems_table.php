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
            // Add manufacturer if not exists
            if (!Schema::hasColumn('firesafety_alarm_systems', 'manufacturer')) {
                $table->string('manufacturer')->nullable()->after('location');
            }

            // Add installation_date if not exists
            if (!Schema::hasColumn('firesafety_alarm_systems', 'installation_date')) {
                $table->date('installation_date')->nullable()->after('manufacturer');
            }

            // Add notes if not exists
            if (!Schema::hasColumn('firesafety_alarm_systems', 'notes')) {
                $table->text('notes')->nullable()->after('next_test_due');
            }
        });
    }
};
