# PPM-CC-Laravel FAZA A: Spatie Setup + Middleware Deployment Script
# Deploys authentication system components to production server

$ErrorActionPreference = "Stop"
$PSDefaultParameterValues['Out-File:Encoding'] = 'utf8BOM'

# Configuration
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$ServerHost = "host379076@host379076.hostido.net.pl"
$ServerPort = "64321"
$ProjectPath = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"
$RemotePath = "domains/ppm.mpptrade.pl/public_html"

Write-Host "=== PPM FAZA A DEPLOYMENT SCRIPT ===" -ForegroundColor Green
Write-Host "Deploying Spatie Setup + Middleware components..." -ForegroundColor Yellow

try {
    # Test SSH connection
    Write-Host "1. Testing SSH connection..." -ForegroundColor Cyan
    $phpVersion = plink -ssh $ServerHost -P $ServerPort -i $HostidoKey -batch "php -v" 2>&1
    if ($phpVersion -match "PHP 8.3") {
        Write-Host "   SSH OK: $($phpVersion.Split("`n")[0])" -ForegroundColor Green
    } else {
        throw "SSH connection failed"
    }

    # Create necessary directories
    Write-Host "2. Creating directory structure..." -ForegroundColor Cyan
    $commands = @(
        "cd $RemotePath && mkdir -p app/Http/Middleware",
        "cd $RemotePath && mkdir -p app/Policies", 
        "cd $RemotePath && mkdir -p bootstrap",
        "cd $RemotePath && mkdir -p routes"
    )
    
    foreach ($cmd in $commands) {
        plink -ssh $ServerHost -P $ServerPort -i $HostidoKey -batch $cmd | Out-Null
        Write-Host "   Directory created: $(($cmd -split 'mkdir -p ')[-1])" -ForegroundColor Gray
    }

    # Upload Middleware files
    Write-Host "3. Uploading Middleware files..." -ForegroundColor Cyan
    $middlewareFiles = @(
        "app/Http/Middleware/RoleMiddleware.php",
        "app/Http/Middleware/PermissionMiddleware.php", 
        "app/Http/Middleware/RoleOrPermissionMiddleware.php"
    )
    
    foreach ($file in $middlewareFiles) {
        $localFile = Join-Path $ProjectPath $file
        $remoteFile = "$RemotePath/$file"
        
        if (Test-Path $localFile) {
            # Upload via plink with cat command
            $content = Get-Content $localFile -Raw -Encoding UTF8
            $encodedContent = [System.Convert]::ToBase64String([System.Text.Encoding]::UTF8.GetBytes($content))
            
            plink -ssh $ServerHost -P $ServerPort -i $HostidoKey -batch "cd $RemotePath && echo '$encodedContent' | base64 -d > $file"
            Write-Host "   Uploaded: $file" -ForegroundColor Green
        }
    }

    # Upload Policy files
    Write-Host "4. Uploading Policy files..." -ForegroundColor Cyan
    $policyFiles = @(
        "app/Policies/BasePolicy.php",
        "app/Policies/UserPolicy.php",
        "app/Policies/ProductPolicy.php",
        "app/Policies/CategoryPolicy.php"
    )
    
    foreach ($file in $policyFiles) {
        $localFile = Join-Path $ProjectPath $file
        if (Test-Path $localFile) {
            $content = Get-Content $localFile -Raw -Encoding UTF8
            $encodedContent = [System.Convert]::ToBase64String([System.Text.Encoding]::UTF8.GetBytes($content))
            
            plink -ssh $ServerHost -P $ServerPort -i $HostidoKey -batch "cd $RemotePath && echo '$encodedContent' | base64 -d > $file"
            Write-Host "   Uploaded: $file" -ForegroundColor Green
        }
    }

    # Upload bootstrap/app.php
    Write-Host "5. Uploading bootstrap configuration..." -ForegroundColor Cyan
    $bootstrapFile = Join-Path $ProjectPath "bootstrap/app.php"
    if (Test-Path $bootstrapFile) {
        $content = Get-Content $bootstrapFile -Raw -Encoding UTF8
        $encodedContent = [System.Convert]::ToBase64String([System.Text.Encoding]::UTF8.GetBytes($content))
        
        plink -ssh $ServerHost -P $ServerPort -i $HostidoKey -batch "cd $RemotePath && echo '$encodedContent' | base64 -d > bootstrap/app.php"
        Write-Host "   Uploaded: bootstrap/app.php" -ForegroundColor Green
    }

    # Upload routes files
    Write-Host "6. Uploading routes..." -ForegroundColor Cyan
    $routeFiles = @(
        "routes/web.php",
        "routes/api.php", 
        "routes/console.php",
        "routes/channels.php"
    )
    
    foreach ($file in $routeFiles) {
        $localFile = Join-Path $ProjectPath $file
        if (Test-Path $localFile) {
            $content = Get-Content $localFile -Raw -Encoding UTF8
            $encodedContent = [System.Convert]::ToBase64String([System.Text.Encoding]::UTF8.GetBytes($content))
            
            plink -ssh $ServerHost -P $ServerPort -i $HostidoKey -batch "cd $RemotePath && echo '$encodedContent' | base64 -d > $file"
            Write-Host "   Uploaded: $file" -ForegroundColor Green
        }
    }

    # Upload updated User.php model
    Write-Host "7. Uploading updated User model..." -ForegroundColor Cyan
    $userModelFile = Join-Path $ProjectPath "app/Models/User.php"
    if (Test-Path $userModelFile) {
        $content = Get-Content $userModelFile -Raw -Encoding UTF8
        $encodedContent = [System.Convert]::ToBase64String([System.Text.Encoding]::UTF8.GetBytes($content))
        
        plink -ssh $ServerHost -P $ServerPort -i $HostidoKey -batch "cd $RemotePath && echo '$encodedContent' | base64 -d > app/Models/User.php"
        Write-Host "   Uploaded: app/Models/User.php" -ForegroundColor Green
    }

    # Clear Laravel caches
    Write-Host "8. Clearing Laravel caches..." -ForegroundColor Cyan
    $cacheCommands = @(
        "cd $RemotePath && php artisan config:cache",
        "cd $RemotePath && php artisan route:cache", 
        "cd $RemotePath && php artisan view:clear",
        "cd $RemotePath && composer dump-autoload"
    )
    
    foreach ($cmd in $cacheCommands) {
        try {
            $result = plink -ssh $ServerHost -P $ServerPort -i $HostidoKey -batch $cmd 2>&1
            Write-Host "   $(($cmd -split 'php artisan ')[-1])" -ForegroundColor Gray
        } catch {
            Write-Host "   Warning: $cmd failed" -ForegroundColor Yellow
        }
    }

    # Test application status
    Write-Host "9. Testing application..." -ForegroundColor Cyan
    $testResult = plink -ssh $ServerHost -P $ServerPort -i $HostidoKey -batch "cd $RemotePath && php artisan --version" 2>&1
    if ($testResult -match "Laravel") {
        Write-Host "   Application OK: $testResult" -ForegroundColor Green
    } else {
        Write-Host "   Warning: Application test failed" -ForegroundColor Yellow
    }

    Write-Host "=== FAZA A DEPLOYMENT COMPLETED ===" -ForegroundColor Green
    Write-Host "Authentication system deployed to: https://ppm.mpptrade.pl" -ForegroundColor White
    Write-Host "Next steps: Test role/permission functionality" -ForegroundColor Yellow

} catch {
    Write-Host "ERROR: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}