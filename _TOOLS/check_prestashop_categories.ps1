#!/usr/bin/env pwsh
$ErrorActionPreference = "Stop"
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== CHECKING CATEGORIES FOR PRODUCT 7510 IN PRESTASHOP ===" -ForegroundColor Cyan
$query = "SELECT cp.id_product, cp.id_category, cl.name FROM ps_category_product cp JOIN ps_category_lang cl ON cp.id_category = cl.id_category AND cl.id_lang = 1 WHERE cp.id_product = 7510 ORDER BY cl.name;"
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "mysql -u host379076_b2b -pYvpQMNnCSj69Wu2qjJNc host379076_b2b -e `"$query`""
