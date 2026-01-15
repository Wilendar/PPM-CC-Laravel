$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
Write-Host "Checking media.auto_sync_on_product_sync setting..." -ForegroundColor Cyan
$cmd = "cd domains/ppm.mpptrade.pl/public_html && php -r `"require 'vendor/autoload.php'; `\$app = require 'bootstrap/app.php'; `\$kernel = `\$app->make(Illuminate\\Contracts\\Console\\Kernel::class); `\$kernel->bootstrap(); echo App\\Models\\SystemSetting::get('media.auto_sync_on_product_sync', false) ? 'ENABLED' : 'DISABLED';`""
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch $cmd
