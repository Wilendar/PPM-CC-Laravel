<?php

/**
 * Test script for BlockRenderer
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\VisualEditor\BlockRegistry;
use App\Services\VisualEditor\BlockRenderer;
use App\Services\VisualEditor\StylesetManager;

echo "=== BLOCK RENDERER TEST ===" . PHP_EOL . PHP_EOL;

// Create services
$registry = new BlockRegistry();
$registry->discoverBlocks();

$stylesetManager = new StylesetManager();
$renderer = new BlockRenderer($registry, $stylesetManager);

echo "Registered blocks: " . $registry->count() . PHP_EOL . PHP_EOL;

// Test 1: Simple heading block
echo "=== TEST 1: Heading Block ===" . PHP_EOL;
$headingBlock = [
    'type' => 'heading',
    'content' => [
        'text' => 'Witaj w Visual Editor',
        'subtitle' => 'Nowy system opisow produktow',
    ],
    'settings' => [
        'level' => 'h2',
        'alignment' => 'center',
        'show_subtitle' => true,
    ],
];

$html = $renderer->renderBlock($headingBlock);
echo $html . PHP_EOL . PHP_EOL;

// Test 2: Two-column layout
echo "=== TEST 2: Two-Column Layout ===" . PHP_EOL;
$twoColumnBlock = [
    'type' => 'two-column',
    'content' => [],
    'settings' => [
        'ratio' => '60-40',
        'gap' => '2rem',
    ],
    'children' => [
        '<p>Lewa kolumna - tresc produktu</p>',
        '<p>Prawa kolumna - specyfikacja</p>',
    ],
];

$html = $renderer->renderBlock($twoColumnBlock);
echo $html . PHP_EOL . PHP_EOL;

// Test 3: Feature card
echo "=== TEST 3: Feature Card ===" . PHP_EOL;
$featureCard = [
    'type' => 'feature-card',
    'content' => [
        'title' => 'Wysoka jakosc',
        'description' => '<p>Nasze produkty charakteryzuja sie najwyzsza jakoscia wykonania.</p>',
        'link' => '#features',
        'link_text' => 'Dowiedz sie wiecej',
    ],
    'settings' => [
        'layout' => 'image-top',
        'card_style' => 'shadow',
    ],
];

$html = $renderer->renderBlock($featureCard);
echo $html . PHP_EOL . PHP_EOL;

// Test 4: Multiple blocks
echo "=== TEST 4: Multiple Blocks ===" . PHP_EOL;
$blocks = [
    [
        'type' => 'hero-banner',
        'content' => [
            'image' => 'https://example.com/banner.jpg',
            'title' => 'Motocykle KAYO',
            'subtitle' => 'Najlepsze pitbike w Polsce',
        ],
        'settings' => [
            'height' => '300px',
            'overlay' => true,
            'text_position' => 'center',
        ],
    ],
    [
        'type' => 'heading',
        'content' => ['text' => 'Cechy produktu'],
        'settings' => ['level' => 'h2'],
    ],
    [
        'type' => 'merit-list',
        'content' => [
            'items' => [
                ['heading' => 'Mocny silnik', 'text' => 'Niezawodny silnik 125cc'],
                ['heading' => 'Lekka rama', 'text' => 'Aluminiowa rama - tylko 65kg'],
                ['heading' => 'Gwarancja', 'text' => '24 miesiace gwarancji'],
            ],
        ],
        'settings' => [
            'layout' => 'grid',
            'columns' => 3,
        ],
    ],
];

$html = $renderer->renderBlocks($blocks);
echo "Rendered " . count($blocks) . " blocks:" . PHP_EOL;
echo substr($html, 0, 500) . "..." . PHP_EOL . PHP_EOL;

// Test 5: Styleset variables
echo "=== TEST 5: Styleset CSS Variables ===" . PHP_EOL;
$css = $stylesetManager->compileVariables();
echo $css . PHP_EOL;

echo "=== ALL TESTS COMPLETED ===" . PHP_EOL;
