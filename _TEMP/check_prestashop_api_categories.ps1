$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== CHECKING PRESTASHOP API FOR PRODUCT PB-KAYO-E-KMB ===" -ForegroundColor Cyan

$result = plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute=`"
// Get shop and client
\`$shop = App\Models\PrestaShopShop::find(5); // test_kayoshop

if (!\`$shop) {
    echo 'Shop not found!' . PHP_EOL;
    exit;
}

echo 'Shop: ' . \`$shop->name . ' (ID: ' . \`$shop->id . ')' . PHP_EOL;
echo 'URL: ' . \`$shop->url . PHP_EOL . PHP_EOL;

// Find product in PrestaShop by SKU
\`$client = \`$shop->version === '8.x'
    ? new App\Services\PrestaShop\PrestaShop8Client(\`$shop)
    : new App\Services\PrestaShop\PrestaShop9Client(\`$shop);

try {
    // Search by reference (SKU)
    \`$xml = \`$client->get('products', ['filter[reference]' => 'PB-KAYO-E-KMB', 'display' => 'full']);

    if (isset(\`$xml->products->product)) {
        \`$product = \`$xml->products->product;

        echo '=== PRESTASHOP PRODUCT DATA ===' . PHP_EOL;
        echo 'ID: ' . \`$product->id . PHP_EOL;
        echo 'Reference: ' . \`$product->reference . PHP_EOL;
        echo 'id_category_default: ' . \`$product->id_category_default . PHP_EOL . PHP_EOL;

        echo '=== CATEGORY ASSOCIATIONS ===' . PHP_EOL;
        if (isset(\`$product->associations->categories->category)) {
            \`$categories = \`$product->associations->categories->category;

            // Handle single vs multiple categories
            if (!is_array(\`$categories)) {
                \`$categories = [\`$categories];
            }

            foreach (\`$categories as \`$cat) {
                \`$catId = (string)\`$cat->id;
                echo '  Category ID: ' . \`$catId;

                // Check if this is default
                if (\`$catId == \`$product->id_category_default) {
                    echo ' [DEFAULT]';
                }

                // Get category name
                try {
                    \`$catXml = \`$client->get('categories/' . \`$catId);
                    if (isset(\`$catXml->category->name->language)) {
                        \`$name = (string)\`$catXml->category->name->language;
                        \`$parentId = (string)\`$catXml->category->id_parent;
                        echo ' - ' . \`$name . ' (parent: ' . \`$parentId . ')';
                    }
                } catch (Exception \`$e) {
                    echo ' - (name fetch failed)';
                }

                echo PHP_EOL;
            }
        } else {
            echo '  No categories assigned!' . PHP_EOL;
        }
    } else {
        echo 'Product not found in PrestaShop!' . PHP_EOL;
    }
} catch (Exception \`$e) {
    echo 'ERROR: ' . \`$e->getMessage() . PHP_EOL;
}
`""

Write-Host "`n$result" -ForegroundColor White
