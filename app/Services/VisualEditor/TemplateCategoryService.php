<?php

declare(strict_types=1);

namespace App\Services\VisualEditor;

/**
 * Template Category Service.
 *
 * Manages template categories for Visual Description Editor.
 * Categories are based on industry product types and content sections.
 */
class TemplateCategoryService
{
    /**
     * Available template categories with Polish labels and icons.
     */
    private const CATEGORIES = [
        // Product type categories
        'motocykle' => [
            'label' => 'Motocykle',
            'icon' => 'fa-motorcycle',
            'description' => 'Szablony dla motocykli i skuterow',
            'type' => 'product',
        ],
        'quady' => [
            'label' => 'Quady / ATV',
            'icon' => 'fa-truck-monster',
            'description' => 'Szablony dla quadow i pojazdow ATV',
            'type' => 'product',
        ],
        'czesci' => [
            'label' => 'Czesci zamienne',
            'icon' => 'fa-cogs',
            'description' => 'Szablony dla czesci zamiennych',
            'type' => 'product',
        ],
        'akcesoria' => [
            'label' => 'Akcesoria',
            'icon' => 'fa-toolbox',
            'description' => 'Szablony dla akcesoriow i dodatkow',
            'type' => 'product',
        ],
        'odziez' => [
            'label' => 'Odziez',
            'icon' => 'fa-tshirt',
            'description' => 'Szablony dla odziezy i ochrony',
            'type' => 'product',
        ],
        // Content section categories
        'intro' => [
            'label' => 'Wprowadzenie',
            'icon' => 'fa-flag',
            'description' => 'Sekcje wprowadzajace produkt',
            'type' => 'section',
        ],
        'features' => [
            'label' => 'Cechy i zalety',
            'icon' => 'fa-list-check',
            'description' => 'Listy cech i zalet produktu',
            'type' => 'section',
        ],
        'specs' => [
            'label' => 'Specyfikacja',
            'icon' => 'fa-table',
            'description' => 'Tabele specyfikacji technicznej',
            'type' => 'section',
        ],
        'gallery' => [
            'label' => 'Galeria',
            'icon' => 'fa-images',
            'description' => 'Sekcje galerii i mediow',
            'type' => 'section',
        ],
        'other' => [
            'label' => 'Inne',
            'icon' => 'fa-folder',
            'description' => 'Pozostale szablony',
            'type' => 'other',
        ],
    ];

    /**
     * Get all available categories.
     *
     * @return array<string, array{label: string, icon: string, description: string, type: string}>
     */
    public function getCategories(): array
    {
        return self::CATEGORIES;
    }

    /**
     * Get category keys only.
     *
     * @return array<string>
     */
    public function getCategoryKeys(): array
    {
        return array_keys(self::CATEGORIES);
    }

    /**
     * Get category label by key.
     */
    public function getCategoryLabel(string $key): string
    {
        return self::CATEGORIES[$key]['label'] ?? ucfirst($key);
    }

    /**
     * Get category icon by key.
     */
    public function getCategoryIcon(string $key): string
    {
        return self::CATEGORIES[$key]['icon'] ?? 'fa-folder';
    }

    /**
     * Get category description by key.
     */
    public function getCategoryDescription(string $key): string
    {
        return self::CATEGORIES[$key]['description'] ?? '';
    }

    /**
     * Get category type by key (product, section, other).
     */
    public function getCategoryType(string $key): string
    {
        return self::CATEGORIES[$key]['type'] ?? 'other';
    }

    /**
     * Get categories grouped by type.
     *
     * @return array<string, array<string, array>>
     */
    public function getCategoriesGroupedByType(): array
    {
        $grouped = [
            'product' => [],
            'section' => [],
            'other' => [],
        ];

        foreach (self::CATEGORIES as $key => $data) {
            $type = $data['type'] ?? 'other';
            $grouped[$type][$key] = $data;
        }

        return $grouped;
    }

    /**
     * Get only product type categories.
     *
     * @return array<string, array>
     */
    public function getProductCategories(): array
    {
        return array_filter(
            self::CATEGORIES,
            fn(array $data) => ($data['type'] ?? '') === 'product'
        );
    }

    /**
     * Get only section type categories.
     *
     * @return array<string, array>
     */
    public function getSectionCategories(): array
    {
        return array_filter(
            self::CATEGORIES,
            fn(array $data) => ($data['type'] ?? '') === 'section'
        );
    }

    /**
     * Check if category key is valid.
     */
    public function isValidCategory(string $key): bool
    {
        return isset(self::CATEGORIES[$key]);
    }

    /**
     * Get categories for select dropdown.
     *
     * @return array<string, string>
     */
    public function getSelectOptions(): array
    {
        $options = [];
        foreach (self::CATEGORIES as $key => $data) {
            $options[$key] = $data['label'];
        }
        return $options;
    }

    /**
     * Get categories for grouped select dropdown (with optgroups).
     *
     * @return array<string, array<string, string>>
     */
    public function getGroupedSelectOptions(): array
    {
        $grouped = $this->getCategoriesGroupedByType();

        return [
            'Typy produktow' => array_map(fn($d) => $d['label'], $grouped['product']),
            'Sekcje opisu' => array_map(fn($d) => $d['label'], $grouped['section']),
            'Pozostale' => array_map(fn($d) => $d['label'], $grouped['other']),
        ];
    }
}
