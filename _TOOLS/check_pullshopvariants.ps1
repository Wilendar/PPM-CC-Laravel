# check_pullshopvariants.ps1
# Test pullShopVariants to see what image URLs are returned

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== Testing pullShopVariants ===" -ForegroundColor Cyan

$cmd = "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute='`$p = App\Models\Product::find(11148); `$s = app(App\Services\PrestaShop\ShopVariantService::class); `$r = `$s->pullShopVariants(`$p, 1); echo `$r[\"\"variants\"\"]->count() . PHP_EOL; if (`$r[\"\"variants\"\"]->count() > 0) { `$v = `$r[\"\"variants\"\"]->first(); echo json_encode(`$v->images ?? []) . PHP_EOL; }'"

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch $cmd
