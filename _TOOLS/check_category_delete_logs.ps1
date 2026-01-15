$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
Write-Host "Checking logs for category deletion..." -ForegroundColor Cyan
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "tail -150 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log | grep -E '(SAVE.*Category|CATEGORY DELETE|physical deletion|categories_to_delete|pendingDeleteCategories|Starting physical deletion)' -A 2"
