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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Display name of the payment method');
            $table->string('code', 50)->unique()->comment('Unique code identifier');
            $table->string('provider', 100)->comment('Payment provider (e.g., aba_pay, stripe)');
            $table->boolean('is_active')->default(true)->comment('Whether the payment method is active');
            $table->json('configuration')->nullable()->comment('Provider-specific configuration');
            $table->integer('sort_order')->default(0)->comment('Display order');
            $table->text('description')->nullable()->comment('Description for admin/customers');
            $table->string('icon')->nullable()->comment('Icon class or image path');
            $table->decimal('min_amount', 10, 2)->nullable()->comment('Minimum transaction amount');
            $table->decimal('max_amount', 10, 2)->nullable()->comment('Maximum transaction amount');
            $table->json('supported_currencies')->nullable()->comment('Supported currencies array');
            $table->timestamps();

            // Indexes
            $table->index(['is_active', 'sort_order']);
            $table->index('provider');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
