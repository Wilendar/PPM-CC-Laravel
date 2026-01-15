$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
Write-Host "Checking cover image API logs..." -ForegroundColor Cyan
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "grep -E '(Set product cover|Failed to set cover|id_default_image|cover image)' domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log | tail -30"
