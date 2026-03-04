<?php

namespace App\Http\Livewire\Admin\Parameters;

use App\Models\Category;
use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Models\SmartKeywordRule;
use App\Models\SmartSyncBrandRule;
use App\Models\SmartVehicleAlias;
use App\Services\Compatibility\VehicleModelDetector;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * SmartMatchingPanel - Panel konfiguracji Smart Matching
 *
 * Sekcje:
 * 1. Reguly Keyword - reguly dopasowania keyword do typow pojazdow
 * 2. Detekcja Modeli - aliasy pojazdow do wyszukiwania czesci
 * 3. Reguly Sync - konfiguracja marek dozwolonych w sync do sklepow
 */
class SmartMatchingPanel extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    // ─── Sekcje ───────────────────────────────────────────────────────────────
    public string $activeSection = 'keyword-rules';

    // ─── Sekcja 1: Keyword Rules ──────────────────────────────────────────────
    public array $keywordRules = [];
    public array $selectedRuleIds = [];
    public bool $showKeywordModal = false;
    public string $editKeyword = '';
    public string $editMatchField = 'any';
    public string $editMatchType = 'contains';
    public ?string $editTargetVehicleType = null;
    public ?string $editTargetBrand = null;
    public float $editScoreBonus = 0.20;
    public string $editNotes = '';
    public ?int $editingRuleId = null;

    // ─── Bulk: Keyword Rules ────────────────────────────────────────────────
    public function toggleRuleSelection(int $id): void
    {
        if (in_array($id, $this->selectedRuleIds)) {
            $this->selectedRuleIds = array_values(array_diff($this->selectedRuleIds, [$id]));
        } else {
            $this->selectedRuleIds[] = $id;
        }
    }

    public function selectAllRules(): void
    {
        $allIds = array_column($this->keywordRules, 'id');
        $this->selectedRuleIds = count($this->selectedRuleIds) === count($allIds) ? [] : $allIds;
    }

    public function deleteSelectedRules(): void
    {
        if (empty($this->selectedRuleIds)) return;
        SmartKeywordRule::whereIn('id', $this->selectedRuleIds)->delete();
        $this->selectedRuleIds = [];
        session()->flash('success', 'Zaznaczone reguly usuniete.');
        $this->loadKeywordRules();
    }

    public function toggleSelectedRulesActive(bool $active): void
    {
        if (empty($this->selectedRuleIds)) return;
        SmartKeywordRule::whereIn('id', $this->selectedRuleIds)->update(['is_active' => $active]);
        $this->selectedRuleIds = [];
        session()->flash('success', $active ? 'Zaznaczone reguly aktywowane.' : 'Zaznaczone reguly dezaktywowane.');
        $this->loadKeywordRules();
    }

    // ─── Bulk: All Aliases ────────────────────────────────────────────────────
    public array $selectedAliasIds = [];

    public function toggleAliasSelection(int $id): void
    {
        if (in_array($id, $this->selectedAliasIds)) {
            $this->selectedAliasIds = array_values(array_diff($this->selectedAliasIds, [$id]));
        } else {
            $this->selectedAliasIds[] = $id;
        }
    }

    public function selectAllAliases(): void
    {
        $pageIds = $this->allAliases->pluck('id')->toArray();
        $allSelected = count(array_intersect($pageIds, $this->selectedAliasIds)) === count($pageIds);
        if ($allSelected) {
            $this->selectedAliasIds = array_values(array_diff($this->selectedAliasIds, $pageIds));
        } else {
            $this->selectedAliasIds = array_values(array_unique(array_merge($this->selectedAliasIds, $pageIds)));
        }
    }

    public function deleteSelectedAliases(): void
    {
        if (empty($this->selectedAliasIds)) return;
        SmartVehicleAlias::whereIn('id', $this->selectedAliasIds)->delete();
        $count = count($this->selectedAliasIds);
        $this->selectedAliasIds = [];
        session()->flash('success', "Usunieto {$count} aliasow.");
        if ($this->selectedVehicleId) {
            $this->loadVehicleAliases();
        }
    }

    public function deleteAllAutoAliases(): void
    {
        $count = SmartVehicleAlias::where('is_auto_generated', true)->count();
        SmartVehicleAlias::where('is_auto_generated', true)->delete();
        $this->selectedAliasIds = [];
        session()->flash('success', "Usunieto {$count} automatycznych aliasow.");
        if ($this->selectedVehicleId) {
            $this->loadVehicleAliases();
        }
    }

    // ─── Sekcja 2: Vehicle Aliases ────────────────────────────────────────────
    public string $aliasSearchVehicle = '';
    public ?int $selectedVehicleId = null;
    public ?string $selectedVehicleName = null;
    public array $vehicleSearchResults = [];
    public array $vehicleAliases = [];
    public bool $showAliasModal = false;
    public string $newAliasText = '';
    public string $newAliasType = 'model_code';
    public ?int $editingAliasId = null;
    public string $editAliasText = '';
    public string $editAliasType = 'model_code';
    public bool $autoGeneratingAll = false;
    public int $autoGeneratedCount = 0;
    public string $allAliasesSearch = '';

    // ─── Sekcja 1+5: Vehicle Categories (for keyword rules dropdown) ──────────
    public array $vehicleCategories = [];

    // ─── Sekcja 3: Sync Brand Rules ───────────────────────────────────────────
    public ?int $selectedShopId = null;
    public array $brandRules = [];
    public array $availableShops = [];
    public array $availableBrands = [];

    public function mount(): void
    {
        if (!auth()->user()->canAny(['parameters.smart_matching.read', 'parameters.read'])) {
            abort(403);
        }
        $this->loadKeywordRules();
        $this->loadAvailableShops();
        $this->loadAvailableBrands();
        $this->loadVehicleCategories();
    }

    // ─── SEKCJA 1: Keyword Rules ──────────────────────────────────────────────

    public function loadKeywordRules(): void
    {
        $this->keywordRules = SmartKeywordRule::orderByPriority()
            ->get()
            ->map(fn($rule) => [
                'id'                  => $rule->id,
                'keyword'             => $rule->keyword,
                'match_field'         => $rule->match_field,
                'match_type'          => $rule->match_type,
                'target_vehicle_type' => $rule->target_vehicle_type,
                'target_brand'        => $rule->target_brand,
                'score_bonus'         => (float) $rule->score_bonus,
                'is_active'           => $rule->is_active,
                'priority'            => $rule->priority,
                'notes'               => $rule->notes,
            ])
            ->toArray();
    }

    public function openAddKeywordModal(): void
    {
        $this->editingRuleId       = null;
        $this->editKeyword         = '';
        $this->editMatchField      = 'any';
        $this->editMatchType       = 'contains';
        $this->editTargetVehicleType = null;
        $this->editTargetBrand     = null;
        $this->editScoreBonus      = 0.20;
        $this->editNotes           = '';
        $this->showKeywordModal    = true;
    }

    public function openEditKeywordModal(int $id): void
    {
        $rule = SmartKeywordRule::findOrFail($id);

        $this->editingRuleId         = $rule->id;
        $this->editKeyword           = $rule->keyword;
        $this->editMatchField        = $rule->match_field;
        $this->editMatchType         = $rule->match_type;
        $this->editTargetVehicleType = $rule->target_vehicle_type;
        $this->editTargetBrand       = $rule->target_brand;
        $this->editScoreBonus        = (float) $rule->score_bonus;
        $this->editNotes             = $rule->notes ?? '';
        $this->showKeywordModal      = true;
    }

    public function saveKeywordRule(): void
    {
        $this->validate([
            'editKeyword'    => 'required|string|max:100',
            'editMatchField' => 'required|in:any,name,sku',
            'editMatchType'  => 'required|in:contains,starts_with,exact,regex',
            'editScoreBonus' => 'required|numeric|min:0|max:1',
        ]);

        $data = [
            'keyword'             => trim($this->editKeyword),
            'match_field'         => $this->editMatchField,
            'match_type'          => $this->editMatchType,
            'target_vehicle_type' => $this->editTargetVehicleType ?: null,
            'target_brand'        => $this->editTargetBrand ?: null,
            'score_bonus'         => $this->editScoreBonus,
            'notes'               => $this->editNotes ?: null,
        ];

        if ($this->editingRuleId) {
            SmartKeywordRule::findOrFail($this->editingRuleId)->update($data);
            session()->flash('success', 'Regula zaktualizowana.');
        } else {
            $data['priority'] = SmartKeywordRule::max('priority') + 10;
            SmartKeywordRule::create($data);
            session()->flash('success', 'Regula dodana.');
        }

        $this->showKeywordModal = false;
        $this->loadKeywordRules();
    }

    public function deleteKeywordRule(int $id): void
    {
        SmartKeywordRule::findOrFail($id)->delete();
        session()->flash('success', 'Regula usunieta.');
        $this->loadKeywordRules();
    }

    public function toggleRuleActive(int $id): void
    {
        $rule = SmartKeywordRule::findOrFail($id);
        $rule->update(['is_active' => !$rule->is_active]);
        $this->loadKeywordRules();
    }

    public function getMatchingProductsCount(int $ruleId): int
    {
        $rule = SmartKeywordRule::find($ruleId);
        if (!$rule) {
            return 0;
        }

        $query = Product::query();

        $keyword = $rule->keyword_normalized ?: mb_strtolower($rule->keyword);

        $fieldMap = match ($rule->match_field) {
            'name' => ['name'],
            'sku'  => ['sku'],
            default => ['name', 'sku'],
        };

        $allowedFields = ['name', 'sku'];

        $query->where(function ($q) use ($fieldMap, $keyword, $rule, $allowedFields) {
            foreach ($fieldMap as $field) {
                if (!in_array($field, $allowedFields)) {
                    continue;
                }
                $q->orWhere(function ($sub) use ($field, $keyword, $rule) {
                    match ($rule->match_type) {
                        'starts_with' => $sub->whereRaw("LOWER({$field}) LIKE ?", [$keyword . '%']),
                        'exact'       => $sub->whereRaw("LOWER({$field}) = ?", [$keyword]),
                        default       => $sub->whereRaw("LOWER({$field}) LIKE ?", ['%' . $keyword . '%']),
                    };
                });
            }
        });

        return $query->count();
    }

    // ─── SEKCJA 2: Vehicle Aliases ────────────────────────────────────────────

    public function getAllAliasesProperty()
    {
        $query = SmartVehicleAlias::with('vehicleProduct:id,name,manufacturer,sku')
            ->active()
            ->orderBy('alias_normalized');

        if (!empty($this->allAliasesSearch)) {
            $search = '%' . $this->allAliasesSearch . '%';
            $query->where(function ($q) use ($search) {
                $q->where('alias', 'like', $search)
                  ->orWhereHas('vehicleProduct', function ($vq) use ($search) {
                      $vq->where('name', 'like', $search)
                          ->orWhere('manufacturer', 'like', $search);
                  });
            });
        }

        return $query->paginate(50, ['*'], 'aliasesPage');
    }

    public function updatedAllAliasesSearch(): void
    {
        $this->resetPage('aliasesPage');
    }

    public function updatedAliasSearchVehicle(): void
    {
        $this->searchVehicles();
    }

    public function searchVehicles(): void
    {
        if (strlen($this->aliasSearchVehicle) < 2) {
            $this->vehicleSearchResults = [];
            return;
        }

        $this->vehicleSearchResults = Product::byType('pojazd')
            ->where(function ($q) {
                $q->where('name', 'like', '%' . $this->aliasSearchVehicle . '%')
                  ->orWhere('sku', 'like', '%' . $this->aliasSearchVehicle . '%');
            })
            ->select('id', 'name', 'sku')
            ->limit(20)
            ->get()
            ->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'sku' => $p->sku])
            ->toArray();
    }

    public function selectVehicle(int $id): void
    {
        $product = Product::findOrFail($id);
        $this->selectedVehicleId   = $id;
        $this->selectedVehicleName = $product->name;
        $this->vehicleSearchResults = [];
        $this->aliasSearchVehicle   = $product->name;
        $this->loadVehicleAliases();
    }

    public function loadVehicleAliases(): void
    {
        if (!$this->selectedVehicleId) {
            $this->vehicleAliases = [];
            return;
        }

        $this->vehicleAliases = SmartVehicleAlias::forVehicle($this->selectedVehicleId)
            ->orderBy('alias_type')
            ->orderBy('alias')
            ->get()
            ->map(fn($a) => [
                'id'               => $a->id,
                'alias'            => $a->alias,
                'alias_type'       => $a->alias_type,
                'is_auto_generated' => $a->is_auto_generated,
                'is_active'        => $a->is_active,
            ])
            ->toArray();
    }

    public function openAddAliasModal(): void
    {
        $this->newAliasText = '';
        $this->newAliasType = 'model_code';
        $this->showAliasModal = true;
    }

    public function saveAlias(): void
    {
        $this->validate([
            'newAliasText' => 'required|string|max:100',
            'newAliasType' => 'required|in:model_code,brand_model,popular_name,sku_prefix,custom',
        ]);

        SmartVehicleAlias::create([
            'vehicle_product_id' => $this->selectedVehicleId,
            'alias'              => trim($this->newAliasText),
            'alias_type'         => $this->newAliasType,
            'is_auto_generated'  => false,
            'is_active'          => true,
        ]);

        $this->showAliasModal = false;
        session()->flash('success', 'Alias dodany.');
        $this->loadVehicleAliases();
    }

    public function deleteAlias(int $id): void
    {
        SmartVehicleAlias::findOrFail($id)->delete();
        session()->flash('success', 'Alias usuniety.');
        $this->loadVehicleAliases();
    }

    public function autoGenerateAliases(): void
    {
        if (!$this->selectedVehicleId) {
            return;
        }

        $vehicle  = Product::findOrFail($this->selectedVehicleId);
        $detector = app(VehicleModelDetector::class);
        $count    = $detector->saveGeneratedAliases($vehicle);

        session()->flash('success', "Wygenerowano {$count} aliasow.");
        $this->loadVehicleAliases();
    }

    public function autoGenerateAllAliases(): void
    {
        $detector = app(VehicleModelDetector::class);
        $total    = 0;

        Product::byType('pojazd')->chunk(50, function ($vehicles) use ($detector, &$total) {
            foreach ($vehicles as $vehicle) {
                $total += $detector->saveGeneratedAliases($vehicle);
            }
        });

        $this->autoGeneratedCount = $total;
        session()->flash('success', "Wygenerowano lacznie {$total} aliasow dla wszystkich pojazdow.");

        if ($this->selectedVehicleId) {
            $this->loadVehicleAliases();
        }
    }

    // ─── Edit Alias ─────────────────────────────────────────────────────────

    public function openEditAliasModal(int $id): void
    {
        $alias = SmartVehicleAlias::findOrFail($id);
        $this->editingAliasId = $alias->id;
        $this->editAliasText = $alias->alias;
        $this->editAliasType = $alias->alias_type;
        $this->showAliasModal = true;
    }

    public function saveAliasEdit(): void
    {
        $this->validate([
            'editAliasText' => 'required|string|max:100',
            'editAliasType' => 'required|in:model_code,brand_model,popular_name,sku_prefix,custom',
        ]);

        SmartVehicleAlias::findOrFail($this->editingAliasId)->update([
            'alias' => trim($this->editAliasText),
            'alias_type' => $this->editAliasType,
        ]);

        $this->editingAliasId = null;
        $this->showAliasModal = false;
        session()->flash('success', 'Alias zaktualizowany.');
        $this->loadVehicleAliases();
    }

    // ─── SEKCJA 5: Vehicle Categories for keyword rules ───────────────────────

    public function loadVehicleCategories(): void
    {
        $this->vehicleCategories = [];

        $pojazdyCategory = Category::where('name', 'Pojazdy')
            ->orWhere('slug', 'pojazdy')
            ->first();

        if (!$pojazdyCategory) {
            return;
        }

        $this->vehicleCategories = $this->buildCategoryOptions($pojazdyCategory);
    }

    protected function buildCategoryOptions(Category $parent, string $prefix = ''): array
    {
        $options = [];
        $children = Category::where('parent_id', $parent->id)
            ->orderBy('name')
            ->get();

        foreach ($children as $child) {
            $label = $prefix ? "{$prefix} > {$child->name}" : $child->name;
            $options[] = ['slug' => $child->slug ?? '', 'label' => $label];

            $subOptions = $this->buildCategoryOptions($child, $label);
            $options = array_merge($options, $subOptions);
        }

        return $options;
    }

    // ─── SEKCJA 3: Sync Brand Rules ───────────────────────────────────────────

    public function loadAvailableShops(): void
    {
        $this->availableShops = PrestaShopShop::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn($s) => ['id' => $s->id, 'name' => $s->name])
            ->toArray();
    }

    public function loadAvailableBrands(): void
    {
        $this->availableBrands = Product::byType('pojazd')
            ->whereNotNull('manufacturer')
            ->where('manufacturer', '!=', '')
            ->distinct()
            ->orderBy('manufacturer')
            ->pluck('manufacturer')
            ->toArray();
    }

    public function updatedSelectedShopId(): void
    {
        $this->loadBrandRules();
    }

    public function selectShop(int $shopId): void
    {
        $this->selectedShopId = $shopId;
        $this->loadBrandRules();
    }

    public function loadBrandRules(): void
    {
        if (!$this->selectedShopId) {
            $this->brandRules = [];
            return;
        }

        $this->brandRules = SmartSyncBrandRule::forShop($this->selectedShopId)
            ->orderBy('brand')
            ->get()
            ->map(fn($r) => [
                'id'             => $r->id,
                'brand'          => $r->brand,
                'is_allowed'     => $r->is_allowed,
                'auto_sync'      => $r->auto_sync,
                'min_confidence' => (float) $r->min_confidence,
                'notes'          => $r->notes,
            ])
            ->toArray();
    }

    public function toggleBrandAllowed(string $brand): void
    {
        if (!$this->selectedShopId) {
            return;
        }

        $rule = SmartSyncBrandRule::forShop($this->selectedShopId)
            ->where('brand', $brand)
            ->first();

        if ($rule) {
            $rule->update(['is_allowed' => !$rule->is_allowed]);
        } else {
            SmartSyncBrandRule::create([
                'shop_id'        => $this->selectedShopId,
                'brand'          => $brand,
                'is_allowed'     => true,
                'auto_sync'      => false,
                'min_confidence' => 0.70,
            ]);
        }

        $this->loadBrandRules();
    }

    public function updateMinConfidence(int $ruleId, float $value): void
    {
        SmartSyncBrandRule::findOrFail($ruleId)->update([
            'min_confidence' => max(0.0, min(1.0, $value)),
        ]);
        $this->loadBrandRules();
    }

    public function deleteBrandRule(int $id): void
    {
        SmartSyncBrandRule::findOrFail($id)->delete();
        session()->flash('success', 'Regula marki usunieta.');
        $this->loadBrandRules();
    }

    public function enableAllBrands(): void
    {
        if (!$this->selectedShopId) return;

        foreach ($this->availableBrands as $brand) {
            SmartSyncBrandRule::updateOrCreate(
                ['shop_id' => $this->selectedShopId, 'brand' => $brand],
                ['is_allowed' => true, 'auto_sync' => false, 'min_confidence' => 0.70]
            );
        }

        session()->flash('success', 'Wszystkie marki dozwolone.');
        $this->loadBrandRules();
    }

    public function disableAllBrands(): void
    {
        if (!$this->selectedShopId) return;

        SmartSyncBrandRule::forShop($this->selectedShopId)->update(['is_allowed' => false]);
        session()->flash('success', 'Wszystkie marki zablokowane.');
        $this->loadBrandRules();
    }

    public function migrateFromLegacy(): void
    {
        if (!$this->selectedShopId) {
            return;
        }

        $shop = PrestaShopShop::find($this->selectedShopId);
        if (!$shop) {
            return;
        }

        $legacyBrands = [];

        // Odczyt z pola allowed_vehicle_brands (JSON) jesli istnieje
        if (isset($shop->allowed_vehicle_brands) && is_array($shop->allowed_vehicle_brands)) {
            $legacyBrands = $shop->allowed_vehicle_brands;
        } elseif (isset($shop->allowed_vehicle_brands) && is_string($shop->allowed_vehicle_brands)) {
            $legacyBrands = json_decode($shop->allowed_vehicle_brands, true) ?? [];
        }

        if (empty($legacyBrands)) {
            session()->flash('info', 'Brak danych legacy do migracji.');
            return;
        }

        $migrated = 0;
        foreach ($legacyBrands as $brand) {
            SmartSyncBrandRule::firstOrCreate(
                ['shop_id' => $this->selectedShopId, 'brand' => $brand],
                ['is_allowed' => true, 'auto_sync' => false, 'min_confidence' => 0.70]
            );
            $migrated++;
        }

        session()->flash('success', "Migracja zakonczona: {$migrated} marek.");
        $this->loadBrandRules();
    }

    public function render()
    {
        return view('livewire.admin.parameters.smart-matching-panel');
    }
}
