<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Compatibility Source Model
 *
 * Źródło informacji o dopasowaniu (Manufacturer, TecDoc, Manual)
 *
 * @property int $id
 * @property string $code Unikalny kod (manufacturer, tecdoc, manual)
 * @property string $name Nazwa źródła
 * @property string $trust_level Poziom zaufania (low, medium, high, verified)
 * @property bool $is_active Czy aktywne
 * @property int|null $position Kolejność wyświetlania
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class CompatibilitySource extends Model
{
    use HasFactory;

    /**
     * Table name
     */
    protected $table = 'compatibility_sources';

    /**
     * Fillable attributes
     */
    protected $fillable = [
        'code',
        'name',
        'trust_level',
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
     * Trust level enum values
     */
    public const TRUST_LOW = 'low';
    public const TRUST_MEDIUM = 'medium';
    public const TRUST_HIGH = 'high';
    public const TRUST_VERIFIED = 'verified';

    /**
     * Source codes
     */
    public const CODE_MANUFACTURER = 'manufacturer';
    public const CODE_TECDOC = 'tecdoc';
    public const CODE_MANUAL = 'manual';
    public const CODE_USER = 'user';

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Vehicle compatibility records from this source
     */
    public function vehicleCompatibility(): HasMany
    {
        return $this->hasMany(VehicleCompatibility::class, 'compatibility_source_id');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Only active sources
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
     * Scope: Filter by trust level
     */
    public function scopeByTrustLevel($query, string $level)
    {
        return $query->where('trust_level', $level);
    }

    /**
     * Scope: Ordered by id (position column does not exist in this table)
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('id', 'asc');
    }

    /*
    |--------------------------------------------------------------------------
    | METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Get trust level badge color
     */
    public function getTrustBadgeColor(): string
    {
        return match($this->trust_level) {
            self::TRUST_VERIFIED => 'success',
            self::TRUST_HIGH => 'info',
            self::TRUST_MEDIUM => 'warning',
            self::TRUST_LOW => 'secondary',
            default => 'secondary',
        };
    }

    /**
     * Get trust level display name
     */
    public function getTrustLevelName(): string
    {
        return match($this->trust_level) {
            self::TRUST_VERIFIED => 'Zweryfikowane',
            self::TRUST_HIGH => 'Wysoka',
            self::TRUST_MEDIUM => 'Średnia',
            self::TRUST_LOW => 'Niska',
            default => 'Nieznana',
        };
    }

    /**
     * Check if source is highly trusted
     */
    public function isHighlyTrusted(): bool
    {
        return in_array($this->trust_level, [self::TRUST_HIGH, self::TRUST_VERIFIED]);
    }

    /**
     * Get available trust levels
     */
    public static function getTrustLevels(): array
    {
        return [
            self::TRUST_LOW => 'Niska',
            self::TRUST_MEDIUM => 'Średnia',
            self::TRUST_HIGH => 'Wysoka',
            self::TRUST_VERIFIED => 'Zweryfikowana',
        ];
    }
}
