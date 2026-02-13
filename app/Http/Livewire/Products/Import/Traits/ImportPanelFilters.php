<?php

namespace App\Http\Livewire\Products\Import\Traits;

use Livewire\Attributes\Url;
use Illuminate\Database\Eloquent\Builder;

/**
 * ImportPanelFilters - Trait dla filtrowania pending products
 *
 * ETAP_06: Filter logic dla ProductImportPanel
 * BUG#5 fix: 9 nowych filtrow, usuniety filtr Sesji
 *
 * Layout:
 * Row 1 (glowne): Search | Status | Typ | Marka | Cel publikacji
 * Row 2 (toggles): Ukryj opublikowane | Bez zdjec | Bez opisow
 *                  | Bez dopasowan | Bez atrybutow | Date range
 */
trait ImportPanelFilters
{
    // --- Row 1: Main dropdowns ---

    #[Url]
    public ?string $filterStatus = null;

    #[Url]
    public ?int $filterProductType = null;

    #[Url]
    public string $filterSearch = '';

    #[Url]
    public ?int $filterManufacturerId = null;

    #[Url]
    public ?string $filterPublicationTarget = null;

    // --- Row 2: Toggle checkboxes ---

    public bool $filterHidePublished = false;

    public bool $filterNoImages = false;

    public bool $filterNoDescriptions = false;

    public bool $filterNoCompatibility = false;

    public bool $filterNoFeatures = false;

    // --- Row 2: Date range ---

    #[Url]
    public ?string $filterPublishedFrom = null;

    #[Url]
    public ?string $filterPublishedTo = null;

    // --- Advanced filters visibility ---

    public bool $showAdvancedFilters = true;

    /**
     * Reset all filters to default
     */
    public function resetFilters(): void
    {
        $this->filterStatus = null;
        $this->filterProductType = null;
        $this->filterSearch = '';
        $this->filterManufacturerId = null;
        $this->filterPublicationTarget = null;
        $this->filterHidePublished = false;
        $this->filterNoImages = false;
        $this->filterNoDescriptions = false;
        $this->filterNoCompatibility = false;
        $this->filterNoFeatures = false;
        $this->filterPublishedFrom = null;
        $this->filterPublishedTo = null;
    }

    /**
     * Toggle advanced filters row visibility
     */
    public function toggleAdvancedFilters(): void
    {
        $this->showAdvancedFilters = !$this->showAdvancedFilters;
    }

    /**
     * Check if any filter is active (for reset button)
     */
    public function hasActiveFilters(): bool
    {
        return $this->filterStatus
            || $this->filterProductType
            || $this->filterSearch
            || $this->filterManufacturerId
            || $this->filterPublicationTarget
            || $this->filterHidePublished
            || $this->filterNoImages
            || $this->filterNoDescriptions
            || $this->filterNoCompatibility
            || $this->filterNoFeatures
            || $this->filterPublishedFrom
            || $this->filterPublishedTo;
    }

    // --- Pagination reset hooks for new filters ---

    public function updatedFilterManufacturerId(): void
    {
        $this->resetPage();
    }

    public function updatedFilterPublicationTarget(): void
    {
        $this->resetPage();
    }

    public function updatedFilterHidePublished(): void
    {
        $this->resetPage();
    }

    public function updatedFilterNoImages(): void
    {
        $this->resetPage();
    }

    public function updatedFilterNoDescriptions(): void
    {
        $this->resetPage();
    }

    public function updatedFilterNoCompatibility(): void
    {
        $this->resetPage();
    }

    public function updatedFilterNoFeatures(): void
    {
        $this->resetPage();
    }

    public function updatedFilterPublishedFrom(): void
    {
        $this->resetPage();
    }

    public function updatedFilterPublishedTo(): void
    {
        $this->resetPage();
    }

    /**
     * Apply status filter to query
     *
     * Logika oparta na completion_percentage:
     * - incomplete = completion_percentage < 100 (partiallyComplete)
     * - ready = completion_percentage == 100 (fullyComplete)
     * - published = published_at IS NOT NULL
     */
    protected function applyStatusFilter(Builder $query): Builder
    {
        return match ($this->filterStatus) {
            'incomplete' => $query->partiallyComplete(),
            'ready' => $query->fullyComplete(),
            'published' => $query->published(),
            default => $query,
        };
    }

