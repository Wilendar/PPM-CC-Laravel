<?php
// Check PrestaShop database access and recent product updates

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PrestaShopShop;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "=== PRESTASHOP DATABASE ACCESS CHECK ===\n\n";

// Get active shops
$shops = PrestaShopShop::where('is_active', true)->get();

if ($shops->isEmpty()) {
    echo "❌ No active PrestaShop shops found\n";
    exit(1);
}

foreach ($shops as $shop) {
    echo "Shop: {$shop->name} (ID: {$shop->id})\n";
    echo "API URL: {$shop->api_url}\n";
    echo "DB Host: " . ($shop->db_host ?? 'N/A') . "\n";
    echo "DB Name: " . ($shop->db_name ?? 'N/A') . "\n";
    echo "DB User: " . ($shop->db_user ?? 'N/A') . "\n";
    echo "\n";

    // Try to connect to PrestaShop database if credentials available
    if ($shop->db_host && $shop->db_name && $shop->db_user && $shop->db_password) {
        try {
            $dsn = "mysql:host={$shop->db_host};dbname={$shop->db_name};charset=utf8mb4";
            $pdo = new PDO($dsn, $shop->db_user, $shop->db_password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            echo "✅ Database connection successful\n\n";

            // Check ps_product table structure
            $stmt = $pdo->query("SHOW TABLES LIKE 'ps_product'");
            if ($stmt->rowCount() > 0) {
                echo "✅ ps_product table exists\n\n";

                // Get recent products with tax info
                $stmt = $pdo->query("
                    SELECT
                        p.id_product,
                        p.reference AS sku,
                        pl.name,
                        p.price AS price_net,
                        p.id_tax_rules_group,
                        p.date_upd,
                        t.rate AS tax_rate
                    FROM ps_product p
                    LEFT JOIN ps_product_lang pl ON p.id_product = pl.id_product AND pl.id_lang = 1
                    LEFT JOIN ps_tax_rules_group trg ON p.id_tax_rules_group = trg.id_tax_rules_group
                    LEFT JOIN ps_tax_rule tr ON trg.id_tax_rules_group = tr.id_tax_rules_group
                    LEFT JOIN ps_tax t ON tr.id_tax = t.id_tax
                    ORDER BY p.date_upd DESC
                    LIMIT 10
                ");

                echo "Recent products (last 10 updated):\n";
                echo str_repeat('-', 120) . "\n";
                printf("%-10s %-20s %-30s %-12s %-18s %-10s %-20s\n",
                    'ID', 'SKU', 'Name', 'Price Net', 'Tax Rules Group', 'Tax Rate', 'Last Updated');
                echo str_repeat('-', 120) . "\n";

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    printf("%-10s %-20s %-30s %-12s %-18s %-10s %-20s\n",
                        $row['id_product'],
                        substr($row['sku'], 0, 20),
                        substr($row['name'] ?? 'N/A', 0, 30),
                        number_format($row['price_net'], 2),
                        $row['id_tax_rules_group'] ?? 'NULL',
                        $row['tax_rate'] ?? 'N/A',
                        $row['date_upd']
                    );
                }
                echo "\n";

                // Check specific_prices table
                $stmt = $pdo->query("SHOW TABLES LIKE 'ps_specific_price'");
                if ($stmt->rowCount() > 0) {
                    echo "✅ ps_specific_price table exists\n";

                    // Count specific_prices
                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM ps_specific_price");
                    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                    echo "Total specific_prices: {$count}\n\n";

                    // Get recent specific_prices
                    $stmt = $pdo->query("
                        SELECT
                            sp.id_specific_price,
                            sp.id_product,
                            p.reference AS sku,
                            sp.id_group,
                            sp.price AS price_override,
                            sp.reduction,
                            sp.reduction_type,
                            sp.from AS date_from,
                            sp.to AS date_to
                        FROM ps_specific_price sp
                        LEFT JOIN ps_product p ON sp.id_product = p.id_product
                        ORDER BY sp.id_specific_price DESC
                        LIMIT 10
                    ");

                    echo "Recent specific_prices (last 10):\n";
                    echo str_repeat('-', 120) . "\n";
                    printf("%-10s %-10s %-20s %-10s %-15s %-10s %-15s %-20s\n",
                        'SP ID', 'Prod ID', 'SKU', 'Group', 'Price Override', 'Reduction', 'Type', 'Valid From-To');
                    echo str_repeat('-', 120) . "\n";

                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $dateRange = ($row['date_from'] === '0000-00-00 00:00:00' ? 'Always' : $row['date_from']) .
                                     ' - ' .
                                     ($row['date_to'] === '0000-00-00 00:00:00' ? 'Forever' : $row['date_to']);

                        printf("%-10s %-10s %-20s %-10s %-15s %-10s %-15s %-20s\n",
                            $row['id_specific_price'],
                            $row['id_product'],
                            substr($row['sku'] ?? 'N/A', 0, 20),
                            $row['id_group'] ?? '0',
                            $row['price_override'] == -1 ? 'Use base' : number_format($row['price_override'], 2),
                            $row['reduction'] ?? '0',
                            $row['reduction_type'] ?? 'N/A',
                            'Always - Forever'
                        );
                    }
                } else {
                    echo "❌ ps_specific_price table NOT found\n";
                }

            } else {
                echo "❌ ps_product table NOT found\n";
            }

        } catch (PDOException $e) {
            echo "❌ Database connection failed: " . $e->getMessage() . "\n";
        }
    } else {
        echo "⚠️ Database credentials not configured for this shop\n";
        echo "   Configure db_host, db_name, db_user, db_password in prestashop_shops table\n";
    }

    echo "\n" . str_repeat('=', 120) . "\n\n";
}

echo "=== CHECK COMPLETED ===\n";
