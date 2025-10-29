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
        Schema::table('ad_versions', function (Blueprint $table) {
            //
            $table->dropColumn('electricity_type');
            $table->dropColumn('legal_status');
            $table->dropColumn('accessibility');
            $table->dropColumn('usage_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ad_versions', function (Blueprint $table) {
            //
            // Rollback des nettoyages
            $table->string('electricity_type')->nullable();
            $table->string('legal_status')->nullable();
            $table->string('accessibility')->nullable();
            $table->string('usage_type')->nullable();
        });
    }
};
