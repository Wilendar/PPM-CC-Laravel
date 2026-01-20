<?php

namespace App\Http\Livewire\Products\Management\Traits;

use App\Models\ERPConnection;
use App\Models\ProductErpData;
use App\Services\ERP\BaselinkerService;
use App\Jobs\ERP\SyncProductToERP;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;

/**
 * ProductFormERPTabs Trait
 *
 * ETAP_08.3: ERP Tab in ProductForm (Shop-Tab Pattern)
 *
 * Trait managing ERP connection tabs in the product form.
 * Uses SAME FORM FIELDS as default/shop modes (not separate fields!)
 *
 * KEY PRINCIPLE: When ERP tab is selected, ERP data loads INTO THE SAME
 * form fields (SKU, name, etc.) and badges show comparison with PPM defaults.
 *
 * MODELED AFTER ProductFormShopTabs - same pattern, same UX!
 *
 * @package App\Http\Livewire\Products\Management\Traits
 * @version 2.0 (Shop-Tab Pattern)
 * @since ETAP_08.3 - ERP Tab Implementation
 */
trait ProductFormERPTabs
{
    /**
     * Active ERP tab identifier
     * Format: 'all' or 'erp_{connectionId}'
     */
    public string $activeErpTab = 'all';

    /**
     * Currently selected ERP connection ID (like $activeShopId for shops)
     * null = showing default PPM data, int = showing specific ERP connection data
     */
    public ?int $activeErpConnectionId = null;

    /**
     * ERP external data cache (from last pull)
     * Structure: [
     *   'connection' => ERPConnection,
     *   'external_id' => string|null,
     *   'sync_status' => string,
     *   'external_data' => array (raw data from ERP API),
     *   'pending_fields' => array,
     *   'last_sync_at' => Carbon|null,
     * ]
     */
    public array $erpExternalData = [];

    /**
     * Original PPM values when entering ERP tab (for comparison)
     * Like $defaultData in shop tabs - stores default values for comparison
     */
    public array $erpDefaultData = [];

    /**
     * Flag indicating if data is being synced to ERP
     */
    public bool $syncingToErp = false;

    /**
     * Flag indicating if data is being loaded from ERP
     */
    public bool $loadingErpData = false;

    // === ETAP_08.5: ERP JOB TRACKING (like PrestaShop pattern) ===

    /**
     * Active ERP job status for UI tracking
     * Values: 'pending'|'running'|'completed'|'failed'|null
     */
    public ?string $activeErpJobStatus = null;

    /**
     * Type of active ERP job
     * Values: 'sync' (PPM → ERP) | 'pull' (ERP → PPM) | null
     */
    public ?string $activeErpJobType = null;

    /**
     * ERP connection ID for which job is running
     */
    public ?int $activeErpJobConnectionId = null;

    /**
     * ISO8601 timestamp when ERP job was created (for countdown animation)
     */
    public ?string $erpJobCreatedAt = null;

    /**
     * Result of ERP job after completion
     * Values: 'success'|'error'|null
     */
    public ?string $erpJobResult = null;

    /**
     * Result message from ERP sync
     */
    public ?string $erpJobMessage = null;

    // ==========================================
    // TAB SELECTION METHODS (Shop-Tab Pattern)
    // ==========================================

    /**
     * Select ERP tab and load ERP data to form fields
     * MIRRORS: selectShopTab() from ProductFormShopTabs
     *
     * @param int $connectionId
     * @return void
     */
    public function selectErpTab(int $connectionId): void
    {
        $this->activeErpConnectionId = $connectionId;
        $this->activeErpTab = "erp_{$connectionId}";

        // IMPORTANT: Clear shop context when entering ERP context
        $this->activeShopId = null;

        // Store current PPM values as defaults (for comparison)
        $this->storeDefaultData();

        // Load ERP data TO FORM FIELDS (KEY!)
        if ($this->product && $this->isEditMode) {
            $this->loadErpDataToForm($connectionId);
        }

        Log::info('ERP tab selected', [
            'product_id' => $this->product->id ?? null,
            'connection_id' => $connectionId,
            'active_erp_tab' => $this->activeErpTab,
        ]);
    }

    /**
     * Reset ERP tab to default view (show PPM default data)
     * MIRRORS: selectDefaultTab() pattern
     *
     * @return void
     */
    public function selectDefaultErpTab(): void
    {
        $this->activeErpConnectionId = null;
        $this->activeErpTab = 'all';
        $this->erpExternalData = [];
        $this->erpDefaultData = [];

        // Restore default PPM data to form fields
        $this->loadDefaultDataToForm();

        Log::info('ERP default tab selected, restored PPM defaults');
    }

    // ==========================================
    // DATA LOADING METHODS
    // ==========================================

    /**
     * Load ERP data to form fields (from product_erp_data + external_data cache)
     *
     * ETAP_08.4: FULL SHOP-TAB PATTERN - OVERRIDE form fields with ERP data!
     *
     * KEY METHOD: Loads ERP-specific overrides to THE SAME form fields
     * Priority: product_erp_data columns > external_data (API cache) > PPM defaults
     *
     * ETAP_08.8 FIX: NIE tworzy automatycznie rekordu ProductErpData!
     * Jeśli produkt nie jest powiązany z ERP, pokazuje UI do sprawdzenia/linkowania.
     *
     * @param int $connectionId
     * @return void
     */
    protected function loadErpDataToForm(int $connectionId): void
    {
        $connection = ERPConnection::find($connectionId);
        if (!$connection || !$this->product) {
            $this->erpExternalData = [];
            return;
        }

        // ETAP_08.8 FIX: NIE używaj getOrCreateErpData() - sprawdź czy istnieje
        $erpData = $this->product->erpData()
            ->where('erp_connection_id', $connectionId)
            ->first();

        // Store default PPM values for comparison (used by getFieldStatus)
        $this->erpDefaultData = $this->defaultData;

        // ETAP_08.8: Jeśli brak rekordu = produkt NIE jest powiązany z tym ERP
        if (!$erpData) {
            $this->erpExternalData = [
                'connection' => $connection,
                'erp_data_id' => null,
                'external_id' => null,
                'sync_status' => 'not_linked',  // Nowy status: nie powiązany
                'pending_fields' => [],
                'external_data' => [],
                'last_sync_at' => null,
                'last_pull_at' => null,
                'last_push_at' => null,
                'error_message' => null,
            ];

            Log::info('ERP tab selected - product NOT linked to this ERP', [
                'product_id' => $this->product->id,
                'connection_id' => $connectionId,
                'connection_name' => $connection->instance_name,
            ]);

            return; // NIE nadpisuj pól formularza, zostaw PPM defaults
        }

        // ETAP_08.4: Check if we should pull fresh data from ERP
        if ($this->shouldPullFromErp($erpData)) {
            $this->tryPullFromErp($connectionId);
            $erpData->refresh();
        }

        // Store ERP data for UI display and comparison
        $this->erpExternalData = [
            'connection' => $connection,
            'erp_data_id' => $erpData->id,
            'external_id' => $erpData->external_id,
            'sync_status' => $erpData->sync_status,
            'pending_fields' => $erpData->pending_fields ?? [],
            'external_data' => $erpData->external_data ?? [],
            'last_sync_at' => $erpData->last_sync_at,
            'last_pull_at' => $erpData->last_pull_at,
            'last_push_at' => $erpData->last_push_at,
            'error_message' => $erpData->error_message,
        ];

        // ETAP_08.4: CRITICAL - OVERRIDE form fields with ERP data (SHOP-TAB PATTERN!)
        // Priority: product_erp_data columns > external_data (API cache) > PPM defaults
        $this->overrideFormFieldsWithErpData($erpData);

        Log::info('ERP data loaded to form fields (OVERRIDE)', [
            'product_id' => $this->product->id,
            'connection_id' => $connectionId,
            'has_external_data' => !empty($erpData->external_data),
            'pending_fields' => $erpData->pending_fields ?? [],
        ]);
    }

