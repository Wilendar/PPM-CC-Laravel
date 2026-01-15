<?php
/**
 * PrestaShop Description Analyzer via API
 * Pobiera opisy produktow przez PrestaShop Web Services API
 */

$shops = [
    'kayo' => [
        'url' => 'https://test.kayomoto.pl/api',
        'key' => '1ZEUFUI8JTYY5Z9XXQV2RRANZTKK4R77',
        'label' => 'KAYO (test.kayomoto.pl)',
        'ids' => [4000,2785,2528,2125,39,38,36,35,11,10,9,7,4016,3171,4015,4005,3612,3407,3001,2559,1332,1331,20,19,16,2256,1762,1761,1760,1759,1758,1330,15]
    ],
    'ycf' => [
        'url' => 'https://dev.ycf.pl/api',
        'key' => 'LHG498YJML94PK5A4DPFQJRFFHD4XS11',
        'label' => 'YCF (dev.ycf.pl)',
        'ids' => [2675,2674,2673,2672,2671,2670,2669,2668,2667,2666,2665,2664,2663,2662,2661,2660,2659,2658,2657,2656,2655,2654,2653,2652,2651,2650,2649]
    ],
    'pitgang' => [
        'url' => 'https://sklep.pitgang.pl/api',
        'key' => 'WGUIR1Z2RCB3VJFK9J3QCR2U4U5RP64Y',
        'label' => 'Pitgang (sklep.pitgang.pl)',
        'ids' => [191,83,47,46]
    ]
];

function extractCssClasses($html) {
    $classes = [];
    if (preg_match_all('/class=["\']([^"\']+)["\']/', $html, $matches)) {
        foreach ($matches[1] as $classString) {
            foreach (preg_split('/\s+/', trim($classString)) as $class) {
                if (!empty($class = trim($class))) {
                    $classes[$class] = ($classes[$class] ?? 0) + 1;
                }
            }
        }
    }
    return $classes;
}

function extractHtmlTags($html) {
    $tags = [];
    if (preg_match_all('/<(\w+)([^>]*)>/', $html, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $tagName = strtolower($match[1]);
            $tags[$tagName] = ($tags[$tagName] ?? 0) + 1;
        }
    }
    return $tags;
}

function extractHtmlStructures($html) {
    $structures = [];
    if (preg_match_all('/<(div|section|article)\s+class=["\']([^"\']+)["\'][^>]*>/', $html, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $key = "{$match[1]}.{$match[2]}";
            $structures[$key] = ($structures[$key] ?? 0) + 1;
        }
    }
    if (preg_match_all('/class=["\'][^"\']*\b(row|col-\w+|grid|flex|container)[^"\']*["\']/', $html, $matches)) {
        foreach ($matches[1] as $gridClass) {
            $structures["grid:{$gridClass}"] = ($structures["grid:{$gridClass}"] ?? 0) + 1;
        }
    }
    return $structures;
}

function identifyPatterns($html) {
    $patterns = [];
    if (preg_match('/class=["\'][^"\']*\b(hero|banner|jumbotron)[^"\']*["\']/', $html)) $patterns['hero_banner'] = true;
    if (preg_match('/class=["\'][^"\']*\b(feature|spec|specification)[^"\']*["\']/', $html)) $patterns['feature_list'] = true;
    if (preg_match('/class=["\'][^"\']*\b(gallery|carousel|slider)[^"\']*["\']/', $html)) $patterns['image_gallery'] = true;
    if (preg_match('/class=["\'][^"\']*\bcol-(6|md-6)[^"\']*["\']/', $html)) $patterns['two_column'] = true;
    if (preg_match('/class=["\'][^"\']*\bcol-(4|md-4)[^"\']*["\']/', $html)) $patterns['three_column'] = true;
    if (preg_match('/<table[^>]*>.*?(specyfikacja|dane techniczne)/is', $html)) $patterns['spec_table'] = true;
    if (preg_match('/<(iframe|video)[^>]*(youtube|vimeo)/i', $html)) $patterns['video_embed'] = true;
    if (preg_match('/class=["\'][^"\']*\b(fa-|icon-)[^"\']*["\']/', $html)) $patterns['icons'] = true;
    if (preg_match('/class=["\'][^"\']*\b(accordion|tab|collapse)[^"\']*["\']/', $html)) $patterns['accordion_tabs'] = true;
    if (preg_match('/class=["\'][^"\']*\b(btn|button|cta)[^"\']*["\']/', $html)) $patterns['cta_buttons'] = true;
    return $patterns;
}

function extractInlineStyles($html) {
    $styles = [];
    if (preg_match_all('/style=["\']([^"\']+)["\']/', $html, $matches)) {
        foreach ($matches[1] as $style) {
            foreach (explode(';', $style) as $prop) {
                if (empty($prop = trim($prop))) continue;
                $parts = explode(':', $prop, 2);
                if (count($parts) === 2) {
                    $styles[trim($parts[0])] = ($styles[trim($parts[0])] ?? 0) + 1;
                }
            }
        }
    }
    return $styles;
}

function fetchProductFromAPI($apiUrl, $apiKey, $productId) {
    $url = "{$apiUrl}/products/{$productId}?output_format=JSON";

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_USERPWD => "{$apiKey}:",
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return null;
    }

    $data = json_decode($response, true);
    return $data['product'] ?? null;
}

