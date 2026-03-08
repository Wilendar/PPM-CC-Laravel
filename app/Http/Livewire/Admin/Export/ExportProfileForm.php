<?php

namespace App\Http\Livewire\Admin\Export;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use App\Models\ExportProfile;
use App\Models\PriceGroup;
use App\Models\Warehouse;
use App\Services\Export\ExportProfileService;
use App\Services\Export\ProductExportService;

/**
 * ExportProfileForm Component - 5-step wizard for creating/editing export profiles.
 *
 * Steps:
 * 1. Basic Info (name, format, schedule)
 * 2. Field Selection (grouped fields with toggle/select all)
 * 3. Filters (active, stock, categories, manufacturer, shops)
 * 4. Price Groups & Warehouses
 * 5. Preview (first 5 products matching criteria)
 *
 * Architecture:
 * - Main component: lifecycle, navigation, save logic
 * - ProfileFormFields trait: field selection operations
 * - ProfileFormFilters trait: filter management
 *
 * @package App\Http\Livewire\Admin\Export
 */
class ExportProfileForm extends Component
{
    use Traits\ProfileFormFields;
    use Traits\ProfileFormFilters;

    /*
    |--------------------------------------------------------------------------
    | PUBLIC PROPERTIES
    |--------------------------------------------------------------------------
    */

    // Mode: null = create, int = edit
    public ?int $profileId = null;

    // Wizard navigation
    public int $currentStep = 1;
    public int $totalSteps = 5;

    // Step 1: Basic info
    public string $name = '';
    public string $format = 'csv';
    public string $schedule = 'manual';

    // Step 4: Price groups & Warehouses
    public array $selectedPriceGroups = [];
    public array $selectedWarehouses = [];
    public array $availablePriceGroups = [];
    public array $availableWarehouses = [];

    // Step 5: Preview
    public array $previewProducts = [];
    public int $previewCount = 0;

    // UI State
    public bool $isSaving = false;

    /*
    |--------------------------------------------------------------------------
    | STEP TITLES
    |--------------------------------------------------------------------------
    */

    protected array $stepTitles = [
        1 => 'Informacje podstawowe',
        2 => 'Wybor pol',
        3 => 'Filtry',
        4 => 'Ceny i magazyny',
        5 => 'Podglad',
    ];

    /*
    |--------------------------------------------------------------------------
    | LIFECYCLE
    |--------------------------------------------------------------------------
    */

    /**
     * Component initialization.
     *
     * @param int|null $profile Profile ID for edit mode
     */
    public function mount(?int $profile = null): void
    {
        // Authorization: create or update permission required
        if ($profile) {
            Gate::authorize('export.update');
        } else {
            Gate::authorize('export.create');
        }

        $this->initFields();
        $this->initFilters();
        $this->initPriceGroupsAndWarehouses();

        if ($profile) {
            $this->loadExistingProfile($profile);
        }
    }

