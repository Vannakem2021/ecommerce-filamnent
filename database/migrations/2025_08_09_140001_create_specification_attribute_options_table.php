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
        Schema::create('specification_attribute_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('specification_attribute_id')
                ->constrained('specification_attributes')
                ->cascadeOnDelete();
            $table->string('value')->index(); // e.g., "Intel i7", "AMD Ryzen 7", "OLED", "LCD"
            $table->string('slug'); // e.g., "intel-i7", "amd-ryzen-7", "oled", "lcd"
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0)->index();
            $table->timestamps();

            // Ensure unique values per attribute
            $table->unique(['specification_attribute_id', 'value'], 'spec_attr_value_unique');
            $table->unique(['specification_attribute_id', 'slug'], 'spec_attr_slug_unique');

            // Indexes for performance
            $table->index(['specification_attribute_id', 'is_active']);
            $table->index(['specification_attribute_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('specification_attribute_options');
    }
};
