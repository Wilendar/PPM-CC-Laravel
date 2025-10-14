# Check failed job details and code verification
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$HostidoHost = "host379076@host379076.hostido.net.pl"
$HostidoPort = "64321"
$RemotePath = "domains/ppm.mpptrade.pl/public_html"

Write-Host "`n=== FAILED JOB DETAILS ===" -ForegroundColor Cyan
plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch "cd $RemotePath && php artisan tinker --execute='echo json_encode(DB::table(""failed_jobs"")->latest()->first(), JSON_PRETTY_PRINT);'"

Write-Host "`n=== CHECK DEPLOYED CODE (BulkImportProducts) ===" -ForegroundColor Cyan
plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch "cd $RemotePath && grep -A 3 'skip_category_analysis' app/Jobs/PrestaShop/BulkImportProducts.php | head -n 20"

Write-Host "`n=== CHECK DEPLOYED CODE (AnalyzeMissingCategories) ===" -ForegroundColor Cyan
plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch "cd $RemotePath && grep -A 3 'skip_category_analysis' app/Jobs/PrestaShop/AnalyzeMissingCategories.php | head -n 20"

Write-Host "`n=== CHECK DEPLOYED CODE (shouldAnalyzeCategories) ===" -ForegroundColor Cyan
plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch "cd $RemotePath && grep -A 10 'shouldAnalyzeCategories' app/Jobs/PrestaShop/BulkImportProducts.php"

Write-Host "`n=== DONE ===" -ForegroundColor Green
