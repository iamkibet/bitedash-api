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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('restaurant_id')->constrained('restaurants')->onDelete('cascade');
            $table->foreignId('rider_id')->nullable()->constrained('users')->onDelete('set null');
            $table->decimal('total_amount', 10, 2);
            $table->enum('status', ['pending', 'preparing', 'on_the_way', 'delivered', 'cancelled'])->default('pending');
            $table->enum('payment_status', ['unpaid', 'paid', 'failed'])->default('unpaid');
            $table->text('delivery_address')->nullable();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('customer_id');
            $table->index('restaurant_id');
            $table->index('rider_id');
            $table->index('status');
            $table->index('payment_status');
            $table->index(['status', 'payment_status']);
            $table->index(['restaurant_id', 'status']); // For restaurant viewing pending orders
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
