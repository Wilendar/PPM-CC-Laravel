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
        Schema::create('bug_report_comments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('bug_report_id')
                ->constrained('bug_reports')
                ->onDelete('cascade');

            // No FK constraint - users table may not exist
            $table->unsignedBigInteger('user_id');

            $table->text('content');
            $table->boolean('is_internal')->default(false);

            $table->timestamps();

            // Indexes
            $table->index('bug_report_id');
            $table->index('user_id');
            $table->index('is_internal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bug_report_comments');
    }
};
