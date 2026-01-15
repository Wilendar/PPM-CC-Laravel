# Deploy Visual Block Builder - ETAP_07f_P4
# Deploys BlockBuilderCanvas component, templates, and migration

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteHost = "host379076@host379076.hostido.net.pl"
$RemotePort = "64321"
$RemotePath = "domains/ppm.mpptrade.pl/public_html"
$LocalPath = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Deploying Visual Block Builder (ETAP_07f_P4)" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

# 1. Deploy Livewire component
Write-Host "`n[1/7] Deploying BlockBuilderCanvas.php..." -ForegroundColor Yellow
$componentDir = "app/Http/Livewire/Products/VisualDescription/BlockBuilder"
plink -ssh $RemoteHost -P $RemotePort -i $HostidoKey -batch "mkdir -p $RemotePath/$componentDir"
pscp -i $HostidoKey -P $RemotePort "$LocalPath/$componentDir/BlockBuilderCanvas.php" "${RemoteHost}:$RemotePath/$componentDir/BlockBuilderCanvas.php"

# 2. Deploy Blade templates
Write-Host "`n[2/7] Deploying Blade templates..." -ForegroundColor Yellow
$viewDir = "resources/views/livewire/products/visual-description/block-builder"
$partialsDir = "$viewDir/partials"
plink -ssh $RemoteHost -P $RemotePort -i $HostidoKey -batch "mkdir -p $RemotePath/$partialsDir"

pscp -i $HostidoKey -P $RemotePort "$LocalPath/$viewDir/canvas.blade.php" "${RemoteHost}:$RemotePath/$viewDir/canvas.blade.php"
pscp -i $HostidoKey -P $RemotePort "$LocalPath/$partialsDir/element-renderer.blade.php" "${RemoteHost}:$RemotePath/$partialsDir/element-renderer.blade.php"
pscp -i $HostidoKey -P $RemotePort "$LocalPath/$partialsDir/property-panel.blade.php" "${RemoteHost}:$RemotePath/$partialsDir/property-panel.blade.php"
pscp -i $HostidoKey -P $RemotePort "$LocalPath/$partialsDir/layer-panel.blade.php" "${RemoteHost}:$RemotePath/$partialsDir/layer-panel.blade.php"

# 3. Deploy updated block-palette.blade.php
Write-Host "`n[3/7] Deploying updated block-palette.blade.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P $RemotePort "$LocalPath/resources/views/livewire/products/visual-description/partials/block-palette.blade.php" "${RemoteHost}:$RemotePath/resources/views/livewire/products/visual-description/partials/block-palette.blade.php"

# 4. Deploy updated visual-description-editor.blade.php
Write-Host "`n[4/7] Deploying updated visual-description-editor.blade.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P $RemotePort "$LocalPath/resources/views/livewire/products/visual-description/visual-description-editor.blade.php" "${RemoteHost}:$RemotePath/resources/views/livewire/products/visual-description/visual-description-editor.blade.php"

# 5. Deploy updated BlockDefinition model
Write-Host "`n[5/7] Deploying updated BlockDefinition.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P $RemotePort "$LocalPath/app/Models/BlockDefinition.php" "${RemoteHost}:$RemotePath/app/Models/BlockDefinition.php"

# 6. Deploy migration
Write-Host "`n[6/7] Deploying migration..." -ForegroundColor Yellow
$migrationFile = Get-ChildItem "$LocalPath/database/migrations" -Filter "*add_builder_document_to_block_definitions_table*" | Select-Object -First 1
if ($migrationFile) {
    pscp -i $HostidoKey -P $RemotePort $migrationFile.FullName "${RemoteHost}:$RemotePath/database/migrations/$($migrationFile.Name)"
}

# 7. Run migration and clear cache
Write-Host "`n[7/7] Running migration and clearing cache..." -ForegroundColor Yellow
plink -ssh $RemoteHost -P $RemotePort -i $HostidoKey -batch "cd $RemotePath && php artisan migrate --force 2>&1"
plink -ssh $RemoteHost -P $RemotePort -i $HostidoKey -batch "cd $RemotePath && php artisan view:clear && php artisan cache:clear && php artisan config:clear"

Write-Host "`n========================================" -ForegroundColor Green
Write-Host "Deployment completed!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host "`nTest URL: https://ppm.mpptrade.pl/products/11148/visual-editor?shop=5" -ForegroundColor Cyan
Write-Host "Look for 'Stworz blok wizualnie' button in block palette" -ForegroundColor Cyan