$results = ['summary' => [], 'css_classes' => [], 'html_tags' => [], 'html_structures' => [], 'patterns' => [], 'samples' => [], 'inline_styles' => []];

echo "=== PrestaShop Description Analyzer (via API) ===\n";
echo "Data: " . date('Y-m-d H:i:s') . "\n\n";

foreach ($shops as $shopKey => $config) {
    echo "[{$shopKey}] Pobieranie z API: {$config['label']}...\n";

    $shopResults = [
        'total_products' => count($config['ids']),
        'products_with_description' => 0,
        'css_classes' => [],
        'html_tags' => [],
        'structures' => [],
        'patterns' => [],
        'inline_styles' => [],
        'samples' => []
    ];

    $fetched = 0;
    foreach ($config['ids'] as $productId) {
        $product = fetchProductFromAPI($config['url'], $config['key'], $productId);

        if (!$product) {
            continue;
        }
        $fetched++;

        // Pobierz opis z pierwszego jezyka
        $description = '';
        if (isset($product['description'])) {
            if (is_array($product['description'])) {
                foreach ($product['description'] as $lang) {
                    if (is_array($lang) && isset($lang['value'])) {
                        $description = $lang['value'];
                        break;
                    } elseif (is_string($lang)) {
                        $description = $lang;
                        break;
                    }
                }
            } else {
                $description = $product['description'];
            }
        }

        if (empty(trim($description))) continue;

        $shopResults['products_with_description']++;

        foreach (extractCssClasses($description) as $class => $count) {
            $shopResults['css_classes'][$class] = ($shopResults['css_classes'][$class] ?? 0) + $count;
        }
        foreach (extractHtmlTags($description) as $tag => $count) {
            $shopResults['html_tags'][$tag] = ($shopResults['html_tags'][$tag] ?? 0) + $count;
        }
        foreach (extractHtmlStructures($description) as $struct => $count) {
            $shopResults['structures'][$struct] = ($shopResults['structures'][$struct] ?? 0) + $count;
        }
        foreach (identifyPatterns($description) as $pattern => $exists) {
            if ($exists) $shopResults['patterns'][$pattern] = ($shopResults['patterns'][$pattern] ?? 0) + 1;
        }
        foreach (extractInlineStyles($description) as $style => $count) {
            $shopResults['inline_styles'][$style] = ($shopResults['inline_styles'][$style] ?? 0) + $count;
        }

        if (count($shopResults['samples']) < 3 && strlen($description) > 500) {
            $name = '';
            if (isset($product['name'])) {
                if (is_array($product['name'])) {
                    foreach ($product['name'] as $lang) {
                        if (is_array($lang) && isset($lang['value'])) {
                            $name = $lang['value'];
                            break;
                        }
                    }
                } else {
                    $name = $product['name'];
                }
            }
            $shopResults['samples'][] = [
                'id' => $productId,
                'name' => $name,
                'html_length' => strlen($description),
                'html_preview' => substr($description, 0, 2000)
            ];
        }

        // Progress
        if ($fetched % 5 === 0) {
            echo "  Pobrano {$fetched}/" . count($config['ids']) . " produktow...\n";
        }
    }

    arsort($shopResults['css_classes']);
    arsort($shopResults['html_tags']);
    arsort($shopResults['structures']);

    $results['summary'][$shopKey] = [
        'label' => $config['label'],
        'total_products' => $shopResults['total_products'],
        'fetched_via_api' => $fetched,
        'products_with_description' => $shopResults['products_with_description'],
        'unique_css_classes' => count($shopResults['css_classes']),
        'unique_html_tags' => count($shopResults['html_tags']),
        'patterns_found' => count($shopResults['patterns'])
    ];

    $results['css_classes'][$shopKey] = $shopResults['css_classes'];
    $results['html_tags'][$shopKey] = $shopResults['html_tags'];
    $results['html_structures'][$shopKey] = $shopResults['structures'];
    $results['patterns'][$shopKey] = $shopResults['patterns'];
    $results['inline_styles'][$shopKey] = $shopResults['inline_styles'];
    $results['samples'][$shopKey] = $shopResults['samples'];

    echo "[{$shopKey}] Znaleziono: {$shopResults['products_with_description']} z opisem, " . count($shopResults['css_classes']) . " klas CSS\n\n";
}

// Zapisz wyniki
$outputFile = __DIR__ . '/analysis_api_results_' . date('Y-m-d_His') . '.json';
file_put_contents($outputFile, json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Wyniki zapisane do: {$outputFile}\n\n";

// Podsumowanie
echo "=== PODSUMOWANIE ===\n";
foreach ($results['summary'] as $shop => $data) {
    echo "\n[{$shop}] {$data['label']}\n";
    echo "  Produkty: {$data['total_products']}, z API: {$data['fetched_via_api']}, z opisem: {$data['products_with_description']}\n";
    echo "  Klasy CSS: {$data['unique_css_classes']}, Patterns: {$data['patterns_found']}\n";
}

echo "\nTOP CSS CLASSES:\n";
foreach ($results['css_classes'] as $shop => $classes) {
    echo "\n[{$shop}]:\n";
    $i = 0;
    foreach ($classes as $class => $count) {
        if ($i++ >= 15) break;
        echo "  {$class}: {$count}\n";
    }
}

echo "\nPATTERNS:\n";
foreach ($results['patterns'] as $shop => $patterns) {
    echo "[{$shop}]: " . implode(', ', array_keys($patterns)) . "\n";
}
