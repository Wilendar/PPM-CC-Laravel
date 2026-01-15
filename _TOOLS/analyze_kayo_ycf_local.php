<?php
/**
 * PrestaShop Description Analyzer - KAYO & YCF
 * DO URUCHOMIENIA NA SERWERZE host226673.hostido.net.pl
 * Uzyj localhost zamiast zewnetrznego hosta
 */

$databases = [
    'kayo' => [
        'host' => 'localhost',
        'name' => 'host226673_test_kayoshop',
        'user' => 'host226673_test_kayoshop',
        'pass' => 'hnMnzhGaCEhcKArm7U4v',
        'prefix' => 'ps_',
        'label' => 'KAYO (test.kayomoto.pl)'
    ],
    'ycf' => [
        'host' => 'localhost',
        'name' => 'host226673_dev_sklep_ycf',
        'user' => 'host226673_dev_sklep_ycf',
        'pass' => 'jHavTdTYzZCedUPV3AL4',
        'prefix' => 'ps_',
        'label' => 'YCF (dev.ycf.pl)'
    ]
];

$productIds = [
    'kayo' => [4000,2785,2528,2125,39,38,36,35,11,10,9,7,4016,3171,4015,4005,3612,3407,3001,2559,1332,1331,20,19,16,2256,1762,1761,1760,1759,1758,1330,15],
    'ycf' => [2675,2674,2673,2672,2671,2670,2669,2668,2667,2666,2665,2664,2663,2662,2661,2660,2659,2658,2657,2656,2655,2654,2653,2652,2651,2650,2649]
];

