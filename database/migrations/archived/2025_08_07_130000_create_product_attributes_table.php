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
        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index(); // e.g., "Color", "Storage", "Size"
            $table->string('slug')->unique(); // e.g., "color", "storage", "size"
            $table->string('type')->default('select'); // select, color, text (for future extensibility)
            $table->boolean('is_required')->default(false); // Whether this attribute is required for variants
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0)->index(); // For ordering attributes in forms
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_attributes');
    }
};
