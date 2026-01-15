$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
Write-Host "=== Checking category 2354 in PrestaShop DATABASE ===" -ForegroundColor Cyan

# PrestaShop database credentials from dane_hostingu.md
$cmd = @'
cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute="
// Connect to PrestaShop database directly
\$psDb = new PDO(
    'mysql:host=localhost;dbname=host379076_devmpp;charset=utf8',
    'host379076_devmpp',
    'CxtsfyV4nWyGct5LTZrb'
);

echo '=== Category 2354 in ps_category ===' . PHP_EOL;
\$stmt = \$psDb->query('SELECT * FROM ps_category WHERE id_category = 2354');
\$cat = \$stmt->fetch(PDO::FETCH_ASSOC);
print_r(\$cat);

echo PHP_EOL . '=== Category 2354 in ps_category_lang ===' . PHP_EOL;
\$stmt = \$psDb->query('SELECT * FROM ps_category_lang WHERE id_category = 2354');
\$langs = \$stmt->fetchAll(PDO::FETCH_ASSOC);
print_r(\$langs);

echo PHP_EOL . '=== Category 2354 in ps_category_shop ===' . PHP_EOL;
\$stmt = \$psDb->query('SELECT * FROM ps_category_shop WHERE id_category = 2354');
\$shops = \$stmt->fetchAll(PDO::FETCH_ASSOC);
print_r(\$shops);

echo PHP_EOL . '=== Highest category IDs ===' . PHP_EOL;
\$stmt = \$psDb->query('SELECT id_category FROM ps_category ORDER BY id_category DESC LIMIT 10');
\$ids = \$stmt->fetchAll(PDO::FETCH_COLUMN);
echo 'Top 10 IDs: ' . implode(', ', \$ids) . PHP_EOL;
"
'@
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch $cmd

Write-Host "`n=== Done ===" -ForegroundColor Green
