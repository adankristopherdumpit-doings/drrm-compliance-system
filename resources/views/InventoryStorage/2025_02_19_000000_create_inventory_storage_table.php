<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_storage', function (Blueprint $table) {
            $table->id();
            $table->string('item_name');
            $table->string('unit')->nullable();
            $table->integer('quantity')->default(0);
            $table->string('status');
            $table->string('location')->nullable();
            $table->string('fund_source')->nullable();
            $table->date('date_received')->nullable();
            $table->date('date_checked')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_storage');
    }
};