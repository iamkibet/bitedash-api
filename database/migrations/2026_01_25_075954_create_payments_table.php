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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->string('transaction_reference')->unique(); // Paystack reference
            $table->decimal('amount', 10, 2);
            $table->string('provider')->default('paystack'); // For future extensibility
            $table->string('method')->default('mpesa'); // mpesa, card, etc.
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->json('raw_payload')->nullable(); // Store full Paystack response
            $table->string('phone_number')->nullable(); // M-Pesa phone number
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('transaction_reference');
            $table->index('status');
            $table->index(['order_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
