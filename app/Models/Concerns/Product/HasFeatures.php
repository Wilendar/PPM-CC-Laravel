<?php

namespace App\Models\Concerns\Product;

use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\ProductFeature;
use App\Models\Media;
use App\Models\FileUpload;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * HasFeatures Trait - Product Features/Attributes/Media Management (STUB for ETAP_05a)
 *
 * Responsibility: EAV system, media gallery, automotive attributes
 *
 * Features (to be implemented in ETAP_05a):
 * - Product attributes (EAV system: Model, Oryginał, Zamiennik)
 * - Media gallery (max 20 zdjęć)
 * - File attachments (instrukcje, certyfikaty)
 * - Automotive compatibility attributes
 *
 * Architecture: SKU-first pattern preserved
 * Performance: Strategic eager loading z gallery order
 * Integration: PrestaShop image/feature mapping ready
 *
 * @package App\Models\Concerns\Product
 * @version 1.0
 * @since ETAP_05a SEKCJA 0 - Product.php Refactoring
 */
trait HasFeatures
{
    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS - Features/Media Relations
    |--------------------------------------------------------------------------
    */

    /**
     * Product attributes relationship (1:many via ProductAttributeValue) - FAZA C ✅ IMPLEMENTED
     *
     * EAV System: Model, Oryginał, Zamiennik, etc.
     * Performance: Optimized dla automotive compatibility
     * Inheritance: Master product values inherited by variants
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attributeValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class)
                    ->whereNull('product_variant_id') // Only master product attributes
                    ->with('attribute')
                    ->valid();
    }

    /**
     * Product features relationship (1:many) - ETAP_05a FAZA 2 ✅ IMPLEMENTED
     *
     * Features System: Cechy produktu (Moc, Pojemność, Kolor obudowy, etc.)
     * Performance: Eager loading ready z feature type + value
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function features(): HasMany
    {
        return $this->hasMany(ProductFeature::class, 'product_id', 'id')
                    ->with(['featureType', 'featureValue']);
    }

    /**
     * Alias for features() - used by PrestaShop sync services
     *
     * ETAP_07e FAZA 4.4 - Feature Sync to PrestaShop
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function productFeatures(): HasMany
    {
        return $this->hasMany(ProductFeature::class, 'product_id', 'id');
    }

    /**
     * Product media/images polymorphic relationship (1:many) - FAZA C ✅ IMPLEMENTED
     *
     * Obsługa: max 20 zdjęć na produkt, różne rozmiary, watermarki, optymalizacja
     * Performance: Strategic eager loading z gallery order
     * Integration: PrestaShop multi-store mapping ready
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable')
                    ->galleryOrder(); // Uses custom scope from Media model
    }

    /**
     * Product files/documents polymorphic relationship (1:many) - FAZA C ✅ IMPLEMENTED
     *
     * Obsługa: instrukcje, certyfikaty, dokumenty techniczne
     * Security: Access level control per file
     * Integration: Container documentation system ready
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function files(): MorphMany
    {
        return $this->morphMany(FileUpload::class, 'uploadable')
                    ->active()
                    ->orderBy('file_type', 'asc')
                    ->orderBy('original_name', 'asc');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS - Computed Feature Attributes
    |--------------------------------------------------------------------------
    */

    /**
     * Get primary image URL - FAZA C ✅ IMPLEMENTED
     *
     * Business Logic: Real image system z fallback do placeholder
     * Performance: Single query optimization dla primary image
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function primaryImage(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                $primaryMedia = $this->media()->primary()->active()->first();

                if ($primaryMedia) {
                    return $primaryMedia->url;
                }

                // Fallback to first available image
                $firstMedia = $this->media()->active()->first();
                if ($firstMedia) {
                    return $firstMedia->url;
                }

                // Final fallback to placeholder
                return $this->getPlaceholderImage();
            }
        );
    }

    /**
     * Get all media collection - FAZA C ✅ IMPLEMENTED
     *
     * Business Logic: Complete gallery dla product
     * Performance: Eager loaded relationship
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function mediaGallery(): Attribute
    {
        return Attribute::make(
            get: function (): \Illuminate\Database\Eloquent\Collection {
                return $this->media()->active()->get();
            }
        );
    }

    /**
     * Get all attribute values formatted - FAZA C ✅ IMPLEMENTED
     *
     * Business Logic: EAV values dla product display
     * Performance: Optimized dla form generation
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function attributesFormatted(): Attribute
    {
        return Attribute::make(
            get: function (): array {
                $formatted = [];

                $attributeValues = $this->attributeValues()->with('attribute')->get();

                foreach ($attributeValues as $value) {
                    $formatted[$value->attribute->code] = [
                        'name' => $value->attribute->name,
                        'value' => $value->formatted_value,
                        'type' => $value->attribute->attribute_type,
                        'group' => $value->attribute->display_group,
                    ];
                }

                return $formatted;
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | BUSINESS METHODS - Feature Operations (STUB - some implemented)
    |--------------------------------------------------------------------------
    */

