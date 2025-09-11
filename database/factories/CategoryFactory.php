<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * CategoryFactory - Factory dla generowania test data kategorii
 * 
 * Enterprise-grade factory dla Category model:
 * - Hierarchiczna struktura kategorii (5 poziomów)
 * - Realistyczne nazwy kategorii dla automotive industry
 * - Path materialization i level calculation
 * - SEO-friendly slugs
 * 
 * Usage:
 * Category::factory()->create() - pojedyncza kategoria
 * Category::factory()->count(50)->create() - 50 kategorii
 * Category::factory()->root()->create() - kategoria główna
 * Category::factory()->child($parentId)->create() - podkategoria
 * Category::factory()->tree(3)->create() - drzewo 3-poziomowe
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 * @package Database\Factories
 * @version 1.0
 * @since FAZA A - Core Models Implementation
 */
class CategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Category::class;

    /**
     * Define the model's default state.
     * 
     * Generates realistic category data dla PPM-CC-Laravel:
     * - Automotive industry category names
     * - SEO-optimized slugs
     * - Random icons (Font Awesome)
     * - Proper sort order
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->generateCategoryName();

        return [
            // === TREE STRUCTURE ===
            'parent_id' => null, // Default to root category
            
            // === BASIC CATEGORY INFO ===
            'name' => $name,
            'slug' => Str::slug($name) . '-' . $this->faker->randomNumber(3),
            'description' => $this->faker->optional(0.6)->paragraph(),
            
            // === TREE STRUCTURE OPTIMIZATION ===
            'level' => 0, // Will be calculated based on parent_id
            'path' => null, // Will be calculated based on parent_id
            
            // === CATEGORY STATUS & ORDERING ===
            'sort_order' => $this->faker->numberBetween(0, 100),
            'is_active' => $this->faker->boolean(90), // 90% active
            'icon' => $this->faker->optional(0.7)->randomElement([
                'fas fa-motorcycle',
                'fas fa-car',
                'fas fa-tools',
                'fas fa-cog',
                'fas fa-oil-can',
                'fas fa-battery-three-quarters',
                'fas fa-tachometer-alt',
                'fas fa-wrench',
                'fas fa-screwdriver',
                'fas fa-bolt',
                'fas fa-fire',
                'fas fa-shield-alt',
                'fas fa-helmet',
                'fas fa-tshirt',
                'fas fa-shoes',
                'fas fa-gloves'
            ]),
            
            // === SEO METADATA ===
            'meta_title' => $this->faker->optional(0.5)->sentence(
                $this->faker->numberBetween(4, 8)
            ),
            'meta_description' => $this->faker->optional(0.4)->sentence(
                $this->faker->numberBetween(10, 15)
            ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | FACTORY STATES - Tree Structure Specific
    |--------------------------------------------------------------------------
    */

    /**
     * Create root categories (level 0)
     */
    public function root(): static
    {
        return $this->state(function (array $attributes) {
            $rootCategories = [
                'Motocykle i Skutery',
                'Quady i ATV',
                'Części zamienne',
                'Akcesoria motocyklowe',
                'Odzież motocyklowa',
                'Kaski i ochrona',
                'Narzędzia i chemia',
                'Opony i felgi'
            ];

            return [
                'parent_id' => null,
                'level' => 0,
                'path' => null,
                'name' => $this->faker->randomElement($rootCategories),
                'sort_order' => $this->faker->numberBetween(1, 20),
            ];
        });
    }

    /**
     * Create child category for specific parent
     */
    public function child(?int $parentId = null): static
    {
        return $this->state(function (array $attributes) use ($parentId) {
            if ($parentId) {
                $parent = Category::find($parentId);
                
                if ($parent && $parent->level < Category::MAX_LEVEL) {
                    return [
                        'parent_id' => $parentId,
                        'level' => $parent->level + 1,
                        'path' => ($parent->path ?: '') . '/' . $parent->id,
                    ];
                }
            }

            // Fallback to existing category or root
            $possibleParents = Category::where('level', '<', Category::MAX_LEVEL)->pluck('id')->toArray();
            
            if (!empty($possibleParents)) {
                $randomParentId = $this->faker->randomElement($possibleParents);
                $parent = Category::find($randomParentId);
                
                return [
                    'parent_id' => $randomParentId,
                    'level' => $parent->level + 1,
                    'path' => ($parent->path ?: '') . '/' . $parent->id,
                ];
            }

            return [
                'parent_id' => null,
                'level' => 0,
                'path' => null,
            ];
        });
    }

    /**
     * Create category tree with specific depth
     */
    public function tree(int $depth = 3): static
    {
        return $this->state(function (array $attributes) use ($depth) {
            // This will be used for creating tree structures
            // Implementation would require post-creation logic
            return [
                'meta_depth' => $depth, // Custom attribute dla tree building
            ];
        });
    }

    /**
     * Create active categories only
     */
    public function active(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => true,
            ];
        });
    }

    /**
     * Create categories with full SEO metadata
     */
    public function withSEO(): static
    {
        return $this->state(function (array $attributes) {
            $name = $attributes['name'] ?? $this->generateCategoryName();
            
            return [
                'meta_title' => $name . ' - Sklep motocyklowy MPP TRADE',
                'meta_description' => 'Szeroki wybór produktów z kategorii ' . strtolower($name) . '. Najlepsze ceny, szybka dostawa.',
            ];
        });
    }

    /**
     * Create categories specific to motorcycle parts
     */
    public function motorcycleParts(): static
    {
        return $this->state(function (array $attributes) {
            $motorcycleCategories = [
                'Silnik i układ wydechowy',
                'Układ hamulcowy',
                'Układ napędowy', 
                'Zawieszenie',
                'Elektryka',
                'Nadwozie i plastiki',
                'Kierownica i manetki',
                'Koła i opony',
                'Filtry i oleje',
                'Sprzęgło i skrzynia'
            ];

            return [
                'name' => $this->faker->randomElement($motorcycleCategories),
                'icon' => 'fas fa-motorcycle',
            ];
        });
    }

    /**
     * Create categories specific to clothing
     */
    public function clothing(): static
    {
        return $this->state(function (array $attributes) {
            $clothingCategories = [
                'Kaski motocyklowe',
                'Kurtki motocyklowe',
                'Spodnie motocyklowe',
                'Rękawice motocyklowe',
                'Buty motocyklowe',
                'Ochraniacze',
                'Odzież przeciwdeszczowa',
                'Termoaktywne',
                'Akcesoria do kasków'
            ];

            return [
                'name' => $this->faker->randomElement($clothingCategories),
                'icon' => 'fas fa-tshirt',
            ];
        });
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER METHODS - Data Generation
    |--------------------------------------------------------------------------
    */

    /**
     * Generate realistic category name dla automotive industry
     */
    private function generateCategoryName(): string
    {
        $generalCategories = [
            // Root level categories
            'Motocykle', 'Skutery', 'Quady', 'Buggy', 'Go-Karty',
            
            // Parts categories
            'Silnik', 'Hamulce', 'Zawieszenie', 'Elektryka', 'Nadwozie',
            'Koła', 'Opony', 'Filtry', 'Oleje', 'Chemia',
            
            // Clothing categories
            'Kaski', 'Kurtki', 'Spodnie', 'Rękawice', 'Buty', 'Ochraniacze',
            
            // Accessory categories
            'Bagażniki', 'Szyby', 'Lusterka', 'Oświetlenie', 'Alarmy',
            
            // Brand categories (subcategories)
            'Yamaha', 'Honda', 'Suzuki', 'Kawasaki', 'KTM', 'Husqvarna',
            
            // Specific parts
            'Tłoki', 'Cylindry', 'Sprzęgła', 'Łańcuchy', 'Zębatki',
            'Amortyzatory', 'Widełki', 'Klocki', 'Tarcze', 'Przewody'
        ];

        $modifiers = [
            'Części do', 'Akcesoria do', 'Oryginalne', 'Zamienne',
            'Sportowe', 'Touring', 'Off-road', 'Enduro', 'Cross'
        ];

        $base = $this->faker->randomElement($generalCategories);
        
        // Sometimes add modifier
        if ($this->faker->boolean(30)) {
            $modifier = $this->faker->randomElement($modifiers);
            return $modifier . ' ' . $base;
        }
        
        return $base;
    }

    /**
     * Generate unique slug with category context
     */
    private function generateUniqueSlug(string $name, ?int $parentId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        $query = Category::where('slug', $slug);
        
        // Check uniqueness within same parent (optional business rule)
        if ($parentId) {
            $query->where('parent_id', $parentId);
        }

        while ($query->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
            $query = Category::where('slug', $slug);
            
            if ($parentId) {
                $query->where('parent_id', $parentId);
            }
        }

        return $slug;
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Category $category) {
            // Auto-calculate level and path if parent_id is set
            if ($category->parent_id && !$category->level) {
                $parent = Category::find($category->parent_id);
                if ($parent) {
                    $category->level = $parent->level + 1;
                    $category->path = ($parent->path ?: '') . '/' . $parent->id;
                    $category->save();
                }
            }

            // Ensure unique slug
            if ($category->slug) {
                $originalSlug = $category->slug;
                $counter = 1;
                
                while (Category::where('slug', $category->slug)->where('id', '!=', $category->id)->exists()) {
                    $category->slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
                
                if ($category->slug !== $originalSlug) {
                    $category->save();
                }
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | STATIC HELPER METHODS - Tree Building
    |--------------------------------------------------------------------------
    */

    /**
     * Create a complete category tree structure
     * 
     * Usage: CategoryFactory::createTree(['Motocykle' => ['125cc', '250cc']])
     */
    public static function createTree(array $structure, ?Category $parent = null): array
    {
        $categories = [];

        foreach ($structure as $name => $children) {
            if (is_numeric($name)) {
                // Simple category name
                $category = Category::factory()->create([
                    'name' => $children,
                    'parent_id' => $parent?->id,
                ]);
            } else {
                // Category with children
                $category = Category::factory()->create([
                    'name' => $name,
                    'parent_id' => $parent?->id,
                ]);

                if (is_array($children)) {
                    // Recursively create children
                    $childCategories = static::createTree($children, $category);
                    $category->childrenFromFactory = $childCategories;
                }
            }

            $categories[] = $category;
        }

        return $categories;
    }

    /**
     * Create sample category tree dla PPM-CC-Laravel
     */
    public static function createSampleTree(): array
    {
        return static::createTree([
            'Motocykle i Skutery' => [
                'Motocykle 125cc' => ['Yamaha 125cc', 'Honda 125cc', 'Suzuki 125cc'],
                'Motocykle 250cc' => ['Yamaha 250cc', 'Honda 250cc', 'KTM 250cc'],
                'Skutery' => ['50cc', '125cc', '150cc+']
            ],
            'Części zamienne' => [
                'Silnik' => ['Tłoki', 'Cylindry', 'Uszczelki'],
                'Hamulce' => ['Klocki', 'Tarcze', 'Przewody'],
                'Zawieszenie' => ['Amortyzatory', 'Sprężyny', 'Tuleje']
            ],
            'Odzież motocyklowa' => [
                'Kaski' => ['Integralne', 'Jet', 'Motocross'],
                'Kurtki' => ['Skórzane', 'Tekstylne', 'Przeciwdeszczowe'],
                'Ochrona' => ['Ochraniacze', 'Protektory', 'Kamizelki']
            ]
        ]);
    }
}