<?php

/**
 * Test Validation Logic (Unit Test - No Database)
 *
 * Tests ValidationService logic without database dependency
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Services\PrestaShop\ValidationService;

echo "\n=== VALIDATION LOGIC TEST (NO DATABASE) ===\n";

$validator = new ValidationService();

// Create mock ProductShopData
$mockShopData = new class {
    public $product_id = 123;
    public $shop_id = 1;
    public $name = 'Test Product';
    public $short_description = 'Short description';
    public $description = 'Long description';

    public function product() {
        return new class {
            public function prices() {
                return new class {
                    public function where($field, $value) {
                        return $this;
                    }
                    public function first() {
                        return new class {
                            public $price = 100.00;
                        };
                    }
                };
            }
            public function stocks() {
                return new class {
                    public function sum($field) {
                        return 50; // 50 units
                    }
                };
            }
        };
    }
};

// TEST 1: Name difference (warning)
echo "\n--- TEST 1: Name Difference (warning) ---\n";
$psDataNameDiff = [
    'name' => 'Different Product Name', // Changed
    'description_short' => 'Short description',
    'description' => 'Long description',
    'price' => 100.00,
    'quantity' => 50,
    'associations' => [
        'categories' => [
            ['id' => 1],
            ['id' => 2],
        ],
    ],
    'active' => true,
];

$warnings1 = $validator->validateProductData($mockShopData, $psDataNameDiff);
echo "Warnings: " . count($warnings1) . "\n";
foreach ($warnings1 as $warning) {
    echo "  - [{$warning['severity']}] {$warning['field']}: {$warning['message']}\n";
    echo "    PPM: {$warning['ppm_value']}\n";
    echo "    PrestaShop: {$warning['prestashop_value']}\n";
}

// Assert
if (count($warnings1) === 1 && $warnings1[0]['severity'] === 'warning' && $warnings1[0]['field'] === 'name') {
    echo "✓ TEST 1 PASSED\n";
} else {
    echo "✗ TEST 1 FAILED\n";
}

// TEST 2: Price diff > 10% (error)
echo "\n--- TEST 2: Price Difference > 10% (error) ---\n";
$psDataPriceDiff = [
    'name' => 'Test Product',
    'description_short' => 'Short description',
    'description' => 'Long description',
    'price' => 120.00, // 20% higher
    'quantity' => 50,
    'associations' => [
        'categories' => [
            ['id' => 1],
        ],
    ],
    'active' => true,
];

$warnings2 = $validator->validateProductData($mockShopData, $psDataPriceDiff);
echo "Warnings: " . count($warnings2) . "\n";
foreach ($warnings2 as $warning) {
    echo "  - [{$warning['severity']}] {$warning['field']}: {$warning['message']}\n";
    echo "    PPM: {$warning['ppm_value']}\n";
    echo "    PrestaShop: {$warning['prestashop_value']}\n";
}

// Assert
$hasPriceError = false;
foreach ($warnings2 as $warning) {
    if ($warning['severity'] === 'error' && $warning['field'] === 'price') {
        $hasPriceError = true;
        break;
    }
}
if ($hasPriceError) {
    echo "✓ TEST 2 PASSED\n";
} else {
    echo "✗ TEST 2 FAILED\n";
}

// TEST 3: Price diff 5-10% (warning)
echo "\n--- TEST 3: Price Difference 5-10% (warning) ---\n";
$psDataPriceWarning = [
    'name' => 'Test Product',
    'description_short' => 'Short description',
    'description' => 'Long description',
    'price' => 107.00, // 7% higher
    'quantity' => 50,
    'associations' => [
        'categories' => [
            ['id' => 1],
        ],
    ],
    'active' => true,
];

$warnings3 = $validator->validateProductData($mockShopData, $psDataPriceWarning);
echo "Warnings: " . count($warnings3) . "\n";
foreach ($warnings3 as $warning) {
    echo "  - [{$warning['severity']}] {$warning['field']}: {$warning['message']}\n";
    echo "    PPM: {$warning['ppm_value']}\n";
    echo "    PrestaShop: {$warning['prestashop_value']}\n";
}

// Assert
$hasPriceWarning = false;
foreach ($warnings3 as $warning) {
    if ($warning['severity'] === 'warning' && $warning['field'] === 'price') {
        $hasPriceWarning = true;
        break;
    }
}
if ($hasPriceWarning) {
    echo "✓ TEST 3 PASSED\n";
} else {
    echo "✗ TEST 3 FAILED\n";
}

// TEST 4: Stock difference (info)
echo "\n--- TEST 4: Stock Difference (info) ---\n";
$psDataStockDiff = [
    'name' => 'Test Product',
    'description_short' => 'Short description',
    'description' => 'Long description',
    'price' => 100.00,
    'quantity' => 70, // 20 units difference
    'associations' => [
        'categories' => [
            ['id' => 1],
        ],
    ],
    'active' => true,
];

$warnings4 = $validator->validateProductData($mockShopData, $psDataStockDiff);
echo "Warnings: " . count($warnings4) . "\n";
foreach ($warnings4 as $warning) {
    echo "  - [{$warning['severity']}] {$warning['field']}: {$warning['message']}\n";
    echo "    PPM: {$warning['ppm_value']}\n";
    echo "    PrestaShop: {$warning['prestashop_value']}\n";
}

// Assert
$hasStockInfo = false;
foreach ($warnings4 as $warning) {
    if ($warning['severity'] === 'info' && $warning['field'] === 'stock') {
        $hasStockInfo = true;
        break;
    }
}
if ($hasStockInfo) {
    echo "✓ TEST 4 PASSED\n";
} else {
    echo "✗ TEST 4 FAILED\n";
}

// TEST 5: No categories (warning)
echo "\n--- TEST 5: No Categories (warning) ---\n";
$psDataNoCategories = [
    'name' => 'Test Product',
    'description_short' => 'Short description',
    'description' => 'Long description',
    'price' => 100.00,
    'quantity' => 50,
    'associations' => [
        'categories' => [], // No categories
    ],
    'active' => true,
];

$warnings5 = $validator->validateProductData($mockShopData, $psDataNoCategories);
echo "Warnings: " . count($warnings5) . "\n";
foreach ($warnings5 as $warning) {
    echo "  - [{$warning['severity']}] {$warning['field']}: {$warning['message']}\n";
    echo "    PPM: {$warning['ppm_value']}\n";
    echo "    PrestaShop: {$warning['prestashop_value']}\n";
}

// Assert
$hasCategoryWarning = false;
foreach ($warnings5 as $warning) {
    if ($warning['severity'] === 'warning' && $warning['field'] === 'categories') {
        $hasCategoryWarning = true;
        break;
    }
}
if ($hasCategoryWarning) {
    echo "✓ TEST 5 PASSED\n";
} else {
    echo "✗ TEST 5 FAILED\n";
}

// TEST 6: Inactive status (info)
echo "\n--- TEST 6: Inactive Status (info) ---\n";
$psDataInactive = [
    'name' => 'Test Product',
    'description_short' => 'Short description',
    'description' => 'Long description',
    'price' => 100.00,
    'quantity' => 50,
    'associations' => [
        'categories' => [
            ['id' => 1],
        ],
    ],
    'active' => false, // Inactive
];

$warnings6 = $validator->validateProductData($mockShopData, $psDataInactive);
echo "Warnings: " . count($warnings6) . "\n";
foreach ($warnings6 as $warning) {
    echo "  - [{$warning['severity']}] {$warning['field']}: {$warning['message']}\n";
    echo "    PPM: {$warning['ppm_value']}\n";
    echo "    PrestaShop: {$warning['prestashop_value']}\n";
}

// Assert
$hasActiveInfo = false;
foreach ($warnings6 as $warning) {
    if ($warning['severity'] === 'info' && $warning['field'] === 'active') {
        $hasActiveInfo = true;
        break;
    }
}
if ($hasActiveInfo) {
    echo "✓ TEST 6 PASSED\n";
} else {
    echo "✗ TEST 6 FAILED\n";
}

// TEST 7: Multiple warnings
echo "\n--- TEST 7: Multiple Warnings (name + price + categories) ---\n";
$psDataMultiple = [
    'name' => 'Different Name', // Changed
    'description_short' => 'Short description',
    'description' => 'Long description',
    'price' => 125.00, // 25% higher
    'quantity' => 50,
    'associations' => [
        'categories' => [], // No categories
    ],
    'active' => true,
];

$warnings7 = $validator->validateProductData($mockShopData, $psDataMultiple);
echo "Warnings: " . count($warnings7) . "\n";
foreach ($warnings7 as $warning) {
    echo "  - [{$warning['severity']}] {$warning['field']}: {$warning['message']}\n";
}

// Assert
if (count($warnings7) >= 3) {
    echo "✓ TEST 7 PASSED\n";
} else {
    echo "✗ TEST 7 FAILED (expected >= 3 warnings, got " . count($warnings7) . ")\n";
}

echo "\n=== ALL TESTS COMPLETED ===\n";
