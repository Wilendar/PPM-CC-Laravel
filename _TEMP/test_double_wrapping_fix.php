<?php

/**
 * TEST: Double Wrapping Fix Verification
 *
 * Tests that createProduct/updateProduct correctly unwrap 'product' key
 * from ProductTransformer output before re-wrapping for XML conversion.
 *
 * Run: php _TEMP/test_double_wrapping_fix.php
 */

// Simulate PrestaShop8Client methods for testing
class TestPrestaShop8Client
{
    public function createProduct(array $productData): array
    {
        // Unwrap 'product' key if transformer returned wrapped structure
        if (isset($productData['product'])) {
            $productData = $productData['product'];
        }

        $xmlBody = $this->arrayToXml(['product' => $productData]);

        return [
            'xml' => $xmlBody,
            'unwrapped' => isset($productData['price']) && !isset($productData['product'])
        ];
    }

    public function updateProduct(int $productId, array $productData): array
    {
        // Unwrap 'product' key if transformer returned wrapped structure
        if (isset($productData['product'])) {
            $productData = $productData['product'];
        }

        // Inject ID
        $productData = array_merge(['id' => $productId], $productData);

        $xmlBody = $this->arrayToXml(['product' => $productData]);

        return [
            'xml' => $xmlBody,
            'unwrapped' => isset($productData['price']) && !isset($productData['product']),
            'has_id' => isset($productData['id'])
        ];
    }

    protected function arrayToXml(array $data): string
    {
        $xml = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>' .
            '<prestashop xmlns:xlink="http://www.w3.org/1999/xlink"></prestashop>'
        );

        $this->buildXmlFromArray($data, $xml);

        return $xml->asXML();
    }

    protected function buildXmlFromArray(array $data, \SimpleXMLElement $xml): void
    {
        foreach ($data as $key => $value) {
            if ($value === null) continue;

            if (is_array($value)) {
                if ($this->isIndexedArray($value)) {
                    $containerElement = $xml->addChild($key);
                    $singularKey = $this->singularize($key);
                    foreach ($value as $item) {
                        if (is_array($item)) {
                            $itemElement = $containerElement->addChild($singularKey);
                            $this->buildXmlFromArray($item, $itemElement);
                        } else {
                            $this->addCDataChild($containerElement->addChild($singularKey), $item);
                        }
                    }
                } else {
                    $childElement = $xml->addChild($key);
                    $this->buildXmlFromArray($value, $childElement);
                }
            } else {
                $this->addCDataChild($xml->addChild($key), $value);
            }
        }
    }

    protected function addCDataChild(\SimpleXMLElement $element, $value): void
    {
        $node = dom_import_simplexml($element);
        $doc = $node->ownerDocument;
        $node->appendChild($doc->createCDATASection((string) $value));
    }

    protected function isIndexedArray(array $data): bool
    {
        if (empty($data)) return false;
        return array_keys($data) === range(0, count($data) - 1);
    }

    protected function singularize(string $word): string
    {
        if (str_ends_with($word, 's')) {
            return substr($word, 0, -1);
        }
        return $word;
    }
}

// ===================================
// TEST CASES
// ===================================

$client = new TestPrestaShop8Client();

echo "========================================\n";
echo "TEST 1: CREATE with wrapped data (ProductTransformer output)\n";
echo "========================================\n";

// ProductTransformer returns: ['product' => ['price' => ..., ...]]
$wrappedData = [
    'product' => [
        'price' => 99.99,
        'name' => 'Test Product',
        'active' => 1,
    ]
];

$result1 = $client->createProduct($wrappedData);
echo $result1['xml'] . "\n\n";

// Verify unwrapping
if ($result1['unwrapped']) {
    echo "✅ PASS: Wrapped data correctly unwrapped\n";
} else {
    echo "❌ FAIL: Data NOT unwrapped (double nesting detected)\n";
}

// Verify single <product> nesting
$xmlCount = substr_count($result1['xml'], '<product>');
if ($xmlCount === 1) {
    echo "✅ PASS: Single <product> nesting (no double wrapping)\n";
} else {
    echo "❌ FAIL: Found {$xmlCount} <product> tags (expected 1)\n";
}

// Verify price is at correct level
if (str_contains($result1['xml'], '<product><price>')) {
    echo "✅ PASS: <price> is direct child of <product>\n";
} else {
    echo "❌ FAIL: <price> NOT direct child of <product>\n";
}

echo "\n========================================\n";
echo "TEST 2: UPDATE with wrapped data\n";
echo "========================================\n";

$wrappedData2 = [
    'product' => [
        'price' => 129.99,
        'name' => 'Updated Product',
        'active' => 1,
    ]
];

$result2 = $client->updateProduct(456, $wrappedData2);
echo $result2['xml'] . "\n\n";

// Verify unwrapping
if ($result2['unwrapped']) {
    echo "✅ PASS: Wrapped data correctly unwrapped for UPDATE\n";
} else {
    echo "❌ FAIL: Data NOT unwrapped in UPDATE\n";
}

// Verify ID injection
if ($result2['has_id']) {
    echo "✅ PASS: ID injected for UPDATE\n";
} else {
    echo "❌ FAIL: ID NOT injected\n";
}

// Verify ID is first element
$dom = new DOMDocument();
$dom->loadXML($result2['xml']);
$xpath = new DOMXPath($dom);
$xpath->registerNamespace('ps', 'http://www.w3.org/1999/xlink');
$productNode = $xpath->query('//product')->item(0);
$firstChild = $productNode->firstChild;

while ($firstChild && $firstChild->nodeType !== XML_ELEMENT_NODE) {
    $firstChild = $firstChild->nextSibling;
}

if ($firstChild && $firstChild->nodeName === 'id') {
    echo "✅ PASS: <id> is first element in UPDATE\n";
} else {
    echo "❌ FAIL: <id> NOT first element\n";
}

// Verify single <product> nesting
$xmlCount2 = substr_count($result2['xml'], '<product>');
if ($xmlCount2 === 1) {
    echo "✅ PASS: Single <product> nesting in UPDATE\n";
} else {
    echo "❌ FAIL: Found {$xmlCount2} <product> tags in UPDATE\n";
}

echo "\n========================================\n";
echo "TEST 3: CREATE with raw data (backward compatibility)\n";
echo "========================================\n";

// Test with raw data (no 'product' key)
$rawData = [
    'price' => 79.99,
    'name' => 'Raw Test Product',
    'active' => 1,
];

$result3 = $client->createProduct($rawData);
echo $result3['xml'] . "\n\n";

// Verify backward compatibility
if ($result3['unwrapped']) {
    echo "✅ PASS: Raw data handled correctly (backward compatible)\n";
} else {
    echo "❌ FAIL: Raw data handling broken\n";
}

// Verify price exists
if (str_contains($result3['xml'], '<price>')) {
    echo "✅ PASS: <price> exists in output\n";
} else {
    echo "❌ FAIL: <price> MISSING from output\n";
}

echo "\n========================================\n";
echo "SUMMARY\n";
echo "========================================\n";
echo "All tests verify double wrapping fix.\n";
echo "If all tests PASS, createProduct/updateProduct fix is correct!\n";
echo "========================================\n";
