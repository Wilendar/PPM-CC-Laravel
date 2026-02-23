<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Reguły keyword dla Smart Matching
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smart_keyword_rules', function (Blueprint $table) {
            $table->id();
            $table->string('keyword', 100);
            $table->string('keyword_normalized', 100);
            $table->enum('match_field', ['name', 'sku', 'any'])->default('any');
            $table->enum('match_type', ['contains', 'starts_with', 'exact', 'regex'])->default('contains');
            $table->string('target_vehicle_type', 50)->nullable();
            $table->string('target_brand', 100)->nullable();
            $table->decimal('score_bonus', 3, 2)->default(0.20);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('priority')->default(100);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['keyword_normalized', 'match_field', 'target_vehicle_type'], 'skr_unique_keyword_field_type');
            $table->index('keyword_normalized');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smart_keyword_rules');
    }
};
