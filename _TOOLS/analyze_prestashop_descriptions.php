<?php
/**
 * PrestaShop Product Description Analyzer
 *
 * Analizuje opisy produktow z wielu sklepow PrestaShop
 * w celu identyfikacji patterns CSS, struktur HTML i wspolnych elementow
 *
 * @author PPM-CC-Laravel Architect Agent
 * @date 2025-12-11
 */

// Konfiguracja baz danych
$databases = [
    'b2b' => [
        'host' => 'host379076.hostido.net.pl',
        'name' => 'host379076_devmpp',
        'user' => 'host379076_devmpp',
        'pass' => 'CxtsfyV4nWyGct5LTZrb',
        'prefix' => 'ps_',
        'label' => 'B2B Test DEV (dev.mpptrade.pl)'
    ],
    'kayo' => [
        'host' => 'host226673.hostido.net.pl',
        'name' => 'host226673_test_kayoshop',
        'user' => 'host226673_test_kayoshop',
        'pass' => 'hnMnzhGaCEhcKArm7U4v',
        'prefix' => 'ps_',
        'label' => 'KAYO (test.kayomoto.pl)'
    ],
    'ycf' => [
        'host' => 'host226673.hostido.net.pl',
        'name' => 'host226673_dev_sklep_ycf',
        'user' => 'host226673_dev_sklep_ycf',
        'pass' => 'jHavTdTYzZCedUPV3AL4',
        'prefix' => 'ps_',
        'label' => 'YCF (dev.ycf.pl)'
    ],
    'pitgang' => [
        'host' => 'mysql53.mydevil.net',
        'name' => 'm1070_gangshop',
        'user' => 'm1070_gangshop',
        'pass' => '5^QcJdY2yfUj5F',
        'prefix' => 'ps_',
        'label' => 'Pitgang (sklep.pitgang.pl)'
    ]
];

// ID produktow do analizy per sklep
$productIds = [
    'b2b' => [9755,9748,9679,9678,9677,9676,9675,9674,9673,9610,9609,9608,9607,9606,9605,9489,9488,9487,9486,9485,9463,9462,9456,9454,9453,9394,9223,8962,8643,8642,8641,8640,8639,8638,8637,8636,8594,8589,8481,8480,8424,8423,8280,8265,8263,7510,7375,7374,7373,7372,7371,7370,7369,7368,7367,7366,7365,7364,7363,1836,1831,1830,1828,1827,1826,162,161,160,159,149,148,146,143,139,138,137,136,135,133,130,56,55,51,50,49,48,47,46,45,44,43,42,34,33,22,21,20,19,18,17,16,15,14,13,12,11,10,9,8,7,6,5,4],
    'kayo' => [4000,2785,2528,2125,39,38,36,35,11,10,9,7,4016,3171,4015,4005,3612,3407,3001,2559,1332,1331,20,19,16,2256,1762,1761,1760,1759,1758,1330,15],
    'ycf' => [2675,2674,2673,2672,2671,2670,2669,2668,2667,2666,2665,2664,2663,2662,2661,2660,2659,2658,2657,2656,2655,2654,2653,2652,2651,2650,2649],
    'pitgang' => [191,83,47,46]
];

// Struktura wynikow
$results = [
    'summary' => [],
    'css_classes' => [],
    'html_tags' => [],
    'html_structures' => [],
    'patterns' => [],
    'samples' => []
];

/**
 * Ekstrakcja klas CSS z HTML
 */
