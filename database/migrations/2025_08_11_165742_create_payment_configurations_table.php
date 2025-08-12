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
        Schema::create('payment_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 100)->comment('Payment provider name');
            $table->enum('environment', ['sandbox', 'production'])->default('sandbox')->comment('Environment type');
            $table->string('merchant_id')->nullable()->comment('Merchant ID for the provider');
            $table->text('api_key')->nullable()->comment('Encrypted API key');
            $table->string('webhook_secret')->nullable()->comment('Webhook secret for verification');
            $table->string('api_url')->nullable()->comment('API endpoint URL');
            $table->string('checkout_url')->nullable()->comment('Checkout page URL');
            $table->json('configuration')->nullable()->comment('Additional provider-specific settings');
            $table->json('supported_currencies')->nullable()->comment('Supported currencies for this config');
            $table->json('supported_payment_options')->nullable()->comment('Supported payment options');
            $table->boolean('is_active')->default(true)->comment('Whether this configuration is active');
            $table->boolean('is_default')->default(false)->comment('Whether this is the default config for provider');
            $table->decimal('min_amount', 10, 2)->nullable()->comment('Minimum transaction amount');
            $table->decimal('max_amount', 10, 2)->nullable()->comment('Maximum transaction amount');
            $table->integer('timeout_seconds')->default(300)->comment('Transaction timeout in seconds');
            $table->timestamps();

            // Indexes and constraints
            $table->unique(['provider', 'environment']);
            $table->index(['provider', 'is_active']);
            $table->index('is_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_configurations');
    }
};
