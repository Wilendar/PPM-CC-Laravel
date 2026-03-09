<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Feed Security Hardening - new columns for token expiry,
     * rotation tracking, and IP whitelisting.
     */
    public function up(): void
    {
        Schema::table('export_profiles', function (Blueprint $table) {
            // Token expiry - null means no expiry
            $table->timestamp('token_expires_at')->nullable()->after('is_public')
                  ->comment('Token expiry date (null = never expires)');

            // Token rotation tracking
            $table->timestamp('token_rotated_at')->nullable()->after('token_expires_at')
                  ->comment('Last token rotation date');

            // IP whitelist - null means all IPs allowed
            $table->json('allowed_ips')->nullable()->after('token_rotated_at')
                  ->comment('Whitelisted IPs (null = all allowed)');
        });
    }

    public function down(): void
    {
        Schema::table('export_profiles', function (Blueprint $table) {
            $table->dropColumn(['token_expires_at', 'token_rotated_at', 'allowed_ips']);
        });
    }
};