    /**
     * ETAP_08.4: Override form fields with ERP data
     *
     * Priority:
     * 1. product_erp_data columns (user edits saved here)
     * 2. external_data cache (raw API data from ERP)
     * 3. PPM defaults (fallback)
     *
     * @param ProductErpData $erpData
     * @return void
     */
    protected function overrideFormFieldsWithErpData(ProductErpData $erpData): void
    {
        // === BASIC INFORMATION ===
        $this->sku = $erpData->sku
            ?? $this->getExternalDataValue('sku', $erpData)
            ?? $this->erpDefaultData['sku']
            ?? $this->sku;

        $this->name = $erpData->name
            ?? $this->getExternalDataValue('name', $erpData)
            ?? $this->erpDefaultData['name']
            ?? $this->name;

        $this->ean = $erpData->ean
            ?? $this->getExternalDataValue('ean', $erpData)
            ?? $this->erpDefaultData['ean']
            ?? $this->ean;

        $this->manufacturer = $erpData->manufacturer
            ?? $this->getExternalDataValue('manufacturer', $erpData)
            ?? $this->erpDefaultData['manufacturer']
            ?? $this->manufacturer;

        $this->supplier_code = $erpData->supplier_code
            ?? $this->getExternalDataValue('supplier_code', $erpData)
            ?? $this->erpDefaultData['supplier_code']
            ?? $this->supplier_code;

        // === DESCRIPTIONS ===
        $this->short_description = $erpData->short_description
            ?? $this->getExternalDataValue('short_description', $erpData)
            ?? $this->erpDefaultData['short_description']
            ?? $this->short_description;

        $this->long_description = $erpData->long_description
            ?? $this->getExternalDataValue('long_description', $erpData)
            ?? $this->erpDefaultData['long_description']
            ?? $this->long_description;

        $this->meta_title = $erpData->meta_title
            ?? $this->getExternalDataValue('meta_title', $erpData)
            ?? $this->erpDefaultData['meta_title']
            ?? $this->meta_title;

        $this->meta_description = $erpData->meta_description
            ?? $this->getExternalDataValue('meta_description', $erpData)
            ?? $this->erpDefaultData['meta_description']
            ?? $this->meta_description;

        // === PHYSICAL PROPERTIES ===
        $this->weight = $erpData->weight
            ?? $this->getExternalDataValue('weight', $erpData)
            ?? $this->erpDefaultData['weight']
            ?? $this->weight;

        $this->height = $erpData->height
            ?? $this->getExternalDataValue('height', $erpData)
            ?? $this->erpDefaultData['height']
            ?? $this->height;

        $this->width = $erpData->width
            ?? $this->getExternalDataValue('width', $erpData)
            ?? $this->erpDefaultData['width']
            ?? $this->width;

        $this->length = $erpData->length
            ?? $this->getExternalDataValue('length', $erpData)
            ?? $this->erpDefaultData['length']
            ?? $this->length;

        $this->tax_rate = $erpData->tax_rate
            ?? $this->getExternalDataValue('tax_rate', $erpData)
            ?? $this->erpDefaultData['tax_rate']
            ?? $this->tax_rate;

        // === STATUS ===
        $this->is_active = $erpData->is_active
            ?? $this->getExternalDataValue('is_active', $erpData)
            ?? $this->erpDefaultData['is_active']
            ?? $this->is_active;
    }

    /**
     * ETAP_08.4: Get value from external_data cache with field mapping
     *
     * Handles different ERP data formats (Baselinker, Subiekt GT, Dynamics)
     *
     * @param string $fieldName PPM field name
     * @param ProductErpData $erpData
     * @return mixed|null
     */
    protected function getExternalDataValue(string $fieldName, ProductErpData $erpData): mixed
    {
        $externalData = $erpData->external_data ?? [];

        if (empty($externalData)) {
            return null;
        }

        // Baselinker mapping (text_fields format)
        $baselinkerMapping = [
            'name' => 'text_fields.name',
            'short_description' => 'text_fields.short_description',
            'long_description' => 'text_fields.description',
            'sku' => 'sku',
            'ean' => 'ean',
            'manufacturer' => 'manufacturer',
            'weight' => 'weight',
            'height' => 'height',
            'width' => 'width',
            'length' => 'length',
            'is_active' => 'is_bundle', // Note: inverse logic may apply
        ];

        // Try direct field access first
        if (isset($externalData[$fieldName])) {
            return $externalData[$fieldName];
        }

        // Try Baselinker-style mapping
        if (isset($baselinkerMapping[$fieldName])) {
            $mappedPath = $baselinkerMapping[$fieldName];

            // Support dot notation for nested fields
            $keys = explode('.', $mappedPath);
            $value = $externalData;

            foreach ($keys as $key) {
                if (!is_array($value) || !isset($value[$key])) {
                    return null;
                }
                $value = $value[$key];
            }

            return $value;
        }

        return null;
    }

    /**
     * ETAP_08.4: Check if fresh data should be pulled from ERP
     *
     * Returns true if:
     * - Never pulled before
     * - Last pull was > 5 minutes ago
     * - Has external_id but no external_data
     *
     * @param ProductErpData $erpData
     * @return bool
     */
    protected function shouldPullFromErp(ProductErpData $erpData): bool
    {
        // Never pulled
        if (!$erpData->last_pull_at) {
            return (bool) $erpData->external_id; // Only if has external_id
        }

        // Pulled > 5 min ago
        if ($erpData->last_pull_at->lt(now()->subMinutes(5))) {
            return true;
        }

        // Has external_id but no data
        if ($erpData->external_id && empty($erpData->external_data)) {
            return true;
        }

        return false;
    }

