<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("product_variants", function (Blueprint $table) {
            // === PRIMARY IDENTITY ===
            $table->id(); // SERIAL PRIMARY KEY
            $table->unsignedBigInteger("product_id"); // FK do products
            $table->string("variant_sku", 100)->unique(); // Unikalny SKU wariantu
            
            // === VARIANT IDENTIFICATION ===
            $table->string("variant_name", 200); // Nazwa wariantu
            $table->string("ean", 20)->nullable(); // Dedykowany EAN dla wariantu
            $table->integer("sort_order")->default(0); // Kolejno\u015b\u0107 wy\u015bwietlania wariant\u00f3w
            
            // === INHERITANCE CONTROL ===
            $table->boolean("inherit_prices")->default(true); // Czy dziedziczy ceny
            $table->boolean("inherit_stock")->default(false); // Czy dziedziczy stany
            $table->boolean("inherit_attributes")->default(true); // Czy dziedziczy atrybuty
            
            // === STATUS & VISIBILITY ===
            $table->boolean("is_active")->default(true); // Status wariantu
            
            // === AUDIT & TIMESTAMPS ===
            $table->timestamps(); // created_at, updated_at
            $table->softDeletes(); // deleted_at for soft delete support
            
            // === FOREIGN KEY CONSTRAINTS ===
            $table->foreign("product_id")
                  ->references("id")->on("products")
                  ->onDelete("cascade") // Usuwa warianty gdy master produkt usuni\u0119ty
                  ->onUpdate("cascade");
            
            // === PERFORMANCE INDEXES ===
            $table->index(["product_id"]); // Cz\u0119ste lookup wariant\u00f3w dla produktu
            $table->index(["variant_sku"]); // SKU lookup
            $table->index(["is_active"]); // Filtrowanie aktywnych wariant\u00f3w
            $table->index(["product_id", "sort_order"]); // Compound dla sortowania wariant\u00f3w
            $table->index(["product_id", "is_active"]); // Compound dla aktywnych wariant\u00f3w produktu
            $table->index(["created_at"]); // Chronological sorting
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("product_variants");
    }
};
