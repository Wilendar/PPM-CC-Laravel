# ETAP_07c FAZA 1 Deployment - Rich Job Progress + Non-blocking Import
# Date: 2025-11-28

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"
$SSHHost = "host379076@host379076.hostido.net.pl"

Write-Host "=== ETAP_07c FAZA 1 DEPLOYMENT ===" -ForegroundColor Cyan
Write-Host "Rich Job Progress + Non-blocking Import" -ForegroundColor Yellow

# 1. Upload Models
Write-Host "`n[1/7] Uploading JobProgress.php model..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Models/JobProgress.php" "${RemoteBase}/app/Models/JobProgress.php"

# 2. Upload Services
Write-Host "[2/7] Uploading JobProgressService.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Services/JobProgressService.php" "${RemoteBase}/app/Services/JobProgressService.php"

# 3. Upload Jobs
Write-Host "[3/7] Uploading AnalyzeMissingCategories.php job..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Jobs/PrestaShop/AnalyzeMissingCategories.php" "${RemoteBase}/app/Jobs/PrestaShop/AnalyzeMissingCategories.php"

# 4. Upload Livewire Components
Write-Host "[4/7] Uploading Livewire components..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Http/Livewire/Products/Listing/ProductList.php" "${RemoteBase}/app/Http/Livewire/Products/Listing/ProductList.php"
pscp -i $HostidoKey -P 64321 "app/Http/Livewire/Components/JobProgressBar.php" "${RemoteBase}/app/Http/Livewire/Components/JobProgressBar.php"

# 5. Upload Blade Views
Write-Host "[5/7] Uploading Blade views..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "resources/views/livewire/components/job-progress-bar.blade.php" "${RemoteBase}/resources/views/livewire/components/job-progress-bar.blade.php"

# 6. Upload Migration
Write-Host "[6/7] Uploading migration..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "database/migrations/2025_11_28_000000_add_rich_progress_fields_to_job_progress.php" "${RemoteBase}/database/migrations/2025_11_28_000000_add_rich_progress_fields_to_job_progress.php"

# 7. Run migration and clear cache
Write-Host "[7/7] Running migration and clearing cache..." -ForegroundColor Yellow
plink -ssh $SSHHost -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan migrate --force && php artisan cache:clear && php artisan view:clear && php artisan config:clear"

Write-Host "`n=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
Write-Host "Test at: https://ppm.mpptrade.pl/admin/products" -ForegroundColor Cyan
