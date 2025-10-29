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
        Schema::create('ads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Clé étrangère vers l'utilisateur
            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Clé étrangère vers la version active (sera ajoutée après la création de la table ad_versions)
            $table->uuid('active_version_id')->nullable();
            
            $table->enum('global_status', ['active', 'inactive', 'draft'])->default('draft');
            
            // Dates importantes
            $table->timestamp('published_at')->nullable()->comment('Date of active version publication');
            $table->timestamp('last_updated_at')->nullable()->comment('Last ad modification date');
            
            // Métriques temps réel
            $table->unsignedInteger('views_count')->default(0)->comment('Cumulative number of views (clicks)');
            $table->unsignedInteger('contact_count')->default(0)->comment('Cumulative number of contact requests');
            $table->unsignedInteger('favorites_count')->default(0)->comment('Cumulative number of times favorited');

            // Scores (pour le ranking)
            $table->float('badge_score')->default(0)->comment('0 or 1 based on user badge');
            $table->float('recency_score')->default(0.3)->comment('Score based on publication recency');
            $table->float('location_score')->default(0)->comment('0 or 1 based on location completeness');
            $table->float('views_score')->default(0)->comment('Views count normalized score');
            $table->float('final_score')->default(0)->comment('Global score for sorting/ranking');

            $table->timestamps(); // created_at (date_creation)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ads');
    }
};