    /**
     * Render the component with admin layout.
     */
    public function render()
    {
        return view('livewire.admin.export.export-profile-form')
            ->layout('layouts.admin', [
                'title' => ($this->profileId ? 'Edytuj profil' : 'Nowy profil eksportu') . ' - Admin PPM',
                'breadcrumb' => $this->profileId ? 'Edytuj profil' : 'Nowy profil eksportu',
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | WIZARD NAVIGATION
    |--------------------------------------------------------------------------
    */

    /**
     * Advance to the next step after validation.
     */
    public function nextStep(): void
    {
        $this->validateStep($this->currentStep);

        if ($this->currentStep < $this->totalSteps) {
            $this->currentStep++;
        }

        // Auto-load preview on Step 5
        if ($this->currentStep === 5) {
            $this->loadPreview();
        }
    }

    /**
     * Go back to the previous step.
     */
    public function previousStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    /**
     * Jump to a specific step (only to visited steps).
     */
    public function goToStep(int $step): void
    {
        if ($step >= 1 && $step <= $this->totalSteps && $step <= $this->currentStep) {
            $this->currentStep = $step;
        }
    }

    /**
     * Get title for a given step number.
     */
    public function getStepTitle(int $step): string
    {
        return $this->stepTitles[$step] ?? '';
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    /**
     * Validate the current step before navigation.
     */
    protected function validateStep(int $step): void
    {
        match ($step) {
            1 => $this->validate([
                'name' => 'required|string|max:255',
                'format' => 'required|in:' . implode(',', ExportProfile::FORMATS),
                'schedule' => 'required|in:' . implode(',', ExportProfile::SCHEDULES),
            ]),
            2 => $this->validateFieldSelection(),
            default => null,
        };
    }

    /**
     * Validate that at least one field is selected (Step 2).
     */
    protected function validateFieldSelection(): void
    {
        if (empty($this->selectedFields)) {
            $this->addError('selectedFields', 'Wybierz co najmniej jedno pole do eksportu.');
            throw new \Illuminate\Validation\ValidationException(
                validator([], []),
            );
        }
    }

    /**
     * Default rules for Livewire automatic validation.
     *
     * @return array<string, string>
     */
    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'format' => 'required|in:' . implode(',', ExportProfile::FORMATS),
            'schedule' => 'required|in:' . implode(',', ExportProfile::SCHEDULES),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | SAVE
    |--------------------------------------------------------------------------
    */

    /**
     * Save (create or update) the export profile.
     */
    public function save(): void
    {
        $this->isSaving = true;

        try {
            $this->validateStep(1);

            if (empty($this->selectedFields)) {
                $this->addError('selectedFields', 'Wybierz co najmniej jedno pole do eksportu.');
                return;
            }

            $data = $this->buildProfileData();

            /** @var ExportProfileService $service */
            $service = app(ExportProfileService::class);
            $userId = Auth::id();

            if ($this->profileId) {
                $profile = ExportProfile::findOrFail($this->profileId);
                $service->update($profile, $data, $userId);
                Log::info('Export profile updated', ['profile_id' => $this->profileId, 'user_id' => $userId]);
                session()->flash('message', 'Profil eksportu zaktualizowany.');
                session()->flash('message_type', 'success');
            } else {
                $profile = $service->create($data, $userId);
                Log::info('Export profile created', ['profile_id' => $profile->id, 'user_id' => $userId]);
                session()->flash('message', 'Profil eksportu utworzony.');
                session()->flash('message_type', 'success');
            }

            $this->redirect(route('admin.export.index'), navigate: true);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Export profile save failed', [
                'error' => $e->getMessage(),
                'profile_id' => $this->profileId,
            ]);
            $this->addError('general', 'Blad zapisu profilu: ' . $e->getMessage());
        } finally {
            $this->isSaving = false;
        }
    }

    /**
     * Build data array for profile create/update.
     *
     * @return array<string, mixed>
     */
    protected function buildProfileData(): array
    {
        return [
            'name' => $this->name,
            'format' => $this->format,
            'schedule' => $this->schedule,
            'field_config' => $this->selectedFields,
            'filter_config' => $this->getFilterConfig(),
            'price_groups' => array_map('intval', $this->selectedPriceGroups),
            'warehouses' => array_map('intval', $this->selectedWarehouses),
            'shop_ids' => array_map('intval', $this->filterShopIds),
            'is_active' => true,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | PREVIEW
    |--------------------------------------------------------------------------
    */

    /**
     * Load preview data (first 5 products matching current profile config).
     */
    public function loadPreview(): void
    {
        try {
            $tempProfile = new ExportProfile([
                'field_config' => $this->selectedFields,
                'filter_config' => $this->getFilterConfig(),
                'price_groups' => array_map('intval', $this->selectedPriceGroups),
                'warehouses' => array_map('intval', $this->selectedWarehouses),
                'shop_ids' => array_map('intval', $this->filterShopIds),
            ]);

            /** @var ProductExportService $exportService */
            $exportService = app(ProductExportService::class);

            $query = $exportService->buildQuery($tempProfile);
            $this->previewCount = $query->count();

            $products = $query->limit(5)->get();
            $this->previewProducts = $exportService->applyFieldSelection($products, $tempProfile);
        } catch (\Exception $e) {
            Log::warning('Export preview failed', ['error' => $e->getMessage()]);
            $this->previewProducts = [];
            $this->previewCount = 0;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DATA PROVIDERS
    |--------------------------------------------------------------------------
    */

    /**
     * Load price groups and warehouses from DB.
     */
    protected function initPriceGroupsAndWarehouses(): void
    {
        $this->availablePriceGroups = PriceGroup::active()
            ->ordered()
            ->get(['id', 'name', 'code'])
            ->map(fn($pg) => ['id' => $pg->id, 'name' => $pg->name, 'code' => $pg->code])
            ->toArray();

        $this->availableWarehouses = Warehouse::active()
            ->ordered()
            ->get(['id', 'name', 'code'])
            ->map(fn($wh) => ['id' => $wh->id, 'name' => $wh->name, 'code' => $wh->code])
            ->toArray();
    }

    /**
     * Get format options from ExportProfileService (delegated).
     *
     * @return array<string, array{label: string, description: string}>
     */
    public function getFormatOptions(): array
    {
        /** @var ExportProfileService $service */
        $service = app(ExportProfileService::class);

        return $service->getFormatOptions();
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT MODE
    |--------------------------------------------------------------------------
    */

    /**
     * Load data from an existing profile for edit mode.
     */
    protected function loadExistingProfile(int $profileId): void
    {
        $profile = ExportProfile::find($profileId);

        if (!$profile) {
            session()->flash('error', 'Profil eksportu nie zostal znaleziony.');
            $this->redirect(route('admin.export.index'), navigate: true);
            return;
        }

        $this->profileId = $profile->id;
        $this->name = $profile->name;
        $this->format = $profile->format;
        $this->schedule = $profile->schedule;

        // Load field selection
        $this->loadFieldsFromProfile($profile);

        // Load filters
        $this->loadFiltersFromProfile($profile);

        // Load price groups & warehouses
        $this->selectedPriceGroups = array_map('strval', $profile->price_groups ?? []);
        $this->selectedWarehouses = array_map('strval', $profile->warehouses ?? []);
    }
}
