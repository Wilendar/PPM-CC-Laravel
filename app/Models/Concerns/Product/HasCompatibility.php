<?php

namespace App\Models\Concerns\Product;

use App\Models\VehicleCompatibility;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * HasCompatibility Trait - Vehicle Compatibility System (STUB for ETAP_05a)
 *
 * Responsibility: Dopasowania pojazdów (Model, Oryginał, Zamiennik)
 *
 * Features (to be implemented in ETAP_05a):
 * - Vehicle compatibility relationships (hasMany VehicleCompatibility)
 * - Compatible vehicles filtering per shop
 * - OEM/Replacement part numbers
 * - Export format: osobne wpisy per model (Model: X, Model: Y)
 *
 * Architecture:
 * - **SKU-FIRST PATTERN** - SKU jako PRIMARY identifier dla compatibility
 * - External IDs (PrestaShop/ERP) jako SECONDARY/FALLBACK only
 * - SKU-based compatibility cache keys
 *
 * Performance: Optimized queries dla automotive filtering
 * Integration: PrestaShop ps_feature_value mapping ready
 *
 * Reference: _DOCS/SKU_ARCHITECTURE_GUIDE.md
 *
 * @package App\Models\Concerns\Product
 * @version 1.0
 * @since ETAP_05a SEKCJA 0 - Product.php Refactoring
 */
trait HasCompatibility
{
    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS - Compatibility Relations (STUB)
    |--------------------------------------------------------------------------
    */

    /**
     * Vehicle compatibility relationship (1:many) - ETAP_05a FAZA 2 ✅ IMPLEMENTED
     *
     * Business Logic: Dopasowania pojazdów per produkt
     * Architecture: **SKU-first pattern** - product_id jako PRIMARY, SKU jako backup
     * Performance: Eager loading ready z proper indexing
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function vehicleCompatibility(): HasMany
    {
        return $this->hasMany(VehicleCompatibility::class, 'product_id', 'id')
                    ->with(['vehicleModel', 'compatibilityAttribute', 'compatibilitySource']);
    }

    /*
    |--------------------------------------------------------------------------
    | BUSINESS METHODS - Compatibility Operations (STUB)
    |--------------------------------------------------------------------------
    */

    /**
     * Get compatible vehicles for this product - ETAP_05a FAZA 2 ✅ IMPLEMENTED
     *
     * Architecture: **SKU-FIRST** - uses product_id as PRIMARY, SKU as backup
     *
     * @param int|null $shopId Filter per shop (optional)
     * @return \Illuminate\Support\Collection
     */
    public function getCompatibleVehicles(?int $shopId = null): \Illuminate\Support\Collection
    {
        return $this->vehicleCompatibility()
                    ->with(['vehicleModel', 'compatibilityAttribute'])
                    ->verified()
                    ->get()
                    ->map(fn($c) => [
                        'vehicle' => $c->vehicleModel->getFullName(),
                        'vehicle_sku' => $c->vehicleModel->sku,
                        'type' => $c->compatibilityAttribute?->name ?? 'Standard',
                        'verified' => $c->is_verified,
                        'source' => $c->compatibilitySource->name,
                        'trust_level' => $c->getTrustLevel(),
                    ]);
    }

    /**
     * Check if product is compatible with specific vehicle - ETAP_05a FAZA 2 ✅ IMPLEMENTED
     *
     * Architecture: **SKU-FIRST** - vehicle lookup based on product relationship
     *
     * @param string $vehicleSku Vehicle SKU or model name
     * @return bool
     */
    public function isCompatibleWith(string $vehicleSku): bool
    {
        return $this->vehicleCompatibility()
                    ->where(function ($query) use ($vehicleSku) {
                        $query->where('vehicle_sku', $vehicleSku)
                              ->orWhereHas('vehicleModel', function ($q) use ($vehicleSku) {
                                  $q->where('sku', $vehicleSku)
                                    ->orWhere('model', 'LIKE', "%{$vehicleSku}%");
                              });
                    })
                    ->exists();
    }

    /**
     * Get compatibility export format (PrestaShop features format) - ETAP_05a FAZA 2 ✅ IMPLEMENTED
     *
     * Format: Osobne wpisy per model (Model: X, Model: Y, etc.)
     *
     * @return array
     */
    public function getCompatibilityExportFormat(): array
    {
        $compatibility = [];
        $vehicles = $this->getCompatibleVehicles();

        foreach ($vehicles as $vehicle) {
            $compatibility[] = [
                'feature' => 'Model',
                'value' => $vehicle['vehicle'],
            ];

            if ($vehicle['type'] !== 'Standard') {
                $compatibility[] = [
                    'feature' => 'Typ',
                    'value' => $vehicle['type'],
                ];
            }
        }

        return $compatibility;
    }
}
