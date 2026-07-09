<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // PASTE YOUR CODE RIGHT HERE:
        Schema::table('firesafety_buildings', function (Blueprint $table) {
            $table->integer('capacity')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('firesafety_buildings', function (Blueprint $table) {
            // This safely reverses it back to mandatory if you ever roll back
            $table->integer('capacity')->nullable(false)->change();
        });
    }
};