function extractCssClasses($html) {
    $classes = [];

    // Wyciagnij class="..."
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

/**
 * Ekstrakcja tagow HTML z atrybutami
 */
function extractHtmlTags($html) {
    $tags = [];

    // Wyciagnij tagi z atrybutami
    if (preg_match_all('/<(\w+)([^>]*)>/', $html, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $tagName = strtolower($match[1]);
            $attrs = $match[2];

            $tags[$tagName] = ($tags[$tagName] ?? 0) + 1;
        }
    }

    return $tags;
}

/**
 * Ekstrakcja struktur HTML (sekcje, gridy, kontenery)
 */
function extractHtmlStructures($html) {
    $structures = [];

    // Sekcje z klasami
    if (preg_match_all('/<(div|section|article|header|footer|aside)\s+class=["\']([^"\']+)["\'][^>]*>/', $html, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $tag = $match[1];
            $classes = $match[2];
            $key = "{$tag}.{$classes}";
            $structures[$key] = ($structures[$key] ?? 0) + 1;
        }
    }

    // Tabele
    if (preg_match_all('/<table[^>]*class=["\']([^"\']*)["\'][^>]*>/', $html, $matches)) {
        foreach ($matches[1] as $tableClass) {
            $key = "table.{$tableClass}";
            $structures[$key] = ($structures[$key] ?? 0) + 1;
        }
    }

    // Gridy (row, col, grid)
    if (preg_match_all('/class=["\'][^"\']*\b(row|col-\w+|grid|flex|container)[^"\']*["\']/', $html, $matches)) {
        foreach ($matches[1] as $gridClass) {
            $key = "grid:{$gridClass}";
            $structures[$key] = ($structures[$key] ?? 0) + 1;
        }
    }

    return $structures;
}

/**
 * Identyfikacja patterns (powtarzajacych sie wzorcow)
 */
function identifyPatterns($html) {
    $patterns = [];

    // Hero/Banner sections
    if (preg_match('/class=["\'][^"\']*\b(hero|banner|jumbotron|header-image)[^"\']*["\']/', $html)) {
        $patterns['hero_banner'] = true;
    }

    // Feature/Spec lists
    if (preg_match('/class=["\'][^"\']*\b(feature|spec|specification|characteristic)[^"\']*["\']/', $html)) {
        $patterns['feature_list'] = true;
    }

    // Image galleries
    if (preg_match('/class=["\'][^"\']*\b(gallery|carousel|slider|lightbox)[^"\']*["\']/', $html)) {
        $patterns['image_gallery'] = true;
    }

    // Two-column layouts
    if (preg_match('/class=["\'][^"\']*\bcol-(6|md-6|lg-6)[^"\']*["\']/', $html)) {
        $patterns['two_column'] = true;
    }

    // Three-column layouts
    if (preg_match('/class=["\'][^"\']*\bcol-(4|md-4|lg-4)[^"\']*["\']/', $html)) {
        $patterns['three_column'] = true;
    }

    // Tables for specs
    if (preg_match('/<table[^>]*>.*?(specyfikacja|dane techniczne|parametry|characteristic)/is', $html)) {
        $patterns['spec_table'] = true;
    }

    // Video embeds
    if (preg_match('/<(iframe|video|embed)[^>]*(youtube|vimeo|video)/i', $html)) {
        $patterns['video_embed'] = true;
    }

    // Icons (Font Awesome, custom)
    if (preg_match('/class=["\'][^"\']*\b(fa-|icon-|material-icons)[^"\']*["\']/', $html)) {
        $patterns['icons'] = true;
    }

    // Accordion/Tabs
    if (preg_match('/class=["\'][^"\']*\b(accordion|tab|collapse|panel)[^"\']*["\']/', $html)) {
        $patterns['accordion_tabs'] = true;
    }

    // Call to action buttons
    if (preg_match('/class=["\'][^"\']*\b(btn|button|cta)[^"\']*["\']/', $html)) {
        $patterns['cta_buttons'] = true;
    }

    // Custom sections (specyficzne dla PrestaShop Warehouse theme)
    if (preg_match('/class=["\'][^"\']*\b(elementor|ets-|sttheme)[^"\']*["\']/', $html)) {
        $patterns['theme_specific'] = true;
    }

    return $patterns;
}

/**
 * Analiza inline styles
 */
function extractInlineStyles($html) {
    $styles = [];

    if (preg_match_all('/style=["\']([^"\']+)["\']/', $html, $matches)) {
        foreach ($matches[1] as $style) {
            // Parsuj poszczegolne wlasciwosci
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

/**
 * Glowna funkcja analizy
 */
function analyzeDatabase($config, $ids, $shopKey) {
    global $results;

    echo "\n[{$shopKey}] Laczenie z baza: {$config['label']}...\n";

    try {
        $dsn = "mysql:host={$config['host']};dbname={$config['name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        echo "[{$shopKey}] Polaczono! Pobieram opisy dla " . count($ids) . " produktow...\n";

        $prefix = $config['prefix'];
        $idsPlaceholder = implode(',', array_fill(0, count($ids), '?'));

        // Pobierz opisy z ps_product_lang
        $sql = "SELECT
                    pl.id_product,
                    pl.id_lang,
                    pl.description,
                    pl.description_short,
                    pl.name
                FROM {$prefix}product_lang pl
                WHERE pl.id_product IN ({$idsPlaceholder})
                AND pl.id_lang = 1
                ORDER BY pl.id_product";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($ids);
        $products = $stmt->fetchAll();

        echo "[{$shopKey}] Pobrano " . count($products) . " opisow. Analizuje...\n";

        $shopResults = [
            'total_products' => count($products),
            'products_with_description' => 0,
            'css_classes' => [],
            'html_tags' => [],
            'structures' => [],
            'patterns' => [],
            'inline_styles' => [],
            'samples' => []
        ];

        foreach ($products as $product) {
            $html = $product['description'] ?? '';

            if (empty(trim($html))) {
                continue;
            }

            $shopResults['products_with_description']++;

            // Ekstrakcja danych
            $classes = extractCssClasses($html);
            $tags = extractHtmlTags($html);
            $structures = extractHtmlStructures($html);
            $patterns = identifyPatterns($html);
            $inlineStyles = extractInlineStyles($html);

            // Agregacja wynikow
            foreach ($classes as $class => $count) {
                $shopResults['css_classes'][$class] = ($shopResults['css_classes'][$class] ?? 0) + $count;
            }

            foreach ($tags as $tag => $count) {
                $shopResults['html_tags'][$tag] = ($shopResults['html_tags'][$tag] ?? 0) + $count;
            }

            foreach ($structures as $struct => $count) {
                $shopResults['structures'][$struct] = ($shopResults['structures'][$struct] ?? 0) + $count;
            }

            foreach ($patterns as $pattern => $exists) {
                if ($exists) {
                    $shopResults['patterns'][$pattern] = ($shopResults['patterns'][$pattern] ?? 0) + 1;
                }
            }

            foreach ($inlineStyles as $style => $count) {
                $shopResults['inline_styles'][$style] = ($shopResults['inline_styles'][$style] ?? 0) + $count;
            }

            // Zbierz sample (max 3 per sklep)
            if (count($shopResults['samples']) < 3 && strlen($html) > 500) {
                $shopResults['samples'][] = [
                    'id' => $product['id_product'],
                    'name' => $product['name'],
                    'html_length' => strlen($html),
                    'html_preview' => substr($html, 0, 2000) . (strlen($html) > 2000 ? '...' : '')
                ];
            }
        }

        // Sortuj wyniki po ilosci wystapien
        arsort($shopResults['css_classes']);
        arsort($shopResults['html_tags']);
        arsort($shopResults['structures']);
        arsort($shopResults['patterns']);
        arsort($shopResults['inline_styles']);

        $results['summary'][$shopKey] = [
            'label' => $config['label'],
            'total_products' => $shopResults['total_products'],
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

        echo "[{$shopKey}] Analiza zakonczona. Znaleziono:\n";
        echo "  - {$shopResults['products_with_description']} produktow z opisem\n";
        echo "  - " . count($shopResults['css_classes']) . " unikalnych klas CSS\n";
        echo "  - " . count($shopResults['patterns']) . " patterns\n";

    } catch (PDOException $e) {
        echo "[{$shopKey}] BLAD: " . $e->getMessage() . "\n";
        $results['summary'][$shopKey] = [
            'label' => $config['label'],
            'error' => $e->getMessage()
        ];
    }
}

// Uruchom analize dla wszystkich sklepow
echo "=== PrestaShop Description Analyzer ===\n";
echo "Data: " . date('Y-m-d H:i:s') . "\n";

foreach ($databases as $shopKey => $config) {
    if (isset($productIds[$shopKey])) {
        analyzeDatabase($config, $productIds[$shopKey], $shopKey);
    }
}

// Znajdz wspolne klasy CSS miedzy sklepami
echo "\n=== Analiza wspolnych elementow ===\n";

$allClasses = [];
foreach ($results['css_classes'] as $shopKey => $classes) {
    foreach ($classes as $class => $count) {
        if (!isset($allClasses[$class])) {
            $allClasses[$class] = [];
        }
        $allClasses[$class][$shopKey] = $count;
    }
}

// Klasy wystepujace w wiecej niz jednym sklepie
$commonClasses = [];
foreach ($allClasses as $class => $shops) {
    if (count($shops) > 1) {
        $commonClasses[$class] = $shops;
    }
}

$results['common_classes'] = $commonClasses;

// Wspolne patterns
$allPatterns = [];
foreach ($results['patterns'] as $shopKey => $patterns) {
    foreach ($patterns as $pattern => $count) {
        if (!isset($allPatterns[$pattern])) {
            $allPatterns[$pattern] = [];
        }
        $allPatterns[$pattern][$shopKey] = $count;
    }
}

$results['common_patterns'] = $allPatterns;

// Zapisz wyniki do pliku JSON
$outputFile = __DIR__ . '/analysis_results_' . date('Y-m-d_His') . '.json';
file_put_contents($outputFile, json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "\nWyniki zapisane do: {$outputFile}\n";

// Wygeneruj podsumowanie
echo "\n=== PODSUMOWANIE ANALIZY ===\n\n";

echo "STATYSTYKI PER SKLEP:\n";
echo str_repeat('-', 80) . "\n";
printf("%-30s | %10s | %10s | %10s | %10s\n", "Sklep", "Produkty", "Z opisem", "Klasy CSS", "Patterns");
echo str_repeat('-', 80) . "\n";

foreach ($results['summary'] as $shopKey => $summary) {
    if (isset($summary['error'])) {
        printf("%-30s | %s\n", $summary['label'], "BLAD: " . substr($summary['error'], 0, 40));
    } else {
        printf("%-30s | %10d | %10d | %10d | %10d\n",
            substr($summary['label'], 0, 30),
            $summary['total_products'],
            $summary['products_with_description'],
            $summary['unique_css_classes'],
            $summary['patterns_found']
        );
    }
}
echo str_repeat('-', 80) . "\n";

echo "\nNAJCZESTSZE KLASY CSS (TOP 20 per sklep):\n";
foreach ($results['css_classes'] as $shopKey => $classes) {
    echo "\n[{$shopKey}]:\n";
    $i = 0;
    foreach ($classes as $class => $count) {
        if ($i++ >= 20) break;
        echo "  {$class}: {$count}\n";
    }
}

echo "\nWSPOLNE KLASY CSS (wystepujace w >1 sklepie):\n";
$sortedCommon = $commonClasses;
uasort($sortedCommon, function($a, $b) {
    return count($b) - count($a);
});
$i = 0;
foreach ($sortedCommon as $class => $shops) {
    if ($i++ >= 30) break;
    $shopList = implode(', ', array_keys($shops));
    echo "  {$class}: [{$shopList}]\n";
}

echo "\nPATTERNS ZNALEZIONE:\n";
foreach ($allPatterns as $pattern => $shops) {
    $shopList = [];
    foreach ($shops as $shop => $count) {
        $shopList[] = "{$shop}({$count})";
    }
    echo "  {$pattern}: " . implode(', ', $shopList) . "\n";
}

echo "\nINLINE STYLES (TOP 10 per sklep):\n";
foreach ($results['inline_styles'] as $shopKey => $styles) {
    echo "\n[{$shopKey}]:\n";
    $i = 0;
    foreach ($styles as $style => $count) {
        if ($i++ >= 10) break;
        echo "  {$style}: {$count}\n";
    }
}

echo "\n=== ANALIZA ZAKONCZONA ===\n";
echo "Pelne wyniki w: {$outputFile}\n";
