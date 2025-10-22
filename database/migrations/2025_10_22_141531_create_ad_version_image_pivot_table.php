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
      Schema::create('ad_version_image', function (Blueprint $table) {
            
            // Clé Étrangère vers la version d'annonce
            $table->uuid('ad_version_id');
            $table->foreign('ad_version_id')->references('id')->on('ad_versions')->onDelete('cascade');

            // Clé Étrangère vers l'image
            $table->uuid('image_id');
            $table->foreign('image_id')->references('id')->on('property_images')->onDelete('cascade');
            
            // Indicateur si c'est la photo principale (très pratique !)
            $table->boolean('is_main')->default(false); 
            
            $table->primary(['ad_version_id', 'image_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_version_image_pivot');
    }
};
