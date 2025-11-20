$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== CHECKING LARAVEL LOGS ===" -ForegroundColor Cyan
Write-Host ""

Write-Host "Fetching last 50 lines from laravel.log..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "tail -n 50 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log | grep -E '(handleConvertVariants|executePendingVariantAction|VariantConversionService)'"
