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

    /**
     * Get complete icon map with HTML entities, labels and categories
     */
    public static function getIconMap(): array
    {
        return [
            // === Glowne ===
            'engine' => ['entity' => '&#9881;', 'label' => 'Silnik', 'category' => 'glowne'],
            'car' => ['entity' => '&#128663;', 'label' => 'Samochod', 'category' => 'glowne'],
            'motorcycle' => ['entity' => '&#127949;', 'label' => 'Motocykl', 'category' => 'glowne'],
            'gear' => ['entity' => '&#9881;', 'label' => 'Zebatka', 'category' => 'glowne'],
            'fuel' => ['entity' => '&#9981;', 'label' => 'Paliwo', 'category' => 'glowne'],
            'electric' => ['entity' => '&#9889;', 'label' => 'Elektryczny', 'category' => 'glowne'],
            'racing' => ['entity' => '&#127937;', 'label' => 'Wyscigowy', 'category' => 'glowne'],
            'quad' => ['entity' => '&#128755;', 'label' => 'Quad/ATV', 'category' => 'glowne'],

            // === Uklad napedowy ===
            'transmission' => ['entity' => '&#128260;', 'label' => 'Skrzynia biegow', 'category' => 'naped'],
            'chain' => ['entity' => '&#128279;', 'label' => 'Lancuch', 'category' => 'naped'],
            'clutch' => ['entity' => '&#128296;', 'label' => 'Sprzeglo', 'category' => 'naped'],
            'differential' => ['entity' => '&#9881;', 'label' => 'Dyferencjal', 'category' => 'naped'],
            'axle' => ['entity' => '&#8596;', 'label' => 'Os napedowa', 'category' => 'naped'],

            // === Podwozie ===
            'wheel' => ['entity' => '&#9899;', 'label' => 'Kolo', 'category' => 'podwozie'],
            'tire' => ['entity' => '&#11044;', 'label' => 'Opona', 'category' => 'podwozie'],
            'brake' => ['entity' => '&#128376;', 'label' => 'Hamulec', 'category' => 'podwozie'],
            'suspension' => ['entity' => '&#8597;', 'label' => 'Zawieszenie', 'category' => 'podwozie'],
            'fork' => ['entity' => '&#10546;', 'label' => 'Widly', 'category' => 'podwozie'],
            'shock' => ['entity' => '&#10972;', 'label' => 'Amortyzator', 'category' => 'podwozie'],
            'frame' => ['entity' => '&#9645;', 'label' => 'Rama', 'category' => 'podwozie'],
            'steering' => ['entity' => '&#9935;', 'label' => 'Kierownica', 'category' => 'podwozie'],

            // === Elektryka ===
            'battery' => ['entity' => '&#128267;', 'label' => 'Akumulator', 'category' => 'elektryka'],
            'light' => ['entity' => '&#128161;', 'label' => 'Oswietlenie', 'category' => 'elektryka'],
            'plug' => ['entity' => '&#128268;', 'label' => 'Wtyczka', 'category' => 'elektryka'],
            'wiring' => ['entity' => '&#128300;', 'label' => 'Instalacja', 'category' => 'elektryka'],
            'sensor' => ['entity' => '&#128225;', 'label' => 'Czujnik', 'category' => 'elektryka'],
            'ecu' => ['entity' => '&#128187;', 'label' => 'Sterownik ECU', 'category' => 'elektryka'],
            'ignition' => ['entity' => '&#128273;', 'label' => 'Zaplonowy', 'category' => 'elektryka'],
            'controller' => ['entity' => '&#127918;', 'label' => 'Kontroler', 'category' => 'elektryka'],

            // === Czesci silnika ===
            'piston' => ['entity' => '&#9898;', 'label' => 'Tlok', 'category' => 'silnik'],
            'carburetor' => ['entity' => '&#9904;', 'label' => 'Gaznik', 'category' => 'silnik'],
            'turbo' => ['entity' => '&#127744;', 'label' => 'Turbo', 'category' => 'silnik'],
            'exhaust' => ['entity' => '&#128168;', 'label' => 'Wydech', 'category' => 'silnik'],
            'radiator' => ['entity' => '&#10052;', 'label' => 'Chlodnica', 'category' => 'silnik'],
            'oil' => ['entity' => '&#128167;', 'label' => 'Olej', 'category' => 'silnik'],
            'filter' => ['entity' => '&#9783;', 'label' => 'Filtr', 'category' => 'silnik'],

            // === Wymiary ===
            'ruler' => ['entity' => '&#128207;', 'label' => 'Wymiar', 'category' => 'wymiary'],
            'weight' => ['entity' => '&#9878;', 'label' => 'Waga', 'category' => 'wymiary'],
            'speed' => ['entity' => '&#128168;', 'label' => 'Predkosc', 'category' => 'wymiary'],
            'power' => ['entity' => '&#9889;', 'label' => 'Moc', 'category' => 'wymiary'],
            'volume' => ['entity' => '&#129506;', 'label' => 'Pojemnosc', 'category' => 'wymiary'],
            'range' => ['entity' => '&#128205;', 'label' => 'Zasieg', 'category' => 'wymiary'],

            // === Nadwozie ===
            'seat' => ['entity' => '&#129681;', 'label' => 'Siedzenie', 'category' => 'nadwozie'],
            'mirror' => ['entity' => '&#128064;', 'label' => 'Lusterko', 'category' => 'nadwozie'],
            'bumper' => ['entity' => '&#128739;', 'label' => 'Zderzak', 'category' => 'nadwozie'],
            'fender' => ['entity' => '&#128736;', 'label' => 'Blotnik', 'category' => 'nadwozie'],

            // === Narzedzia ===
            'wrench' => ['entity' => '&#128295;', 'label' => 'Klucz', 'category' => 'narzedzia'],
            'bolt' => ['entity' => '&#128297;', 'label' => 'Sruba', 'category' => 'narzedzia'],
            'toolkit' => ['entity' => '&#129520;', 'label' => 'Zestaw narzedzi', 'category' => 'narzedzia'],

            // === Inne ===
            'document' => ['entity' => '&#128196;', 'label' => 'Dokument', 'category' => 'inne'],
            'info' => ['entity' => '&#8505;', 'label' => 'Info', 'category' => 'inne'],
            'certificate' => ['entity' => '&#128220;', 'label' => 'Certyfikat', 'category' => 'inne'],
            'tag' => ['entity' => '&#127991;', 'label' => 'Etykieta', 'category' => 'inne'],
            'shield' => ['entity' => '&#128737;', 'label' => 'Gwarancja', 'category' => 'inne'],
        ];
    }

    /**
     * Get icon categories with Polish labels
     */
    public static function getIconCategories(): array
    {
        return [
            'glowne' => 'Glowne',
            'naped' => 'Naped',
            'podwozie' => 'Podwozie',
            'elektryka' => 'Elektryka',
            'silnik' => 'Czesci silnika',
            'wymiary' => 'Wymiary',
            'nadwozie' => 'Nadwozie',
            'narzedzia' => 'Narzedzia',
            'inne' => 'Inne',
        ];
    }
}