    /**
     * Try to pull product data from ERP (if mapping exists)
     *
     * @param int $connectionId
     * @return void
     */
    protected function tryPullFromErp(int $connectionId): void
    {
        $connection = ERPConnection::find($connectionId);
        if (!$connection || !$this->product) {
            return;
        }

        // Check if product has existing mapping via IntegrationMapping
        $mapping = $this->product->integrationMappings()
            ->where('integration_type', $connection->erp_type)
            ->where('integration_identifier', $connection->instance_name)
            ->first();

        if ($mapping && $mapping->external_id) {
            // Has mapping - trigger async pull
            Log::info('ERP mapping found, pulling data', [
                'product_id' => $this->product->id,
                'connection_id' => $connectionId,
                'external_id' => $mapping->external_id,
            ]);

            // Update ProductErpData with external_id from mapping
            $this->product->erpData()
                ->where('erp_connection_id', $connectionId)
                ->update(['external_id' => $mapping->external_id]);
        }
    }

    // ==========================================
    // PULL/PUSH METHODS
    // ==========================================

    /**
     * Pull product data from ERP (PULL: ERP -> PPM)
     * MIRRORS: loadProductDataFromPrestaShop() in shop tabs
     *
     * @param int $connectionId
     * @param bool $forceRefresh
     * @return void
     */
    public function pullProductDataFromErp(int $connectionId, bool $forceRefresh = false): void
    {
        $connection = ERPConnection::find($connectionId);
        if (!$connection || !$this->product) {
            $this->addError('erp_pull', 'Brak polaczenia ERP lub produktu');
            return;
        }

        $this->loadingErpData = true;

        try {
            // Get ProductErpData record
            $erpData = $this->product->getOrCreateErpData($connectionId);

            if (!$erpData->external_id) {
                $this->addError('erp_pull', 'Produkt nie jest powiazany z tym systemem ERP. Najpierw wykonaj synchronizacje.');
                return;
            }

            // Use ERP service to pull data
            $service = app(BaselinkerService::class);
            $result = $service->syncProductFromERP($connection, $erpData->external_id);

            if ($result['success']) {
                // Update external_data cache
                $erpData->update([
                    'external_data' => $result['data'],
                    'last_pull_at' => now(),
                    'sync_status' => ProductErpData::STATUS_SYNCED,
                    'error_message' => null,
                ]);

                // Refresh UI data
                $this->erpExternalData['external_data'] = $result['data'];
                $this->erpExternalData['sync_status'] = ProductErpData::STATUS_SYNCED;
                $this->erpExternalData['last_pull_at'] = now();

                session()->flash('message', 'Dane pobrane z ERP: ' . $connection->instance_name);

                Log::info('ERP data pulled successfully', [
                    'product_id' => $this->product->id,
                    'connection_id' => $connectionId,
                ]);
            } else {
                $errorMsg = $result['message'] ?? 'Unknown error';
                $erpData->markAsError($errorMsg);
                $this->addError('erp_pull', 'Blad pobierania: ' . $errorMsg);
            }
        } catch (\Exception $e) {
            $this->addError('erp_pull', 'Blad pobierania: ' . $e->getMessage());
            Log::error('ERP pull error', [
                'product_id' => $this->product->id,
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
            ]);
        } finally {
            $this->loadingErpData = false;
        }
    }

    /**
     * ETAP_08.5: Sync product to ERP (PUSH: PPM -> ERP) - ASYNC JOB DISPATCH
     *
     * Full Shop-Tab pattern (like PrestaShop):
     * 1. Check if already syncing (prevent duplicates!)
     * 2. Save current form data to product_erp_data columns
     * 3. Mark as syncing with job tracking
     * 4. Dispatch SyncProductToERP Job (async)
     *
     * @param int $connectionId
     * @return void
     */
    public function syncToErp(int $connectionId): void
    {
        $connection = ERPConnection::find($connectionId);
        if (!$connection || !$this->product) {
            $this->addError('erp_sync', 'Brak polaczenia ERP lub produktu');
            return;
        }

        // ETAP_08.8 FIX: Sprawdź czy produkt jest powiązany z ERP przed sync
        if (!$this->isProductLinkedToErp($connectionId)) {
            $this->addError('erp_sync', 'Produkt nie jest powiazany z tym ERP. Najpierw kliknij "Dodac do ERP?"');
            Log::warning('syncToErp: Product not linked to ERP', [
                'product_id' => $this->product->id,
                'connection_id' => $connectionId,
            ]);
            return;
        }

        // ETAP_08.5: PREVENT DUPLICATE DISPATCH!
        // Check if we're already syncing this connection
        if ($this->activeErpJobStatus === 'pending' || $this->activeErpJobStatus === 'running') {
            if ($this->activeErpJobConnectionId === $connectionId) {
                Log::info('syncToErp: Skipped - job already running', [
                    'product_id' => $this->product->id,
                    'connection_id' => $connectionId,
                    'current_status' => $this->activeErpJobStatus,
                ]);
                $this->dispatch('warning', message: 'Synchronizacja juz w trakcie. Poczekaj na zakonczenie.');
                return;
            }
        }

        $this->syncingToErp = true;

        try {
            // ETAP_08.5 Step 1: Save current form data to product_erp_data columns
            $this->saveCurrentErpData($connectionId);

            // ETAP_08.5 Step 2: Mark ProductErpData as syncing
            // ETAP_08.8 FIX: Używamy zwykłego query - wiemy że rekord istnieje
            $erpData = $this->product->erpData()
                ->where('erp_connection_id', $connectionId)
                ->first();

            if (!$erpData) {
                throw new \Exception('ERP data record not found');
            }

            $erpData->markSyncing();

            // ETAP_08.5 Step 3: Set job tracking for UI (like PrestaShop pattern)
            $this->activeErpJobStatus = 'pending';
            $this->activeErpJobType = 'sync';
            $this->activeErpJobConnectionId = $connectionId;
            $this->erpJobCreatedAt = now()->toIso8601String();
            $this->erpJobResult = null;
            $this->erpJobMessage = null;

            // Update UI status
            $this->erpExternalData['sync_status'] = ProductErpData::STATUS_SYNCING;

            // ETAP_08.5 Step 4: Dispatch async Job
            SyncProductToERP::dispatch($this->product, $connection);

            // ETAP_08.6: Notify Alpine to start ERP polling (analogia do PrestaShop job-started)
            $this->dispatch('erp-job-started');

            Log::info('ERP sync job dispatched with tracking (SHOP-TAB PATTERN)', [
                'product_id' => $this->product->id,
                'connection_id' => $connectionId,
                'connection_name' => $connection->instance_name,
                'pending_fields' => $erpData->pending_fields ?? [],
                'job_status' => $this->activeErpJobStatus,
            ]);

        } catch (\Exception $e) {
            // Reset job tracking on error
            $this->activeErpJobStatus = 'failed';
            $this->erpJobResult = 'error';
            $this->erpJobMessage = $e->getMessage();

            $this->addError('erp_sync', 'Blad synchronizacji: ' . $e->getMessage());
            Log::error('ERP sync error', [
                'product_id' => $this->product->id,
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
            ]);
        } finally {
            $this->syncingToErp = false;
        }
    }

