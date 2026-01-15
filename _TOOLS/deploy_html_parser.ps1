# Deploy HTML to BlockDocument Parser - ETAP_07f_P4
# Deploys HtmlToBlockDocumentParser service and updated BlockBuilderCanvas

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteHost = "host379076@host379076.hostido.net.pl"
$RemotePort = "64321"
$RemotePath = "domains/ppm.mpptrade.pl/public_html"
$LocalPath = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Deploying HTML to BlockDocument Parser" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

# 1. Deploy HtmlToBlockDocumentParser service
Write-Host "`n[1/3] Deploying HtmlToBlockDocumentParser.php..." -ForegroundColor Yellow
$serviceDir = "app/Services/VisualEditor"
plink -ssh $RemoteHost -P $RemotePort -i $HostidoKey -batch "mkdir -p $RemotePath/$serviceDir"
pscp -i $HostidoKey -P $RemotePort "$LocalPath/$serviceDir/HtmlToBlockDocumentParser.php" "${RemoteHost}:$RemotePath/$serviceDir/HtmlToBlockDocumentParser.php"

# 2. Deploy updated BlockBuilderCanvas
Write-Host "`n[2/3] Deploying updated BlockBuilderCanvas.php..." -ForegroundColor Yellow
$componentDir = "app/Http/Livewire/Products/VisualDescription/BlockBuilder"
pscp -i $HostidoKey -P $RemotePort "$LocalPath/$componentDir/BlockBuilderCanvas.php" "${RemoteHost}:$RemotePath/$componentDir/BlockBuilderCanvas.php"

# 3. Clear cache
Write-Host "`n[3/3] Clearing cache..." -ForegroundColor Yellow
plink -ssh $RemoteHost -P $RemotePort -i $HostidoKey -batch "cd $RemotePath && php artisan view:clear && php artisan cache:clear && php artisan config:clear"

Write-Host "`n========================================" -ForegroundColor Green
Write-Host "Deployment completed!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host "`nTest URL: https://ppm.mpptrade.pl/products/11148/visual-editor?shop=5" -ForegroundColor Cyan
Write-Host "Open any existing block to test HTML import" -ForegroundColor Cyan
