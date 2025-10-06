# Fix PrestaShop Shops Version Field
# Executes SQL update to set default version = '8' for all shops

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$HostidoUser = "host379076@host379076.hostido.net.pl"
$HostidoPort = 64321
$RemotePath = "domains/ppm.mpptrade.pl/public_html"

Write-Host "`n=== FIXING PRESTASHOP VERSION FIELD ===" -ForegroundColor Cyan

# Step 1: Check current state
Write-Host "`nStep 1: Checking current version field values..." -ForegroundColor Yellow
plink -ssh $HostidoUser -P $HostidoPort -i $HostidoKey -batch "cd $RemotePath && php artisan tinker --execute=""echo '--- BEFORE UPDATE ---'; DB::table('prestashop_shops')->select('id', 'shop_name', 'version')->get()->each(function(\$s) { echo sprintf('ID: %d | %s | Version: [%s]', \$s->id, \$s->shop_name, \$s->version ?? 'NULL') . PHP_EOL; });"""

# Step 2: Update empty versions to '8'
Write-Host "`nStep 2: Updating empty version fields to '8'..." -ForegroundColor Yellow
plink -ssh $HostidoUser -P $HostidoPort -i $HostidoKey -batch "cd $RemotePath && php artisan tinker --execute=""DB::table('prestashop_shops')->where(function(\$q) { \$q->whereNull('version')->orWhere('version', '')->orWhereRaw('TRIM(version) = '''''); })->update(['version' => '8']); echo 'Version field updated for all shops with empty version';"""

# Step 3: Verify update
Write-Host "`nStep 3: Verifying update..." -ForegroundColor Yellow
plink -ssh $HostidoUser -P $HostidoPort -i $HostidoKey -batch "cd $RemotePath && php artisan tinker --execute=""echo '--- AFTER UPDATE ---'; DB::table('prestashop_shops')->select('id', 'shop_name', 'version')->get()->each(function(\$s) { echo sprintf('ID: %d | %s | Version: [%s]', \$s->id, \$s->shop_name, \$s->version ?? 'NULL') . PHP_EOL; });"""

Write-Host "`n=== UPDATE COMPLETE ===" -ForegroundColor Green
Write-Host "All PrestaShop shops should now have version = '8'" -ForegroundColor Green
