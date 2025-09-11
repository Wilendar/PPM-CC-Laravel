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
        Schema::create('maintenance_tasks', function (Blueprint $table) {
            $table->id();
            
            // Podstawowe informacje o zadaniu
            $table->string('name');
            $table->enum('type', [
                'database_optimization', 
                'log_cleanup', 
                'cache_cleanup', 
                'security_check',
                'file_cleanup',
                'index_rebuild',
                'stats_update'
            ]);
            $table->enum('status', [
                'pending', 
                'running', 
                'completed', 
                'failed',
                'skipped'
            ])->default('pending');
            
            // Planowanie i wykonanie
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_seconds')->nullable();
            
            // Wyniki i błędy
            $table->json('result_data')->nullable();
            $table->text('error_message')->nullable();
            $table->json('configuration')->nullable();
            
            // Zadania cykliczne
            $table->boolean('is_recurring')->default(false);
            $table->string('recurrence_rule')->nullable(); // daily, weekly, monthly lub cron
            $table->timestamp('next_run_at')->nullable();
            
            // Audyting
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            
            // Indeksy
            $table->index('status');
            $table->index('type');
            $table->index('scheduled_at');
            $table->index(['status', 'scheduled_at']);
            $table->index('is_recurring');
            $table->index('next_run_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_tasks');
    }
};