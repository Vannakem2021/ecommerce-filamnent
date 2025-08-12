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
        Schema::table('addresses', function (Blueprint $table) {
            // Drop old columns if they exist
            if (Schema::hasColumn('addresses', 'first_name')) {
                $table->dropColumn(['first_name', 'last_name', 'phone', 'street_address', 'city', 'state', 'zip_code']);
            }

            // Add new columns if they don't exist
            if (!Schema::hasColumn('addresses', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            }

            if (!Schema::hasColumn('addresses', 'type')) {
                $table->string('type')->default('shipping')->after('order_id');
            }

            if (!Schema::hasColumn('addresses', 'contact_name')) {
                $table->string('contact_name')->after('type');
            }

            if (!Schema::hasColumn('addresses', 'phone_number')) {
                $table->string('phone_number')->after('contact_name');
            }

            if (!Schema::hasColumn('addresses', 'house_number')) {
                $table->string('house_number')->nullable()->after('phone_number');
            }

            if (!Schema::hasColumn('addresses', 'street_number')) {
                $table->string('street_number')->nullable()->after('house_number');
            }

            if (!Schema::hasColumn('addresses', 'city_province')) {
                $table->string('city_province')->after('street_number');
            }

            if (!Schema::hasColumn('addresses', 'district_khan')) {
                $table->string('district_khan')->after('city_province');
            }

            if (!Schema::hasColumn('addresses', 'commune_sangkat')) {
                $table->string('commune_sangkat')->after('district_khan');
            }

            if (!Schema::hasColumn('addresses', 'postal_code')) {
                $table->string('postal_code')->after('commune_sangkat');
            }

            if (!Schema::hasColumn('addresses', 'additional_info')) {
                $table->text('additional_info')->nullable()->after('postal_code');
            }

            if (!Schema::hasColumn('addresses', 'is_default')) {
                $table->boolean('is_default')->default(false)->after('additional_info');
            }

            // Make order_id nullable for user addresses
            $table->foreignId('order_id')->nullable()->change();
        });

        // Add indexes
        Schema::table('addresses', function (Blueprint $table) {
            try {
                $table->index(['user_id', 'type']);
            } catch (\Exception) {
                // Index might already exist
            }

            try {
                $table->index(['user_id', 'is_default']);
            } catch (\Exception) {
                // Index might already exist
            }

            try {
                $table->index('postal_code');
            } catch (\Exception) {
                // Index might already exist
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            // Drop new columns
            $table->dropColumn([
                'user_id', 'type', 'contact_name', 'phone_number', 'house_number',
                'street_number', 'city_province', 'district_khan', 'commune_sangkat',
                'postal_code', 'additional_info', 'is_default'
            ]);

            // Add back old columns
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone')->nullable();
            $table->longText('street_address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip_code')->nullable();

            // Make order_id required again
            $table->foreignId('order_id')->change();
        });
    }
};
