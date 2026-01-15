$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== CLEANING UP STUCK PROGRESS BARS ===" -ForegroundColor Cyan

Write-Host "`n[1] Count of running/pending jobs older than 5 minutes:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute=\"echo DB::table('job_progress')->whereIn('status', ['running', 'pending'])->where('created_at', '<', now()->subMinutes(5))->count();\""

Write-Host "`n[2] Marking old stuck jobs as failed:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute=\"DB::table('job_progress')->whereIn('status', ['running', 'pending'])->where('created_at', '<', now()->subMinutes(5))->update(['status' => 'failed', 'completed_at' => now()]); echo 'Done';\""

Write-Host "`n[3] Current active jobs:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute=\"echo 'Active: ' . DB::table('job_progress')->whereIn('status', ['running', 'pending', 'awaiting_user'])->count();\""

Write-Host "`n=== DONE ===" -ForegroundColor Green
