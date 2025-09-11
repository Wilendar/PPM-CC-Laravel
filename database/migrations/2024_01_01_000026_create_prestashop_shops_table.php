<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * FAZA B: Shop & ERP Management - PrestaShop Shops Table
     * 
     * Tabela prestashop_shops przechowuje konfiguracje wszystkich sklepów PrestaShop
     * podłączonych do systemu PPM-CC-Laravel. Każdy sklep może mieć:
     * - Własną konfigurację API i credentials
     * - Dedykowane ustawienia synchronizacji
     * - Mapowanie kategorii, grup cenowych i magazynów
     * - Monitoring zdrowia połączenia z metrykami performance
     * 
     * Multi-Store Support:
     * - Jeden sklep może obsługiwać wielu klientów z różnymi settings
     * - Field mappings per sklep dla custom attributes
     * - Conflict resolution policies per sklep
     * - Performance monitoring i alerting per sklep
     */
    public function up(): void
    {
        Schema::create('prestashop_shops', function (Blueprint $table) {
            // Primary key
            $table->id();
            
            // Basic shop information
            $table->string('name', 200)->comment('Nazwa sklepu dla identyfikacji');
            $table->string('url', 500)->comment('URL sklepu PrestaShop');
            $table->string('description', 1000)->nullable()->comment('Opis sklepu');
            $table->boolean('is_active')->default(true)->comment('Czy sklep jest aktywny');
            
            // API Configuration
            $table->string('api_key', 200)->comment('Klucz API PrestaShop (encrypted)');
            $table->string('api_version', 20)->default('1.7')->comment('Wersja API PrestaShop');
            $table->boolean('ssl_verify')->default(true)->comment('Weryfikacja certyfikatu SSL');
            $table->integer('timeout_seconds')->default(30)->comment('Timeout połączenia API');
            $table->integer('rate_limit_per_minute')->default(60)->comment('Limit zapytań per minuta');
            
            // Connection Health Monitoring
            $table->enum('connection_status', [
                'connected',    // Połączenie działa
                'disconnected', // Brak połączenia
                'error',        // Błędy połączenia
                'maintenance'   // Tryb konserwacji
            ])->default('disconnected')->comment('Status połączenia');
            
            $table->timestamp('last_connection_test')->nullable()->comment('Ostatni test połączenia');
            $table->decimal('last_response_time', 8, 3)->nullable()->comment('Czas odpowiedzi (ms)');
            $table->integer('consecutive_failures')->default(0)->comment('Liczba niepowodzeń z rzędu');
            $table->text('last_error_message')->nullable()->comment('Ostatni błąd połączenia');
            
            // PrestaShop Version Compatibility
            $table->string('prestashop_version', 50)->nullable()->comment('Wykryta wersja PrestaShop');
            $table->boolean('version_compatible')->default(true)->comment('Czy wersja jest kompatybilna');
            $table->json('supported_features')->nullable()->comment('Lista wspieranych funkcji');
            
            // Synchronization Settings
            $table->enum('sync_frequency', [
                'realtime',     // Real-time sync
                'hourly',       // Co godzinę
                'daily',        // Raz dziennie
                'manual'        // Tylko manual sync
            ])->default('hourly')->comment('Częstotliwość synchronizacji');
            
            $table->json('sync_settings')->nullable()->comment('Ustawienia synchronizacji');
            $table->boolean('auto_sync_products')->default(true)->comment('Auto sync produktów');
            $table->boolean('auto_sync_categories')->default(true)->comment('Auto sync kategorii');
            $table->boolean('auto_sync_prices')->default(true)->comment('Auto sync cen');
            $table->boolean('auto_sync_stock')->default(true)->comment('Auto sync stanów');
            
            // Conflict Resolution
            $table->enum('conflict_resolution', [
                'ppm_wins',         // PPM ma priorytet
                'prestashop_wins',  // PrestaShop ma priorytet
                'manual',           // Ręczne rozwiązywanie
                'newest_wins'       // Najnowsza wersja wygrywa
            ])->default('ppm_wins')->comment('Strategia rozwiązywania konfliktów');
            
            // Field Mappings (JSONB dla flexibility)
            $table->json('category_mappings')->nullable()->comment('Mapowanie kategorii PPM → PrestaShop');
            $table->json('price_group_mappings')->nullable()->comment('Mapowanie grup cenowych');
            $table->json('warehouse_mappings')->nullable()->comment('Mapowanie magazynów');
            $table->json('custom_field_mappings')->nullable()->comment('Mapowanie custom fields');
            
            // Sync Statistics
            $table->timestamp('last_sync_at')->nullable()->comment('Ostatnia synchronizacja');
            $table->timestamp('next_scheduled_sync')->nullable()->comment('Następna zaplanowana synchronizacja');
            $table->integer('products_synced')->default(0)->comment('Liczba zsynchronizowanych produktów');
            $table->integer('sync_success_count')->default(0)->comment('Liczba udanych synchronizacji');
            $table->integer('sync_error_count')->default(0)->comment('Liczba błędów synchronizacji');
            
            // Performance Metrics
            $table->decimal('avg_response_time', 8, 3)->nullable()->comment('Średni czas odpowiedzi API');
            $table->integer('api_quota_used')->default(0)->comment('Wykorzystana quota API');
            $table->integer('api_quota_limit')->nullable()->comment('Limit quota API');
            $table->timestamp('quota_reset_at')->nullable()->comment('Reset quota API');
            
            // Notification Settings
            $table->json('notification_settings')->nullable()->comment('Ustawienia powiadomień');
            $table->boolean('notify_on_errors')->default(true)->comment('Powiadomienia o błędach');
            $table->boolean('notify_on_sync_complete')->default(false)->comment('Powiadomienia po sync');
            
            // Audit and timestamps
            $table->timestamps();
            
            // Indexes dla performance
            $table->index(['is_active'], 'idx_shops_active');
            $table->index(['connection_status'], 'idx_shops_connection_status');
            $table->index(['sync_frequency'], 'idx_shops_sync_frequency');
            $table->index(['last_sync_at'], 'idx_shops_last_sync');
            $table->index(['next_scheduled_sync'], 'idx_shops_scheduled_sync');
            $table->index(['consecutive_failures'], 'idx_shops_failures');
            
            // Unique constraint
            $table->unique(['url'], 'unique_shop_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prestashop_shops');
    }
};