    /**
     * Apply search filter (SKU + name + manufacturer text)
     */
    protected function applySearchFilter(Builder $query): Builder
    {
        $search = trim($this->filterSearch);
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('sku', 'like', "%{$search}%")
              ->orWhere('name', 'like', "%{$search}%")
              ->orWhere('manufacturer', 'like', "%{$search}%");
        });
    }

    /**
     * Apply all advanced filters to query builder
     */
    protected function applyAdvancedFilters(Builder $query): Builder
    {
        // Manufacturer (marka) dropdown
        if ($this->filterManufacturerId) {
            $query->where('manufacturer_id', $this->filterManufacturerId);
        }

        // Publication target (ERP / PrestaShop / Both)
        if ($this->filterPublicationTarget) {
            $query = $this->applyPublicationTargetFilter($query);
        }

        // Hide published products
        if ($this->filterHidePublished) {
            $query->whereNull('published_at');
        }

        // Without images
        if ($this->filterNoImages) {
            $query->where(function ($q) {
                $q->whereNull('temp_media_paths')
                  ->orWhereRaw("JSON_LENGTH(COALESCE(JSON_EXTRACT(temp_media_paths, '$.images'), '[]')) = 0");
            });
        }

        // Without descriptions (short OR long missing)
        if ($this->filterNoDescriptions) {
            $query->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->whereNull('short_description')
                       ->orWhere('short_description', '');
                })->orWhere(function ($q2) {
                    $q2->whereNull('long_description')
                       ->orWhere('long_description', '');
                });
            });
        }

        // Without compatibility data (for Czesc zamienna)
        if ($this->filterNoCompatibility) {
            $query->where(function ($q) {
                $q->whereNull('compatibility_data')
                  ->orWhereRaw("JSON_LENGTH(COALESCE(JSON_EXTRACT(compatibility_data, '$.compatibilities'), '[]')) = 0");
            });
        }

        // Without feature data (for Pojazd)
        if ($this->filterNoFeatures) {
            $query->where(function ($q) {
                $q->whereNull('feature_data')
                  ->orWhereRaw("JSON_LENGTH(COALESCE(JSON_EXTRACT(feature_data, '$.features'), '[]')) = 0");
            });
        }

        // Published date range
        if ($this->filterPublishedFrom) {
            $query->where('published_at', '>=', $this->filterPublishedFrom . ' 00:00:00');
        }
        if ($this->filterPublishedTo) {
            $query->where('published_at', '<=', $this->filterPublishedTo . ' 23:59:59');
        }

        return $query;
    }

    /**
     * Apply publication target filter (JSON column)
     */
    protected function applyPublicationTargetFilter(Builder $query): Builder
    {
        return match ($this->filterPublicationTarget) {
            'erp' => $query->where(function ($q) {
                $q->whereRaw("JSON_LENGTH(COALESCE(JSON_EXTRACT(publication_targets, '$.erp_connections'), '[]')) > 0")
                  ->orWhereRaw("JSON_EXTRACT(publication_targets, '$.erp_primary') = true");
            }),
            'prestashop' => $query->whereRaw(
                "JSON_LENGTH(COALESCE(JSON_EXTRACT(publication_targets, '$.prestashop_shops'), '[]')) > 0"
            ),
            'both' => $query->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->whereRaw("JSON_LENGTH(COALESCE(JSON_EXTRACT(publication_targets, '$.erp_connections'), '[]')) > 0")
                       ->orWhereRaw("JSON_EXTRACT(publication_targets, '$.erp_primary') = true");
                })->whereRaw(
                    "JSON_LENGTH(COALESCE(JSON_EXTRACT(publication_targets, '$.prestashop_shops'), '[]')) > 0"
                );
            }),
            default => $query,
        };
    }

    /**
     * Set filter for quick filtering buttons (stats badges)
     */
    public function setQuickFilter(string $type): void
    {
        $this->resetFilters();

        match ($type) {
            'all' => null,
            'incomplete' => $this->filterStatus = 'incomplete',
            'ready' => $this->filterStatus = 'ready',
            default => null,
        };

        $this->resetPage();
    }

    /**
     * Get available status options
     */
    public function getStatusOptions(): array
    {
        return [
            '' => 'Wszystkie statusy',
            'incomplete' => 'Niekompletne',
            'ready' => 'Gotowe do publikacji',
            'published' => 'Opublikowane',
        ];
    }
}
