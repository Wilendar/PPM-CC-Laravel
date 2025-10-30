$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== E2E TEST 2: Export TO PrestaShop ===" -ForegroundColor Cyan

# Step 1: Upload test script
Write-Host "[1/4] Uploading test script..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 `
  "_TEMP/test_attribute_sync_e2e.php" `
  "$RemoteBase/_TEMP/test_attribute_sync_e2e.php"

if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: Failed to upload test script" -ForegroundColor Red
    exit 1
}

# Step 2: Run test script via tinker
Write-Host "[2/4] Running test script (creating AttributeType + values)..." -ForegroundColor Yellow
$output = plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker < _TEMP/test_attribute_sync_e2e.php 2>&1"

Write-Host $output

# Step 3: Process queue job
Write-Host "[3/4] Processing sync job..." -ForegroundColor Yellow
$queueOutput = plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php artisan queue:work --once 2>&1"

Write-Host $queueOutput

# Step 4: Check sync status
Write-Host "[4/4] Checking sync status..." -ForegroundColor Yellow
$statusCheck = plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch @"
cd domains/ppm.mpptrade.pl/public_html
php artisan tinker --execute="
DB::table('prestashop_attribute_group_mapping')
  ->where('sync_status', 'synced')
  ->orderBy('id', 'desc')
  ->first();
"
"@

Write-Host $statusCheck

Write-Host "`n=== E2E TEST 2 EXECUTION COMPLETE ===" -ForegroundColor Green
Write-Host "Check the output above for:" -ForegroundColor Yellow
Write-Host "  ✅ AttributeType created (ID shown)" -ForegroundColor Yellow
Write-Host "  ✅ Job processed successfully" -ForegroundColor Yellow
Write-Host "  ✅ Mapping has sync_status='synced'" -ForegroundColor Yellow
