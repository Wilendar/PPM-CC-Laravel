# Run SyncJob Linkage Verification on Production
# 2025-11-07

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteHost = "host379076@host379076.hostido.net.pl"
$RemoteBase = "domains/ppm.mpptrade.pl/public_html"

Write-Host "`n=== PRODUCTION VERIFICATION ===" -ForegroundColor Cyan
Write-Host "[1/2] Uploading verification script..." -ForegroundColor Yellow

pscp -i $HostidoKey -P 64321 `
    "_TEMP\verify_syncjob_linkage_production.php" `
    "${RemoteHost}:${RemoteBase}/_TEMP/verify_syncjob_linkage_production.php"

if ($LASTEXITCODE -ne 0) {
    Write-Host "✗ Upload failed" -ForegroundColor Red
    exit 1
}

Write-Host "`n[2/2] Running verification on production..." -ForegroundColor Yellow

plink -ssh $RemoteHost -P 64321 -i $HostidoKey -batch `
    "cd ${RemoteBase} && php _TEMP/verify_syncjob_linkage_production.php"

Write-Host "`n=== VERIFICATION COMPLETE ===" -ForegroundColor Green
