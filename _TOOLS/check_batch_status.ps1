$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
Write-Host "=== CHECKING BATCH STATUS IN DATABASE ===" -ForegroundColor Cyan
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute=\"echo json_encode(DB::table('job_batches')->where('id', 'a08f6e0e-452c-4e0a-8f0e-56e3f0c994fd')->first(), JSON_PRETTY_PRINT);\""
Write-Host ""
Write-Host "=== CHECKING QUEUE JOBS ===" -ForegroundColor Cyan
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan queue:size"
Write-Host ""
Write-Host "=== CHECKING FAILED JOBS ===" -ForegroundColor Cyan
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan queue:failed | tail -5"
