<?php

namespace App\Http\Livewire\Admin\Parameters;

use Livewire\Component;
use App\Services\Product\ProductStatusAggregator;
use App\DTOs\ProductStatusDTO;

/**
 * StatusMonitoringConfig - Configuration panel for product status monitoring
 *
 * Allows admin to configure:
 * - Which field groups to monitor (basic, descriptions, physical, etc.)
 * - Which fields to ignore in each group
 * - Conditional checks (attributes for vehicles, compatibility for spare parts)
 *
 * @since 2026-02-04
 * @see Plan_Projektu/synthetic-mixing-thunder.md
 */
class StatusMonitoringConfig extends Component
{
    /**
     * Monitoring toggles per category
     */
    public bool $monitorBasic = true;
    public bool $monitorDescriptions = true;
    public bool $monitorPhysical = true;
    public bool $monitorAttributes = true;
    public bool $monitorCompatibility = true;
    public bool $monitorImages = true;
    public bool $monitorZeroPrice = true;
    public bool $monitorLowStock = true;

    /**
     * Ignored fields per category
     */
    public array $ignoredBasicFields = [];
    public array $ignoredDescFields = [];

    /**
     * Available fields for ignoring
     */
    public array $availableBasicFields = [
        'supplier_code' => 'Kod dostawcy',
        'ean' => 'Kod EAN',
        'sort_order' => 'Kolejność sortowania',
    ];

    public array $availableDescFields = [
        'meta_title' => 'Tytuł SEO',
        'meta_description' => 'Opis SEO',
    ];

    /**
     * Cache settings
     */
    public bool $cacheEnabled = true;
    public int $cacheTtl = 300; // 5 minutes

    /**
     * Load configuration on mount
     */
    public function mount(): void
    {
        $aggregator = app(ProductStatusAggregator::class);
        $config = $aggregator->getCurrentConfig();

        // Load monitoring toggles
        $monitoring = $config['monitoring'] ?? [];
        $this->monitorBasic = $monitoring['basic'] ?? true;
        $this->monitorDescriptions = $monitoring['descriptions'] ?? true;
        $this->monitorPhysical = $monitoring['physical'] ?? true;
        $this->monitorAttributes = $monitoring['attributes'] ?? true;
        $this->monitorCompatibility = $monitoring['compatibility'] ?? true;
        $this->monitorImages = $monitoring['images'] ?? true;
        $this->monitorZeroPrice = $monitoring['zero_price'] ?? true;
        $this->monitorLowStock = $monitoring['low_stock'] ?? true;

        // Load ignored fields
        $ignored = $config['ignored_fields'] ?? [];
        $this->ignoredBasicFields = $ignored['basic'] ?? ['supplier_code', 'ean', 'sort_order'];
        $this->ignoredDescFields = $ignored['descriptions'] ?? ['meta_title', 'meta_description'];

        // Load cache settings
        $this->cacheEnabled = $config['cache_enabled'] ?? true;
        $this->cacheTtl = $config['cache_ttl'] ?? 300;
    }

    /**
     * Save configuration
     */
    public function saveConfig(): void
    {
        $aggregator = app(ProductStatusAggregator::class);

        $config = [
            'monitoring' => [
                'basic' => $this->monitorBasic,
                'descriptions' => $this->monitorDescriptions,
                'physical' => $this->monitorPhysical,
                'attributes' => $this->monitorAttributes,
                'compatibility' => $this->monitorCompatibility,
                'images' => $this->monitorImages,
                'zero_price' => $this->monitorZeroPrice,
                'low_stock' => $this->monitorLowStock,
            ],
            'ignored_fields' => [
                'basic' => $this->ignoredBasicFields,
                'descriptions' => $this->ignoredDescFields,
            ],
            'cache_enabled' => $this->cacheEnabled,
            'cache_ttl' => $this->cacheTtl,
        ];

        $aggregator->updateConfig($config);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Konfiguracja monitorowania zapisana pomyślnie.',
        ]);
    }

    /**
     * Reset to defaults
     */
    public function resetToDefaults(): void
    {
        $this->monitorBasic = true;
        $this->monitorDescriptions = true;
        $this->monitorPhysical = true;
        $this->monitorAttributes = true;
        $this->monitorCompatibility = true;
        $this->monitorImages = true;
        $this->monitorZeroPrice = true;
        $this->monitorLowStock = true;

        $this->ignoredBasicFields = ['supplier_code', 'ean', 'sort_order'];
        $this->ignoredDescFields = ['meta_title', 'meta_description'];

        $this->cacheEnabled = true;
        $this->cacheTtl = 300;

        $this->saveConfig();
    }

    /**
     * Clear all cache
     */
    public function clearCache(): void
    {
        $aggregator = app(ProductStatusAggregator::class);
        $aggregator->clearAllCache();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Cache statusów produktów wyczyszczony.',
        ]);
    }

    public function render()
    {
        return view('livewire.admin.parameters.status-monitoring-config');
    }
}