    /**
     * Get placeholder image for product
     *
     * Business Logic: Fallback system dla produktów bez zdjęć
     * Performance: Static assets dla fast loading
     *
     * @return string
     */
    private function getPlaceholderImage(): string
    {
        // Different placeholders based on product type
        $placeholders = [
            'pojazd' => '/images/placeholders/vehicle-placeholder.jpg',
            'czesc-zamiennicza' => '/images/placeholders/spare-part-placeholder.jpg',
            'odziez' => '/images/placeholders/clothing-placeholder.jpg',
            'inne' => '/images/placeholders/default-placeholder.jpg',
        ];

        $typeSlug = $this->productType?->slug ?? 'inne';
        return $placeholders[$typeSlug] ?? $placeholders['inne'];
    }

    /**
     * Add media to product
     *
     * @param string $filePath
     * @param array $metadata
     * @return \App\Models\Media
     */
    public function addMedia(string $filePath, array $metadata = []): Media
    {
        $media = new Media();
        $media->file_path = $filePath;
        $media->file_name = $metadata['file_name'] ?? basename($filePath);
        $media->original_name = $metadata['original_name'] ?? $media->file_name;
        $media->file_size = $metadata['file_size'] ?? (file_exists($filePath) ? filesize($filePath) : 0);
        $media->mime_type = $metadata['mime_type'] ?? 'image/jpeg';
        $media->width = $metadata['width'] ?? null;
        $media->height = $metadata['height'] ?? null;
        $media->alt_text = $metadata['alt_text'] ?? null;
        $media->sort_order = $metadata['sort_order'] ?? $this->media()->count();
        $media->is_primary = $metadata['is_primary'] ?? ($this->media()->count() === 0);

        $this->media()->save($media);

        return $media;
    }

    /**
     * Set attribute value using EAV system (renamed to avoid Laravel Model conflict)
     *
     * @param string|int $attributeCode
     * @param mixed $value
     * @return \App\Models\ProductAttributeValue
     */
    public function setProductAttributeValue(string|int $attributeCode, mixed $value): ProductAttributeValue
    {
        // Find attribute by code or ID
        $attribute = is_numeric($attributeCode)
            ? ProductAttribute::find($attributeCode)
            : ProductAttribute::where('code', $attributeCode)->first();

        if (!$attribute) {
            throw new \InvalidArgumentException("Attribute not found: {$attributeCode}");
        }

        // Find or create attribute value
        $attributeValue = $this->attributeValues()
            ->where('attribute_id', $attribute->id)
            ->first();

        if (!$attributeValue) {
            $attributeValue = new ProductAttributeValue();
            $attributeValue->product_id = $this->id;
            $attributeValue->attribute_id = $attribute->id;
        }

        $attributeValue->value = $value;
        $attributeValue->save();

        return $attributeValue;
    }

    /**
     * Get attribute value using EAV system (renamed to avoid Laravel Model conflict)
     *
     * @param string|int $attributeCode
     * @return mixed
     */
    public function getProductAttributeValue(string|int $attributeCode): mixed
    {
        // Find attribute by code or ID
        $attribute = is_numeric($attributeCode)
            ? ProductAttribute::find($attributeCode)
            : ProductAttribute::where('code', $attributeCode)->first();

        if (!$attribute) {
            return null;
        }

        $attributeValue = $this->attributeValues()
            ->where('attribute_id', $attribute->id)
            ->first();

        return $attributeValue?->effective_value;
    }

    /**
     * Check if product has specific product attribute (renamed to avoid Laravel Model conflict)
     *
     * @param string|int $attributeCode
     * @return bool
     */
    public function hasProductAttribute(string|int $attributeCode): bool
    {
        return $this->getProductAttributeValue($attributeCode) !== null;
    }

    /**
     * Get all automotive compatibility attributes (STUB for ETAP_05a)
     *
     * TODO: Expand in ETAP_05a with full compatibility system
     *
     * @return array
     */
    public function getAutomotiveAttributes(): array
    {
        $automotive = [];

        // Vehicle Model compatibility
        $models = $this->getProductAttributeValue('model');
        if ($models) {
            $automotive['models'] = is_array($models) ? $models : [$models];
        }

        // OEM part numbers
        $original = $this->getProductAttributeValue('original');
        if ($original) {
            $automotive['original'] = $original;
        }

        // Replacement part numbers
        $replacement = $this->getProductAttributeValue('replacement');
        if ($replacement) {
            $automotive['replacement'] = $replacement;
        }

        return $automotive;
    }

    /**
     * Get all product features - ETAP_05a FAZA 2 ✅ IMPLEMENTED
     *
     * Business Logic: Cechy produktu dla display
     * Performance: Eager loaded relationships
     *
     * @return \Illuminate\Support\Collection
     */
    public function getFeatures(): \Illuminate\Support\Collection
    {
        return $this->features()
                    ->with('featureType', 'featureValue')
                    ->get();
    }

    /**
     * Get specific feature value by code - ETAP_05a FAZA 2 ✅ IMPLEMENTED
     *
     * @param string $featureTypeCode
     * @return mixed
     */
    public function getFeatureValue(string $featureTypeCode): mixed
    {
        $feature = $this->features()
            ->whereHas('featureType', function ($query) use ($featureTypeCode) {
                $query->where('code', $featureTypeCode);
            })
            ->first();

        return $feature ? $feature->getValue() : null;
    }
}
