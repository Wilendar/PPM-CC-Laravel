$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== ADDING MEDIA JOB TYPES TO DATABASE ===" -ForegroundColor Cyan

Write-Host "`n[1] Adding media_pull, media_push to job_type ENUM:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute=\"DB::statement(\\\"ALTER TABLE job_progress MODIFY COLUMN job_type ENUM('import','sync','export','category_delete','category_analysis','bulk_export','bulk_update','stock_sync','price_sync','media_pull','media_push') NOT NULL\\\");\""

Write-Host "`n[2] Verify change:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute=\"echo DB::select(\\\"SHOW COLUMNS FROM job_progress WHERE Field = 'job_type'\\\")[0]->Type;\""

Write-Host "`n[3] Clear failed jobs and retry:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan queue:flush && php artisan cache:clear"

Write-Host "`n=== DONE ===" -ForegroundColor Green
