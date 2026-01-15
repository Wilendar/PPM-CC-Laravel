#!/usr/bin/env pwsh
$ErrorActionPreference = "Stop"
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== CHECKING B2B Test DEV CATEGORY CHILDREN (id_parent=2) ===" -ForegroundColor Cyan
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "mysql -u host379076_devmpp -pCxtsfyV4nWyGct5LTZrb -h localhost host379076_devmpp -e 'SELECT id_category, id_parent, level_depth FROM ps_category WHERE id_parent = 2 ORDER BY id_category;'"
