$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "Uploading clear cache script..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\_TOOLS\clear_visual_desc_cache.php" "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/_TOOLS/"

Write-Host "Running clear cache for product 11183, shop 5..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php _TOOLS/clear_visual_desc_cache.php 11183 5"

Write-Host "Done!" -ForegroundColor Green
