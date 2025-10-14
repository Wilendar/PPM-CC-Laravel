# SSH diagnostyka importu produktow
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$HostidoHost = "host379076@host379076.hostido.net.pl"
$HostidoPort = "64321"
$RemotePath = "domains/ppm.mpptrade.pl/public_html"

Write-Host "`n=== 1. CHECKING LARAVEL LOGS ===" -ForegroundColor Cyan
plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch "cd $RemotePath && tail -n 200 storage/logs/laravel.log"

Write-Host "`n=== 2. CHECKING QUEUE WORKERS ===" -ForegroundColor Cyan
plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch "ps aux | grep -E 'queue:work|queue:listen' | grep -v grep"

Write-Host "`n=== 3. CHECKING JOBS COUNT ===" -ForegroundColor Cyan
plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch "cd $RemotePath && php artisan tinker --execute='echo DB::table(""jobs"")->count() . PHP_EOL;'"

Write-Host "`n=== 4. CHECKING DEPLOYED CODE (BulkImportProducts) ===" -ForegroundColor Cyan
plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch "cd $RemotePath && grep -n 'skip_category_analysis' app/Jobs/PrestaShop/BulkImportProducts.php | head -n 10"

Write-Host "`n=== 5. CHECKING DEPLOYED CODE (AnalyzeMissingCategories) ===" -ForegroundColor Cyan
plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch "cd $RemotePath && grep -n 'skip_category_analysis' app/Jobs/PrestaShop/AnalyzeMissingCategories.php | head -n 10"

Write-Host "`n=== 6. CHECKING JOB PROGRESS TABLE ===" -ForegroundColor Cyan
plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch "cd $RemotePath && php artisan tinker --execute='echo DB::table(""job_progress"")->latest(""updated_at"")->take(5)->get();'"

Write-Host "`n=== 7. CHECKING CATEGORY PREVIEW TABLE ===" -ForegroundColor Cyan
plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch "cd $RemotePath && php artisan tinker --execute='echo DB::table(""category_preview"")->where(""status"", ""pending"")->count() . "" pending previews"" . PHP_EOL;'"

Write-Host "`n=== DIAGNOSTYKA ZAKONCZONA ===" -ForegroundColor Green
