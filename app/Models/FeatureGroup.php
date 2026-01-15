<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Feature Group Model
 *
 * ETAP_07e FAZA 1.2.2 - Grupa cech produktu (Silnik, Wymiary, Hamulce, etc.)
 *
 * @property int $id
 * @property string $code Unikalny kod grupy (silnik, wymiary)
 * @property string $name Nazwa grupy
 * @property string|null $name_pl Polska nazwa wyswietlana
 * @property string|null $icon Ikona (engine, ruler, wheel)
 * @property string|null $color Kolor Tailwind (orange, blue)
 * @property int $sort_order Kolejnosc wyswietlania
 * @property string|null $vehicle_type_filter Filtr typu pojazdu (elektryczne/spalinowe)
 * @property string|null $description Opis grupy
 * @property bool $is_active Czy aktywna
 * @property bool $is_collapsible Czy zwijan (accordion)
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \Illuminate\Database\Eloquent\Collection|FeatureType[] $featureTypes
 */
class FeatureGroup extends Model
{
    use HasFactory;

    protected $table = 'feature_groups';

    protected $fillable = [
        'code',
        'name',
        'name_pl',
        'icon',
        'color',
        'sort_order',
        'vehicle_type_filter',
        'description',
        'is_active',
        'is_collapsible',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'is_collapsible' => 'boolean',
    ];

    /**
     * Predefined group codes
     */
    public const GROUP_IDENTYFIKACJA = 'identyfikacja';
    public const GROUP_SILNIK = 'silnik';
    public const GROUP_NAPED = 'naped';
    public const GROUP_WYMIARY = 'wymiary';
    public const GROUP_ZAWIESZENIE = 'zawieszenie';
    public const GROUP_HAMULCE = 'hamulce';
    public const GROUP_KOLA = 'kola';
    public const GROUP_ELEKTRYCZNE = 'elektryczne';
    public const GROUP_SPALINOWE = 'spalinowe';
    public const GROUP_DOKUMENTACJA = 'dokumentacja';
    public const GROUP_INNE = 'inne';

    /**
     * Vehicle type filters
     */
    public const FILTER_ELECTRIC = 'elektryczne';
    public const FILTER_COMBUSTION = 'spalinowe';

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Feature types in this group
     */
    public function featureTypes(): HasMany
    {
        return $this->hasMany(FeatureType::class, 'feature_group_id')
                    ->orderBy('position');
    }

    /**
     * Active feature types in this group
     */
    public function activeFeatureTypes(): HasMany
    {
        return $this->featureTypes()->where('is_active', true);
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Only active groups
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Ordered by sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Scope: Find by code
     */
    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }

    /**
     * Scope: For specific vehicle type
     *
     * @param string|null $vehicleType 'elektryczne', 'spalinowe', or null for all
     */
    public function scopeForVehicleType($query, ?string $vehicleType)
    {
        return $query->where(function ($q) use ($vehicleType) {
            $q->whereNull('vehicle_type_filter');
            if ($vehicleType) {
                $q->orWhere('vehicle_type_filter', $vehicleType);
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Get display name (Polish if available, otherwise English)
     */
    public function getDisplayName(): string
    {
        return $this->name_pl ?? $this->name;
    }

    /**
     * Check if group is conditional (only for specific vehicle type)
     */
    public function isConditional(): bool
    {
        return $this->vehicle_type_filter !== null;
    }

    /**
     * Check if group should be shown for given vehicle type
     */
    public function isVisibleForVehicleType(?string $vehicleType): bool
    {
        if ($this->vehicle_type_filter === null) {
            return true;
        }

        return $this->vehicle_type_filter === $vehicleType;
    }

    /**
     * Get icon class for display
     */
    public function getIconClass(): string
    {
        $iconMap = [
            'engine' => 'fas fa-cog',
            'ruler' => 'fas fa-ruler-combined',
            'wheel' => 'fas fa-circle',
            'brake' => 'fas fa-compact-disc',
            'suspension' => 'fas fa-arrows-alt-v',
            'electric' => 'fas fa-bolt',
            'fuel' => 'fas fa-gas-pump',
            'document' => 'fas fa-file-alt',
            'info' => 'fas fa-info-circle',
            'car' => 'fas fa-car',
        ];

        return $iconMap[$this->icon] ?? 'fas fa-tag';
    }

    /**
     * Get Tailwind color classes
     */
    public function getColorClasses(): string
    {
        $colorMap = [
            'orange' => 'text-orange-400 bg-orange-500/10',
            'blue' => 'text-blue-400 bg-blue-500/10',
            'green' => 'text-green-400 bg-green-500/10',
            'yellow' => 'text-yellow-400 bg-yellow-500/10',
            'red' => 'text-red-400 bg-red-500/10',
            'purple' => 'text-purple-400 bg-purple-500/10',
            'cyan' => 'text-cyan-400 bg-cyan-500/10',
            'gray' => 'text-gray-400 bg-gray-500/10',
        ];

        return $colorMap[$this->color] ?? 'text-gray-400 bg-gray-500/10';
    }

    /**
     * Get feature count
     */
    public function getFeatureCount(): int
    {
        return $this->featureTypes()->count();
    }

    /**
     * Get active feature count
     */
    public function getActiveFeatureCount(): int
    {
        return $this->activeFeatureTypes()->count();
    }

    /*
    |--------------------------------------------------------------------------
    | STATIC METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Get all groups with feature types for a vehicle type
     *
     * @param string|null $vehicleType 'elektryczne', 'spalinowe', or null
     * @return Collection
     */
    public static function getGroupsWithFeatures(?string $vehicleType = null): Collection
    {
        return static::active()
            ->ordered()
            ->forVehicleType($vehicleType)
            ->with(['activeFeatureTypes'])
            ->get();
    }

    /**
     * Get available group codes
     */
    public static function getGroupCodes(): array
    {
        return [
            self::GROUP_IDENTYFIKACJA,
            self::GROUP_SILNIK,
            self::GROUP_NAPED,
            self::GROUP_WYMIARY,
            self::GROUP_ZAWIESZENIE,
            self::GROUP_HAMULCE,
            self::GROUP_KOLA,
            self::GROUP_ELEKTRYCZNE,
            self::GROUP_SPALINOWE,
            self::GROUP_DOKUMENTACJA,
            self::GROUP_INNE,
        ];
    }
}
