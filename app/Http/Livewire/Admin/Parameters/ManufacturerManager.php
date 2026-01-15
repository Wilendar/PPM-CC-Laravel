<?php

namespace App\Http\Livewire\Admin\Parameters;

use App\Models\Manufacturer;
use App\Models\PrestaShopShop;
use App\Services\PrestaShop\PrestaShopClientFactory;
use App\Services\PrestaShop\ManufacturerSyncService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Computed;

/**
 * ManufacturerManager - CRUD dla Marek z przypisaniem do sklepów
 *
 * ETAP 07g: Extended with SEO fields, logo upload, and sync controls
 *
 * Funkcje:
 * - Lista marek z filtrowaniem i wyszukiwaniem
 * - Dodawanie/edycja marki z polami SEO
 * - Upload/podgląd logo
 * - Przypisywanie do sklepów PrestaShop
 * - Status synchronizacji per sklep z kontrolkami sync
 */
class ManufacturerManager extends Component
{
    use WithFileUploads;

    // Modal states
    public bool $showModal = false;
    public bool $showShopsModal = false;
    public bool $showDeleteModal = false;
    public bool $showSyncModal = false;

    // Editing state
    public ?int $editingId = null;

    // Logo upload
    public $logoUpload = null;
    public ?string $existingLogoUrl = null;

    // Form data - ETAP 07g: Extended with SEO fields
    public array $formData = [
        'name' => '',
        'code' => '',
        'description' => '',
        'short_description' => '',
        'meta_title' => '',
        'meta_description' => '',
        'meta_keywords' => '',
        'ps_link_rewrite' => '',
        'website' => '',
        'is_active' => true,
        'sort_order' => 0,
    ];

    // Sync modal data
    public ?int $syncManufacturerId = null;
    public array $shopSyncDetails = [];
    public bool $isSyncing = false;

    // Shop assignment
    public ?int $selectedManufacturerId = null;
    public array $selectedShopIds = [];

    // Filters
    public string $search = '';
    public string $statusFilter = 'all';

    // Delete confirmation
    public ?int $deleteId = null;
    public string $deleteName = '';

    protected $listeners = ['refreshManufacturers' => '$refresh'];

    #[Computed]
    public function manufacturers()
    {
        return Manufacturer::query()
            ->withCount(['products', 'shops'])
            ->when($this->search, fn($q) => $q->search($this->search))
            ->when($this->statusFilter === 'active', fn($q) => $q->active())
            ->when($this->statusFilter === 'inactive', fn($q) => $q->where('is_active', false))
            ->ordered()
            ->get();
    }

    #[Computed]
    public function shops()
    {
        return PrestaShopShop::active()->orderBy('name')->get();
    }

