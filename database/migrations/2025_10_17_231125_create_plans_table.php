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
        Schema::create('plans', function (Blueprint $table) {
          $table->uuid('id')->primary();

            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('duration_days')->comment('Duration in days (e.g., 30)');
            $table->integer('max_rent_ads')->comment('Limit of "for rent" ads');
            $table->integer('max_sale_ads')->comment('Limit of "for sale" ads');
            $table->enum('visibility_level', ['normal', 'increased', 'maximum'])->default('normal');
            $table->boolean('has_dashboard')->default(true);
            $table->boolean('has_verified_badge')->default(false);
            $table->boolean('has_multi_user_management')->default(false);
            $table->boolean('has_priority_support')->default(false)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
