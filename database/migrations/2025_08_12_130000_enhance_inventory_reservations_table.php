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
        Schema::table('inventory_reservations', function (Blueprint $table) {
            // Add required columns for inventory reservation system
            $table->string('session_id')->after('id')->index()
                ->comment('Session ID to track reservations');
            
            $table->foreignId('product_id')->after('session_id')
                ->constrained('products')->cascadeOnDelete()
                ->comment('Product being reserved');
            
            $table->foreignId('product_variant_id')->nullable()->after('product_id')
                ->constrained('product_variants')->cascadeOnDelete()
                ->comment('Variant being reserved (if applicable)');
            
            $table->integer('quantity')->after('product_variant_id')
                ->comment('Quantity reserved');
            
            $table->timestamp('expires_at')->after('quantity')->index()
                ->comment('When this reservation expires');
            
            // Add composite indexes for performance
            $table->index(['product_id', 'product_variant_id'], 'idx_product_variant');
            $table->index(['session_id', 'expires_at'], 'idx_session_expires');
            $table->index(['expires_at'], 'idx_expires_cleanup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_reservations', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropForeign(['product_variant_id']);
            $table->dropIndex(['product_id', 'product_variant_id']);
            $table->dropIndex(['session_id', 'expires_at']);
            $table->dropIndex(['expires_at']);
            $table->dropColumn([
                'session_id',
                'product_id', 
                'product_variant_id',
                'quantity',
                'expires_at'
            ]);
        });
    }
};
