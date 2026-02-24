<?php

namespace App\Services\Import;

use App\Models\ProductType;

/**
 * ProductTypeDetector - Intelligent Product Type Detection
 *
 * Detects product type based on PrestaShop category names during import.
 * Uses priority-based keyword matching with include/exclude rules.
 *
 * @package App\Services\Import
 * @since Import Panel Redesign
 */
class ProductTypeDetector
{
    /**
     * Detection rules (priority order - first match wins)
     *
     * Each rule: [slug, include_keywords[], exclude_keywords[]]
     */
    protected array $rules = [
        // PRIORITY 1: Pojazd (DB slug: 'pojazd')
        ['pojazd', [
            'pojazd', 'pojazdy',
            'pit bike', 'pitbike', 'dirt bike', 'dirtbike',
            'quad', 'quady', 'buggy', 'homologacja',
            'mini gp', 'minigp', 'motorowery', 'motorower', 'supermoto',
            'elektryczne', 'electric',
        ], ['czesc', 'czesci', 'zamienn', 'akcesori']],

        // PRIORITY 2: Czesc zamienna (DB slug: 'czesc-zamienna')
        ['czesc-zamienna', [
            'czesc', 'czesci', 'zamienn', 'zamienne', 'zamiennik', 'czesci zamienne',
        ], []],

        // PRIORITY 3: Akcesoria
        ['akcesoria', [
            'akcesori', 'akcesorium', 'akcesoria',
        ], ['czesc', 'czesci', 'zamienn']],

        // PRIORITY 4: Oleje i chemia
        ['oleje-i-chemia', [
            'olej', 'oleje', 'chemia', 'klej', 'kleje', 'plyn', 'plyny', 'smar', 'smary',
        ], []],

        // PRIORITY 5: Odziez
        ['odziez', [
            'odziez', 'stroj', 'stroje', 'komin', 'kominy', 't-shirt',
            'nakolannik', 'nakolanniki', 'koszul', 'koszule', 'bluz', 'bluzy',
            'czapk', 'czapki', 'okular', 'okulary',
        ], ['czesc', 'czesci', 'zamienn', 'akcesori']],

        // PRIORITY 6: Outlet
        ['outlet', ['outlet'], []],
    ];

    /**
     * Cache for ProductType models
     */
    protected array $typeCache = [];

    /**
     * Detect product type from category names
     *
     * @param array $categoryNames Array of category name strings from PrestaShop
     * @return ProductType|null Detected ProductType or fallback 'inne'
     */
    public function detect(array $categoryNames): ?ProductType
    {
        if (empty($categoryNames)) {
            return $this->getType('inne');
        }

        $normalized = array_map([$this, 'normalize'], $categoryNames);
        $joinedNames = implode(' ', $normalized);

        foreach ($this->rules as [$slug, $includeKeywords, $excludeKeywords]) {
            $hasInclude = false;
            foreach ($includeKeywords as $keyword) {
                if (str_contains($joinedNames, $keyword)) {
                    $hasInclude = true;
                    break;
                }
            }

            if (!$hasInclude) {
                continue;
            }

            $hasExclude = false;
            foreach ($excludeKeywords as $excludeKeyword) {
                if (str_contains($joinedNames, $excludeKeyword)) {
                    $hasExclude = true;
                    break;
                }
            }

            if ($hasExclude) {
                continue;
            }

            return $this->getType($slug);
        }

        return $this->getType('inne');
    }

    /**
     * Detect type and return display info (for CategoryPreviewModal)
     *
     * @param array $categoryNames
     * @return array{name: string, slug: string, color: string}
     */
    public function detectWithInfo(array $categoryNames): array
    {
        $type = $this->detect($categoryNames);

        return [
            'name' => $type?->name ?? 'Nie wykryto',
            'slug' => $type?->slug ?? 'unknown',
            'color' => $this->getTypeColor($type?->slug),
        ];
    }

    /**
     * Get CSS color class for product type badge
     */
    public function getTypeColor(?string $slug): string
    {
        if (!$slug) {
            return \App\Models\ProductType::DEFAULT_LABEL_COLOR;
        }

        $type = $this->getType($slug);
        return $type?->label_color ?? (\App\Models\ProductType::DEFAULT_LABEL_COLORS[$slug] ?? \App\Models\ProductType::DEFAULT_LABEL_COLOR);
    }

    /**
     * Normalize string for keyword matching
     *
     * Converts to lowercase and removes Polish diacritics
     */
    protected function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text));

        $from = ['ą', 'ć', 'ę', 'ł', 'ń', 'ó', 'ś', 'ź', 'ż'];
        $to   = ['a', 'c', 'e', 'l', 'n', 'o', 's', 'z', 'z'];

        return str_replace($from, $to, $text);
    }

    /**
     * Get ProductType by slug (cached)
     */
    protected function getType(string $slug): ?ProductType
    {
        if (!isset($this->typeCache[$slug])) {
            $this->typeCache[$slug] = ProductType::where('slug', $slug)->first();
        }

        return $this->typeCache[$slug];
    }
}
