<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Visual Description Editor - Template Model (UVE ETAP_07f_P5)
 *
 * Szablon opisu z predefiniowanym ukladem blokow.
 * Moze byc globalny (shop_id = null) lub przypisany do konkretnego sklepu.
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int|null $shop_id
 * @property string|null $category
 * @property array $blocks_json
 * @property string|null $thumbnail_path
 * @property bool $is_default
 * @property int|null $created_by
 * @property string $source_type (import|manual|auto)
 * @property int|null $source_shop_id
 * @property int|null $source_product_id
 * @property string|null $structure_signature MD5 hash for deduplication
 * @property array|null $document_json UVE document structure
 * @property array|null $labels Tags/labels
 * @property array|null $variables Editable template variables
 * @property array|null $css_classes Required CSS classes
 * @property int $usage_count Number of products using this template
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read PrestaShopShop|null $shop
 * @property-read PrestaShopShop|null $sourceShop
 * @property-read Product|null $sourceProduct
 * @property-read User|null $creator
 * @property-read \Illuminate\Database\Eloquent\Collection|ProductDescription[] $productDescriptions
 */
class DescriptionTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'shop_id',
        'category',
        'blocks_json',
        'thumbnail_path',
        'is_default',
        'created_by',
        // UVE fields (ETAP_07f_P5)
        'source_type',
        'source_shop_id',
        'source_product_id',
        'structure_signature',
        'document_json',
        'labels',
        'variables',
        'css_classes',
        'usage_count',
    ];

    protected $casts = [
        'blocks_json' => 'array',
        'document_json' => 'array',
        'labels' => 'array',
        'variables' => 'array',
        'css_classes' => 'array',
        'is_default' => 'boolean',
        'shop_id' => 'integer',
        'source_shop_id' => 'integer',
        'source_product_id' => 'integer',
        'created_by' => 'integer',
        'usage_count' => 'integer',
    ];

    // Source types constants
    public const SOURCE_IMPORT = 'import';
    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_AUTO = 'auto';

    // =====================
    // RELATIONSHIPS
    // =====================

    /**
     * Shop this template belongs to (null = global)
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(PrestaShopShop::class, 'shop_id');
    }

    /**
     * User who created the template
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Product descriptions using this template
     */
    public function productDescriptions(): HasMany
    {
        return $this->hasMany(ProductDescription::class, 'template_id');
    }

    /**
     * UVE: Shop where this template originated (for imports)
     */
    public function sourceShop(): BelongsTo
    {
        return $this->belongsTo(PrestaShopShop::class, 'source_shop_id');
    }

    /**
     * UVE: Product this template was created from (for imports)
     */
    public function sourceProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'source_product_id');
    }

    // =====================
    // SCOPES
    // =====================

    /**
     * Scope: Templates for specific shop (including global)
     */
    public function scopeForShop(Builder $query, ?int $shopId): Builder
    {
        return $query->where(function ($q) use ($shopId) {
            $q->whereNull('shop_id'); // Global templates
            if ($shopId) {
                $q->orWhere('shop_id', $shopId);
            }
        });
    }

    /**
     * Scope: Only global templates
     */
    public function scopeGlobal(Builder $query): Builder
    {
        return $query->whereNull('shop_id');
    }

    /**
     * Scope: Only default templates
     */
    public function scopeDefaults(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope: Created by specific user
     */
    public function scopeCreatedBy(Builder $query, int $userId): Builder
    {
        return $query->where('created_by', $userId);
    }

    /**
     * Scope: By category
     */
    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * UVE Scope: By source type
     */
    public function scopeBySourceType(Builder $query, string $sourceType): Builder
    {
        return $query->where('source_type', $sourceType);
    }

    /**
     * UVE Scope: Only imported templates
     */
    public function scopeImported(Builder $query): Builder
    {
        return $query->where('source_type', self::SOURCE_IMPORT);
    }

    /**
     * UVE Scope: Only auto-generated templates
     */
    public function scopeAutoGenerated(Builder $query): Builder
    {
        return $query->where('source_type', self::SOURCE_AUTO);
    }

    /**
     * UVE Scope: Only manually created templates
     */
    public function scopeManual(Builder $query): Builder
    {
        return $query->where('source_type', self::SOURCE_MANUAL);
    }

    /**
     * UVE Scope: By structure signature (for deduplication)
     */
    public function scopeBySignature(Builder $query, string $signature): Builder
    {
        return $query->where('structure_signature', $signature);
    }

    /**
     * UVE Scope: Templates with specific label
     */
    public function scopeWithLabel(Builder $query, string $label): Builder
    {
        return $query->whereJsonContains('labels', $label);
    }

    // =====================
    // ACCESSORS
    // =====================

    /**
     * Get blocks with fallback to empty array
     * UVE: Preferuje document_json jesli dostepny
     */
    public function getBlocksAttribute(): array
    {
        // UVE format - use document_json
        if (!empty($this->document_json)) {
            return $this->document_json;
        }
        // Legacy format
        return $this->blocks_json ?? [];
    }

    /**
     * UVE: Get document structure
     */
    public function getDocumentAttribute(): ?array
    {
        return $this->document_json;
    }

    /**
     * Get block count
     */
    public function getBlockCountAttribute(): int
    {
        $blocks = $this->blocks;
        return count($blocks);
    }

    /**
     * Check if template is global
     */
    public function getIsGlobalAttribute(): bool
    {
        return $this->shop_id === null;
    }

    /**
     * UVE: Check if template uses new UVE format
     */
    public function getIsUveFormatAttribute(): bool
    {
        return !empty($this->document_json);
    }

    /**
     * UVE: Check if template was imported
     */
    public function getIsImportedAttribute(): bool
    {
        return $this->source_type === self::SOURCE_IMPORT;
    }

    /**
     * UVE: Check if template was auto-generated
     */
    public function getIsAutoGeneratedAttribute(): bool
    {
        return $this->source_type === self::SOURCE_AUTO;
    }

    /**
     * UVE: Get labels as array
     */
    public function getLabelsListAttribute(): array
    {
        return $this->labels ?? [];
    }

    // =====================
    // METHODS
    // =====================

    /**
     * Duplicate template with new name
     */
    public function duplicate(string $newName, ?int $newShopId = null): self
    {
        $duplicate = $this->replicate([
            'is_default', // Don't copy is_default flag
        ]);

        $duplicate->name = $newName;
        $duplicate->shop_id = $newShopId ?? $this->shop_id;
        $duplicate->is_default = false;
        $duplicate->created_by = auth()->id();
        $duplicate->thumbnail_path = null; // Thumbnail needs regeneration
        $duplicate->save();

        return $duplicate;
    }

    /**
     * Export template as array for JSON export
     */
    public function export(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'blocks' => $this->blocks_json,
            'exported_at' => now()->toIso8601String(),
            'version' => '1.0',
        ];
    }

    /**
     * Import template from array
     */
    public static function import(array $data, ?int $shopId = null, ?int $userId = null): self
    {
        return self::create([
            'name' => $data['name'] . ' (Import)',
            'description' => $data['description'] ?? null,
            'shop_id' => $shopId,
            'blocks_json' => $data['blocks'] ?? [],
            'is_default' => false,
            'created_by' => $userId ?? auth()->id(),
        ]);
    }

    /**
     * Get usage count (how many products use this template)
     */
    public function getUsageCount(): int
    {
        return $this->productDescriptions()->count();
    }

    // =====================
    // UVE METHODS
    // =====================

    /**
     * UVE: Generate structure signature for deduplication
     */
    public function generateSignature(): string
    {
        $document = $this->document_json ?? $this->blocks_json ?? [];

        // Extract structure without content (for deduplication)
        $structure = $this->extractStructure($document);

        return md5(json_encode($structure));
    }

    /**
     * UVE: Extract structural elements (types, layout) without content
     */
    protected function extractStructure(array $blocks): array
    {
        return array_map(function ($block) {
            $structure = ['type' => $block['type'] ?? 'unknown'];

            if (isset($block['document']['root'])) {
                $structure['elements'] = $this->extractElementStructure($block['document']['root']);
            }

            return $structure;
        }, $blocks);
    }

    /**
     * UVE: Recursively extract element structure
     */
    protected function extractElementStructure(array $element): array
    {
        $structure = ['type' => $element['type'] ?? 'unknown'];

        if (!empty($element['children'])) {
            $structure['children'] = array_map(
                fn($child) => $this->extractElementStructure($child),
                $element['children']
            );
        }

        return $structure;
    }

    /**
     * UVE: Check if similar template exists
     */
    public static function findSimilar(string $signature, ?int $shopId = null): ?self
    {
        return self::bySignature($signature)
            ->forShop($shopId)
            ->first();
    }

    /**
     * UVE: Increment usage count
     */
    public function incrementUsage(): self
    {
        $this->increment('usage_count');
        return $this;
    }

    /**
     * UVE: Add label to template
     */
    public function addLabel(string $label): self
    {
        $labels = $this->labels ?? [];
        if (!in_array($label, $labels)) {
            $labels[] = $label;
            $this->labels = $labels;
            $this->save();
        }
        return $this;
    }

    /**
     * UVE: Remove label from template
     */
    public function removeLabel(string $label): self
    {
        $labels = $this->labels ?? [];
        $labels = array_values(array_filter($labels, fn($l) => $l !== $label));
        $this->labels = $labels;
        $this->save();
        return $this;
    }

    /**
     * UVE: Update document and regenerate signature
     */
    public function updateDocument(array $document): self
    {
        $this->document_json = $document;
        $this->structure_signature = $this->generateSignature();
        $this->save();
        return $this;
    }

    /**
     * UVE: Create template from product description
     */
    public static function createFromProductDescription(
        ProductDescription $description,
        string $name,
        ?int $shopId = null
    ): self {
        $template = self::create([
            'name' => $name,
            'shop_id' => $shopId,
            'source_type' => self::SOURCE_IMPORT,
            'source_shop_id' => $description->shop_id,
            'source_product_id' => $description->product_id,
            'document_json' => $description->blocks_v2 ?? null,
            'blocks_json' => $description->blocks_json ?? [],
            'created_by' => auth()->id(),
        ]);

        $template->structure_signature = $template->generateSignature();
        $template->labels = ['auto-generated'];
        $template->save();

        return $template;
    }
}
