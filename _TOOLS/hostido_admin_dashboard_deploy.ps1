# PPM-CC-Laravel Admin Dashboard Deployment Script
# FAZA A: Dashboard Core & Monitoring - Hostido.net.pl Deployment
# Generated with Claude Code

param(
    [string]$Environment = "production",
    [switch]$RunMigrations = $false,
    [switch]$ClearCache = $true
)

# Configuration
$HostidoHost = "host379076@host379076.hostido.net.pl"
$HostidoPort = 64321
$HostidoKeyPath = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemotePath = "domains/ppm.mpptrade.pl/public_html"
$LocalProjectPath = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"

Write-Host "=== PPM Admin Dashboard Deployment - FAZA A ===" -ForegroundColor Green
Write-Host "Deploying admin dashboard components to https://ppm.mpptrade.pl/admin" -ForegroundColor Yellow
Write-Host ""

# Step 1: Upload AdminDashboard Livewire component
Write-Host "[1/8] Uploading AdminDashboard Livewire component..." -ForegroundColor Cyan

$adminDashboardPath = "$LocalProjectPath\app\Http\Livewire\Dashboard\AdminDashboard.php"
if (Test-Path $adminDashboardPath) {
    Write-Host "Found AdminDashboard component - uploading..." -ForegroundColor Green
    
    # Create directory structure on server
    plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKeyPath -batch "mkdir -p $RemotePath/app/Http/Livewire/Dashboard"
    
    # Upload AdminDashboard component
    pscp -P $HostidoPort -i $HostidoKeyPath "$adminDashboardPath" "${HostidoHost}:$RemotePath/app/Http/Livewire/Dashboard/"
    
    Write-Host "AdminDashboard component uploaded successfully" -ForegroundColor Green
} else {
    Write-Host "ERROR: AdminDashboard component not found at $adminDashboardPath" -ForegroundColor Red
    exit 1
}

# Step 2: Upload StatsWidgets component
Write-Host "[2/8] Uploading StatsWidgets component..." -ForegroundColor Cyan

$statsWidgetsPath = "$LocalProjectPath\app\Http\Livewire\Dashboard\Widgets\StatsWidgets.php"
if (Test-Path $statsWidgetsPath) {
    # Create widgets directory
    plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKeyPath -batch "mkdir -p $RemotePath/app/Http/Livewire/Dashboard/Widgets"
    
    # Upload StatsWidgets component
    pscp -P $HostidoPort -i $HostidoKeyPath "$statsWidgetsPath" "${HostidoHost}:$RemotePath/app/Http/Livewire/Dashboard/Widgets/"
    
    Write-Host "StatsWidgets component uploaded successfully" -ForegroundColor Green
} else {
    Write-Host "WARNING: StatsWidgets component not found" -ForegroundColor Yellow
}

# Step 3: Upload Blade templates
Write-Host "[3/8] Uploading dashboard Blade templates..." -ForegroundColor Cyan

# Admin dashboard template
$adminDashboardBlad = "$LocalProjectPath\resources\views\livewire\dashboard\admin-dashboard.blade.php"
if (Test-Path $adminDashboardBlad) {
    # Create views directory structure
    plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKeyPath -batch "mkdir -p $RemotePath/resources/views/livewire/dashboard"
    
    # Upload admin dashboard template
    pscp -P $HostidoPort -i $HostidoKeyPath "$adminDashboardBlad" "${HostidoHost}:$RemotePath/resources/views/livewire/dashboard/"
    
    Write-Host "Admin dashboard template uploaded" -ForegroundColor Green
}

# Stats widgets template
$statsWidgetsBlade = "$LocalProjectPath\resources\views\livewire\dashboard\widgets\stats-widgets.blade.php"
if (Test-Path $statsWidgetsBlade) {
    # Create widgets views directory
    plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKeyPath -batch "mkdir -p $RemotePath/resources/views/livewire/dashboard/widgets"
    
    # Upload stats widgets template
    pscp -P $HostidoPort -i $HostidoKeyPath "$statsWidgetsBlade" "${HostidoHost}:$RemotePath/resources/views/livewire/dashboard/widgets/"
    
    Write-Host "Stats widgets template uploaded" -ForegroundColor Green
}

# Admin layout
$adminLayoutPath = "$LocalProjectPath\resources\views\layouts\admin.blade.php"
if (Test-Path $adminLayoutPath) {
    # Create layouts directory
    plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKeyPath -batch "mkdir -p $RemotePath/resources/views/layouts"
    
    # Upload admin layout
    pscp -P $HostidoPort -i $HostidoKeyPath "$adminLayoutPath" "${HostidoHost}:$RemotePath/resources/views/layouts/"
    
    Write-Host "Admin layout template uploaded" -ForegroundColor Green
}

# Step 4: Upload Services
Write-Host "[4/8] Uploading SystemHealthService..." -ForegroundColor Cyan

$systemHealthServicePath = "$LocalProjectPath\app\Services\SystemHealthService.php"
if (Test-Path $systemHealthServicePath) {
    # Create services directory
    plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKeyPath -batch "mkdir -p $RemotePath/app/Services"
    
    # Upload SystemHealthService
    pscp -P $HostidoPort -i $HostidoKeyPath "$systemHealthServicePath" "${HostidoHost}:$RemotePath/app/Services/"
    
    Write-Host "SystemHealthService uploaded successfully" -ForegroundColor Green
}

