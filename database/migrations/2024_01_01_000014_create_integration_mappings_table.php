<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * FAZA C: Media & Relations - Universal Integration Mapping System
     * 
     * Tabela integration_mappings mapuje obiekty PPM na systemy zewnętrzne:
     * - PrestaShop (produkty, kategorie, ceny, magazyny)
     * - Baselinker (produkty, zamówienia, stany)
     * - Subiekt GT (towary, kontrahenci, dokumenty)
     * - Microsoft Dynamics (entities, business data)
     * 
     * Multi-Store PrestaShop Support:
     * - integration_identifier może być 'shop_id' dla różnych sklepów
     * - external_data JSONB przechowuje pełne dane z każdego systemu
     * - Conflict detection dla różnych wersji tego samego obiektu
     * - Sync direction control dla each integration
     */
    public function up(): void
    {
        Schema::create('integration_mappings', function (Blueprint $table) {
            // Primary key
            $table->id();
            
            // Polymorphic relationship - universal mapping
            $table->string('mappable_type', 100)->comment('Product, Category, PriceGroup, Warehouse, User');
            $table->unsignedBigInteger('mappable_id')->comment('ID obiektu w systemie PPM');
            
            // Integration system identification
            $table->enum('integration_type', [
                'prestashop',
                'baselinker', 
                'subiekt_gt',
                'dynamics',
                'custom'
            ])->comment('System zewnętrzny');
            
            // External system identification
            $table->string('integration_identifier', 200)->comment('Identyfikator systemu (shop_id, instance_name, etc.)');
            $table->string('external_id', 200)->comment('ID w systemie zewnętrznym');
            $table->string('external_reference', 300)->nullable()->comment('Dodatkowa referencja (SKU, code, etc.)');
            
            // Complete external data storage - JSONB dla flexibility
            $table->json('external_data')->nullable()->comment('Pełne dane z systemu zewnętrznego');
            
            // Synchronization status and control
            $table->enum('sync_status', [
                'pending',      // Oczekuje na synchronizację
                'synced',       // Zsynchronizowane pomyślnie
                'error',        // Błąd synchronizacji
                'conflict',     // Konflikt danych (wymaga interwencji)
                'disabled'      // Synchronizacja wyłączona
            ])->default('pending')->comment('Status synchronizacji');
            
            $table->enum('sync_direction', [
                'both',         // Dwukierunkowa synchronizacja
                'to_external',  // Tylko PPM -> System zewnętrzny
                'from_external',// Tylko System zewnętrzny -> PPM
                'disabled'      // Synchronizacja wyłączona
            ])->default('both')->comment('Kierunek synchronizacji');
            
            // Synchronization metadata
            $table->timestamp('last_sync_at')->nullable()->comment('Ostatnia synchronizacja');
            $table->timestamp('next_sync_at')->nullable()->comment('Następna zaplanowana synchronizacja');
            $table->text('error_message')->nullable()->comment('Szczegóły ostatniego błędu');
            $table->integer('error_count')->default(0)->comment('Liczba błędów z rzędu');
            
            // Conflict resolution
            $table->json('conflict_data')->nullable()->comment('Dane konfliktu do rozwiązania');
            $table->timestamp('conflict_detected_at')->nullable()->comment('Kiedy wykryto konflikt');
            
            // Version control dla conflict detection
            $table->string('ppm_version_hash', 64)->nullable()->comment('Hash wersji danych w PPM');
            $table->string('external_version_hash', 64)->nullable()->comment('Hash wersji danych zewnętrznych');
            
            // Audit and timestamps
            $table->timestamps();
            
            // Unique constraint - one mapping per object per integration type per identifier
            $table->unique([
                'mappable_type', 
                'mappable_id', 
                'integration_type', 
                'integration_identifier'
            ], 'unique_mapping_per_integration');
            
            // Strategic indexes dla integration performance
            // 1. Primary polymorphic access pattern
            $table->index(['mappable_type', 'mappable_id'], 'idx_mappings_polymorphic');
            
            // 2. Integration type filtering
            $table->index(['integration_type'], 'idx_mappings_integration_type');
            
            // 3. Sync status monitoring
            $table->index(['sync_status'], 'idx_mappings_sync_status');
            
            // 4. Error handling and retry logic
            $table->index(['sync_status', 'error_count'], 'idx_mappings_error_handling');
            
            // 5. Scheduled sync queries
            $table->index(['next_sync_at', 'sync_status'], 'idx_mappings_scheduled_sync');
            
            // 6. External ID lookups (reverse mapping)
            $table->index(['integration_type', 'external_id'], 'idx_mappings_external_lookup');
            
            // 7. Conflict resolution workflows
            $table->index(['sync_status', 'conflict_detected_at'], 'idx_mappings_conflicts');
            
            // 8. Integration identifier queries (multi-store support)
            $table->index(['integration_type', 'integration_identifier'], 'idx_mappings_identifier');
            
            // 9. Last sync monitoring
            $table->index(['last_sync_at'], 'idx_mappings_last_sync');
            
            // 10. JSONB/JSON index dla external_data searches (MariaDB 10.3+)
            if (DB::connection()->getDriverName() === 'mysql') {
                $table->index(['external_data'], 'idx_mappings_external_data');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integration_mappings');
    }
};