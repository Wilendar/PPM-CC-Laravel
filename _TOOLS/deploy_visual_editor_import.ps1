$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "Deploying VisualDescriptionEditor.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\app\Http\Livewire\Products\VisualDescription\VisualDescriptionEditor.php" "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/app/Http/Livewire/Products/VisualDescription/"

Write-Host "Deploying HtmlToBlocksParser.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\app\Services\VisualEditor\HtmlToBlocksParser.php" "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/app/Services/VisualEditor/"

Write-Host "Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear"

Write-Host "Deploy completed!" -ForegroundColor Green
