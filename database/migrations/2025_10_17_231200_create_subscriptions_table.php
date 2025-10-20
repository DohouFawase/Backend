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
        Schema::create('subscriptions', function (Blueprint $table) {
         $table->uuid('id')->primary();

            $table->uuid('user_id');
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            $table->uuid('plan_id');
            $table->foreign('plan_id')->references('id')->on('plans')->onDelete('restrict');

            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->enum('status', ['active', 'expired', 'pending', 'cancelled'])->default('pending');
            $table->string('payment_method', 100)->nullable();
            $table->string('transaction_reference', 150)->unique()->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10);
            $table->boolean('auto_renewal')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
