$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
Write-Host "=== FIXING category 2354 shop association ===" -ForegroundColor Cyan

$cmd = @'
cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute="
// Connect to PrestaShop database directly
\$psDb = new PDO(
    'mysql:host=localhost;dbname=host379076_devmpp;charset=utf8',
    'host379076_devmpp',
    'CxtsfyV4nWyGct5LTZrb'
);

// Check if entry already exists
\$check = \$psDb->query('SELECT COUNT(*) FROM ps_category_shop WHERE id_category = 2354 AND id_shop = 1')->fetchColumn();

if (\$check > 0) {
    echo 'Entry already exists!' . PHP_EOL;
} else {
    // Get position from ps_category
    \$pos = \$psDb->query('SELECT position FROM ps_category WHERE id_category = 2354')->fetchColumn();

    // Insert shop association
    \$stmt = \$psDb->prepare('INSERT INTO ps_category_shop (id_category, id_shop, position) VALUES (?, ?, ?)');
    \$result = \$stmt->execute([2354, 1, \$pos]);

    if (\$result) {
        echo 'SUCCESS: Added category 2354 to shop 1!' . PHP_EOL;
    } else {
        echo 'ERROR: ' . implode(' ', \$stmt->errorInfo()) . PHP_EOL;
    }
}

// Verify
echo PHP_EOL . '=== Verification ===' . PHP_EOL;
\$stmt = \$psDb->query('SELECT * FROM ps_category_shop WHERE id_category = 2354');
\$shops = \$stmt->fetchAll(PDO::FETCH_ASSOC);
print_r(\$shops);
"
'@
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch $cmd

Write-Host "`n=== Now testing API ===" -ForegroundColor Yellow

$cmd2 = @'
cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute="
\$shop = App\Models\PrestaShopShop::find(1);
\$client = app(App\Services\PrestaShop\PrestaShopClientFactory::class)->create(\$shop);

try {
    \$cat = \$client->getCategory(2354);
    echo 'API now returns category 2354: ' . (\$cat['name'] ?? 'N/A') . PHP_EOL;
} catch (Exception \$e) {
    echo 'Still error: ' . \$e->getMessage() . PHP_EOL;
}

// Clear PPM cache
\$service = app(App\Services\PrestaShop\PrestaShopCategoryService::class);
\$service->clearCache(\$shop);
echo 'PPM cache cleared!' . PHP_EOL;
"
'@
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch $cmd2

Write-Host "`n=== DONE ===" -ForegroundColor Green
