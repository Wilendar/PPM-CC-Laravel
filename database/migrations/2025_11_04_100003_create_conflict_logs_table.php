<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ETAP_07 FAZA 5 - Migration 3/5
     *
     * Creates conflict_logs table for SKU conflict tracking and manual resolution.
     *
     * PURPOSE:
     * - Log conflicts during import (duplicate SKU, validation errors, missing dependencies)
     * - Enable manual conflict resolution workflow
     * - Track resolution decisions (who, when, how, why)
     * - Provide audit trail for import conflicts
     *
     * BUSINESS RULES:
     * - Cascade delete: if import_batch deleted → conflicts deleted
     * - Set null: if resolver deleted → conflict preserved but resolved_by_user_id=null
     * - Resolution status: pending → resolved/ignored
     * - Track resolver identity and resolution timestamp
     *
     * CONFLICT TYPES:
     * - duplicate_sku: SKU already exists in database
     * - validation_error: Data validation failed
     * - missing_dependency: Required related data missing (e.g., category)
     *
     * DATA STRUCTURE (JSON):
     * - existing_data: Current database record for this SKU
     * - new_data: Incoming data from import file/API
     *
     * EXAMPLES:
     * - sku='ABC123', conflict_type='duplicate_sku', resolution_status='pending'
     * - sku='XYZ789', conflict_type='validation_error', resolution_status='resolved'
     *
     * RELATIONSHIPS:
     * - belongs to ImportBatch (cascade delete)
     * - belongs to User as resolvedBy (set null on delete)
     */
    public function up(): void
    {
        Schema::create('conflict_logs', function (Blueprint $table) {
            $table->id();

            // Import batch relation (cascade delete)
            $table->foreignId('import_batch_id')
                  ->constrained()
                  ->cascadeOnDelete()
                  ->comment('Parent import batch');

            // Conflict identification
            $table->string('sku')
                  ->comment('Conflicting SKU');

            $table->enum('conflict_type', ['duplicate_sku', 'validation_error', 'missing_dependency'])
                  ->comment('Type of conflict detected');

            // Conflict data (JSON)
            $table->json('existing_data')
                  ->comment('Current DB data for this SKU');

            $table->json('new_data')
                  ->comment('Incoming data from import');

            // Resolution tracking
            $table->enum('resolution_status', ['pending', 'resolved', 'ignored'])
                  ->default('pending')
                  ->comment('Current resolution status');

            $table->unsignedBigInteger('resolved_by_user_id')->nullable()->comment('Users.id - who resolved conflict');
            // Note: FK to users will be added later (OAuth implementation phase)
            // $table->foreign('resolved_by_user_id')->references('id')->on('users')->onDelete('set null');

            $table->timestamp('resolved_at')->nullable()
                  ->comment('When conflict was resolved');

            $table->text('resolution_notes')->nullable()
                  ->comment('Resolution strategy/notes');

            $table->timestamps();

            // Indexes for performance
            $table->index(['import_batch_id', 'resolution_status'], 'idx_conflict_batch_status');
            $table->index('sku', 'idx_conflict_sku');
            $table->index('resolution_status', 'idx_conflict_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conflict_logs');
    }
};