    #[Computed]
    public function stats(): array
    {
        return [
            'total' => Manufacturer::count(),
            'active' => Manufacturer::active()->count(),
            'inactive' => Manufacturer::where('is_active', false)->count(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | CRUD OPERATIONS
    |--------------------------------------------------------------------------
    */

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $manufacturer = Manufacturer::findOrFail($id);

        $this->editingId = $id;
        $this->formData = [
            'name' => $manufacturer->name,
            'code' => $manufacturer->code,
            'description' => $manufacturer->description ?? '',
            'short_description' => $manufacturer->short_description ?? '',
            'meta_title' => $manufacturer->meta_title ?? '',
            'meta_description' => $manufacturer->meta_description ?? '',
            'meta_keywords' => $manufacturer->meta_keywords ?? '',
            'ps_link_rewrite' => $manufacturer->ps_link_rewrite ?? '',
            'website' => $manufacturer->website ?? '',
            'is_active' => $manufacturer->is_active,
            'sort_order' => $manufacturer->sort_order,
        ];

        // Load existing logo URL for preview
        $this->existingLogoUrl = $manufacturer->getLogoUrl();
        $this->logoUpload = null;

        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'formData.name' => 'required|string|max:255',
            'formData.code' => 'nullable|string|max:50',
            'formData.description' => 'nullable|string',
            'formData.short_description' => 'nullable|string|max:1000',
            'formData.meta_title' => 'nullable|string|max:255',
            'formData.meta_description' => 'nullable|string|max:512',
            'formData.meta_keywords' => 'nullable|string|max:255',
            'formData.ps_link_rewrite' => 'nullable|string|max:128|regex:/^[a-z0-9-]*$/',
            'formData.website' => 'nullable|url|max:255',
            'formData.is_active' => 'boolean',
            'formData.sort_order' => 'integer|min:0',
            'logoUpload' => 'nullable|image|max:2048', // Max 2MB
        ]);

        $data = $this->formData;

        // Auto-generate code if empty
        if (empty($data['code'])) {
            $data['code'] = Str::slug($data['name'], '_');
        }

        // Auto-generate link_rewrite if empty
        if (empty($data['ps_link_rewrite'])) {
            $data['ps_link_rewrite'] = Str::slug($data['name']);
        }

        // Check code uniqueness
        $existingCode = Manufacturer::where('code', $data['code'])
            ->when($this->editingId, fn($q) => $q->where('id', '!=', $this->editingId))
            ->exists();

        if ($existingCode) {
            $this->addError('formData.code', 'Taki kod juz istnieje');
            return;
        }

        if ($this->editingId) {
            $manufacturer = Manufacturer::findOrFail($this->editingId);
            $manufacturer->update($data);
            $message = "Marka '{$manufacturer->name}' zostala zaktualizowana";
        } else {
            $manufacturer = Manufacturer::create($data);
            $message = "Marka '{$manufacturer->name}' zostala utworzona";
        }

        // Handle logo upload
        if ($this->logoUpload) {
            $this->handleLogoUpload($manufacturer);
        }

        $this->closeModal();
        $this->dispatch('flash-message', type: 'success', message: $message);
    }

