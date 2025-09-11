<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FAZA D: Integration & System Tables  
     * 5.3.1 Notifications Table - system powiadomień
     * 
     * Tabela zgodna z Laravel Notifications system:
     * - UUID primary keys (Laravel standard)
     * - Polymorphic notifications (głównie dla User)
     * - JSONB data dla elastycznych powiadomień
     * - Read/unread tracking
     * - Obsługa różnych typów powiadomień PPM
     * 
     * Typy powiadomień w PPM:
     * - ProductSyncNotification (synchronizacja z PrestaShop/ERP)
     * - ImportCompletedNotification (zakończenie importu)
     * - StockLowNotification (niski stan magazynowy)  
     * - OrderStatusNotification (status zamówienia)
     * - SystemMaintenanceNotification (konserwacja systemu)
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            // === PRIMARY KEY ===
            // UUID zgodnie z Laravel Notifications standard
            $table->uuid('id')->primary();
            
            // === SEKCJA 1: TYP POWIADOMIENIA ===
            // Pełna nazwa klasy powiadomienia Laravel
            $table->string('type', 200)->index(); // App\Notifications\ProductSyncNotification
            
            // === SEKCJA 2: ADRESAT POWIADOMIENIA ===
            // Polymorphic relation - głównie User, ale może być rozszerzone
            $table->string('notifiable_type', 100)->index(); // App\Models\User
            $table->unsignedBigInteger('notifiable_id')->index(); // user_id
            
            // === SEKCJA 3: DANE POWIADOMIENIA ===
            // JSONB dla przechowywania elastycznych danych powiadomienia
            // Przykład: {"title": "Import completed", "message": "100 products imported", "action_url": "/imports/123"}
            $table->json('data'); 
            
            // === SEKCJA 4: STATUS PRZECZYTANIA ===
            // NULL = nie przeczytane, TIMESTAMP = kiedy przeczytane
            $table->timestamp('read_at')->nullable()->index();
            
            // === TIMESTAMPS ===
            $table->timestamps(); // created_at, updated_at
            
            // === COMPOSITE INDEXES DLA PERFORMANCE ===
            // Najczęściej używane zapytania notifications
            $table->index(['notifiable_type', 'notifiable_id'], 'idx_notifications_notifiable');
            $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'idx_notifications_unread');
            $table->index(['type', 'created_at'], 'idx_notifications_type_time');
            $table->index(['created_at', 'read_at'], 'idx_notifications_time_read');
        });
        
        // === FOREIGN KEY CONSTRAINTS ===
        // Constraint dla głównego przypadku użycia (User notifications)
        Schema::table('notifications', function (Blueprint $table) {
            // Conditional foreign key - będzie działać dla notifiable_type = User
            // Dla innych typów będzie ignorowane
            DB::statement("
                ALTER TABLE notifications 
                ADD CONSTRAINT fk_notifications_user 
                FOREIGN KEY (notifiable_id) 
                REFERENCES users(id) 
                ON DELETE CASCADE
            ");
        });
        
        // === KOMENTARZE TABELI ===
        DB::statement("ALTER TABLE notifications COMMENT = 'Laravel-compatible notifications system for PPM application'");
        
        // === PERFORMANCE OPTIMIZATION ===
        // Dla shared hosting - optymalizacja JSONB queries
        DB::statement("ALTER TABLE notifications ENGINE=InnoDB");
    }

    /**
     * Cofnięcie zmian - usuwa tabelę notifications
     */
    public function down(): void
    {
        // Usuń foreign key constraint przed usunięciem tabeli
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropForeign('fk_notifications_user');
        });
        
        Schema::dropIfExists('notifications');
    }
};