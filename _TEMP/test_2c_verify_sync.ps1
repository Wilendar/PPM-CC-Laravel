$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== TEST 2C: VERIFY SYNC STATUS ===" -ForegroundColor Cyan
Write-Host "Creating new AttributeType in PrestaShop first..." -ForegroundColor Yellow

# Create new test
$createOutput = plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php artisan test:attribute-sync"

Write-Host $createOutput

# Process queue to create in PrestaShop
Write-Host "`nProcessing queue (create)..." -ForegroundColor Yellow
$queue1 = plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php artisan queue:work --once"
Write-Host $queue1

# Re-run sync to verify status changes to 'synced'
Write-Host "`nRe-running sync check (should find existing and return 'synced')..." -ForegroundColor Yellow
$syncOutput = plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php artisan test:attribute-sync"
Write-Host $syncOutput

# Process queue again
Write-Host "`nProcessing queue (re-sync)..." -ForegroundColor Yellow
$queue2 = plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php artisan queue:work --once"
Write-Host $queue2

# Check final logs
Write-Host "`n=== CHECKING FINAL LOGS ===" -ForegroundColor Cyan
$logs = plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && tail -100 storage/logs/laravel.log | grep -E '(Attribute group sync|status)' | tail -10"
Write-Host $logs

Write-Host "`n=== DONE ===" -ForegroundColor Green
