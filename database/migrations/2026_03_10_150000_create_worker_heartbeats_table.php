<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('worker_heartbeats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_progress_id')->nullable();
            $table->string('job_id', 255);
            $table->integer('worker_pid');
            $table->string('worker_type', 50)->default('scheduler');
            $table->string('queue_name', 100)->nullable();
            $table->enum('status', ['starting', 'processing', 'idle', 'dead'])->default('starting');
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('last_heartbeat_at')->useCurrent();
            $table->timestamp('finished_at')->nullable();
            $table->longText('metadata')->nullable();
            $table->timestamps();

            $table->foreign('job_progress_id')
                ->references('id')
                ->on('job_progress')
                ->onDelete('set null');

            $table->index('job_id', 'idx_worker_job_id');
            $table->index(['status', 'last_heartbeat_at'], 'idx_worker_status_heartbeat');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worker_heartbeats');
    }
};