    /**
     * Pull from ERP (legacy method alias)
     */
    public function pullFromErp(int $connectionId): void
    {
        $this->pullProductDataFromErp($connectionId, true);
    }

    // ==========================================
    // PENDING CHANGES TRACKING
    // ==========================================

    /**
     * Save pending changes for ERP synchronization
     *
     * Called when user modifies a field in ERP context.
     * Marks the field as pending sync to ERP.
     *
     * @param int $connectionId
     * @param array $changedFields
     * @return void
     */
    protected function savePendingErpChanges(int $connectionId, array $changedFields): void
    {
        if (!$this->product || empty($changedFields)) {
            return;
        }

        $erpData = $this->product->getOrCreateErpData($connectionId);
        $erpData->markAsPending($changedFields);

        // Update UI
        $this->erpExternalData['pending_fields'] = $erpData->pending_fields;
        $this->erpExternalData['sync_status'] = ProductErpData::STATUS_PENDING;

        Log::debug('ERP pending changes saved', [
            'product_id' => $this->product->id,
            'connection_id' => $connectionId,
            'pending_fields' => $changedFields,
        ]);
    }

    /**
     * ETAP_08.4: Save current form data to product_erp_data columns
     *
     * Called before sync job dispatch.
     * Saves form field values to per-ERP columns (like Shop Tab saves to ProductShopData).
     *
     * @param int $connectionId
     * @return void
     */
    protected function saveCurrentErpData(int $connectionId): void
    {
        if (!$this->product) {
            return;
        }

        // ETAP_08.8 FIX: NIE twórz rekordu jeśli produkt nie jest powiązany z ERP!
        $erpData = $this->product->erpData()
            ->where('erp_connection_id', $connectionId)
            ->first();

        if (!$erpData) {
            Log::info('saveCurrentErpData: Product not linked to ERP - skipping save', [
                'product_id' => $this->product->id,
                'connection_id' => $connectionId,
            ]);
            return;
        }

        // Save current form fields to product_erp_data columns
        $erpData->update([
            'sku' => $this->sku,
            'name' => $this->name,
            'ean' => $this->ean,
            'manufacturer' => $this->manufacturer,
            'supplier_code' => $this->supplier_code,
            'short_description' => $this->short_description,
            'long_description' => $this->long_description,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'weight' => $this->weight,
            'height' => $this->height,
            'width' => $this->width,
            'length' => $this->length,
            'tax_rate' => $this->tax_rate,
            'is_active' => $this->is_active,
        ]);

        // Detect changed fields by comparing with defaults
        $changedFields = $this->detectChangedErpFields();

        if (!empty($changedFields)) {
            $erpData->markAsPending($changedFields);

            // Update UI
            $this->erpExternalData['pending_fields'] = $erpData->pending_fields;
        }

        Log::info('saveCurrentErpData: Form data saved to product_erp_data', [
            'product_id' => $this->product->id,
            'connection_id' => $connectionId,
            'changed_fields' => $changedFields,
        ]);
    }

    /**
     * ETAP_08.4: Detect which fields changed from ERP defaults
     *
     * Compares current form values with erpDefaultData (PPM defaults)
     * to determine which fields were modified by user.
     *
     * @return array List of changed field names
     */
    protected function detectChangedErpFields(): array
    {
        if (empty($this->erpDefaultData)) {
            return [];
        }

        $changedFields = [];
        $trackableFields = [
            'sku', 'name', 'ean', 'manufacturer', 'supplier_code',
            'short_description', 'long_description', 'meta_title', 'meta_description',
            'weight', 'height', 'width', 'length', 'tax_rate', 'is_active'
        ];

        foreach ($trackableFields as $field) {
            $currentValue = $this->$field ?? null;
            $defaultValue = $this->erpDefaultData[$field] ?? null;

            // Normalize values for comparison (handle empty strings vs null)
            $currentNormalized = $this->normalizeValueForComparison($currentValue);
            $defaultNormalized = $this->normalizeValueForComparison($defaultValue);

            if ($currentNormalized !== $defaultNormalized) {
                $changedFields[] = $field;
            }
        }

        return $changedFields;
    }

    /**
     * Normalize value for comparison (handle empty strings, nulls, type conversions)
     *
     * @param mixed $value
     * @return mixed
     */
    protected function normalizeValueForComparison(mixed $value): mixed
    {
        // Treat empty string as null
        if ($value === '') {
            return null;
        }

        // Convert numeric strings to floats for numeric fields
        if (is_numeric($value)) {
            return (float) $value;
        }

        return $value;
    }

    /**
     * ETAP_08.4: Track ERP field change (called from updated() hook)
     *
     * Records field as pending when user edits in ERP context.
     *
     * @param string $propertyName
     * @return void
     */
    public function trackErpFieldChange(string $propertyName): void
    {
        if ($this->activeErpConnectionId === null || !$this->product) {
            return;
        }

        $trackableFields = [
            'sku', 'name', 'ean', 'manufacturer', 'supplier_code',
            'short_description', 'long_description', 'meta_title', 'meta_description',
            'weight', 'height', 'width', 'length', 'tax_rate', 'is_active'
        ];

        if (!in_array($propertyName, $trackableFields)) {
            return;
        }

        $erpData = $this->product->getOrCreateErpData($this->activeErpConnectionId);

        // Add to pending fields
        $pending = $erpData->pending_fields ?? [];
        if (!in_array($propertyName, $pending)) {
            $pending[] = $propertyName;
            $erpData->update(['pending_fields' => $pending, 'sync_status' => ProductErpData::STATUS_PENDING]);

            // Update UI
            $this->erpExternalData['pending_fields'] = $pending;
            $this->erpExternalData['sync_status'] = ProductErpData::STATUS_PENDING;
        }

        Log::debug('ERP field change tracked', [
            'product_id' => $this->product->id,
            'connection_id' => $this->activeErpConnectionId,
            'field' => $propertyName,
            'pending_fields' => $pending,
        ]);
    }

