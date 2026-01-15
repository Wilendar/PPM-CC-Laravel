#!/usr/bin/env pwsh
$ErrorActionPreference = "Stop"
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== CHECKING SYNC STATUS FOR PRODUCT 11063 ===" -ForegroundColor Cyan
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "mysql -u host379076_ppm -pqkS4FuXMMDDN4DJhatg6 -h localhost host379076_ppm -e 'SELECT id, product_id, shop_id, sync_status, last_sync_at, last_success_sync_at, prestashop_product_id FROM product_shop_data WHERE product_id = 11063;'"
