<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_status_integration_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_status_id')
                ->constrained('product_statuses')
                ->cascadeOnDelete();
            $table->string('integration_type', 50);
            $table->boolean('maps_to_active')->default(true);
            $table->string('description', 255)->nullable();
            $table->timestamps();

            $table->unique(['product_status_id', 'integration_type'], 'psim_status_integration_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_status_integration_mappings');
    }
};
