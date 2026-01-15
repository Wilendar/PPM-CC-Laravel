# Deploy ETAP_07f Faza 1 - Visual Description Editor Database Schema
# Migracje, Modele, Seedery

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"
$RemoteHost = "host379076@host379076.hostido.net.pl"

Write-Host "=== DEPLOYING ETAP_07f FAZA 1 ===" -ForegroundColor Cyan

# 1. Upload Migrations
Write-Host "[1/5] Uploading migrations..." -ForegroundColor Yellow
$migrations = @(
    "2025_12_11_100001_create_description_blocks_table.php",
    "2025_12_11_100002_create_description_templates_table.php",
    "2025_12_11_100003_create_product_descriptions_table.php",
    "2025_12_11_100004_create_shop_stylesets_table.php"
)

foreach ($migration in $migrations) {
    $localPath = "database/migrations/$migration"
    if (Test-Path $localPath) {
        pscp -i $HostidoKey -P 64321 $localPath "${RemoteBase}/database/migrations/$migration"
        Write-Host "  Uploaded: $migration" -ForegroundColor Green
    } else {
        Write-Host "  NOT FOUND: $localPath" -ForegroundColor Red
    }
}

# 2. Upload Models
Write-Host "[2/5] Uploading models..." -ForegroundColor Yellow
$models = @(
    "DescriptionBlock.php",
    "DescriptionTemplate.php",
    "ProductDescription.php",
    "ShopStyleset.php"
)

foreach ($model in $models) {
    $localPath = "app/Models/$model"
    if (Test-Path $localPath) {
        pscp -i $HostidoKey -P 64321 $localPath "${RemoteBase}/app/Models/$model"
        Write-Host "  Uploaded: $model" -ForegroundColor Green
    } else {
        Write-Host "  NOT FOUND: $localPath" -ForegroundColor Red
    }
}

# 3. Upload Seeders
Write-Host "[3/5] Uploading seeders..." -ForegroundColor Yellow
$seeders = @(
    "DescriptionBlockSeeder.php",
    "ShopStylesetSeeder.php"
)

foreach ($seeder in $seeders) {
    $localPath = "database/seeders/$seeder"
    if (Test-Path $localPath) {
        pscp -i $HostidoKey -P 64321 $localPath "${RemoteBase}/database/seeders/$seeder"
        Write-Host "  Uploaded: $seeder" -ForegroundColor Green
    } else {
        Write-Host "  NOT FOUND: $localPath" -ForegroundColor Red
    }
}

# 4. Run Migrations
Write-Host "[4/5] Running migrations..." -ForegroundColor Yellow
$migrateCmd = "cd domains/ppm.mpptrade.pl/public_html && php artisan migrate --force 2>&1"
$migrateResult = plink -ssh $RemoteHost -P 64321 -i $HostidoKey -batch $migrateCmd
Write-Host $migrateResult

# 5. Run Seeders
Write-Host "[5/5] Running seeders..." -ForegroundColor Yellow

# Description Blocks Seeder
$seederCmd1 = "cd domains/ppm.mpptrade.pl/public_html && php artisan db:seed --class=DescriptionBlockSeeder --force 2>&1"
$seederResult1 = plink -ssh $RemoteHost -P 64321 -i $HostidoKey -batch $seederCmd1
Write-Host "DescriptionBlockSeeder: $seederResult1"

# Shop Styleset Seeder
$seederCmd2 = "cd domains/ppm.mpptrade.pl/public_html && php artisan db:seed --class=ShopStylesetSeeder --force 2>&1"
$seederResult2 = plink -ssh $RemoteHost -P 64321 -i $HostidoKey -batch $seederCmd2
Write-Host "ShopStylesetSeeder: $seederResult2"

# Clear cache
Write-Host "[FINAL] Clearing cache..." -ForegroundColor Yellow
$clearCmd = "cd domains/ppm.mpptrade.pl/public_html && php artisan cache:clear && php artisan config:clear && composer dump-autoload -o 2>&1"
plink -ssh $RemoteHost -P 64321 -i $HostidoKey -batch $clearCmd

Write-Host "=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
Write-Host "Tables created: description_blocks, description_templates, product_descriptions, shop_stylesets" -ForegroundColor Cyan
