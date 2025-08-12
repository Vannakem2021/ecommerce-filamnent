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
        Schema::create('product_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_attribute_id')->constrained('product_attributes')->cascadeOnDelete();
            $table->string('value')->index(); // e.g., "Red", "16GB", "Large"
            $table->string('slug'); // e.g., "red", "16gb", "large"
            $table->string('color_code')->nullable(); // For color attributes: #FF0000
            $table->text('description')->nullable(); // Optional description
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0)->index(); // For ordering values
            $table->timestamps();

            // Ensure unique slug per attribute
            $table->unique(['product_attribute_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_attribute_values');
    }
};
