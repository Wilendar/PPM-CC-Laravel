# Check queue worker and jobs status
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$HostidoHost = "host379076@host379076.hostido.net.pl"
$HostidoPort = "64321"
$RemotePath = "domains/ppm.mpptrade.pl/public_html"

Write-Host "`n=== QUEUE WORKER STATUS ===" -ForegroundColor Cyan
plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch "ps aux | grep -E 'queue:work|queue:listen' | grep -v grep"

Write-Host "`n=== JOBS IN QUEUE ===" -ForegroundColor Cyan
plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch "cd $RemotePath && php artisan queue:size"

Write-Host "`n=== FAILED JOBS ===" -ForegroundColor Cyan
plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch "cd $RemotePath && php artisan queue:failed"

Write-Host "`n=== JOB PROGRESS TABLE ===" -ForegroundColor Cyan
plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch "cd $RemotePath && php artisan tinker --execute='echo json_encode(DB::table(""job_progress"")->latest(""updated_at"")->take(3)->get()->toArray(), JSON_PRETTY_PRINT);'"

Write-Host "`n=== DONE ===" -ForegroundColor Green
