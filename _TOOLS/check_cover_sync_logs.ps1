$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
Write-Host "Checking cover image sync logs..." -ForegroundColor Cyan
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "tail -150 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log | grep -E '(MEDIA SYNC|Cover image|cover sync|setProductImageCover|id_default_image|Checking cover)' -A 1"
