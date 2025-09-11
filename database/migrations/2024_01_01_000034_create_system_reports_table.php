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
        Schema::create('system_reports', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', [
                'usage_analytics', 
                'performance', 
                'business_intelligence', 
                'integration_performance',
                'security_audit'
            ]);
            $table->enum('period', ['daily', 'weekly', 'monthly', 'quarterly'])
                  ->default('daily');
            $table->date('report_date');
            
            // Report data
            $table->json('data'); // Main report data
            $table->json('metadata')->nullable(); // Generation metadata
            $table->text('summary')->nullable(); // Executive summary
            
            // Status
            $table->enum('status', ['generating', 'completed', 'failed'])
                  ->default('generating');
            $table->timestamp('generated_at')->nullable();
            $table->foreignId('generated_by')
                  ->constrained('users');
            
            // Performance metrics
            $table->integer('generation_time_seconds')->nullable();
            $table->integer('data_points_count')->nullable();
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['type', 'period', 'report_date']);
            $table->index(['status', 'created_at']);
            $table->unique(['type', 'period', 'report_date'], 'unique_report_per_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_reports');
    }
};