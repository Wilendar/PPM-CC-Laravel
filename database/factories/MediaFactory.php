<?php

namespace Database\Factories;

use App\Models\Media;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * MediaFactory - FAZA C: Factory dla generowania test danych mediów
 * 
 * Generuje realistyczne dane dla testów:
 * - Polymorphic relationships (Product/ProductVariant)
 * - Realistic file paths i metadata
 * - Image dimensions i MIME types
 * - Gallery logic (primary images, sort order)
 * - PrestaShop mapping examples
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Media>
 * @since FAZA C - Media & Relations Implementation
 */
class MediaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Media::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fileName = $this->faker->uuid() . '.jpg';
        $originalName = $this->faker->words(3, true) . '.jpg';
        
        return [
            'mediable_type' => Product::class, // Default to Product, can be overridden
            'mediable_id' => Product::factory(),
            
            // File information
            'file_name' => $fileName,
            'original_name' => $originalName,
            'file_path' => 'media/products/' . date('Y/m/d') . '/' . $fileName,
            'file_size' => $this->faker->numberBetween(50000, 5000000), // 50KB - 5MB
            
            // Image metadata
            'mime_type' => $this->faker->randomElement([
                'image/jpeg',
                'image/png',
                'image/webp',
                'image/gif',
            ]),
            'width' => $this->faker->numberBetween(400, 2000),
            'height' => $this->faker->numberBetween(400, 2000),
            'alt_text' => $this->faker->optional(0.7)->sentence(6),
            
            // Gallery settings
            'sort_order' => $this->faker->numberBetween(0, 10),
            'is_primary' => false, // Will be set by primaryImage() state
            
            // PrestaShop mapping (optional)
            'prestashop_mapping' => $this->faker->optional(0.3)->passthrough([
                'shop_1' => [
                    'image_id' => $this->faker->numberBetween(1, 1000),
                    'position' => $this->faker->numberBetween(1, 5),
                    'cover' => $this->faker->boolean(20),
                    'synced_at' => $this->faker->dateTimeThisYear()->toISOString(),
                ],
            ]),
            
            // Sync status
            'sync_status' => $this->faker->randomElement(['pending', 'synced', 'error', 'ignored']),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that this is a primary image.
     */
    public function primaryImage(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
            'sort_order' => 0,
            'alt_text' => $this->faker->words(4, true) . ' - główne zdjęcie',
        ]);
    }

    /**
     * Indicate that this media belongs to a ProductVariant.
     */
    public function forVariant(ProductVariant $variant = null): static
    {
        $variant = $variant ?? ProductVariant::factory()->create();
        
        return $this->state(fn (array $attributes) => [
            'mediable_type' => ProductVariant::class,
            'mediable_id' => $variant->id,
            'file_path' => 'media/variants/' . date('Y/m/d') . '/' . $attributes['file_name'],
        ]);
    }

    /**
     * Indicate that this media belongs to a Product.
     */
    public function forProduct(Product $product = null): static
    {
        $product = $product ?? Product::factory()->create();
        
        return $this->state(fn (array $attributes) => [
            'mediable_type' => Product::class,
            'mediable_id' => $product->id,
            'file_path' => 'media/products/' . date('Y/m/d') . '/' . $attributes['file_name'],
        ]);
    }

    /**
     * Indicate that this is a high-resolution image.
     */
    public function highResolution(): static
    {
        return $this->state(fn (array $attributes) => [
            'width' => $this->faker->numberBetween(1920, 4000),
            'height' => $this->faker->numberBetween(1920, 4000),
            'file_size' => $this->faker->numberBetween(2000000, 8000000), // 2MB - 8MB
        ]);
    }

    /**
     * Indicate that this is a thumbnail image.
     */
    public function thumbnail(): static
    {
        return $this->state(fn (array $attributes) => [
            'width' => $this->faker->numberBetween(150, 400),
            'height' => $this->faker->numberBetween(150, 400),
            'file_size' => $this->faker->numberBetween(10000, 100000), // 10KB - 100KB
        ]);
    }

    /**
     * Indicate that this media is synced to PrestaShop.
     */
    public function syncedToPrestaShop(int $shopId = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'sync_status' => 'synced',
            'prestashop_mapping' => [
                "shop_{$shopId}" => [
                    'image_id' => $this->faker->numberBetween(1, 10000),
                    'position' => $this->faker->numberBetween(1, 20),
                    'cover' => $this->faker->boolean(10),
                    'synced_at' => now()->subMinutes($this->faker->numberBetween(1, 1440))->toISOString(),
                    'status' => 'synced',
                ],
            ],
        ]);
    }

    /**
     * Indicate that this media has sync error.
     */
    public function withSyncError(): static
    {
        return $this->state(fn (array $attributes) => [
            'sync_status' => 'error',
            'prestashop_mapping' => [
                'shop_1' => [
                    'error_at' => now()->subHours($this->faker->numberBetween(1, 24))->toISOString(),
                    'error_message' => $this->faker->randomElement([
                        'File upload failed',
                        'Invalid image format',
                        'Network timeout',
                        'Permission denied',
                    ]),
                    'status' => 'error',
                ],
            ],
        ]);
    }

    /**
     * Indicate that this is a PNG image.
     */
    public function png(): static
    {
        return $this->state(fn (array $attributes) => [
            'mime_type' => 'image/png',
            'file_name' => str_replace('.jpg', '.png', $attributes['file_name']),
            'original_name' => str_replace('.jpg', '.png', $attributes['original_name']),
            'file_path' => str_replace('.jpg', '.png', $attributes['file_path']),
        ]);
    }

    /**
     * Indicate that this is a WebP image.
     */
    public function webp(): static
    {
        return $this->state(fn (array $attributes) => [
            'mime_type' => 'image/webp',
            'file_name' => str_replace('.jpg', '.webp', $attributes['file_name']),
            'original_name' => str_replace('.jpg', '.webp', $attributes['original_name']),
            'file_path' => str_replace('.jpg', '.webp', $attributes['file_path']),
            'file_size' => intval($attributes['file_size'] * 0.7), // WebP is typically smaller
        ]);
    }

    /**
     * Indicate that this media is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'sync_status' => 'ignored',
        ]);
    }

    /**
     * Create a complete gallery for a product (primary + additional images).
     */
    public function gallery(int $count = 5): array
    {
        $gallery = [];
        
        // Create primary image
        $gallery[] = $this->primaryImage();
        
        // Create additional images
        for ($i = 1; $i < $count; $i++) {
            $gallery[] = $this->state([
                'sort_order' => $i,
                'is_primary' => false,
            ]);
        }
        
        return $gallery;
    }

    /**
     * Configure the factory after making.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Media $media) {
            // Ensure realistic aspect ratios
            if ($media->width && $media->height) {
                $aspectRatio = $media->width / $media->height;
                
                // Adjust height based on common aspect ratios
                if ($aspectRatio < 0.5) {
                    $media->height = intval($media->width * 1.5); // More reasonable ratio
                } elseif ($aspectRatio > 3) {
                    $media->width = intval($media->height * 2); // More reasonable ratio
                }
            }
        });
    }
}