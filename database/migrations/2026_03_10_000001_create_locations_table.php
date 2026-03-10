<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('warehouse_id')
                ->nullable()
                ->constrained('warehouses')
                ->nullOnDelete();

            $table->string('code', 50);
            $table->string('normalized_code', 50);
            $table->string('description', 255)->nullable();

            $table->enum('pattern_type', [
                'coded',
                'dash',
                'wall',
                'named',
                'gift',
                'other',
            ]);

            $table->string('zone', 10)->nullable();
            $table->string('row_code', 10)->nullable();
            $table->smallInteger('shelf')->unsigned()->nullable();
            $table->smallInteger('bin')->unsigned()->nullable();

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('locations')
                ->nullOnDelete();

            $table->tinyInteger('depth')->unsigned()->default(0);
            $table->string('path', 255)->nullable();

            $table->unsignedInteger('product_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['warehouse_id', 'code']);
            $table->index(['warehouse_id', 'zone']);
            $table->index(['warehouse_id', 'parent_id']);
            $table->index('normalized_code');
            $table->index('product_count');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
