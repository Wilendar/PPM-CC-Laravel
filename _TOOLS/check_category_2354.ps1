$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
Write-Host "=== Checking category 2354 ===" -ForegroundColor Cyan

Write-Host "`n[1] Checking PrestaShop API directly for category 2354..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute=\"\\\$shop = App\\Models\\PrestaShopShop::find(1); \\\$client = app(App\\Services\\PrestaShop\\PrestaShopClientFactory::class)->create(\\\$shop); try { \\\$cat = \\\$client->getCategory(2354); echo 'Category 2354 EXISTS: ' . json_encode(\\\$cat); } catch (Exception \\\$e) { echo 'Error: ' . \\\$e->getMessage(); }\""

Write-Host "`n[2] Checking cache status..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute=\"\\\$key = 'prestashop_categories_shop_1'; \\\$cached = Cache::get(\\\$key); echo 'Cache key: ' . \\\$key . PHP_EOL; echo 'Cache exists: ' . (\\\$cached ? 'YES (' . count(\\\$cached) . ' items)' : 'NO');\""

Write-Host "`n[3] Clearing category cache and checking fresh data..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute=\"\\\$shop = App\\Models\\PrestaShopShop::find(1); \\\$service = app(App\\Services\\PrestaShop\\PrestaShopCategoryService::class); \\\$service->clearCache(\\\$shop); echo 'Cache cleared!'; \\\$tree = \\\$service->getCachedCategoryTree(\\\$shop); echo PHP_EOL . 'Fresh tree has ' . count(\\\$tree) . ' root categories';\""

Write-Host "`n=== Done ===" -ForegroundColor Green
