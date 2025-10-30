<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Compatibility Attribute Model
 *
 * Typ dopasowania części do pojazdu (Original, Replacement, Performance)
 *
 * @property int $id
 * @property string $code Unikalny kod (original, replacement, performance)
 * @property string $name Nazwa atrybutu
 * @property string|null $badge_color Kolor badge (success, warning, info)
 * @property bool $is_active Czy aktywny
 * @property int|null $position Kolejność wyświetlania
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class CompatibilityAttribute extends Model
{
    use HasFactory;

    /**
     * Table name
     */
    protected $table = 'compatibility_attributes';

    /**
     * Fillable attributes
     */
    protected $fillable = [
        'code',
        'name',
        'badge_color',
        'is_active',
        'position',
    ];

    /**
     * Attribute casts
     */
    protected $casts = [
        'is_active' => 'boolean',
        'position' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Attribute type codes
     */
    public const CODE_ORIGINAL = 'original';
    public const CODE_REPLACEMENT = 'replacement';
    public const CODE_PERFORMANCE = 'performance';
    public const CODE_UNIVERSAL = 'universal';

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Vehicle compatibility records using this attribute
     */
    public function vehicleCompatibility(): HasMany
    {
        return $this->hasMany(VehicleCompatibility::class, 'compatibility_attribute_id');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Only active attributes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Find by code
     */
    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }

    /**
     * Scope: Ordered by position
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('position', 'asc')->orderBy('id', 'asc');
    }

    /*
    |--------------------------------------------------------------------------
    | METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Get badge HTML
     */
    public function getBadgeHtml(): string
    {
        $color = $this->badge_color ?? 'secondary';
        return sprintf(
            '<span class="badge badge-%s">%s</span>',
            $color,
            htmlspecialchars($this->name)
        );
    }

    /**
     * Check if this is original fit
     */
    public function isOriginal(): bool
    {
        return $this->code === self::CODE_ORIGINAL;
    }

    /**
     * Check if this is replacement part
     */
    public function isReplacement(): bool
    {
        return $this->code === self::CODE_REPLACEMENT;
    }

    /**
     * Check if this is performance upgrade
     */
    public function isPerformance(): bool
    {
        return $this->code === self::CODE_PERFORMANCE;
    }
}
