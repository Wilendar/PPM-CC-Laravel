# Simple FAZA C Test Script
$ErrorActionPreference = "Stop"
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

$SSHHost = "host379076@host379076.hostido.net.pl"
$SSHPort = "64321" 
$SSHKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemotePath = "domains/ppm.mpptrade.pl/public_html"

Write-Host "Testing FAZA C deployment connectivity..." -ForegroundColor Cyan

try {
    # Test SSH connectivity
    Write-Host "Testing SSH connection..." -ForegroundColor Yellow
    $result = plink -ssh $SSHHost -P $SSHPort -i "$SSHKey" -batch "cd $RemotePath && php -v | head -1"
    Write-Host "PHP Version: $result" -ForegroundColor Green
    
    # Check if Laravel is working
    Write-Host "Testing Laravel routes..." -ForegroundColor Yellow
    $result = plink -ssh $SSHHost -P $SSHPort -i "$SSHKey" -batch "cd $RemotePath && php artisan route:list | grep admin | head -5"
    Write-Host "Admin routes found: $result" -ForegroundColor Green
    
    Write-Host "Connectivity test PASSED" -ForegroundColor Green
    
} catch {
    Write-Host "Connectivity test FAILED: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}