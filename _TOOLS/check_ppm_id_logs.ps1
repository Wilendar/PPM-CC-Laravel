#!/usr/bin/env pwsh
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
Write-Host "=== CHECKING PPM ID FIX LOGS ===" -ForegroundColor Cyan
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "tail -150 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log | grep -E 'CATEGORY CREATE|ppm_category_id|Replaced tempId'"
