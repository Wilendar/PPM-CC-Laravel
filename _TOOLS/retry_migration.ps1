$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$LocalBase = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== RETRYING FEATURE MAPPINGS MIGRATION ===" -ForegroundColor Cyan

# 1. Upload fixed migration
Write-Host "[1/4] Uploading fixed migration..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "$LocalBase\database\migrations\2025_12_02_100003_create_prestashop_feature_mappings_table.php" "$RemoteBase/database/migrations/2025_12_02_100003_create_prestashop_feature_mappings_table.php"

# 2. Upload fixed model
Write-Host "[2/4] Uploading fixed model..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "$LocalBase\app\Models\PrestashopFeatureMapping.php" "$RemoteBase/app/Models/PrestashopFeatureMapping.php"

# 3. Drop failed table if exists and run migration
Write-Host "[3/4] Running migration..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute=\"DB::statement('DROP TABLE IF EXISTS prestashop_feature_mappings');\""
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan migrate --force"

# 4. Clear cache
Write-Host "[4/4] Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan cache:clear && php artisan config:clear"

Write-Host "=== DONE ===" -ForegroundColor Green
