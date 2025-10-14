# Cleanup failed jobs and stuck progress records
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$HostidoHost = "host379076@host379076.hostido.net.pl"
$HostidoPort = "64321"
$RemotePath = "domains/ppm.mpptrade.pl/public_html"

Write-Host "`n=== CLEANING UP FAILED STATE ===" -ForegroundColor Cyan

Write-Host "`n[1/3] Flushing failed jobs..." -ForegroundColor Yellow
plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch `
    "cd $RemotePath && php artisan queue:flush"

Write-Host "`n[2/3] Marking stuck job_progress as failed..." -ForegroundColor Yellow
plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch `
    "cd $RemotePath && php artisan tinker --execute='DB::table(""job_progress"")->whereIn(""id"", [61, 62, 63])->update([""status"" => ""failed""]); echo ""Updated 3 records\n"";'"

Write-Host "`n[3/3] Verifying cleanup..." -ForegroundColor Yellow
plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch `
    "cd $RemotePath && php artisan queue:failed"

Write-Host "`n=== CLEANUP COMPLETE ===" -ForegroundColor Green
Write-Host "`nReady to test import!" -ForegroundColor Cyan
Write-Host "URL: https://ppm.mpptrade.pl/admin/products" -ForegroundColor White
