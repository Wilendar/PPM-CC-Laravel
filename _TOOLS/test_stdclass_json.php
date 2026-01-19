<?php
// Test stdClass JSON encoding

echo "=== Test stdClass JSON Encoding ===\n\n";

// Test 1: stdClass with numeric string keys
$obj = new stdClass();
$obj->{'0'} = 'url1';
$obj->{'1'} = 'url2';
$obj->{'2'} = 'url3';

echo "Test 1: stdClass with string keys\n";
echo "JSON: " . json_encode(['images' => $obj]) . "\n\n";

// Test 2: Regular array
$arr = ['url1', 'url2', 'url3'];
echo "Test 2: Regular array\n";
echo "JSON: " . json_encode(['images' => $arr]) . "\n\n";

// Test 3: Associative array with numeric string keys
$assoc = ['0' => 'url1', '1' => 'url2', '2' => 'url3'];
echo "Test 3: Associative array with string keys\n";
echo "JSON: " . json_encode(['images' => $assoc]) . "\n\n";

// Test 4: Real simulation of the code
$imagesObject = new \stdClass();
$urls = [
    'https://ppm.mpptrade.pl/public/storage/products/BG-KAYO-S200/buggy_kayo_s200_01.jpg',
    'https://ppm.mpptrade.pl/public/storage/products/BG-KAYO-S200/buggy_kayo_s200_02.jpg',
];

$imageIndex = 0;
foreach ($urls as $url) {
    $imagesObject->{(string)$imageIndex} = $url;
    $imageIndex++;
}

echo "Test 4: Simulated code output\n";
echo "Type: " . gettype($imagesObject) . "\n";
echo "get_object_vars: " . print_r(get_object_vars($imagesObject), true) . "\n";
echo "JSON: " . json_encode(['images' => $imagesObject]) . "\n\n";

// Test 5: What if we nest it in array
$params = [
    'inventory_id' => '22652',
    'images' => $imagesObject
];
echo "Test 5: Full params array\n";
echo "JSON: " . json_encode($params) . "\n\n";

// Test 6: Check what happens when we assign stdClass to array key
$data = ['key' => 'value'];
$data['images'] = $imagesObject;
echo "Test 6: Assignment to array\n";
echo "Type after assignment: " . gettype($data['images']) . "\n";
echo "JSON: " . json_encode($data) . "\n";
