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
        Schema::create('ad_version_equipment', function (Blueprint $table) {
                     $table->uuid('equipment_id'); // ⬅️ Clé en UUID
            $table->foreign('equipment_id')->references('id')->on('equipments')->onDelete('cascade');
            
            $table->uuid('ad_version_id'); // ⬅️ Clé en UUID (vers ta table existante)
            $table->foreign('ad_version_id')->references('id')->on('ad_versions')->onDelete('cascade');
            
            // Clé primaire composite
            $table->primary(['equipment_id', 'ad_version_id']);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_version_equipment');
    }
};
