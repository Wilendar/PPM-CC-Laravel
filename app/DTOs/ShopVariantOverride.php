<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\ProductVariant;
use Livewire\Wireable;

/**
 * DTO for shop-specific variant overrides stored in product_shop_data.attribute_mappings
 *
 * Part of per-shop variant isolation system (ETAP_05b FAZA 5)
 * Pattern consistent with features system (ProductFormFeatures)
 *
 * Implements Wireable for Livewire 3.x serialization support
 */
class ShopVariantOverride implements Wireable
{
    public function __construct(
        public int $defaultVariantId,
        public string $sku,
        public string $name,
        public bool $isActive,
        public bool $isDefault,
        public array $attributes,      // [typeId => valueId]
        public ?array $prices = null,
        public ?array $stock = null,
        public array $mediaIds = [],
        public int $position = 0
    ) {}

    /**
     * Create override from default ProductVariant model
     */
    public static function fromDefaultVariant(ProductVariant $variant, ?string $skuSuffix = null): self
    {
        $sku = $variant->sku;
        if ($skuSuffix) {
            $sku .= $skuSuffix;
        }

        return new self(
            defaultVariantId: $variant->id,
            sku: $sku,
            name: $variant->name,
            isActive: $variant->is_active ?? true,
            isDefault: $variant->is_default ?? false,
            attributes: $variant->attributes->mapWithKeys(
                fn($attr) => [$attr->attribute_type_id => $attr->value_id]
            )->toArray(),
            mediaIds: $variant->images->pluck('id')->toArray(),
            position: $variant->position ?? 0
        );
    }

    /**
     * Create override from JSON array (loaded from attribute_mappings)
     */
    public static function fromArray(int $variantId, array $data): self
    {
        return new self(
            defaultVariantId: $variantId,
            sku: $data['sku'] ?? '',
            name: $data['name'] ?? '',
            isActive: $data['is_active'] ?? true,
            isDefault: $data['is_default'] ?? false,
            attributes: $data['attributes'] ?? [],
            prices: $data['prices'] ?? null,
            stock: $data['stock'] ?? null,
            mediaIds: $data['media_ids'] ?? [],
            position: $data['position'] ?? 0
        );
    }

    /**
     * Convert to array for JSON storage
     */
    public function toArray(): array
    {
        return [
            'sku' => $this->sku,
            'name' => $this->name,
            'is_active' => $this->isActive,
            'is_default' => $this->isDefault,
            'attributes' => $this->attributes,
            'prices' => $this->prices,
            'stock' => $this->stock,
            'media_ids' => $this->mediaIds,
            'position' => $this->position,
        ];
    }

    /**
     * Check if override is identical to default variant data
     */
    public function isIdenticalTo(array $defaultData): bool
    {
        return (
            $this->sku === ($defaultData['sku'] ?? '') &&
            $this->name === ($defaultData['name'] ?? '') &&
            $this->isActive === ($defaultData['is_active'] ?? true) &&
            $this->attributes === ($defaultData['attributes'] ?? [])
        );
    }

    /**
     * Create a modified copy with updated fields
     */
    public function withUpdates(array $updates): self
    {
        return new self(
            defaultVariantId: $this->defaultVariantId,
            sku: $updates['sku'] ?? $this->sku,
            name: $updates['name'] ?? $this->name,
            isActive: $updates['is_active'] ?? $this->isActive,
            isDefault: $updates['is_default'] ?? $this->isDefault,
            attributes: $updates['attributes'] ?? $this->attributes,
            prices: $updates['prices'] ?? $this->prices,
            stock: $updates['stock'] ?? $this->stock,
            mediaIds: $updates['media_ids'] ?? $this->mediaIds,
            position: $updates['position'] ?? $this->position
        );
    }

    /*
    |--------------------------------------------------------------------------
    | LIVEWIRE WIREABLE INTERFACE
    |--------------------------------------------------------------------------
    */

    /**
     * Serialize DTO for Livewire state (required by Wireable)
     */
    public function toLivewire(): array
    {
        return [
            'defaultVariantId' => $this->defaultVariantId,
            'sku' => $this->sku,
            'name' => $this->name,
            'isActive' => $this->isActive,
            'isDefault' => $this->isDefault,
            'attributes' => $this->attributes,
            'prices' => $this->prices,
            'stock' => $this->stock,
            'mediaIds' => $this->mediaIds,
            'position' => $this->position,
        ];
    }

    /**
     * Deserialize DTO from Livewire state (required by Wireable)
     */
    public static function fromLivewire($value): self
    {
        return new self(
            defaultVariantId: $value['defaultVariantId'],
            sku: $value['sku'],
            name: $value['name'],
            isActive: $value['isActive'],
            isDefault: $value['isDefault'],
            attributes: $value['attributes'] ?? [],
            prices: $value['prices'] ?? null,
            stock: $value['stock'] ?? null,
            mediaIds: $value['mediaIds'] ?? [],
            position: $value['position'] ?? 0
        );
    }
}
