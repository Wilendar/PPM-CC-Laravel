#!/usr/bin/env pwsh
$ErrorActionPreference = "Stop"
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== FINDING TEST PPM CATEGORY ===" -ForegroundColor Cyan
$query = "SELECT c.id_category, cl.name FROM ps_category c JOIN ps_category_lang cl ON c.id_category = cl.id_category AND cl.id_lang = 1 WHERE cl.name LIKE '%TEST PPM%';"
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "mysql -u host379076_b2b -pYvpQMNnCSj69Wu2qjJNc host379076_b2b -e `"$query`""
