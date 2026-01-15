#!/usr/bin/env pwsh
$ErrorActionPreference = "Stop"
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== CATEGORY NAMES (children of id=2 Wszystko) ===" -ForegroundColor Cyan
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "mysql -u host379076_devmpp -pCxtsfyV4nWyGct5LTZrb -h localhost host379076_devmpp -e 'SELECT c.id_category, cl.name FROM ps_category c JOIN ps_category_lang cl ON c.id_category = cl.id_category AND cl.id_lang = 1 WHERE c.id_parent = 2 ORDER BY cl.name;'"
