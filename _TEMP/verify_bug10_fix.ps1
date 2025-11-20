# Verify BUG #10 FIX - Test if new jobs now appear in UI
# 2025-11-12

Write-Host "[VERIFICATION] Running BUG #10 diagnostic after fix..." -ForegroundColor Cyan
Write-Host ""

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php _TEMP/diagnose_bug10_jobs_not_showing.php"

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "=== VERIFICATION COMPLETE ===" -ForegroundColor Green
} else {
    Write-Host ""
    Write-Host "=== VERIFICATION FAILED ===" -ForegroundColor Red
    exit 1
}
