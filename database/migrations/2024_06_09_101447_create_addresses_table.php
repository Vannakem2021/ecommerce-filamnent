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
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained('orders')->cascadeOnDelete();
            $table->string('type')->default('shipping'); // shipping, billing

            // Contact Information
            $table->string('contact_name'); // Combined name field for Cambodia
            $table->string('phone_number'); // Required phone number

            // Cambodia Address Structure
            $table->string('house_number')->nullable(); // House/building number
            $table->string('street_number')->nullable(); // Street number/name
            $table->string('city_province'); // City/Province (រាជធានីភ្នំពេញ)
            $table->string('district_khan'); // District/Srok/Khan (ខណ្ឌចំការមន)
            $table->string('commune_sangkat'); // Commune/Khum/Sangkat (សង្កាត់ ទន្លេបាសាក់)
            $table->string('postal_code'); // Auto-filled based on area selection

            // Additional fields
            $table->text('additional_info')->nullable(); // Additional delivery instructions
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'is_default']);
            $table->index('postal_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