function extractCssClasses($html) {
    $classes = [];
    if (preg_match_all('/class=["\']([^"\']+)["\']/', $html, $matches)) {
        foreach ($matches[1] as $classString) {
            $classList = preg_split('/\s+/', trim($classString));
            foreach ($classList as $class) {
                $class = trim($class);
                if (!empty($class)) {
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
    if (preg_match_all('/<(div|section|article|header|footer|aside)\s+class=["\']([^"\']+)["\'][^>]*>/', $html, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $key = "{$match[1]}.{$match[2]}";
            $structures[$key] = ($structures[$key] ?? 0) + 1;
        }
    }
    if (preg_match_all('/class=["\'][^"\']*\b(row|col-\w+|grid|flex|container)[^"\']*["\']/', $html, $matches)) {
        foreach ($matches[1] as $gridClass) {
            $key = "grid:{$gridClass}";
            $structures[$key] = ($structures[$key] ?? 0) + 1;
        }
    }
    return $structures;
}

function identifyPatterns($html) {
    $patterns = [];
    if (preg_match('/class=["\'][^"\']*\b(hero|banner|jumbotron|header-image)[^"\']*["\']/', $html)) $patterns['hero_banner'] = true;
    if (preg_match('/class=["\'][^"\']*\b(feature|spec|specification|characteristic)[^"\']*["\']/', $html)) $patterns['feature_list'] = true;
    if (preg_match('/class=["\'][^"\']*\b(gallery|carousel|slider|lightbox)[^"\']*["\']/', $html)) $patterns['image_gallery'] = true;
    if (preg_match('/class=["\'][^"\']*\bcol-(6|md-6|lg-6)[^"\']*["\']/', $html)) $patterns['two_column'] = true;
    if (preg_match('/class=["\'][^"\']*\bcol-(4|md-4|lg-4)[^"\']*["\']/', $html)) $patterns['three_column'] = true;
    if (preg_match('/<table[^>]*>.*?(specyfikacja|dane techniczne|parametry)/is', $html)) $patterns['spec_table'] = true;
    if (preg_match('/<(iframe|video|embed)[^>]*(youtube|vimeo|video)/i', $html)) $patterns['video_embed'] = true;
    if (preg_match('/class=["\'][^"\']*\b(fa-|icon-|material-icons)[^"\']*["\']/', $html)) $patterns['icons'] = true;
    if (preg_match('/class=["\'][^"\']*\b(accordion|tab|collapse|panel)[^"\']*["\']/', $html)) $patterns['accordion_tabs'] = true;
    if (preg_match('/class=["\'][^"\']*\b(btn|button|cta)[^"\']*["\']/', $html)) $patterns['cta_buttons'] = true;
    if (preg_match('/class=["\'][^"\']*\b(elementor|ets-|sttheme)[^"\']*["\']/', $html)) $patterns['theme_specific'] = true;
    return $patterns;
}

function extractInlineStyles($html) {
    $styles = [];
    if (preg_match_all('/style=["\']([^"\']+)["\']/', $html, $matches)) {
        foreach ($matches[1] as $style) {
            $properties = explode(';', $style);
            foreach ($properties as $prop) {
                $prop = trim($prop);
                if (empty($prop)) continue;
                $parts = explode(':', $prop, 2);
                if (count($parts) === 2) {
                    $propName = trim($parts[0]);
                    $styles[$propName] = ($styles[$propName] ?? 0) + 1;
                }
            }
        }
    }
    return $styles;
}

$results = ['summary' => [], 'css_classes' => [], 'html_tags' => [], 'html_structures' => [], 'patterns' => [], 'samples' => [], 'inline_styles' => []];

echo "=== KAYO/YCF Description Analyzer (localhost) ===\n";
echo "Data: " . date('Y-m-d H:i:s') . "\n\n";

foreach ($databases as $shopKey => $config) {
    echo "[{$shopKey}] Laczenie z baza: {$config['label']}...\n";

    try {
        $dsn = "mysql:host={$config['host']};dbname={$config['name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['user'], $config['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        $ids = $productIds[$shopKey];
        $prefix = $config['prefix'];
        $idsPlaceholder = implode(',', array_fill(0, count($ids), '?'));

        $sql = "SELECT pl.id_product, pl.id_lang, pl.description, pl.description_short, pl.name
                FROM {$prefix}product_lang pl WHERE pl.id_product IN ({$idsPlaceholder}) AND pl.id_lang = 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($ids);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "[{$shopKey}] Pobrano " . count($products) . " produktow.\n";

        $shopResults = ['total_products' => count($products), 'products_with_description' => 0, 'css_classes' => [], 'html_tags' => [], 'structures' => [], 'patterns' => [], 'inline_styles' => [], 'samples' => []];

        foreach ($products as $product) {
            $html = $product['description'] ?? '';
            if (empty(trim($html))) continue;

            $shopResults['products_with_description']++;

            foreach (extractCssClasses($html) as $class => $count) $shopResults['css_classes'][$class] = ($shopResults['css_classes'][$class] ?? 0) + $count;
            foreach (extractHtmlTags($html) as $tag => $count) $shopResults['html_tags'][$tag] = ($shopResults['html_tags'][$tag] ?? 0) + $count;
            foreach (extractHtmlStructures($html) as $struct => $count) $shopResults['structures'][$struct] = ($shopResults['structures'][$struct] ?? 0) + $count;
            foreach (identifyPatterns($html) as $pattern => $exists) if ($exists) $shopResults['patterns'][$pattern] = ($shopResults['patterns'][$pattern] ?? 0) + 1;
            foreach (extractInlineStyles($html) as $style => $count) $shopResults['inline_styles'][$style] = ($shopResults['inline_styles'][$style] ?? 0) + $count;

            if (count($shopResults['samples']) < 3 && strlen($html) > 500) {
                $shopResults['samples'][] = ['id' => $product['id_product'], 'name' => $product['name'], 'html_length' => strlen($html), 'html_preview' => substr($html, 0, 2000)];
            }
        }

        arsort($shopResults['css_classes']);
        arsort($shopResults['html_tags']);
        arsort($shopResults['structures']);

        $results['summary'][$shopKey] = ['label' => $config['label'], 'total_products' => $shopResults['total_products'], 'products_with_description' => $shopResults['products_with_description'], 'unique_css_classes' => count($shopResults['css_classes']), 'unique_html_tags' => count($shopResults['html_tags']), 'patterns_found' => count($shopResults['patterns'])];
        $results['css_classes'][$shopKey] = $shopResults['css_classes'];
        $results['html_tags'][$shopKey] = $shopResults['html_tags'];
        $results['html_structures'][$shopKey] = $shopResults['structures'];
        $results['patterns'][$shopKey] = $shopResults['patterns'];
        $results['inline_styles'][$shopKey] = $shopResults['inline_styles'];
        $results['samples'][$shopKey] = $shopResults['samples'];

        echo "[{$shopKey}] Znaleziono: {$shopResults['products_with_description']} z opisem, " . count($shopResults['css_classes']) . " klas CSS, " . count($shopResults['patterns']) . " patterns\n\n";

    } catch (PDOException $e) {
        echo "[{$shopKey}] BLAD: " . $e->getMessage() . "\n\n";
        $results['summary'][$shopKey] = ['label' => $config['label'], 'error' => $e->getMessage()];
    }
}

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
