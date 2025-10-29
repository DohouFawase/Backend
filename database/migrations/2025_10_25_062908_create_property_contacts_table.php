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
        Schema::create('property_contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Lien vers l'annonce (AdVersion)
            $table->uuid('ad_version_id');
            $table->foreign('ad_version_id')
                  ->references('id')
                  ->on('ad_versions')
                  ->onDelete('cascade');
            
            // Propriétaire du bien (celui qui reçoit le message)
            $table->uuid('owner_id');
            $table->foreign('owner_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
            
            // Informations du visiteur (celui qui envoie le message)
            $table->string('visitor_name');
            $table->string('visitor_email');
            $table->string('visitor_phone')->nullable();
            
            // Message
            $table->text('message');
            
            // Statut
            $table->enum('status', ['new', 'read', 'replied', 'archived'])
                  ->default('new');
            
            // Métadonnées
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            
            // Dates
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            
            // Index pour performance
            $table->index(['owner_id', 'status']);
            $table->index('ad_version_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_contacts');
    }
};
