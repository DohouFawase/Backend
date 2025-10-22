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
        Schema::create('property_images', function (Blueprint $table) {
           $table->uuid('id')->primary(); 
            
            // 🎯 L'identifiant du fichier stocké (ex: nom généré)
            $table->string('filename')->unique();
            
            // 🎯 Chemin complet du fichier (ex: 'annonces/123/image.jpg')
            $table->string('filepath');
            
            // 🎯 Taille du fichier en octets
            $table->unsignedBigInteger('file_size');
            
            // 🎯 Type MIME (ex: 'image/jpeg')
            $table->string('mime_type', 50);
            
            // 🎯 Qui a téléchargé l'image (utile pour le nettoyage)
            $table->uuid('user_id'); 
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_images');
    }
};
