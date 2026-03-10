<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_progress', function (Blueprint $table) {
            $table->integer('worker_pid')->nullable()->after('action_button');
            $table->timestamp('last_heartbeat_at')->nullable()->after('worker_pid');
        });
    }

    public function down(): void
    {
        Schema::table('job_progress', function (Blueprint $table) {
            $table->dropColumn(['worker_pid', 'last_heartbeat_at']);
        });
    }
};
