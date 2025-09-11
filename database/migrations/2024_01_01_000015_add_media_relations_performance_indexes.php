<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * FAZA C: Media & Relations - Advanced Performance Indexes
     * 
     * Ta migracja dodaje zaawansowane indeksy dla FAZA C tabel:
     * - Composite indexes dla polymorphic queries <100ms
     * - Partial indexes dla active records only  
     * - Advanced EAV indexes dla attribute searches
     * - JSON/JSONB indexes dla integration data
     * - Full-text preparation indexes
     * 
     * Target Performance:
     * - Media polymorphic queries: <50ms
     * - EAV attribute lookups: <100ms
     * - Integration mapping queries: <25ms
     * - File upload access control: <10ms
     */
    public function up(): void
    {
        // 1. ADVANCED MEDIA INDEXES
        Schema::table('media', function (Blueprint $table) {
            // Primary image selection optimization
            $table->index(['mediable_type', 'mediable_id', 'is_primary', 'is_active'], 'idx_media_primary_active');
            
            // Gallery loading optimization (active images in sort order)
            $table->index(['mediable_type', 'mediable_id', 'is_active', 'sort_order'], 'idx_media_gallery_sort');
            
            // File path uniqueness check (prevent duplicates)
            $table->index(['file_path'], 'idx_media_file_path');
            
            // MIME type filtering dla image processing
            $table->index(['mime_type', 'is_active'], 'idx_media_mime_active');
            
            // Size-based queries dla optimization
            $table->index(['file_size'], 'idx_media_file_size');
        });
        
        // 2. FILE_UPLOADS ACCESS CONTROL INDEXES
        Schema::table('file_uploads', function (Blueprint $table) {
            // User access pattern (most critical dla security)
            $table->index(['uploadable_type', 'uploadable_id', 'access_level', 'is_active'], 'idx_uploads_access_control');
            
            // File type and access combo dla file browser
            $table->index(['uploadable_type', 'uploadable_id', 'file_type', 'access_level'], 'idx_uploads_type_access');
            
            // User upload history and audit
            $table->index(['uploaded_by', 'created_at'], 'idx_uploads_user_audit');
            
            // File cleanup queries (size, age-based)
            $table->index(['file_size', 'created_at'], 'idx_uploads_cleanup');
            
            // MIME type filtering dla download handlers
            $table->index(['mime_type', 'is_active'], 'idx_uploads_mime_active');
        });
        
        // 3. EAV PERFORMANCE CRITICAL INDEXES
        Schema::table('product_attribute_values', function (Blueprint $table) {
            // Product effective attributes (most frequent query pattern)
            $table->index(['product_id', 'is_inherited', 'is_valid'], 'idx_values_product_effective');
            
            // Variant-specific overrides dla inheritance logic
            $table->index(['product_variant_id', 'is_override', 'is_valid'], 'idx_values_variant_override');
            
            // Attribute-based product searches (reverse lookup) 
            // Note: value_text is TEXT type, need prefix index to avoid MySQL key length limit
            $table->index(['attribute_id', 'value_number', 'is_valid'], 'idx_values_attribute_number');
            $table->index(['attribute_id', 'value_boolean', 'is_valid'], 'idx_values_attribute_boolean');
            
            // Date range filtering dla time-based attributes
            $table->index(['attribute_id', 'value_date'], 'idx_values_attribute_date');
            
            // Validation error reporting
            $table->index(['is_valid', 'updated_at'], 'idx_values_validation_errors');
        });
        
        // 4. INTEGRATION MAPPINGS ADVANCED INDEXES
        Schema::table('integration_mappings', function (Blueprint $table) {
            // Multi-store PrestaShop support (critical dla performance)
            $table->index(['integration_type', 'integration_identifier', 'sync_status'], 'idx_mappings_store_status');
            
            // Sync queue processing optimization
            $table->index(['sync_status', 'next_sync_at', 'error_count'], 'idx_mappings_sync_queue');
            
            // Error retry logic
            $table->index(['sync_status', 'error_count', 'last_sync_at'], 'idx_mappings_retry_logic');
            
            // Version conflict detection
            $table->index(['ppm_version_hash', 'external_version_hash'], 'idx_mappings_version_conflict');
            
            // External reference lookups (SKU, code mapping)
            $table->index(['integration_type', 'external_reference'], 'idx_mappings_external_ref');
            
            // Conflict resolution workflow
            $table->index(['conflict_detected_at', 'sync_status'], 'idx_mappings_conflict_workflow');
        });
        
        // 5. PRODUCT ATTRIBUTES FORM GENERATION INDEXES
        Schema::table('product_attributes', function (Blueprint $table) {
            // Form field generation (grouped by display_group)
            $table->index(['display_group', 'sort_order', 'is_active'], 'idx_attributes_form_display');
            
            // Filter form generation
            $table->index(['is_filterable', 'attribute_type', 'is_active'], 'idx_attributes_filter_form');
            
            // Required field validation
            $table->index(['is_required', 'is_active'], 'idx_attributes_required');
            
            // Variant-specific attributes
            $table->index(['is_variant_specific', 'is_active'], 'idx_attributes_variant_specific');
        });
        
        // 6. PREFIX INDEXES for TEXT columns (MySQL key length limitations)
        // Create prefix index for value_text searches (first 191 chars)
        DB::statement('CREATE INDEX idx_values_attribute_text ON product_attribute_values (attribute_id, value_text(191), is_valid)');
        
        // 7. PARTIAL INDEXES dla MySQL 8.0+ (if supported)
        // Note: MariaDB support dla partial indexes varies by version
        if ($this->supportsFunctionalIndexes()) {
            // Active media only index
            DB::statement('CREATE INDEX idx_media_active_only ON media (mediable_type, mediable_id, sort_order) WHERE is_active = 1');
            
            // Active file uploads only
            DB::statement('CREATE INDEX idx_uploads_active_only ON file_uploads (uploadable_type, uploadable_id, access_level) WHERE is_active = 1');
            
            // Valid attribute values only
            DB::statement('CREATE INDEX idx_values_valid_only ON product_attribute_values (product_id, attribute_id) WHERE is_valid = 1');
            
            // Pending sync mappings only
            DB::statement('CREATE INDEX idx_mappings_pending_only ON integration_mappings (integration_type, next_sync_at) WHERE sync_status = \'pending\'');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop prefix indexes first
        DB::statement('DROP INDEX IF EXISTS idx_values_attribute_text ON product_attribute_values');
        
        // Drop partial indexes first (if they exist)
        if ($this->supportsFunctionalIndexes()) {
            DB::statement('DROP INDEX IF EXISTS idx_media_active_only ON media');
            DB::statement('DROP INDEX IF EXISTS idx_uploads_active_only ON file_uploads');
            DB::statement('DROP INDEX IF EXISTS idx_values_valid_only ON product_attribute_values');
            DB::statement('DROP INDEX IF EXISTS idx_mappings_pending_only ON integration_mappings');
        }
        
        // Drop composite indexes
        Schema::table('media', function (Blueprint $table) {
            $table->dropIndex('idx_media_primary_active');
            $table->dropIndex('idx_media_gallery_sort');
            $table->dropIndex('idx_media_file_path');
            $table->dropIndex('idx_media_mime_active');
            $table->dropIndex('idx_media_file_size');
        });
        
        Schema::table('file_uploads', function (Blueprint $table) {
            $table->dropIndex('idx_uploads_access_control');
            $table->dropIndex('idx_uploads_type_access');
            $table->dropIndex('idx_uploads_user_audit');
            $table->dropIndex('idx_uploads_cleanup');
            $table->dropIndex('idx_uploads_mime_active');
        });
        
        Schema::table('product_attribute_values', function (Blueprint $table) {
            $table->dropIndex('idx_values_product_effective');
            $table->dropIndex('idx_values_variant_override');
            $table->dropIndex('idx_values_attribute_text');
            $table->dropIndex('idx_values_attribute_number');
            $table->dropIndex('idx_values_attribute_boolean');
            $table->dropIndex('idx_values_attribute_date');
            $table->dropIndex('idx_values_validation_errors');
        });
        
        Schema::table('integration_mappings', function (Blueprint $table) {
            $table->dropIndex('idx_mappings_store_status');
            $table->dropIndex('idx_mappings_sync_queue');
            $table->dropIndex('idx_mappings_retry_logic');
            $table->dropIndex('idx_mappings_version_conflict');
            $table->dropIndex('idx_mappings_external_ref');
            $table->dropIndex('idx_mappings_conflict_workflow');
        });
        
        Schema::table('product_attributes', function (Blueprint $table) {
            $table->dropIndex('idx_attributes_form_display');
            $table->dropIndex('idx_attributes_filter_form');
            $table->dropIndex('idx_attributes_required');
            $table->dropIndex('idx_attributes_variant_specific');
        });
    }
    
    /**
     * Check if database supports functional/partial indexes
     */
    private function supportsFunctionalIndexes(): bool
    {
        try {
            $version = DB::select('SELECT VERSION() as version')[0]->version;
            
            // MySQL 8.0+ supports functional indexes
            if (strpos($version, 'MySQL') !== false || strpos($version, 'mysql') !== false) {
                preg_match('/(\d+)\.(\d+)/', $version, $matches);
                if ($matches && intval($matches[1]) >= 8) {
                    return true;
                }
            }
            
            // MariaDB 10.3+ has limited support, 10.6+ better support
            if (strpos($version, 'MariaDB') !== false || strpos($version, 'mariadb') !== false) {
                preg_match('/(\d+)\.(\d+)/', $version, $matches);
                if ($matches && (intval($matches[1]) > 10 || (intval($matches[1]) == 10 && intval($matches[2]) >= 6))) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            // If version check fails, assume no support
            return false;
        }
        
        return false;
    }
};