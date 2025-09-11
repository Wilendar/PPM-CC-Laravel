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
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            
            // Kategoryzacja ustawień
            $table->string('category', 50)->default('general'); // general, security, product, integration
            
            // Klucz ustawienia (unikalny)
            $table->string('key', 100)->unique();
            
            // Wartość ustawienia (może być JSON)
            $table->longText('value')->nullable();
            
            // Typ wartości dla walidacji
            $table->enum('type', ['string', 'integer', 'boolean', 'json', 'email', 'url', 'file'])
                  ->default('string');
            
            // Opis ustawienia
            $table->text('description')->nullable();
            
            // Czy wartość jest szyfrowana
            $table->boolean('is_encrypted')->default(false);
            
            // Audyting
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            
            // Indeksy dla wydajności
            $table->index('category');
            $table->index(['category', 'key']);
            $table->index('is_encrypted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};