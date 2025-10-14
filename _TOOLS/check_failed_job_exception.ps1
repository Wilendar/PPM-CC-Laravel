# Check failed job exception details
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$HostidoHost = "host379076@host379076.hostido.net.pl"
$HostidoPort = "64321"
$RemotePath = "domains/ppm.mpptrade.pl/public_html"

Write-Host "`n=== FAILED JOB EXCEPTION ===" -ForegroundColor Cyan
plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch "cd $RemotePath && php artisan tinker --execute='echo DB::table(""failed_jobs"")->orderBy(""id"", ""desc"")->first()->exception;' | head -n 100"

Write-Host "`n=== CHECK CURRENT JOBS IN QUEUE ===" -ForegroundColor Cyan
plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch "cd $RemotePath && php artisan tinker --execute='echo DB::table(""jobs"")->count() . "" jobs in queue\n"";'"

Write-Host "`n=== LARAVEL LOG - ANALYZE MISSING CATEGORIES ERROR ===" -ForegroundColor Cyan
plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch "cd $RemotePath && grep -A 20 'AnalyzeMissingCategories' storage/logs/laravel.log | tail -n 50"

Write-Host "`n=== DONE ===" -ForegroundColor Green
