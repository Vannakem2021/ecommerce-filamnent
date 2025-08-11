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
        Schema::create('variant_migration_audit', function (Blueprint $table) {
            $table->id();
            
            // Migration tracking
            $table->string('phase')->comment('Migration phase (e.g., phase_1, phase_2)');
            $table->string('step')->comment('Migration step within phase');
            $table->string('entity_type')->comment('Type of entity (product, variant, attribute)');
            $table->unsignedBigInteger('entity_id')->nullable()->comment('ID of the affected entity');
            
            // Migration data
            $table->json('old_data')->nullable()->comment('Original data before migration');
            $table->json('new_data')->nullable()->comment('New data after migration');
            $table->string('status')->default('pending')->comment('Status: pending, processing, completed, failed');
            
            // Error tracking
            $table->text('error_message')->nullable()->comment('Error message if migration failed');
            $table->json('validation_errors')->nullable()->comment('Detailed validation errors');
            
            // Timing and performance
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('processing_time_ms')->nullable()->comment('Processing time in milliseconds');
            
            // Rollback support
            $table->boolean('rollback_available')->default(true)->comment('Can this migration be rolled back');
            $table->json('rollback_data')->nullable()->comment('Data needed for rollback');
            
            // Batch tracking
            $table->string('batch_id')->nullable()->comment('Batch ID for group operations');
            $table->string('user_id')->nullable()->comment('User who initiated the migration');
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['phase', 'step']);
            $table->index(['entity_type', 'entity_id']);
            $table->index(['status']);
            $table->index(['batch_id']);
            $table->index(['started_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variant_migration_audit');
    }
};
