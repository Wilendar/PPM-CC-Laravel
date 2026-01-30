<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create business_partners unified table
 * ETAP: BusinessPartner System (Dostawca/Producent/Importer)
 *
 * Replaces separate manufacturers table with a unified business_partners table
 * supporting multiple partner types: supplier, manufacturer, importer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_partners', function (Blueprint $table) {
            $table->id();

            // Partner type
            $table->enum('type', ['supplier', 'manufacturer', 'importer'])
                ->comment('Business partner type');

            // Basic info
            $table->string('name', 255)->comment('Display name');
            $table->string('code', 50)->comment('Unique code per type');
            $table->string('company_name', 255)->nullable()->comment('Full company name');

            // Address
            $table->string('address', 500)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('country', 100)->nullable()->default('Polska');

            // Contact
            $table->string('email', 255)->nullable();
            $table->string('phone', 50)->nullable();

            // PrestaShop SEO
            $table->string('ps_link_rewrite', 128)->nullable()
                ->comment('PrestaShop URL slug');

            // Descriptions
            $table->text('description')->nullable();
            $table->text('short_description')->nullable();

            // SEO meta
            $table->string('meta_title', 255)->nullable();
            $table->string('meta_description', 512)->nullable();
            $table->string('meta_keywords', 255)->nullable();

            // Media & web
            $table->string('logo_path', 500)->nullable();
            $table->string('website', 255)->nullable();

            // Status & ordering
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            // ERP integration
            $table->integer('subiekt_contractor_id')->nullable()
                ->comment('Subiekt GT contractor ID (kh_Id)');

            $table->timestamps();
            $table->softDeletes();

            // Unique constraint: type + code
            $table->unique(['type', 'code'], 'bp_type_code_unique');

            // Indexes
            $table->index('type', 'idx_bp_type');
            $table->index('is_active', 'idx_bp_is_active');
            $table->index('code', 'idx_bp_code');
            $table->index('sort_order', 'idx_bp_sort_order');
            $table->index('subiekt_contractor_id', 'idx_bp_subiekt_contractor');
            $table->index('name', 'idx_bp_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_partners');
    }
};
