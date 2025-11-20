$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== UPLOADING AND RUNNING CLEANUP SCRIPT ===" -ForegroundColor Cyan

# Upload script
Write-Host "`nUploading cleanup_ghost_categories.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "_TEMP/cleanup_ghost_categories.php" "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/_TEMP/cleanup_ghost_categories.php"

# Run script
Write-Host "`nRunning cleanup script..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php _TEMP/cleanup_ghost_categories.php"

Write-Host "`n=== DONE ===" -ForegroundColor Green
