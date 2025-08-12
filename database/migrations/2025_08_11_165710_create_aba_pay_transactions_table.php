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
        Schema::create('aba_pay_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('cascade');
            $table->string('transaction_id')->unique()->comment('ABA Pay transaction ID');
            $table->string('merchant_id')->comment('ABA Pay merchant ID');
            $table->decimal('amount', 10, 2)->comment('Transaction amount');
            $table->string('currency', 3)->default('USD')->comment('Transaction currency');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded'])
                  ->default('pending')->comment('Transaction status');
            $table->string('payment_option')->nullable()->comment('ABA Pay payment option (aba_pay, khqr, etc.)');
            $table->string('payment_gate')->nullable()->comment('Payment gate identifier');
            $table->string('request_time', 14)->comment('Request timestamp (YYYYMMDDHHmmss)');
            $table->text('hash')->comment('HMAC SHA-512 hash');
            $table->decimal('shipping', 10, 2)->nullable()->comment('Shipping amount');
            $table->string('type')->default('purchase')->comment('Transaction type (purchase, pre-auth)');
            $table->string('view_type')->nullable()->comment('View type (hosted_view, popup)');
            $table->json('customer_info')->nullable()->comment('Customer information');
            $table->json('urls')->nullable()->comment('Return, cancel, success URLs');
            $table->json('custom_fields')->nullable()->comment('Custom fields data');
            $table->json('response_data')->nullable()->comment('ABA Pay response data');
            $table->json('webhook_data')->nullable()->comment('Webhook response data');
            $table->timestamp('processed_at')->nullable()->comment('When transaction was processed');
            $table->timestamp('webhook_received_at')->nullable()->comment('When webhook was received');
            $table->text('error_message')->nullable()->comment('Error message if failed');
            $table->timestamps();

            // Indexes
            $table->index(['status', 'created_at']);
            $table->index(['merchant_id', 'request_time']);
            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aba_pay_transactions');
    }
};