    /**
     * Handle logo upload for manufacturer
     */
    protected function handleLogoUpload(Manufacturer $manufacturer): void
    {
        try {
            // Generate filename
            $extension = $this->logoUpload->getClientOriginalExtension();
            $filename = 'manufacturers/' . Str::slug($manufacturer->name) . '-' . $manufacturer->id . '.' . $extension;

            // Delete old logo if exists
            if ($manufacturer->logo_path && Storage::disk('public')->exists($manufacturer->logo_path)) {
                Storage::disk('public')->delete($manufacturer->logo_path);
            }

            // Store new logo
            $this->logoUpload->storeAs('public', $filename);

            // Update manufacturer
            $manufacturer->update(['logo_path' => $filename]);

            // Mark logo as not synced for all shops
            $manufacturer->shops()->update(['logo_synced' => false, 'logo_synced_at' => null]);

            Log::info('[MANUFACTURER] Logo uploaded', [
                'manufacturer_id' => $manufacturer->id,
                'filename' => $filename,
            ]);

        } catch (\Exception $e) {
            Log::error('[MANUFACTURER] Logo upload failed', [
                'manufacturer_id' => $manufacturer->id,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('flash-message', type: 'warning', message: 'Logo nie zostalo zapisane: ' . $e->getMessage());
        }
    }

    public function confirmDelete(int $id): void
    {
        $manufacturer = Manufacturer::findOrFail($id);
        $this->deleteId = $id;
        $this->deleteName = $manufacturer->name;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        if (!$this->deleteId) {
            return;
        }

        $manufacturer = Manufacturer::findOrFail($this->deleteId);

        if (!$manufacturer->canDelete()) {
            $this->dispatch('flash-message', type: 'error', message: 'Nie mozna usunac marki - przypisane produkty');
            $this->showDeleteModal = false;
            return;
        }

        $name = $manufacturer->name;
        $manufacturer->delete();

        $this->showDeleteModal = false;
        $this->deleteId = null;
        $this->deleteName = '';

        $this->dispatch('flash-message', type: 'success', message: "Marka '{$name}' zostala usunieta");
    }

    /*
    |--------------------------------------------------------------------------
    | SHOP ASSIGNMENT
    |--------------------------------------------------------------------------
    */

    public function openShopsModal(int $manufacturerId): void
    {
        $manufacturer = Manufacturer::with('shops')->findOrFail($manufacturerId);

        $this->selectedManufacturerId = $manufacturerId;
        $this->selectedShopIds = $manufacturer->shops->pluck('id')->toArray();
        $this->showShopsModal = true;
    }

    public function toggleShop(int $shopId): void
    {
        if (in_array($shopId, $this->selectedShopIds)) {
            $this->selectedShopIds = array_values(array_diff($this->selectedShopIds, [$shopId]));
        } else {
            $this->selectedShopIds[] = $shopId;
        }
    }

    public function saveShopAssignments(): void
    {
        if (!$this->selectedManufacturerId) {
            return;
        }

        $manufacturer = Manufacturer::findOrFail($this->selectedManufacturerId);

        // Get current shop IDs
        $currentShopIds = $manufacturer->shops->pluck('id')->toArray();

        // Shops to add (sync as pending)
        $toAdd = array_diff($this->selectedShopIds, $currentShopIds);
        foreach ($toAdd as $shopId) {
            $manufacturer->assignToShop($shopId);
        }

        // Shops to remove
        $toRemove = array_diff($currentShopIds, $this->selectedShopIds);
        foreach ($toRemove as $shopId) {
            $manufacturer->removeFromShop($shopId);
        }

        $this->showShopsModal = false;
        $this->dispatch('flash-message', type: 'success', message: 'Przypisanie sklepow zaktualizowane');
    }

    /*
    |--------------------------------------------------------------------------
    | SYNC OPERATIONS - ETAP 07g
    |--------------------------------------------------------------------------
    */

    /**
     * Open sync modal for manufacturer
     */
    public function openSyncModal(int $manufacturerId): void
    {
        $manufacturer = Manufacturer::with('shops')->findOrFail($manufacturerId);

        $this->syncManufacturerId = $manufacturerId;
        $this->shopSyncDetails = [];

        // Build sync details for each assigned shop
        foreach ($manufacturer->shops as $shop) {
            $this->shopSyncDetails[$shop->id] = [
                'shop_name' => $shop->name,
                'ps_manufacturer_id' => $shop->pivot->ps_manufacturer_id,
                'sync_status' => $shop->pivot->sync_status ?? 'pending',
                'synced_at' => $shop->pivot->synced_at,
                'logo_synced' => $shop->pivot->logo_synced ?? false,
                'logo_synced_at' => $shop->pivot->logo_synced_at,
                'sync_error' => $shop->pivot->sync_error,
            ];
        }

        $this->showSyncModal = true;
    }

    /**
     * Sync manufacturer to specific shop
     */
    public function syncToShop(int $shopId): void
    {
        if (!$this->syncManufacturerId) {
            return;
        }

        $this->isSyncing = true;

        try {
            $manufacturer = Manufacturer::findOrFail($this->syncManufacturerId);
            $shop = PrestaShopShop::findOrFail($shopId);

            $syncService = app(ManufacturerSyncService::class);
            $result = $syncService->syncToPrestaShop($manufacturer, $shop, syncLogo: true);

            if ($result['success']) {
                $action = $result['action'] === 'created' ? 'utworzony' : 'zaktualizowany';
                $this->dispatch('flash-message', type: 'success', message: "Producent {$action} w {$shop->name} (PS ID: {$result['ps_id']})");

                // Refresh sync details
                $this->refreshSyncDetails();
            } else {
                $this->dispatch('flash-message', type: 'error', message: "Blad sync: {$result['error']}");
            }

        } catch (\Exception $e) {
            Log::error('[MANUFACTURER UI] Sync failed', [
                'manufacturer_id' => $this->syncManufacturerId,
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('flash-message', type: 'error', message: 'Blad synchronizacji: ' . $e->getMessage());
        }

        $this->isSyncing = false;
    }

    /**
     * Import logo from PrestaShop
     */
    public function importLogoFromShop(int $shopId): void
    {
        if (!$this->syncManufacturerId) {
            return;
        }

        $this->isSyncing = true;

        try {
            $manufacturer = Manufacturer::findOrFail($this->syncManufacturerId);
            $shop = PrestaShopShop::findOrFail($shopId);

            $syncService = app(ManufacturerSyncService::class);
            $success = $syncService->importLogoFromPrestaShop($manufacturer, $shop);

            if ($success) {
                $this->dispatch('flash-message', type: 'success', message: "Logo zaimportowane z {$shop->name}");
                $this->refreshSyncDetails();
            } else {
                $this->dispatch('flash-message', type: 'warning', message: 'Brak logo w PrestaShop lub blad pobierania');
            }

        } catch (\Exception $e) {
            Log::error('[MANUFACTURER UI] Logo import failed', [
                'manufacturer_id' => $this->syncManufacturerId,
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('flash-message', type: 'error', message: 'Blad importu logo: ' . $e->getMessage());
        }

        $this->isSyncing = false;
    }

    /**
     * Sync logo to PrestaShop
     */
    public function syncLogoToShop(int $shopId): void
    {
        if (!$this->syncManufacturerId) {
            return;
        }

        $this->isSyncing = true;

        try {
            $manufacturer = Manufacturer::findOrFail($this->syncManufacturerId);
            $shop = PrestaShopShop::findOrFail($shopId);

            if (!$manufacturer->hasLogo()) {
                $this->dispatch('flash-message', type: 'warning', message: 'Brak logo do synchronizacji');
                $this->isSyncing = false;
                return;
            }

            $syncService = app(ManufacturerSyncService::class);
            $success = $syncService->syncLogoToPrestaShop($manufacturer, $shop);

            if ($success) {
                $this->dispatch('flash-message', type: 'success', message: "Logo wyslane do {$shop->name}");
                $this->refreshSyncDetails();
            } else {
                $this->dispatch('flash-message', type: 'error', message: 'Blad wysylania logo');
            }

        } catch (\Exception $e) {
            Log::error('[MANUFACTURER UI] Logo sync failed', [
                'manufacturer_id' => $this->syncManufacturerId,
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('flash-message', type: 'error', message: 'Blad synchronizacji logo: ' . $e->getMessage());
        }

        $this->isSyncing = false;
    }

    /**
     * Delete logo from manufacturer
     */
    public function deleteLogo(): void
    {
        if (!$this->editingId) {
            return;
        }

        try {
            $manufacturer = Manufacturer::findOrFail($this->editingId);

            if ($manufacturer->logo_path && Storage::disk('public')->exists($manufacturer->logo_path)) {
                Storage::disk('public')->delete($manufacturer->logo_path);
            }

            $manufacturer->update(['logo_path' => null]);

            // Mark logo as not synced for all shops
            $manufacturer->shops()->update(['logo_synced' => false, 'logo_synced_at' => null]);

            $this->existingLogoUrl = null;
            $this->logoUpload = null;

            $this->dispatch('flash-message', type: 'success', message: 'Logo zostalo usuniete');

        } catch (\Exception $e) {
            Log::error('[MANUFACTURER] Logo delete failed', [
                'manufacturer_id' => $this->editingId,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('flash-message', type: 'error', message: 'Blad usuwania logo');
        }
    }

    /**
     * Refresh sync details after operation
     */
    protected function refreshSyncDetails(): void
    {
        if (!$this->syncManufacturerId) {
            return;
        }

        $manufacturer = Manufacturer::with('shops')->findOrFail($this->syncManufacturerId);

        $this->shopSyncDetails = [];
        foreach ($manufacturer->shops as $shop) {
            $this->shopSyncDetails[$shop->id] = [
                'shop_name' => $shop->name,
                'ps_manufacturer_id' => $shop->pivot->ps_manufacturer_id,
                'sync_status' => $shop->pivot->sync_status ?? 'pending',
                'synced_at' => $shop->pivot->synced_at,
                'logo_synced' => $shop->pivot->logo_synced ?? false,
                'logo_synced_at' => $shop->pivot->logo_synced_at,
                'sync_error' => $shop->pivot->sync_error,
            ];
        }
    }

    /**
     * Close sync modal
     */
    public function closeSyncModal(): void
    {
        $this->showSyncModal = false;
        $this->syncManufacturerId = null;
        $this->shopSyncDetails = [];
        $this->isSyncing = false;
    }

    /*
    |--------------------------------------------------------------------------
    | PRESTASHOP IMPORT
    |--------------------------------------------------------------------------
    */

    /**
     * Import manufacturers from PrestaShop
     *
     * @param int $shopId PrestaShop shop ID
     */
    public function importFromPrestaShop(int $shopId): void
    {
        $shop = PrestaShopShop::find($shopId);

        if (!$shop) {
            $this->dispatch('flash-message', type: 'error', message: 'Sklep nie znaleziony');
            return;
        }

        try {
            $client = app(PrestaShopClientFactory::class)->create($shop);
            $psManufacturers = $client->getManufacturers();

            $imported = 0;
            $updated = 0;
            $skipped = 0;

            foreach ($psManufacturers as $psManufacturer) {
                $result = $this->importSingleManufacturer($psManufacturer, $shop);

                if ($result === 'imported') {
                    $imported++;
                } elseif ($result === 'updated') {
                    $updated++;
                } else {
                    $skipped++;
                }
            }

            $message = "Import zakonczony: {$imported} dodanych, {$updated} zaktualizowanych, {$skipped} pominietych";
            $this->dispatch('flash-message', type: 'success', message: $message);

            Log::info('Manufacturers import from PrestaShop completed', [
                'shop_id' => $shopId,
                'shop_name' => $shop->name,
                'imported' => $imported,
                'updated' => $updated,
                'skipped' => $skipped,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to import manufacturers from PrestaShop', [
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('flash-message', type: 'error', message: 'Blad importu: ' . $e->getMessage());
        }
    }

    /**
     * Import single manufacturer from PrestaShop data
     *
     * @param array $psManufacturer PrestaShop manufacturer data
     * @param PrestaShopShop $shop Shop instance
     * @return string 'imported'|'updated'|'skipped'
     */
    protected function importSingleManufacturer(array $psManufacturer, PrestaShopShop $shop): string
    {
        // Extract manufacturer name (handle multilang format)
        $name = $this->extractMultilangValue($psManufacturer['name'] ?? '');

        if (empty($name)) {
            return 'skipped';
        }

        // Generate code from name
        $code = Str::slug($name, '_');

        // Check if manufacturer exists by code
        $existing = Manufacturer::where('code', $code)->first();

        $manufacturerData = [
            'name' => $name,
            'code' => $code,
            'description' => $this->extractMultilangValue($psManufacturer['description'] ?? null),
            'is_active' => ($psManufacturer['active'] ?? '1') === '1',
        ];

        if ($existing) {
            // Update existing
            $existing->update($manufacturerData);

            // Ensure shop assignment
            if (!$existing->shops()->where('prestashop_shop_id', $shop->id)->exists()) {
                $existing->assignToShop($shop->id);
            }

            return 'updated';
        } else {
            // Create new
            $manufacturer = Manufacturer::create($manufacturerData);

            // Assign to shop with PrestaShop ID
            $manufacturer->shops()->attach($shop->id, [
                'prestashop_manufacturer_id' => $psManufacturer['id'] ?? null,
                'sync_status' => 'synced',
                'synced_at' => now(),
            ]);

            return 'imported';
        }
    }

    /**
     * Extract value from multilang format
     *
     * @param mixed $value
     * @return string|null
     */
    protected function extractMultilangValue($value): ?string
    {
        if (is_string($value)) {
            return $value ?: null;
        }

        if (is_array($value)) {
            // Format: ['language' => [['id' => 1, 'value' => 'Text']]]
            if (isset($value['language'])) {
                $languages = is_array($value['language']) ? $value['language'] : [$value['language']];
                $first = reset($languages);
                return is_array($first) ? ($first['value'] ?? null) : (string) $first;
            }

            // Format: [['id' => 1, 'value' => 'Text']]
            $first = reset($value);
            return is_array($first) ? ($first['value'] ?? null) : (string) $first;
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function closeShopsModal(): void
    {
        $this->showShopsModal = false;
        $this->selectedManufacturerId = null;
        $this->selectedShopIds = [];
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deleteId = null;
        $this->deleteName = '';
    }

    private function resetForm(): void
    {
        $this->formData = [
            'name' => '',
            'code' => '',
            'description' => '',
            'short_description' => '',
            'meta_title' => '',
            'meta_description' => '',
            'meta_keywords' => '',
            'ps_link_rewrite' => '',
            'website' => '',
            'is_active' => true,
            'sort_order' => 0,
        ];
        $this->editingId = null;
        $this->logoUpload = null;
        $this->existingLogoUrl = null;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.admin.parameters.manufacturer-manager');
    }
}
