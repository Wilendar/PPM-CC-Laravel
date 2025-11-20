# Run User ID Audit on Production
# 2025-11-07

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteHost = "host379076@host379076.hostido.net.pl"
$RemoteBase = "domains/ppm.mpptrade.pl/public_html"

Write-Host "`n=== UPLOADING USER_ID AUDIT SCRIPT ===`n" -ForegroundColor Cyan

pscp -i $HostidoKey -P 64321 `
    "_TEMP\check_sync_jobs_user_id.php" `
    "${RemoteHost}:${RemoteBase}/_TEMP/check_sync_jobs_user_id.php"

if ($LASTEXITCODE -ne 0) {
    Write-Host "✗ Upload failed" -ForegroundColor Red
    exit 1
}

Write-Host "`n=== RUNNING AUDIT ON PRODUCTION ===`n" -ForegroundColor Cyan

plink -ssh $RemoteHost -P 64321 -i $HostidoKey -batch `
    "cd ${RemoteBase} && php _TEMP/check_sync_jobs_user_id.php"
