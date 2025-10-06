# Quick Upload - category-form.blade.php fix
# UTF-8 encoding fix dla PPM-CC-Laravel

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$LocalFile = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\resources\views\livewire\products\categories\category-form.blade.php"
$RemotePath = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/resources/views/livewire/products/categories/category-form.blade.php"

Write-Host "`n[36m🚀 Quick Upload - Category Form Fix[0m" -NoNewline
Write-Host "`n[36m====================================[0m`n"

# WinSCP Upload
$WinSCPPath = "C:\Program Files (x86)\WinSCP\WinSCP.com"

if (Test-Path $WinSCPPath) {
    Write-Host "[36m📤 Uploading via WinSCP...[0m"

    $WinSCPScript = @"
option batch on
option confirm off
open sftp://host379076@host379076.hostido.net.pl:64321/ -privatekey="$HostidoKey" -hostkey="ssh-ed25519 255 s5jsBvAUexZAUyZgYF3ONT2RvrcsHjhso6DCiTBICiM"
put "$LocalFile" "/domains/ppm.mpptrade.pl/public_html/resources/views/livewire/products/categories/"
exit
"@

    $ScriptFile = "$env:TEMP\winscp_upload.txt"
    $WinSCPScript | Out-File -FilePath $ScriptFile -Encoding ASCII

    & $WinSCPPath /script=$ScriptFile

    if ($LASTEXITCODE -eq 0) {
        Write-Host "[32m✅ Upload successful![0m"

        # Clear cache
        Write-Host "`n[36m🧹 Clearing Laravel cache...[0m"
        plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear"

        Write-Host "[32m✅ Cache cleared![0m"
        Write-Host "`n[36m🌐 Test at: https://ppm.mpptrade.pl/admin/products/categories/create[0m`n"
    } else {
        Write-Host "[31m❌ Upload failed! Exit code: $LASTEXITCODE[0m"
    }

    Remove-Item $ScriptFile -ErrorAction SilentlyContinue
} else {
    Write-Host "[31m❌ WinSCP not found at: $WinSCPPath[0m"
    Write-Host "[33m💡 Install WinSCP or check path[0m"
}