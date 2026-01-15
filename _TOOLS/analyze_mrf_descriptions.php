<?php
/**
 * MRF PrestaShop Description Analyzer
 * Connects to MRF database and extracts CSS patterns from product descriptions
 */

// MRF Database Configuration
$config = [
    'host' => 'mysql53.mydevil.net',
    'database' => 'm1070_sklepMRF',
    'username' => 'm1070_mrfsklep',
    'password' => '8bR1VyuSPrG2y+he&+hZ+1Vq5-5s0g',
];

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "=== MRF PRESTASHOP DESCRIPTION ANALYSIS ===\n\n";
    echo "Connected to: {$config['database']}\n\n";

    // Query for product descriptions
    $sql = "SELECT id_product, name, description
            FROM ps_product_lang
            WHERE id_lang = 1
            AND description IS NOT NULL
            AND description != ''
            AND LENGTH(description) > 100
            ORDER BY id_product DESC
            LIMIT 50";

    $stmt = $pdo->query($sql);
    $products = $stmt->fetchAll();

    echo "Found " . count($products) . " products with descriptions\n\n";

    $allClasses = [];
    $htmlPatterns = [];
    $structurePatterns = [];

    foreach ($products as $product) {
        $description = $product['description'];

        // Extract CSS classes
        preg_match_all('/class=["\']([^"\']+)["\']/', $description, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $classString) {
                $classes = preg_split('/\s+/', trim($classString));
                foreach ($classes as $class) {
                    if (!empty($class)) {
                        $allClasses[$class] = ($allClasses[$class] ?? 0) + 1;
                    }
                }
            }
        }

        // Extract HTML structure patterns
        preg_match_all('/<(div|section|article|table|ul|ol|header|footer|nav|aside|figure|figcaption)[^>]*>/', $description, $tagMatches);
        if (!empty($tagMatches[1])) {
            foreach ($tagMatches[1] as $tag) {
                $htmlPatterns[$tag] = ($htmlPatterns[$tag] ?? 0) + 1;
            }
        }

        // Detect specific patterns
        if (preg_match('/pd-hero|blok-hero|hero-banner|main-banner/', $description)) {
            $structurePatterns['Hero Banner'] = ($structurePatterns['Hero Banner'] ?? 0) + 1;
        }
        if (preg_match('/pd-features|blok-features|feature-card|zalety/', $description)) {
            $structurePatterns['Feature Cards'] = ($structurePatterns['Feature Cards'] ?? 0) + 1;
        }
        if (preg_match('/pd-specs|blok-specs|specification|dane-techniczne|parametry/', $description)) {
            $structurePatterns['Specification Table'] = ($structurePatterns['Specification Table'] ?? 0) + 1;
        }
        if (preg_match('/swiper|slider|carousel|gallery/', $description)) {
            $structurePatterns['Slider/Carousel'] = ($structurePatterns['Slider/Carousel'] ?? 0) + 1;
        }
        if (preg_match('/pd-two-col|blok-two-col|grid|columns/', $description)) {
            $structurePatterns['Multi-Column Layout'] = ($structurePatterns['Multi-Column Layout'] ?? 0) + 1;
        }
    }

    // Sort by frequency
    arsort($allClasses);
    arsort($htmlPatterns);
    arsort($structurePatterns);

    // Output results
    echo "=== CSS CLASSES (Top 50 by frequency) ===\n";
    $i = 0;
    foreach ($allClasses as $class => $count) {
        if ($i++ >= 50) break;
        echo sprintf("  %-40s : %d\n", $class, $count);
    }

    echo "\n=== HTML TAG PATTERNS ===\n";
    foreach ($htmlPatterns as $tag => $count) {
        echo sprintf("  %-20s : %d\n", $tag, $count);
    }

    echo "\n=== STRUCTURE PATTERNS DETECTED ===\n";
    foreach ($structurePatterns as $pattern => $count) {
        echo sprintf("  %-25s : %d\n", $pattern, $count);
    }

    echo "\n=== CSS NAMESPACE ANALYSIS ===\n";
    $namespaces = [];
    foreach (array_keys($allClasses) as $class) {
        if (preg_match('/^([a-z]+-)[a-z]/', $class, $m)) {
            $namespaces[$m[1]] = ($namespaces[$m[1]] ?? 0) + 1;
        }
    }
    arsort($namespaces);
    foreach ($namespaces as $ns => $count) {
        echo sprintf("  %-20s : %d classes\n", $ns . '*', $count);
    }

    // Sample descriptions
    echo "\n=== SAMPLE DESCRIPTIONS (first 3) ===\n";
    for ($i = 0; $i < min(3, count($products)); $i++) {
        $desc = $products[$i]['description'];
        $truncated = substr(strip_tags($desc), 0, 200);
        echo "\n--- Product ID: {$products[$i]['id_product']} - {$products[$i]['name']} ---\n";
        echo "Length: " . strlen($desc) . " chars\n";
        echo "Preview: {$truncated}...\n";
    }

    // Full class list for styleset
    echo "\n=== FULL CLASS LIST (for styleset) ===\n";
    echo json_encode(array_keys($allClasses), JSON_PRETTY_PRINT);

    echo "\n\n=== ANALYSIS COMPLETE ===\n";
    echo "Total unique CSS classes: " . count($allClasses) . "\n";
    echo "Total products analyzed: " . count($products) . "\n";

} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
    exit(1);
}