    /**
     * ETAP_08.5: Save ERP context and dispatch sync job
     *
     * Called from saveAndClose() when in ERP context.
     * This is the main "save" action for ERP Tab - saves data and dispatches job.
     *
     * IMPORTANT: Does NOT dispatch if job already pending/running (prevents duplicates!)
     *
     * @return void
     */
    public function saveErpContextAndDispatchJob(): void
    {
        if ($this->activeErpConnectionId === null || !$this->product) {
            Log::warning('saveErpContextAndDispatchJob called without ERP context');
            return;
        }

        // ETAP_08.8 FIX: NIE dispatchuj joba jeśli produkt nie jest powiązany z ERP!
        // Użytkownik musi najpierw kliknąć "Dodać do ERP?" żeby utworzyć powiązanie
        if (!$this->isProductLinkedToErp($this->activeErpConnectionId)) {
            Log::info('saveErpContextAndDispatchJob: Product NOT linked to ERP - skipping job dispatch', [
                'product_id' => $this->product->id,
                'connection_id' => $this->activeErpConnectionId,
                'erp_link_status' => $this->getErpLinkStatus($this->activeErpConnectionId),
            ]);
            return;
        }

        $connection = ERPConnection::find($this->activeErpConnectionId);
        if (!$connection) {
            $this->addError('erp_save', 'ERP connection not found');
            return;
        }

        try {
            // Step 1: Save current form data to product_erp_data
            $this->saveCurrentErpData($this->activeErpConnectionId);

            // ETAP_08.5: Check if job already dispatched (prevents duplicate!)
            if ($this->activeErpJobStatus === 'pending' || $this->activeErpJobStatus === 'running') {
                if ($this->activeErpJobConnectionId === $this->activeErpConnectionId) {
                    Log::info('saveErpContextAndDispatchJob: Skipping dispatch - job already active', [
                        'product_id' => $this->product->id,
                        'connection_id' => $this->activeErpConnectionId,
                        'job_status' => $this->activeErpJobStatus,
                    ]);
                    return;
                }
            }

            // Step 2: Check if there are pending changes
            // ETAP_08.8 FIX: Używamy zwykłego query zamiast getOrCreateErpData()
            $erpData = $this->product->erpData()
                ->where('erp_connection_id', $this->activeErpConnectionId)
                ->first();
            $hasPendingChanges = !empty($erpData->pending_fields);

            if ($hasPendingChanges) {
                // Step 3: Mark as syncing
                $erpData->markSyncing();

                // ETAP_08.5: Set job tracking (like PrestaShop pattern)
                $this->activeErpJobStatus = 'pending';
                $this->activeErpJobType = 'sync';
                $this->activeErpJobConnectionId = $this->activeErpConnectionId;
                $this->erpJobCreatedAt = now()->toIso8601String();
                $this->erpJobResult = null;
                $this->erpJobMessage = null;

                // Update UI status
                $this->erpExternalData['sync_status'] = ProductErpData::STATUS_SYNCING;

                // Step 4: Dispatch async Job
                SyncProductToERP::dispatch($this->product, $connection);

                // ETAP_08.6: Notify Alpine to start ERP polling
                $this->dispatch('erp-job-started');

                Log::info('saveErpContextAndDispatchJob: ERP sync job dispatched with tracking', [
                    'product_id' => $this->product->id,
                    'connection_id' => $this->activeErpConnectionId,
                    'pending_fields' => $erpData->pending_fields,
                    'job_status' => $this->activeErpJobStatus,
                ]);

            } else {
                Log::info('saveErpContextAndDispatchJob: No pending changes, skipping job dispatch', [
                    'product_id' => $this->product->id,
                    'connection_id' => $this->activeErpConnectionId,
                ]);
            }

        } catch (\Exception $e) {
            // Reset job tracking on error
            $this->activeErpJobStatus = 'failed';
            $this->erpJobResult = 'error';
            $this->erpJobMessage = $e->getMessage();

            $this->addError('erp_save', 'Blad zapisu ERP: ' . $e->getMessage());
            Log::error('saveErpContextAndDispatchJob failed', [
                'product_id' => $this->product->id,
                'connection_id' => $this->activeErpConnectionId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * ETAP_08.4: Check if ERP context has pending changes
     *
     * @return bool
     */
    public function hasErpPendingChanges(): bool
    {
        if ($this->activeErpConnectionId === null || !$this->product) {
            return false;
        }

        $erpData = $this->product->erpData()
            ->where('erp_connection_id', $this->activeErpConnectionId)
            ->first();

        return !empty($erpData?->pending_fields);
    }

    /**
     * ETAP_08.4: Get count of pending ERP changes
     *
     * @return int
     */
    public function getErpPendingChangesCount(): int
    {
        if ($this->activeErpConnectionId === null || !$this->product) {
            return 0;
        }

        $erpData = $this->product->erpData()
            ->where('erp_connection_id', $this->activeErpConnectionId)
            ->first();

        return count($erpData?->pending_fields ?? []);
    }

    // ==========================================
    // COMPUTED PROPERTIES
    // ==========================================

    /**
     * Get all active ERP connections for display
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    #[Computed]
    public function activeErpConnections()
    {
        return ERPConnection::where('is_active', true)
            ->orderBy('priority', 'asc')
            ->orderBy('instance_name', 'asc')
            ->get();
    }

    // ==========================================
    // STATUS DISPLAY METHODS
    // ==========================================

    /**
     * Get ERP sync status display data for a specific connection
     *
     * @param int $connectionId
     * @return array
     */
    public function getErpSyncStatusDisplay(int $connectionId): array
    {
        $connection = ERPConnection::find($connectionId);
        if (!$connection || !$this->product) {
            return [
                'status' => 'unknown',
                'icon' => '?',
                'text' => 'Nieznany',
                'class' => 'bg-gray-500 text-white',
                'external_id' => null,
                'last_sync' => null,
            ];
        }

        // Get ProductErpData if exists
        $erpData = $this->product->erpData()
            ->where('erp_connection_id', $connectionId)
            ->first();

        // ETAP_08.8: Check erpExternalData for UI reactivity (not_found status)
        if ($this->activeErpConnectionId === $connectionId) {
            $uiStatus = $this->erpExternalData['sync_status'] ?? null;
            if ($uiStatus === 'not_found') {
                return [
                    'status' => 'not_found',
                    'icon' => '⊘',
                    'text' => 'Nie znaleziono',
                    'class' => 'bg-orange-600 text-white',
                    'external_id' => null,
                    'last_sync' => null,
                ];
            }
        }

        if (!$erpData) {
            return [
                'status' => 'not_linked',
                'icon' => '○',
                'text' => 'Nie polaczony',
                'class' => 'bg-gray-600 text-gray-300',
                'external_id' => null,
                'last_sync' => null,
            ];
        }

        // ETAP_08.6 FIX: Pobierz pending_fields dla wyswietlenia listy (jak w PrestaShop TAB)
        $pendingFields = $erpData->pending_fields ?? [];
        $pendingText = !empty($pendingFields)
            ? 'Oczekuje: ' . implode(', ', $pendingFields)
            : 'Oczekuje';

        $statusMap = [
            ProductErpData::STATUS_SYNCED => [
                'icon' => '✓',
                'text' => 'Zsynchronizowany',
                'class' => 'bg-green-600 text-white',
            ],
            ProductErpData::STATUS_PENDING => [
                'icon' => '⏳',
                'text' => $pendingText,  // ETAP_08.6: Dynamiczny tekst z lista pol
                'class' => 'bg-yellow-600 text-white',
            ],
            ProductErpData::STATUS_SYNCING => [
                'icon' => '🔄',
                'text' => 'Synchronizacja...',
                'class' => 'bg-blue-600 text-white',
            ],
            ProductErpData::STATUS_ERROR => [
                'icon' => '✗',
                'text' => 'Blad',
                'class' => 'bg-red-600 text-white',
            ],
            ProductErpData::STATUS_CONFLICT => [
                'icon' => '⚠',
                'text' => 'Konflikt',
                'class' => 'bg-orange-600 text-white',
            ],
            ProductErpData::STATUS_DISABLED => [
                'icon' => '⏸',
                'text' => 'Wylaczony',
                'class' => 'bg-gray-500 text-gray-300',
            ],
        ];

        $display = $statusMap[$erpData->sync_status] ?? $statusMap[ProductErpData::STATUS_PENDING];
        $display['status'] = $erpData->sync_status;
        $display['external_id'] = $erpData->external_id;
        $display['last_sync'] = $erpData->last_sync_at?->diffForHumans();

        return $display;
    }

    /**
     * Get ERP sync status for product across all connections
     *
     * @return array
     */
    public function getProductErpSyncStatus(): array
    {
        if (!$this->product) {
            return [];
        }

        $status = [];

        foreach ($this->product->erpData as $erpData) {
            $key = $erpData->erp_connection_id;
            $status[$key] = [
                'erp_data_id' => $erpData->id,
                'external_id' => $erpData->external_id,
                'sync_status' => $erpData->sync_status,
                'last_sync_at' => $erpData->last_sync_at,
                'error_message' => $erpData->error_message,
                'pending_fields' => $erpData->pending_fields,
            ];
        }

        return $status;
    }

    // ==========================================
    // FIELD STATUS INDICATOR METHODS (ETAP_08.4)
    // ==========================================

    /**
     * ETAP_08.4: Get field status indicator for ERP context
     *
     * ETAP_08.5 FIX: Compares form value with PPM DEFAULT, not with Baselinker API cache!
     *
     * Returns badge info for showing field comparison status:
     * - Zgodne (green) - Value matches PPM default (same as default tab)
     * - Własne (orange) - Value differs from PPM default (custom for this ERP)
     * - Dziedziczone (purple) - Form value is empty, uses PPM default
     *
     * @param string $fieldName
     * @return array ['show' => bool, 'class' => string, 'text' => string]
     */
    public function getErpFieldStatusIndicator(string $fieldName): array
    {
        // Only show indicators when in ERP context
        if ($this->activeErpConnectionId === null) {
            return ['show' => false, 'class' => '', 'text' => ''];
        }

        // ETAP_08.8 FIX: For unlinked/not_found products, ALL fields are "Dziedziczone" from PPM
        // There's no ERP data to compare with, so everything inherits from local PPM data
        $syncStatus = $this->erpExternalData['sync_status'] ?? null;
        if (in_array($syncStatus, ['not_linked', 'not_found'])) {
            return [
                'show' => true,
                'class' => 'status-label-inherited',
                'text' => 'Dziedziczone',
            ];
        }

        $pendingFields = $this->erpExternalData['pending_fields'] ?? [];

        // Check if field is in pending changes (awaiting sync to ERP)
        $isPending = in_array($fieldName, $pendingFields);

        // Get current form value
        $currentValue = $this->$fieldName ?? null;

        // ETAP_08.5 FIX: Compare with PPM DEFAULT, not with external_data!
        $defaultValue = $this->erpDefaultData[$fieldName] ?? $this->defaultData[$fieldName] ?? null;

        // Normalize for comparison
        $currentNorm = $this->normalizeValueForComparison($currentValue);
        $defaultNorm = $this->normalizeValueForComparison($defaultValue);

        // Determine status
        if ($isPending) {
            // Field has pending changes awaiting sync - show "Oczekuje" (yellow/orange)
            return [
                'show' => true,
                'class' => 'pending-sync-badge',
                'text' => 'Oczekuje synchronizacji',
            ];
        }

        // If current form value is empty/null -> inherited from PPM default
        if ($currentValue === null || $currentValue === '' || (is_array($currentValue) && empty($currentValue))) {
            return [
                'show' => true,
                'class' => 'status-label-inherited',
                'text' => 'Dziedziczone',
            ];
        }

        // Compare current form value with PPM default
        if ($currentNorm === $defaultNorm) {
            // Values match PPM default - show "Zgodne" (green)
            return [
                'show' => true,
                'class' => 'status-label-same',
                'text' => 'Zgodne',
            ];
        }

        // Values differ from PPM default - show "Własne" (orange)
        return [
            'show' => true,
            'class' => 'status-label-different',
            'text' => 'Własne',
        ];
    }

    /**
     * ETAP_08.4: Get CSS classes for ERP field input
     *
     * Returns appropriate CSS classes based on field status:
     * - Normal input: form-input-enterprise
     * - Pending change: form-input-enterprise + yellow border
     * - Error: form-input-enterprise + red border
     *
     * @param string $fieldName
     * @return string CSS classes
     */
    public function getErpFieldClasses(string $fieldName): string
    {
        $baseClasses = 'form-input-enterprise w-full';

        // Only apply special styling when in ERP context
        if ($this->activeErpConnectionId === null) {
            return $baseClasses;
        }

        $pendingFields = $this->erpExternalData['pending_fields'] ?? [];
        $isPending = in_array($fieldName, $pendingFields);

        if ($isPending) {
            // Pending field - yellow border (indicates awaiting sync)
            return $baseClasses . ' !border-yellow-500 !ring-yellow-500/20';
        }

        return $baseClasses;
    }

    /**
     * Helper to get external data value by field name
     *
     * @param string $fieldName
     * @param array $externalData
     * @return mixed|null
     */
    protected function getExternalDataValueByName(string $fieldName, array $externalData): mixed
    {
        if (empty($externalData)) {
            return null;
        }

        // Direct field access
        if (isset($externalData[$fieldName])) {
            return $externalData[$fieldName];
        }

        // Baselinker text_fields mapping
        $textFieldsMapping = [
            'name' => 'name',
            'short_description' => 'short_description',
            'long_description' => 'description',
        ];

        if (isset($textFieldsMapping[$fieldName]) && isset($externalData['text_fields'][$textFieldsMapping[$fieldName]])) {
            return $externalData['text_fields'][$textFieldsMapping[$fieldName]];
        }

        return null;
    }

    /**
     * ETAP_08.4: Check if specific field has pending ERP changes
     *
     * @param string $fieldName
     * @return bool
     */
    public function isErpFieldPending(string $fieldName): bool
    {
        if ($this->activeErpConnectionId === null) {
            return false;
        }

        $pendingFields = $this->erpExternalData['pending_fields'] ?? [];
        return in_array($fieldName, $pendingFields);
    }

    /**
     * ETAP_08.4: Get ERP external value for display (comparison with current)
     *
     * @param string $fieldName
     * @return mixed|null
     */
    public function getErpExternalValue(string $fieldName): mixed
    {
        $externalData = $this->erpExternalData['external_data'] ?? [];
        return $this->getExternalDataValueByName($fieldName, $externalData);
    }

    // ==========================================
    // ETAP_08.5: ERP JOB STATUS POLLING
    // ==========================================

    /**
     * ETAP_08.5: Check ERP sync job status (wire:poll)
     *
     * Called by wire:poll to check job completion status.
     * Updates UI when job completes or fails.
     *
     * @return void
     */
    public function checkErpJobStatus(): void
    {
        // Skip if no active job or already completed
        if (!$this->activeErpJobStatus || $this->activeErpJobStatus === 'completed' || $this->activeErpJobStatus === 'failed') {
            return;
        }

        if (!$this->product || !$this->activeErpJobConnectionId) {
            return;
        }

        try {
            // Check ProductErpData sync_status (updated by job)
            $erpData = $this->product->erpData()
                ->where('erp_connection_id', $this->activeErpJobConnectionId)
                ->first();

            if (!$erpData) {
                return;
            }

            // Check status changes
            if ($erpData->sync_status === ProductErpData::STATUS_SYNCED) {
                // Job completed successfully
                $this->activeErpJobStatus = 'completed';
                $this->erpJobResult = 'success';
                $this->erpJobMessage = 'Synchronizacja zakonczona pomyslnie';

                // Update UI data
                $this->erpExternalData['sync_status'] = ProductErpData::STATUS_SYNCED;
                $this->erpExternalData['pending_fields'] = [];
                $this->erpExternalData['last_sync_at'] = $erpData->last_sync_at;

                Log::info('checkErpJobStatus: Job completed successfully', [
                    'product_id' => $this->product->id,
                    'connection_id' => $this->activeErpJobConnectionId,
                ]);

            } elseif ($erpData->sync_status === ProductErpData::STATUS_ERROR) {
                // Job failed
                $this->activeErpJobStatus = 'failed';
                $this->erpJobResult = 'error';
                $this->erpJobMessage = $erpData->error_message ?? 'Blad synchronizacji';

                // Update UI data
                $this->erpExternalData['sync_status'] = ProductErpData::STATUS_ERROR;
                $this->erpExternalData['error_message'] = $erpData->error_message;

                Log::info('checkErpJobStatus: Job failed', [
                    'product_id' => $this->product->id,
                    'connection_id' => $this->activeErpJobConnectionId,
                    'error' => $erpData->error_message,
                ]);

            } elseif ($erpData->sync_status === ProductErpData::STATUS_SYNCING) {
                // Still running
                $this->activeErpJobStatus = 'running';
            }

        } catch (\Exception $e) {
            Log::error('checkErpJobStatus error', [
                'error' => $e->getMessage(),
                'product_id' => $this->product?->id,
                'connection_id' => $this->activeErpJobConnectionId,
            ]);
        }
    }

    /**
     * ETAP_08.5: Check if ERP sync is in progress
     *
     * Used by UI to show blocking overlay.
     *
     * @return bool
     */
    public function hasActiveErpSyncJob(): bool
    {
        return in_array($this->activeErpJobStatus, ['pending', 'running']);
    }

    /**
     * ETAP_08.5: Reset ERP job tracking (used after viewing results)
     *
     * @return void
     */
    public function resetErpJobTracking(): void
    {
        $this->activeErpJobStatus = null;
        $this->activeErpJobType = null;
        $this->activeErpJobConnectionId = null;
        $this->erpJobCreatedAt = null;
        $this->erpJobResult = null;
        $this->erpJobMessage = null;
    }

    // ==========================================
    // ETAP_08.7: ERP JOB DETECTION ON MOUNT (PERSISTENCY)
    // ==========================================

    /**
     * ETAP_08.7: Detect active ERP sync job on mount
     *
     * Restores job tracking state from product_erp_data.sync_status = 'syncing'
     * This ensures blocking overlay persists even after page refresh.
     *
     * MIRRORS: detectActiveJobOnMount() from PrestaShop pattern
     *
     * @return void
     */
    public function detectActiveErpJobOnMount(): void
    {
        if (!$this->product || !$this->product->exists) {
            return;
        }

        try {
            // Find any ERP connection with syncing status for this product
            $syncingErpData = $this->product->erpData()
                ->where('sync_status', ProductErpData::STATUS_SYNCING)
                ->orderBy('updated_at', 'desc')
                ->first();

            if ($syncingErpData) {
                // Active ERP sync job found - restore job tracking state
                $this->activeErpJobStatus = 'running';
                $this->activeErpJobType = 'sync';
                $this->activeErpJobConnectionId = $syncingErpData->erp_connection_id;
                $this->erpJobCreatedAt = $syncingErpData->updated_at->toIso8601String();

                // Restore UI state
                $this->erpExternalData['sync_status'] = ProductErpData::STATUS_SYNCING;

                Log::info('[MOUNT] Detected active ERP sync job - restoring tracking state', [
                    'product_id' => $this->product->id,
                    'connection_id' => $syncingErpData->erp_connection_id,
                    'sync_status' => $syncingErpData->sync_status,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('[MOUNT] Failed to detect active ERP job', [
                'product_id' => $this->product->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // ==========================================
    // ETAP_08.8: ERP PRODUCT LINKING (Check/Link/Create)
    // ==========================================

    /**
     * ETAP_08.8: Check if product exists in ERP by SKU/EAN
     *
     * Called when user clicks "Sprawdź czy jest w ERP".
     * Searches ERP for product by SKU or EAN.
     *
     * @param int $connectionId
     * @return void
     */
    public function checkProductInErp(int $connectionId): void
    {
        $connection = ERPConnection::find($connectionId);
        if (!$connection || !$this->product) {
            $this->addError('erp_check', 'Brak polaczenia ERP lub produktu');
            return;
        }

        $this->loadingErpData = true;

        try {
            // Use BaselinkerService to search for product by SKU
            $service = app(BaselinkerService::class);
            $result = $service->findProductBySku($connection, $this->product->sku);

            if ($result['success'] && !empty($result['external_id'])) {
                // Product FOUND in ERP - link it automatically
                $this->linkProductToErp($connectionId, $result['external_id'], $result['data'] ?? []);

                session()->flash('message', 'Produkt znaleziony w ERP i polaczony: ' . $connection->instance_name);

                Log::info('checkProductInErp: Product found and linked', [
                    'product_id' => $this->product->id,
                    'connection_id' => $connectionId,
                    'external_id' => $result['external_id'],
                ]);
            } else {
                // Product NOT FOUND in ERP - show "Dodać do ERP?" button
                $this->erpExternalData['sync_status'] = 'not_found';

                session()->flash('warning', 'Produkt nie zostal znaleziony w ERP. Mozesz go dodac.');

                Log::info('checkProductInErp: Product not found in ERP', [
                    'product_id' => $this->product->id,
                    'connection_id' => $connectionId,
                    'sku' => $this->product->sku,
                ]);
            }

        } catch (\Exception $e) {
            $this->addError('erp_check', 'Blad sprawdzania ERP: ' . $e->getMessage());
            Log::error('checkProductInErp error', [
                'product_id' => $this->product->id,
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
            ]);
        } finally {
            $this->loadingErpData = false;
        }
    }

    /**
     * ETAP_08.8: Link product to existing ERP product
     *
     * Creates ProductErpData record with external_id and pulls data from ERP.
     *
     * @param int $connectionId
     * @param string $externalId
     * @param array $externalData
     * @return void
     */
    protected function linkProductToErp(int $connectionId, string $externalId, array $externalData = []): void
    {
        if (!$this->product) {
            return;
        }

        // Create ProductErpData record with external_id
        $erpData = $this->product->erpData()->create([
            'erp_connection_id' => $connectionId,
            'external_id' => $externalId,
            'sync_status' => ProductErpData::STATUS_SYNCED,
            'external_data' => $externalData,
            'last_pull_at' => now(),
        ]);

        // Update UI state
        $this->erpExternalData = [
            'connection' => ERPConnection::find($connectionId),
            'erp_data_id' => $erpData->id,
            'external_id' => $externalId,
            'sync_status' => ProductErpData::STATUS_SYNCED,
            'pending_fields' => [],
            'external_data' => $externalData,
            'last_sync_at' => null,
            'last_pull_at' => now(),
            'last_push_at' => null,
            'error_message' => null,
        ];

        // Load ERP data to form fields
        if (!empty($externalData)) {
            $this->overrideFormFieldsWithErpData($erpData);
        }

        Log::info('linkProductToErp: Product linked to ERP', [
            'product_id' => $this->product->id,
            'connection_id' => $connectionId,
            'external_id' => $externalId,
        ]);
    }

    /**
     * ETAP_08.8: Add product to ERP (create new in ERP)
     *
     * Called when user clicks "Dodać do ERP?".
     * Creates new product in ERP system via sync job.
     *
     * @param int $connectionId
     * @return void
     */
    public function addProductToErp(int $connectionId): void
    {
        $connection = ERPConnection::find($connectionId);
        if (!$connection || !$this->product) {
            $this->addError('erp_add', 'Brak polaczenia ERP lub produktu');
            return;
        }

        try {
            // Create ProductErpData record WITHOUT external_id (will be created by sync)
            $erpData = $this->product->erpData()->create([
                'erp_connection_id' => $connectionId,
                'external_id' => null,  // Will be filled after sync creates product
                'sync_status' => ProductErpData::STATUS_PENDING,
                'pending_fields' => ['sku', 'name', 'ean', 'manufacturer', 'weight'],  // Mark all as pending
            ]);

            // Update UI state
            $this->erpExternalData = [
                'connection' => $connection,
                'erp_data_id' => $erpData->id,
                'external_id' => null,
                'sync_status' => ProductErpData::STATUS_PENDING,
                'pending_fields' => $erpData->pending_fields,
                'external_data' => [],
                'last_sync_at' => null,
                'last_pull_at' => null,
                'last_push_at' => null,
                'error_message' => null,
            ];

            session()->flash('message', 'Produkt przygotowany do dodania do ERP. Kliknij Synchronizuj aby wyslac.');

            Log::info('addProductToErp: Product prepared for ERP creation', [
                'product_id' => $this->product->id,
                'connection_id' => $connectionId,
            ]);

        } catch (\Exception $e) {
            $this->addError('erp_add', 'Blad przygotowania do ERP: ' . $e->getMessage());
            Log::error('addProductToErp error', [
                'product_id' => $this->product->id,
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * ETAP_08.8: Check if product is linked to specific ERP connection
     *
     * @param int $connectionId
     * @return bool
     */
    public function isProductLinkedToErp(int $connectionId): bool
    {
        if (!$this->product) {
            return false;
        }

        return $this->product->erpData()
            ->where('erp_connection_id', $connectionId)
            ->exists();
    }

    /**
     * ETAP_08.8: Get ERP link status for UI display
     *
     * Returns: 'linked' | 'not_linked' | 'not_found' | 'pending'
     *
     * @param int $connectionId
     * @return string
     */
    public function getErpLinkStatus(int $connectionId): string
    {
        // Check current erpExternalData state first (for UI reactivity)
        if ($this->activeErpConnectionId === $connectionId) {
            $status = $this->erpExternalData['sync_status'] ?? 'not_linked';
            if ($status === 'not_linked' || $status === 'not_found') {
                return $status;
            }
        }

        if (!$this->product) {
            return 'not_linked';
        }

        $erpData = $this->product->erpData()
            ->where('erp_connection_id', $connectionId)
            ->first();

        if (!$erpData) {
            return 'not_linked';
        }

        return 'linked';
    }
}
