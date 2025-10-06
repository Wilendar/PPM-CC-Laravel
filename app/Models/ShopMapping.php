<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Shop Mapping Model
 *
 * Maps PPM entities to PrestaShop entities for each shop
 *
 * Mapping types:
 * - category: PPM Category ID → PrestaShop category_id
 * - attribute: PPM Attribute → PrestaShop attribute_id
 * - feature: PPM Feature → PrestaShop feature_id
 * - warehouse: PPM Warehouse ID → PrestaShop warehouse_id
 * - price_group: PPM Price Group ID → PrestaShop customer_group_id
 * - tax_rule: PPM Tax Rule → PrestaShop tax_rule_id
 *
 * @property int $id
 * @property int $shop_id
 * @property string $mapping_type category|attribute|feature|warehouse|price_group|tax_rule
 * @property string $ppm_value PPM entity value (ID or name)
 * @property int $prestashop_id PrestaShop entity ID
 * @property string|null $prestashop_value PrestaShop entity value (optional label)
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read PrestaShopShop $shop
 */
class ShopMapping extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'shop_mappings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'shop_id',
        'mapping_type',
        'ppm_value',
        'prestashop_id',
        'prestashop_value',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'prestashop_id' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Mapping type constants
     */
    public const TYPE_CATEGORY = 'category';
    public const TYPE_ATTRIBUTE = 'attribute';
    public const TYPE_FEATURE = 'feature';
    public const TYPE_WAREHOUSE = 'warehouse';
    public const TYPE_PRICE_GROUP = 'price_group';
    public const TYPE_TAX_RULE = 'tax_rule';

    /**
     * Get the shop that this mapping belongs to
     *
     * @return BelongsTo
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(PrestaShopShop::class, 'shop_id');
    }

    /**
     * Scope: Filter by mapping type
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('mapping_type', $type);
    }

    /**
     * Scope: Category mappings
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCategories($query)
    {
        return $query->where('mapping_type', self::TYPE_CATEGORY);
    }

    /**
     * Scope: Attribute mappings
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAttributes($query)
    {
        return $query->where('mapping_type', self::TYPE_ATTRIBUTE);
    }

    /**
     * Scope: Feature mappings
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFeatures($query)
    {
        return $query->where('mapping_type', self::TYPE_FEATURE);
    }

    /**
     * Scope: Warehouse mappings
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWarehouses($query)
    {
        return $query->where('mapping_type', self::TYPE_WAREHOUSE);
    }

    /**
     * Scope: Price group mappings
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePriceGroups($query)
    {
        return $query->where('mapping_type', self::TYPE_PRICE_GROUP);
    }

    /**
     * Scope: Tax rule mappings
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTaxRules($query)
    {
        return $query->where('mapping_type', self::TYPE_TAX_RULE);
    }

    /**
     * Scope: Filter by shop
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $shopId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForShop($query, int $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    /**
     * Scope: Filter by PPM value
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByPpmValue($query, string $value)
    {
        return $query->where('ppm_value', $value);
    }

    /**
     * Scope: Active mappings
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Inactive mappings
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Find mapping for PPM value
     *
     * @param int $shopId
     * @param string $type
     * @param string $ppmValue
     * @return ShopMapping|null
     */
    public static function findMapping(int $shopId, string $type, string $ppmValue): ?ShopMapping
    {
        return static::where('shop_id', $shopId)
            ->where('mapping_type', $type)
            ->where('ppm_value', $ppmValue)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get PrestaShop ID for PPM value
     *
     * @param int $shopId
     * @param string $type
     * @param string $ppmValue
     * @return int|null
     */
    public static function getPrestaShopId(int $shopId, string $type, string $ppmValue): ?int
    {
        $mapping = static::findMapping($shopId, $type, $ppmValue);

        return $mapping ? $mapping->prestashop_id : null;
    }

    /**
     * Create or update mapping
     *
     * @param int $shopId
     * @param string $type
     * @param string $ppmValue
     * @param int $prestashopId
     * @param string|null $prestashopValue
     * @return ShopMapping
     */
    public static function createOrUpdateMapping(
        int $shopId,
        string $type,
        string $ppmValue,
        int $prestashopId,
        ?string $prestashopValue = null
    ): ShopMapping {
        return static::updateOrCreate(
            [
                'shop_id' => $shopId,
                'mapping_type' => $type,
                'ppm_value' => $ppmValue,
            ],
            [
                'prestashop_id' => $prestashopId,
                'prestashop_value' => $prestashopValue,
                'is_active' => true,
            ]
        );
    }

    /**
     * Activate mapping
     *
     * @return bool
     */
    public function activate(): bool
    {
        return $this->update(['is_active' => true]);
    }

    /**
     * Deactivate mapping
     *
     * @return bool
     */
    public function deactivate(): bool
    {
        return $this->update(['is_active' => false]);
    }

    /**
     * Check if mapping is for category
     *
     * @return bool
     */
    public function isCategory(): bool
    {
        return $this->mapping_type === self::TYPE_CATEGORY;
    }

    /**
     * Check if mapping is for price group
     *
     * @return bool
     */
    public function isPriceGroup(): bool
    {
        return $this->mapping_type === self::TYPE_PRICE_GROUP;
    }

    /**
     * Check if mapping is for warehouse
     *
     * @return bool
     */
    public function isWarehouse(): bool
    {
        return $this->mapping_type === self::TYPE_WAREHOUSE;
    }
}
