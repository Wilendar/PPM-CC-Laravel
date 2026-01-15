# Check job progress status in database
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== CHECKING JOB PROGRESS STATUS ===" -ForegroundColor Cyan

$command = 'cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute="echo App\\Models\\JobProgress::orderBy(\"id\", \"desc\")->take(5)->get([\"id\", \"status\", \"job_type\", \"completed_at\"])->toJson(JSON_PRETTY_PRINT);"'

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch $command
