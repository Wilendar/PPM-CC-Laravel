$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
Write-Host "Checking JobProgress table..." -ForegroundColor Cyan
$cmd = 'cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute="echo json_encode(App\Models\JobProgress::orderByDesc(\"id\")->take(5)->get([\"id\",\"job_id\",\"job_type\",\"status\",\"current_count\",\"total_count\"])->toArray());"'
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch $cmd
