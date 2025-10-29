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
        Schema::create('ad_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Clé étrangère vers l'annonce (nullable au début)
            $table->uuid('ad_id')->nullable();
            $table->foreign('ad_id')->references('id')->on('ads')->onDelete('cascade');
            
            // Statut de validation
            $table->enum('status', ['draft', 'pending', 'validated', 'refused', 'archived'])->default('pending');
            $table->timestamp('validated_at')->nullable();
            $table->uuid('validated_by_id')->nullable();
            $table->foreign('validated_by_id')->references('id')->on('users')->onDelete('set null');

            // --- Étape 1 : Type & SEO
            $table->enum('ad_type', ['for_rent', 'for_sale']);
            $table->string('seo_description', 255);

            // --- Étape 2 : Localisation
            $table->string('full_address')->nullable();
            $table->string('country', 100);
            $table->string('department', 100)->nullable();
            $table->string('city', 100);
            $table->string('district', 100)->nullable();
            $table->string('street', 100)->nullable();
            $table->text('additional_info')->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();

            // --- Étape 3 : Caractéristiques
            $table->decimal('area_value', 10, 2)->nullable();
            $table->enum('area_unit', ['sqm', 'ha'])->nullable();
            $table->integer('unit_count')->nullable();
            $table->string('construction_type')->nullable();
            $table->string('electricity_type')->nullable();
            $table->text('description');
            $table->string('legal_status')->nullable();
            $table->string('accessibility')->nullable();
            $table->string('usage_type')->nullable();

            // --- Étape 4 : Tarification
            $table->decimal('price', 10, 2);
            $table->string('currency', 10);
            $table->decimal('commission', 10, 2)->nullable();
            $table->integer('deposit_months')->nullable();
            $table->enum('periodicity', ['day', 'night', 'week', 'month'])->nullable();
            $table->boolean('is_negotiable')->default(false);
            
            // --- Relation vers PropertyType
            $table->uuid('property_type_id')->nullable();
            $table->foreign('property_type_id')->references('id')->on('property_types')->onDelete('set null');
            
            // --- Champs legacy (seront dépréciés progressivement)
            $table->json('photos_json')->nullable()->comment('Array of image UUIDs for quick access');
            $table->string('main_photo_filename', 255)->nullable();
            $table->string('video_url', 255)->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->dropForeign(['active_version_id']);
        });
        
        Schema::dropIfExists('ad_versions');
    }
};