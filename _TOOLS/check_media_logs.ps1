$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== CHECKING MEDIA SYNC LOGS ===" -ForegroundColor Cyan

Write-Host "`n[1] Latest media-related logs:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "tail -200 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log | grep -iE '(SyncMedia|dispatchMedia|media_pull|MediaSync)' | tail -20"

Write-Host "`n[2] Check pending queue jobs:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php -r 'require \"vendor/autoload.php\"; \$app = require_once \"bootstrap/app.php\"; \$app->make(\"Illuminate\Contracts\Console\Kernel\")->bootstrap(); echo DB::table(\"jobs\")->count() . \" pending jobs\n\";'"

Write-Host "`n[3] Check failed_jobs:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php -r 'require \"vendor/autoload.php\"; \$app = require_once \"bootstrap/app.php\"; \$app->make(\"Illuminate\Contracts\Console\Kernel\")->bootstrap(); echo DB::table(\"failed_jobs\")->count() . \" failed jobs\n\";'"

Write-Host "`n[4] Check job_progress for media:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php -r 'require \"vendor/autoload.php\"; \$app = require_once \"bootstrap/app.php\"; \$app->make(\"Illuminate\Contracts\Console\Kernel\")->bootstrap(); echo DB::table(\"job_progress\")->where(\"job_type\", \"media_pull\")->count() . \" media_pull records\n\";'"

Write-Host "`n=== DONE ===" -ForegroundColor Green
