# Test Phase 1 Strategy B Migrations (ONLY)
# Bypasses broken FAZA B migrations

Write-Host "`n=== PHASE 1 STRATEGY B MIGRATION TEST ===`n" -ForegroundColor Cyan

# Set location
Set-Location "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"

Write-Host "Step 1: Dropping Strategy B tables manually..." -ForegroundColor Yellow
php artisan tinker --execute="
DB::statement('SET FOREIGN_KEY_CHECKS=0');
DB::statement('DROP TABLE IF EXISTS stock_inheritance_logs');
DB::statement('DROP TABLE IF EXISTS product_stock');
DB::statement('DROP TABLE IF EXISTS warehouses');
DB::statement('DROP TABLE IF EXISTS prestashop_shops');
DB::statement('DROP TABLE IF EXISTS products');
DB::statement('SET FOREIGN_KEY_CHECKS=1');
echo 'Tables dropped successfully';
"

Write-Host "`nStep 2: Creating minimal tables for dependencies..." -ForegroundColor Yellow
php artisan tinker --execute="
Schema::create('products', function(\$table) {
    \$table->id();
    \$table->string('name');
    \$table->timestamps();
});

Schema::create('prestashop_shops', function(\$table) {
    \$table->id();
    \$table->string('name');
    \$table->string('url');
    \$table->string('api_key');
    \$table->timestamps();
});

echo 'Minimal dependencies created';
"

Write-Host "`nStep 3: Running Phase 1 migrations..." -ForegroundColor Yellow
php artisan migrate --path=database/migrations/2025_11_13_120000_create_warehouses_table.php --force
php artisan migrate --path=database/migrations/2025_11_13_120001_add_warehouse_linkage_to_shops.php --force
php artisan migrate --path=database/migrations/2024_01_01_000009_create_product_stock_table.php --force
php artisan migrate --path=database/migrations/2025_11_13_120002_extend_stock_tables_dual_resolution.php --force
php artisan migrate --path=database/migrations/2025_11_13_120003_migrate_existing_stocks_to_warehouses.php --force
php artisan migrate --path=database/migrations/2025_11_13_120004_create_stock_inheritance_logs_table.php --force

Write-Host "`nStep 4: Running WarehouseSeeder..." -ForegroundColor Yellow
php artisan db:seed --class=WarehouseSeeder --force

Write-Host "`n=== PHASE 1 MIGRATION TEST COMPLETE ===`n" -ForegroundColor Green
