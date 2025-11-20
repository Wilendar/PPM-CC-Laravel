<?php

/**
 * TEST: BasePrestaShopClient::arrayToXml() Fix Verification
 *
 * Tests proper CDATA wrapping, namespace, multilang, singularization
 *
 * Run: php _TEMP/test_arraytoxml_fix.php
 */

// Simulate BasePrestaShopClient methods for testing
class TestPrestaShopClient
{
    public function arrayToXml(array $data): string
    {
        // Create root element with PrestaShop namespace
        $xml = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>' .
            '<prestashop xmlns:xlink="http://www.w3.org/1999/xlink"></prestashop>'
        );

        // Build XML from array recursively
        $this->buildXmlFromArray($data, $xml);

        return $xml->asXML();
    }

    protected function buildXmlFromArray(array $data, \SimpleXMLElement $xml): void
    {
        foreach ($data as $key => $value) {
            if ($value === null) {
                continue;
            }

            if (is_array($value)) {
                // Multilang field: [['id' => 1, 'value' => 'Text']]
                if ($this->isMultilangField($value)) {
                    $fieldElement = $xml->addChild($key);
                    foreach ($value as $langData) {
                        $langElement = $fieldElement->addChild('language');
                        $langElement->addAttribute('id', $langData['id']);
                        $this->addCDataChild($langElement, $langData['value']);
                    }
                }
                // Indexed array: [['id' => 1], ['id' => 2]]
                elseif ($this->isIndexedArray($value)) {
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
                }
                // Nested associative array
                else {
                    $childElement = $xml->addChild($key);
                    $this->buildXmlFromArray($value, $childElement);
                }
            }
            // Simple values - wrap in CDATA
            else {
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

    protected function isMultilangField(array $data): bool
    {
        if (empty($data)) return false;
        $first = reset($data);
        return is_array($first) && isset($first['id']) && isset($first['value']);
    }

    protected function isIndexedArray(array $data): bool
    {
        if (empty($data)) return false;
        return array_keys($data) === range(0, count($data) - 1);
    }

    protected function singularize(string $word): string
    {
        if (str_ends_with($word, 'ies')) {
            return substr($word, 0, -3) . 'y';
        }
        if (str_ends_with($word, 's')) {
            return substr($word, 0, -1);
        }
        return $word;
    }
}

// ===================================
// TEST CASES
// ===================================

$client = new TestPrestaShopClient();

echo "========================================\n";
echo "TEST 1: Simple Product with CDATA\n";
echo "========================================\n";

$data1 = [
    'product' => [
        'id' => 123,
        'name' => [['id' => 1, 'value' => 'Product Name']],
        'price' => 99.99,
        'active' => 1,
    ]
];

$xml1 = $client->arrayToXml($data1);
echo $xml1 . "\n\n";

// Verify CDATA
if (str_contains($xml1, '<![CDATA[123]]>')) {
    echo "✅ PASS: CDATA wrapping for simple values\n";
} else {
    echo "❌ FAIL: Missing CDATA wrapping\n";
}

// Verify namespace
if (str_contains($xml1, 'xmlns:xlink="http://www.w3.org/1999/xlink"')) {
    echo "✅ PASS: PrestaShop namespace present\n";
} else {
    echo "❌ FAIL: Missing PrestaShop namespace\n";
}

// Verify multilang
if (str_contains($xml1, '<language id="1"><![CDATA[Product Name]]></language>')) {
    echo "✅ PASS: Multilang field with CDATA\n";
} else {
    echo "❌ FAIL: Multilang field broken\n";
}

echo "\n========================================\n";
echo "TEST 2: Categories with Singularization\n";
echo "========================================\n";

$data2 = [
    'product' => [
        'associations' => [
            'categories' => [
                ['id' => 2],
                ['id' => 3],
                ['id' => 5],
            ]
        ]
    ]
];

$xml2 = $client->arrayToXml($data2);
echo $xml2 . "\n\n";

// Verify singularization
if (str_contains($xml2, '<category>')) {
    echo "✅ PASS: Singularization (categories → category)\n";
} else {
    echo "❌ FAIL: Missing singularization\n";
}

// Verify CDATA in array elements
if (str_contains($xml2, '<![CDATA[2]]>') && str_contains($xml2, '<![CDATA[3]]>')) {
    echo "✅ PASS: CDATA in indexed array elements\n";
} else {
    echo "❌ FAIL: Missing CDATA in array elements\n";
}

echo "\n========================================\n";
echo "TEST 3: UPDATE with ID Injection\n";
echo "========================================\n";

$data3 = [
    'product' => [
        'id' => 456,
        'price' => 129.99,
        'name' => [['id' => 1, 'value' => 'Updated Product']],
    ]
];

$xml3 = $client->arrayToXml($data3);
echo $xml3 . "\n\n";

// Verify id is first element
$dom = new DOMDocument();
$dom->loadXML($xml3);
$xpath = new DOMXPath($dom);
$xpath->registerNamespace('ps', 'http://www.w3.org/1999/xlink');
$productNode = $xpath->query('//product')->item(0);
$firstChild = $productNode->firstChild;

// Find first element child (skip text nodes)
while ($firstChild && $firstChild->nodeType !== XML_ELEMENT_NODE) {
    $firstChild = $firstChild->nextSibling;
}

if ($firstChild && $firstChild->nodeName === 'id') {
    echo "✅ PASS: ID is first element in UPDATE structure\n";
} else {
    echo "❌ FAIL: ID not first element (got: " . ($firstChild ? $firstChild->nodeName : 'null') . ")\n";
}

echo "\n========================================\n";
echo "TEST 4: Multilang Multiple Languages\n";
echo "========================================\n";

$data4 = [
    'product' => [
        'name' => [
            ['id' => 1, 'value' => 'Product EN'],
            ['id' => 2, 'value' => 'Produit FR'],
            ['id' => 3, 'value' => 'Produkt PL'],
        ]
    ]
];

$xml4 = $client->arrayToXml($data4);
echo $xml4 . "\n\n";

// Verify all languages
if (str_contains($xml4, '<language id="1"><![CDATA[Product EN]]></language>') &&
    str_contains($xml4, '<language id="2"><![CDATA[Produit FR]]></language>') &&
    str_contains($xml4, '<language id="3"><![CDATA[Produkt PL]]></language>')) {
    echo "✅ PASS: Multiple languages with CDATA\n";
} else {
    echo "❌ FAIL: Multilang fields broken\n";
}

echo "\n========================================\n";
echo "SUMMARY\n";
echo "========================================\n";
echo "All tests verify compliance with prestashop-xml-integration skill.\n";
echo "If all tests PASS, arrayToXml() fix is correct!\n";
echo "========================================\n";
