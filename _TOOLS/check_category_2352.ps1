#!/usr/bin/env pwsh
$ErrorActionPreference = "Stop"
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

# CORRECTED: Use host379076_devmpp database (B2B Test DEV PrestaShop)
$DbUser = "host379076_devmpp"
$DbPass = "CxtsfyV4nWyGct5LTZrb"
$DbName = "host379076_devmpp"

Write-Host "=== CHECKING CATEGORY 2352 ===" -ForegroundColor Cyan
$query1 = "SELECT * FROM ps_category WHERE id_category = 2352;"
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "mysql -u $DbUser -p$DbPass $DbName -e `"$query1`""

Write-Host ""
Write-Host "=== CHECKING ALL CATEGORY_PRODUCT FOR PRODUCT 7510 ===" -ForegroundColor Cyan
$query2 = "SELECT * FROM ps_category_product WHERE id_product = 7510;"
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "mysql -u $DbUser -p$DbPass $DbName -e `"$query2`""
