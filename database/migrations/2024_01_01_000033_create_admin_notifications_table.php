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
        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('message');
            $table->enum('type', ['system', 'security', 'integration', 'user'])
                  ->default('system');
            $table->enum('priority', ['low', 'normal', 'high', 'critical'])
                  ->default('normal');
            $table->enum('channel', ['web', 'email', 'both'])
                  ->default('web');
            
            // Status tracking
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->boolean('is_acknowledged')->default(false);
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()
                  ->constrained('users');
            
            // Related entities
            $table->string('related_type')->nullable(); // polymorphic
            $table->unsignedBigInteger('related_id')->nullable();
            $table->json('metadata')->nullable(); // additional context data
            
            // Recipients
            $table->json('recipients')->nullable(); // user IDs or email addresses
            $table->foreignId('created_by')->nullable()
                  ->constrained('users');
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['type', 'priority']);
            $table->index(['is_read', 'created_at']);
            $table->index(['related_type', 'related_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_notifications');
    }
};