# Upload and run skip_descriptions migration
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== DEPLOYING SKIP_DESCRIPTIONS MIGRATION ===" -ForegroundColor Cyan

Write-Host "[1/2] Uploading migration file..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "database/migrations/2025_12_10_180000_add_skip_descriptions_to_pending_products.php" "$RemoteBase/database/migrations/2025_12_10_180000_add_skip_descriptions_to_pending_products.php"

Write-Host "[2/2] Running migration..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan migrate --force"

Write-Host "=== MIGRATION COMPLETE ===" -ForegroundColor Green
