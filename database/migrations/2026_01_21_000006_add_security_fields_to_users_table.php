<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ETAP_04 FAZA A: Add Security Fields to Users Table
 *
 * Extends users table with password policy and security fields.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Password management
            if (!Schema::hasColumn('users', 'force_password_change')) {
                $table->boolean('force_password_change')->default(false)->after('password');
            }

            if (!Schema::hasColumn('users', 'password_changed_at')) {
                $table->timestamp('password_changed_at')->nullable()->after('force_password_change');
            }

            if (!Schema::hasColumn('users', 'password_policy_id')) {
                $table->foreignId('password_policy_id')
                    ->nullable()
                    ->after('password_changed_at')
                    ->constrained('password_policies')
                    ->nullOnDelete();
            }

            // Login security
            if (!Schema::hasColumn('users', 'failed_login_attempts')) {
                $table->integer('failed_login_attempts')->default(0)->after('password_policy_id');
            }

            if (!Schema::hasColumn('users', 'locked_until')) {
                $table->timestamp('locked_until')->nullable()->after('failed_login_attempts');
            }

            // Session management
            if (!Schema::hasColumn('users', 'max_concurrent_sessions')) {
                $table->integer('max_concurrent_sessions')->default(3)->after('locked_until');
            }

            // Last activity
            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('max_concurrent_sessions');
            }

            if (!Schema::hasColumn('users', 'last_login_ip')) {
                $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [
                'force_password_change',
                'password_changed_at',
                'password_policy_id',
                'failed_login_attempts',
                'locked_until',
                'max_concurrent_sessions',
                'last_login_at',
                'last_login_ip'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    if ($column === 'password_policy_id') {
                        $table->dropForeign(['password_policy_id']);
                    }
                    $table->dropColumn($column);
                }
            }
        });
    }
};
