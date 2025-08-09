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
        Schema::create('inventory_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->string('session_id')->nullable()->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['active', 'expired', 'fulfilled', 'cancelled'])->default('active')->index();
            $table->timestamp('expires_at')->index();
            $table->string('reference_type')->nullable(); // 'cart', 'checkout', 'order'
            $table->string('reference_id')->nullable(); // cart_item_id, checkout_id, order_id
            $table->timestamps();

            // Indexes for performance
            $table->index(['product_id', 'status', 'expires_at']);
            $table->index(['product_variant_id', 'status', 'expires_at']);
            $table->index(['session_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_reservations');
    }
};
