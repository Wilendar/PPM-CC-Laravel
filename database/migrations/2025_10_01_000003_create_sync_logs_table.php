<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ETAP_07 FAZA 1: PrestaShop API Integration - Sync Logs Table
     *
     * Tabela sync_logs przechowuje szczegółowe logi wszystkich operacji synchronizacji
     * z PrestaShop API. Każde wywołanie API jest logowane z pełnym kontekstem.
     *
     * Rodzaje operacji (operation):
     * - 'sync_product' - Synchronizacja pojedynczego produktu
     * - 'sync_category' - Synchronizacja kategorii
     * - 'sync_image' - Synchronizacja zdjęcia produktu (FAZA 2)
     * - 'sync_stock' - Synchronizacja stanu magazynowego
     * - 'sync_price' - Synchronizacja cen
     * - 'webhook' - Przetwarzanie webhook z PrestaShop (FAZA 3)
     *
     * Kierunek (direction):
     * - 'ppm_to_ps' - Wysyłanie danych PPM → PrestaShop
     * - 'ps_to_ppm' - Odbiór danych PrestaShop → PPM (FAZA 2)
     *
     * Status operacji:
     * - 'started' - Operacja rozpoczęta
     * - 'success' - Operacja zakończona sukcesem
     * - 'error' - Błąd operacji
     * - 'warning' - Operacja zakończona z ostrzeżeniami
     *
     * Zastosowanie:
     * - Debugging problemów synchronizacji
     * - Audit trail wszystkich zmian
     * - Performance monitoring (execution_time_ms)
     * - API rate limiting tracking
     * - Analiza błędów i patterns
     *
     * Retention policy:
     * - Logi starsze niż 90 dni mogą być archiwizowane/usuwane
     * - Error logs powinny być zachowane dłużej dla analizy
     */
    public function up(): void
    {
        Schema::create('sync_logs', function (Blueprint $table) {
            // Primary key
            $table->id();

            // Foreign keys
            $table->foreignId('shop_id')
                ->constrained('prestashop_shops')
                ->onDelete('cascade')
                ->comment('ID sklepu PrestaShop');

            $table->foreignId('product_id')
                ->nullable()
                ->constrained('products')
                ->onDelete('set null')
                ->comment('ID produktu (nullable - może być ogólna operacja)');

            // Rodzaj operacji
            $table->enum('operation', [
                'sync_product',
                'sync_category',
                'sync_image',
                'sync_stock',
                'sync_price',
                'webhook'
            ])->comment('Rodzaj operacji synchronizacji');

            // Kierunek synchronizacji
            $table->enum('direction', [
                'ppm_to_ps',
                'ps_to_ppm'
            ])->comment('Kierunek synchronizacji');

            // Status operacji
            $table->enum('status', [
                'started',
                'success',
                'error',
                'warning'
            ])->comment('Status operacji');

            // Komunikat i szczegóły
            $table->text('message')->nullable()->comment('Komunikat operacji');

            // Dane request/response (JSON) dla pełnego audytu
            $table->json('request_data')->nullable()->comment('Dane wysłane do PrestaShop API');
            $table->json('response_data')->nullable()->comment('Odpowiedź z PrestaShop API');

            // Performance metrics
            $table->unsignedInteger('execution_time_ms')->nullable()->comment('Czas wykonania operacji (ms)');

            // API endpoint details
            $table->string('api_endpoint', 500)->nullable()->comment('Endpoint PrestaShop API');
            $table->unsignedSmallInteger('http_status_code')->nullable()->comment('HTTP status code odpowiedzi');

            // Timestamp (tylko created_at - logi nie są edytowane)
            $table->timestamp('created_at')->useCurrent()->comment('Data i czas operacji');

            // Indexes dla performance
            $table->index(['shop_id', 'operation'], 'idx_shop_operation');
            $table->index(['status', 'created_at'], 'idx_status_created');
            $table->index(['product_id', 'created_at'], 'idx_product_created');
            $table->index(['operation', 'direction'], 'idx_operation_direction');
            $table->index(['http_status_code'], 'idx_http_status');
            $table->index(['created_at'], 'idx_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
    }
};
