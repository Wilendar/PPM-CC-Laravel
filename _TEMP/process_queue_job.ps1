$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== PROCESSING QUEUE JOB ===" -ForegroundColor Cyan
Write-Host ""

Write-Host "[1/4] Checking jobs BEFORE processing..." -ForegroundColor Yellow
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php -r 'require \"vendor/autoload.php\"; \$app = require \"bootstrap/app.php\"; \$app->make(\"Illuminate\Contracts\Console\Kernel\")->bootstrap(); echo \"Jobs: \" . DB::table(\"jobs\")->count() . \"\n\"; echo \"SyncJobs: \" . DB::table(\"sync_jobs\")->count() . \"\n\";'"

Write-Host "`n[2/4] Processing ONE job from queue..." -ForegroundColor Yellow
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && timeout 60 php artisan queue:work --once --timeout=55 2>&1"

Write-Host "`n[3/4] Checking jobs AFTER processing..." -ForegroundColor Yellow
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php -r 'require \"vendor/autoload.php\"; \$app = require \"bootstrap/app.php\"; \$app->make(\"Illuminate\Contracts\Console\Kernel\")->bootstrap(); echo \"Jobs: \" . DB::table(\"jobs\")->count() . \"\n\"; echo \"SyncJobs: \" . DB::table(\"sync_jobs\")->count() . \"\n\";'"

Write-Host "`n[4/4] Showing latest sync_job..." -ForegroundColor Yellow
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php -r 'require \"vendor/autoload.php\"; \$app = require \"bootstrap/app.php\"; \$app->make(\"Illuminate\Contracts\Console\Kernel\")->bootstrap(); \$job = DB::table(\"sync_jobs\")->orderBy(\"id\", \"desc\")->first(); if (\$job) { echo \"Latest SyncJob:\n\"; echo \"  ID: \" . \$job->id . \"\n\"; echo \"  Type: \" . \$job->job_type . \"\n\"; echo \"  Status: \" . \$job->status . \"\n\"; echo \"  Source ID: \" . \$job->source_id . \"\n\"; echo \"  Target ID: \" . \$job->target_id . \"\n\"; } else { echo \"No sync_jobs found\n\"; }'"

Write-Host ""
Write-Host "=== PROCESSING COMPLETE ===" -ForegroundColor Green
Write-Host ""
