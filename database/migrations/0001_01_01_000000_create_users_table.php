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
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('profile_type', ['individual', 'professional', 'agency'])->default('individual');
            $table->string('last_name', 100)->nullable();
            $table->string('first_name', 100)->nullable();
            $table->string('username', 100)->unique()->nullable();
            $table->string('otp_code')->nullable(); 
            $table->boolean('is_verified')->default(false);
            $table->string('email', 150)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->text('profile_description')->nullable();
            $table->string('profile_photo_url', 255)->nullable();
            $table->string('password', 255);
            $table->boolean('badge')->default(false);
            $table->enum('profile_status', ['active', 'suspended'])->default('active');
            // $table->uuid('subscription_id')->nullable()->after('id');
            // $table->uuid('subscription_id')->nullable();
            // $table->foreign('subscription_id')->references('id')->on('subscriptions')->onDelete('set null');
            $table->unsignedSmallInteger('reset_attempts')->default(0); // Compteur de tentatives
            $table->timestamp('block_expires_at')->nullable();
            $table->rememberToken();
            $table->timestamp('last_login')->nullable(); 
            $table->timestamps();
            $table->timestamp('otp_expires_at')->nullable();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