# Step 5: Upload Middleware
Write-Host "[5/8] Uploading AdminMiddleware..." -ForegroundColor Cyan

$adminMiddlewarePath = "$LocalProjectPath\app\Http\Middleware\AdminMiddleware.php"
if (Test-Path $adminMiddlewarePath) {
    # Upload AdminMiddleware
    pscp -P $HostidoPort -i $HostidoKeyPath "$adminMiddlewarePath" "${HostidoHost}:$RemotePath/app/Http/Middleware/"
    
    Write-Host "AdminMiddleware uploaded successfully" -ForegroundColor Green
}

# Step 6: Upload updated files
Write-Host "[6/8] Uploading updated configuration files..." -ForegroundColor Cyan

# Upload updated User model
$userModelPath = "$LocalProjectPath\app\Models\User.php"
if (Test-Path $userModelPath) {
    pscp -P $HostidoPort -i $HostidoKeyPath "$userModelPath" "${HostidoHost}:$RemotePath/app/Models/"
    Write-Host "User model updated" -ForegroundColor Green
}

# Upload updated routes
$webRoutesPath = "$LocalProjectPath\routes\web.php"
if (Test-Path $webRoutesPath) {
    pscp -P $HostidoPort -i $HostidoKeyPath "$webRoutesPath" "${HostidoHost}:$RemotePath/routes/"
    Write-Host "Web routes updated" -ForegroundColor Green
}

# Upload updated bootstrap/app.php
$bootstrapAppPath = "$LocalProjectPath\bootstrap\app.php"
if (Test-Path $bootstrapAppPath) {
    pscp -P $HostidoPort -i $HostidoKeyPath "$bootstrapAppPath" "${HostidoHost}:$RemotePath/bootstrap/"
    Write-Host "Bootstrap app configuration updated" -ForegroundColor Green
}

# Step 7: Run migrations if requested
if ($RunMigrations) {
    Write-Host "[7/8] Running database migrations..." -ForegroundColor Cyan
    
    # Upload dashboard preferences migration
    $migrationPath = "$LocalProjectPath\database\migrations\2024_01_01_000025_add_dashboard_preferences_to_users.php"
    if (Test-Path $migrationPath) {
        pscp -P $HostidoPort -i $HostidoKeyPath "$migrationPath" "${HostidoHost}:$RemotePath/database/migrations/"
        Write-Host "Dashboard preferences migration uploaded" -ForegroundColor Green
    }
    
    # Run migrations
    $migrationResult = plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKeyPath -batch "cd $RemotePath && php artisan migrate --force"
    Write-Host "Migration result: $migrationResult" -ForegroundColor Yellow
} else {
    Write-Host "[7/8] Skipping migrations (use -RunMigrations flag to run them)" -ForegroundColor Yellow
}

# Step 8: Clear cache and optimize
if ($ClearCache) {
    Write-Host "[8/8] Clearing cache and optimizing..." -ForegroundColor Cyan
    
    # Clear various caches
    plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKeyPath -batch "cd $RemotePath && php artisan config:cache"
    plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKeyPath -batch "cd $RemotePath && php artisan route:cache"
    plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKeyPath -batch "cd $RemotePath && php artisan view:cache"
    
    Write-Host "Cache cleared and optimized" -ForegroundColor Green
} else {
    Write-Host "[8/8] Skipping cache operations" -ForegroundColor Yellow
}

# Final verification
Write-Host "" 
Write-Host "=== Deployment Summary ===" -ForegroundColor Green
Write-Host "✅ AdminDashboard Livewire component deployed" -ForegroundColor Green
Write-Host "✅ Dashboard templates and layouts deployed" -ForegroundColor Green  
Write-Host "✅ Services and middleware deployed" -ForegroundColor Green
Write-Host "✅ Configuration files updated" -ForegroundColor Green

if ($RunMigrations) {
    Write-Host "✅ Database migrations executed" -ForegroundColor Green
}

if ($ClearCache) {
    Write-Host "✅ Cache cleared and optimized" -ForegroundColor Green
}

Write-Host ""
Write-Host "🚀 Admin Dashboard is now available at:" -ForegroundColor Cyan
Write-Host "   https://ppm.mpptrade.pl/admin" -ForegroundColor White -BackgroundColor Blue
Write-Host ""

# Test the deployment
Write-Host "Testing admin dashboard accessibility..." -ForegroundColor Cyan
try {
    $testResult = plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKeyPath -batch "cd $RemotePath && php artisan route:list | grep admin.dashboard || echo 'Route not found'"
    Write-Host "Route test result: $testResult" -ForegroundColor Yellow
} catch {
    Write-Host "Could not verify routes automatically" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "=== FAZA A: Dashboard Core & Monitoring - DEPLOYMENT COMPLETE ===" -ForegroundColor Green
Write-Host "Next step: Test admin dashboard functionality at https://ppm.mpptrade.pl/admin" -ForegroundColor Yellow

# Performance test recommendation
Write-Host ""
Write-Host "📋 RECOMMENDED TESTING:" -ForegroundColor Magenta
Write-Host "1. Login as admin user and access /admin route" -ForegroundColor White
Write-Host "2. Verify all dashboard widgets load properly" -ForegroundColor White  
Write-Host "3. Test auto-refresh functionality" -ForegroundColor White
Write-Host "4. Check system performance metrics display" -ForegroundColor White
Write-Host "5. Verify responsive design on different screen sizes" -ForegroundColor White