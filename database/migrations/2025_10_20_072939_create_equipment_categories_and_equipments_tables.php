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
         Schema::create('equipment_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 50)->unique(); 
            $table->string('icon_class', 50)->nullable(); 
            $table->integer('sort_order')->default(0); 
            $table->timestamps();
        });

        Schema::create('equipments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('category_id');
            $table->foreign('category_id')->references('id')->on('equipment_categories')->onDelete('cascade');

            $table->string('name', 100)->unique(); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
      Schema::dropIfExists('equipments');
        Schema::dropIfExists('equipment_categories');
    }
};
