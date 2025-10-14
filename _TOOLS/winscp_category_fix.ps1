# WinSCP Upload - Category Form Fix
# UTF-8 BOM encoding

$OutputEncoding = [System.Text.Encoding]::UTF8
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

$WinSCPPath = "C:\Program Files (x86)\WinSCP\WinSCP.com"
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$LocalFile = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\resources\views\livewire\products\categories\category-form.blade.php"

Write-Host "`n" -NoNewline
Write-Host "🚀 WinSCP Upload - Category Form Fix" -ForegroundColor Cyan
Write-Host "======================================" -ForegroundColor Cyan
Write-Host "`n"

if (-not (Test-Path $WinSCPPath)) {
    Write-Host "❌ WinSCP not found!" -ForegroundColor Red
    Write-Host "Install from: https://winscp.net/eng/download.php" -ForegroundColor Yellow
    exit 1
}

if (-not (Test-Path $LocalFile)) {
    Write-Host "❌ Local file not found!" -ForegroundColor Red
    exit 1
}

Write-Host "📤 Uploading via WinSCP..." -ForegroundColor Cyan

# WinSCP Script
$WinSCPScript = @"
option batch on
option confirm off
option reconnecttime 30
open sftp://host379076@host379076.hostido.net.pl:64321/ -privatekey="$HostidoKey" -hostkey="ssh-ed25519 255 s5jsBvAUexZAUyZgYF3ONT2RvrcsHjhso6DCiTBICiM"
cd domains/ppm.mpptrade.pl/public_html/resources/views/livewire/products/categories/
put "$LocalFile"
close
exit
"@

$ScriptFile = "$env:TEMP\winscp_upload_category.txt"
$WinSCPScript | Out-File -FilePath $ScriptFile -Encoding ASCII -Force

Write-Host "Running WinSCP..." -ForegroundColor Gray

# Execute WinSCP
$result = & $WinSCPPath /log="$env:TEMP\winscp_log.txt" /script=$ScriptFile 2>&1

if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ Upload successful!" -ForegroundColor Green

    # Clear Laravel cache
    Write-Host "`n🧹 Clearing Laravel cache..." -ForegroundColor Cyan

    $cacheResult = & plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear" 2>&1

    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ Cache cleared!" -ForegroundColor Green
    } else {
        Write-Host "⚠️ Cache clear might have failed - check manually" -ForegroundColor Yellow
    }

    Write-Host "`n🌐 Test URL: https://ppm.mpptrade.pl/admin/products/categories/create" -ForegroundColor Cyan
    Write-Host "`n✅ Deployment complete!" -ForegroundColor Green

} else {
    Write-Host "❌ Upload failed! Exit code: $LASTEXITCODE" -ForegroundColor Red
    Write-Host "`nWinSCP output:" -ForegroundColor Yellow
    $result | ForEach-Object { Write-Host $_ -ForegroundColor Gray }

    if (Test-Path "$env:TEMP\winscp_log.txt") {
        Write-Host "`nWinSCP log:" -ForegroundColor Yellow
        Get-Content "$env:TEMP\winscp_log.txt" -Tail 20 | ForEach-Object { Write-Host $_ -ForegroundColor Gray }
    }
}

# Cleanup
Remove-Item $ScriptFile -ErrorAction SilentlyContinue

Write-Host "`n"