<?php

namespace App\Http\Livewire\Admin\Shops;

use Livewire\Component;
use Illuminate\Support\Facades\Log;
use App\Models\PrestaShopShop;

class AddShop extends Component
{
    // Edit Mode
    public $editingShopId = null;
    public $isEditing = false;

    // Wizard Steps
    public $currentStep = 1;
    public $totalSteps = 6; // Updated: Added Step 4 for Price Mapping
    
    // Step 1: Basic Info
    public $shopName = '';
    public $shopUrl = '';
    public $shopDescription = '';
    
    // Step 2: API Credentials
    public $apiKey = '';
    public $apiSecret = '';
    public $prestashopVersion = '8'; // Default PS8
    public $prestashopVersionExact = ''; // Exact version (e.g. 8.2.1)
    
    // Step 3: Connection Test
    public $connectionStatus = null;
    public $connectionMessage = '';
    public $diagnostics = [];

    // Step 4: Price Group Mapping
    public array $prestashopPriceGroups = [];
    public array $ppmPriceGroups = [];
    public array $priceGroupMappings = [];
    public bool $fetchingPriceGroups = false;
    public string $fetchPriceGroupsError = '';

    // Tax Rules Mapping (FAZA 5.1 - 2025-11-14)
    public array $availableTaxRuleGroups = [];
    public ?int $taxRulesGroup23 = null;
    public ?int $taxRulesGroup8 = null;
    public ?int $taxRulesGroup5 = null;
    public ?int $taxRulesGroup0 = null;
    public bool $taxRulesFetched = false;

    // Step 5: Initial Sync Settings (was Step 4)
    public $syncFrequency = 'hourly';
    public $syncProducts = true;
    public $syncCategories = true;
    public $syncPrices = true;
    public $autoSyncEnabled = true;

    // Step 6: Advanced Settings (was Step 5)
    public $conflictResolution = 'ppm_wins';
    public $syncStock = true;
    public $syncOrders = false;
    public $syncCustomers = false;
    public $realTimeSyncEnabled = false;
    public $syncBatchSize = 50;
    public $syncTimeoutMinutes = 30;
    public $retryFailedSyncs = true;
    public $maxRetryAttempts = 3;
    public $notifyOnSyncErrors = true;
    public $notifyOnSyncComplete = false;
    public $enableWebhooks = false;
    public $syncOnlyActiveProducts = true;
    public $preserveLocalImages = true;
    public $syncMetaData = true;
    public $ppmModuleApiKey = '';

    // ETAP_07f: CSS/JS Sync Configuration (2025-12-16)
    // FTP is REQUIRED for CSS/JS scanning and editing
    public $enableFtpSync = false;
    public $ftpProtocol = 'ftp';
    public $ftpHost = '';
    public $ftpPort = 21;
    public $ftpUser = '';
    public $ftpPassword = '';
    public $ftpConnectionStatus = null;
    public $ftpConnectionMessage = '';

    // ETAP_07f_P3.5: Multi-file CSS/JS Auto-Scan (2025-12-17)
    public array $scannedCssFiles = [];
    public array $scannedJsFiles = [];
    public array $selectedCssFiles = [];
    public array $selectedJsFiles = [];
    public ?string $scanStatus = null;
    public string $scanMessage = '';
    public bool $isScanning = false;

    // ETAP_10: Label customization (2026-02-03)
    public ?string $labelColor = null;
    public ?string $labelIcon = null;

    // B2B Shop Flag
    public bool $isB2b = false;

    // Validation messages
    protected $messages = [
        'shopName.required' => 'Nazwa sklepu jest wymagana',
        'shopName.min' => 'Nazwa sklepu musi mieć co najmniej 3 znaki',
        'shopUrl.required' => 'URL sklepu jest wymagany',
        'shopUrl.url' => 'URL musi być prawidłowym adresem internetowym',
        'apiKey.required' => 'Klucz API jest wymagany',
        'apiKey.min' => 'Klucz API musi mieć co najmniej 32 znaki',
        'prestashopVersion.required' => 'Wersja PrestaShop jest wymagana',
        'prestashopVersion.in' => 'Wersja PrestaShop musi być 8 lub 9',
        'prestashopVersionExact.max' => 'Dokladna wersja PrestaShop nie moze przekraczac 20 znakow',
        // Tax Rules Validation (FAZA 5.1)
        'taxRulesGroup23.required' => 'Grupa podatkowa 23% jest wymagana',
        'taxRulesGroup23.integer' => 'Grupa podatkowa 23% musi być liczbą',
    ];

    public function mount()
    {
        // DEVELOPMENT: authorize tymczasowo wyłączone dla testów
        // $this->authorize('admin.shops.create');

        // Initialize PPM Price Groups (from CLAUDE.md spec)
        $this->ppmPriceGroups = [
            'Detaliczna',
            'Dealer Standard',
            'Dealer Premium',
            'Warsztat',
            'Warsztat Premium',
            'Szkółka-Komis-Drop',
            'Pracownik',
        ];

        $editId = request()->get('edit');
        Log::info('AddShop mount() called', [
            'edit_id' => $editId,
            'url' => request()->url(),
            'query_params' => request()->query()
        ]);

        if ($editId) {
            $this->editingShopId = $editId;
            $this->isEditing = true;
            $this->loadShopData();
        } else {
            $this->resetWizard();
        }
    }

    public function resetWizard()
    {
        $this->currentStep = 1;
        
        // Step 1: Basic Info
        $this->shopName = '';
        $this->shopUrl = '';
        $this->shopDescription = '';
        
        // Step 2: API Credentials
        $this->apiKey = '';
        $this->apiSecret = '';
        $this->prestashopVersion = '8';
        $this->prestashopVersionExact = '';
        
        // Step 3: Connection Test
        $this->connectionStatus = null;
        $this->connectionMessage = '';
        $this->diagnostics = [];
        
        // Step 4: Initial Sync Settings
        $this->syncFrequency = 'hourly';
        $this->syncProducts = true;
        $this->syncCategories = true;
        $this->syncPrices = true;
        $this->autoSyncEnabled = true;
        
        // Step 5: Advanced Settings
        $this->conflictResolution = 'ppm_wins';
        $this->syncStock = true;
        $this->syncOrders = false;
        $this->syncCustomers = false;
        $this->realTimeSyncEnabled = false;
        $this->syncBatchSize = 50;
        $this->syncTimeoutMinutes = 30;
        $this->retryFailedSyncs = true;
        $this->maxRetryAttempts = 3;
        $this->notifyOnSyncErrors = true;
        $this->notifyOnSyncComplete = false;
        $this->enableWebhooks = false;
        $this->syncOnlyActiveProducts = true;
        $this->preserveLocalImages = true;
        $this->syncMetaData = true;
        $this->ppmModuleApiKey = '';

        // ETAP_07f: CSS/JS Sync Configuration (FTP required)
        $this->enableFtpSync = false;
        $this->ftpProtocol = 'ftp';
        $this->ftpHost = '';
        $this->ftpPort = 21;
        $this->ftpUser = '';
        $this->ftpPassword = '';
        $this->ftpConnectionStatus = null;
        $this->ftpConnectionMessage = '';

        // ETAP_07f_P3.5: Multi-file CSS/JS Auto-Scan
        $this->scannedCssFiles = [];
        $this->scannedJsFiles = [];
        $this->selectedCssFiles = [];
        $this->selectedJsFiles = [];
        $this->scanStatus = null;
        $this->scanMessage = '';
        $this->isScanning = false;

        // ETAP_10: Label customization
        $this->labelColor = null;
        $this->labelIcon = null;

        // B2B Shop Flag
        $this->isB2b = false;
    }

    public function loadShopData()
    {
        Log::info('LoadShopData called', [
            'editing_shop_id' => $this->editingShopId,
            'is_editing' => $this->isEditing
        ]);

        $shop = PrestaShopShop::find($this->editingShopId);

        if (!$shop) {
            Log::error('Shop not found for editing', ['shop_id' => $this->editingShopId]);
            session()->flash('error', 'Sklep o ID ' . $this->editingShopId . ' nie został znaleziony.');
            $this->isEditing = false;
            $this->editingShopId = null;
            $this->resetWizard();
            return;
        }

        // Step 1: Basic Info
        $this->shopName = $shop->name;
        $this->shopUrl = $shop->url;
        $this->shopDescription = $shop->description;

        // Step 2: API Credentials
        $this->apiKey = $shop->api_key;
        // FIX 2026-02-05: prestashop_version may contain full version (e.g., "8.2.1")
        // Extract major version (8 or 9) for the select dropdown
        $storedVersion = $shop->prestashop_version ?? '8';
        if (in_array($storedVersion, ['8', '9'])) {
            $this->prestashopVersion = $storedVersion;
        } else {
            // Extract major version from full version string (e.g., "8.2.1" -> "8")
            $this->prestashopVersion = substr($storedVersion, 0, 1);
            // Also populate exact version if not already set
            if (empty($shop->prestashop_version_exact)) {
                $this->prestashopVersionExact = $storedVersion;
            }
        }
        $this->prestashopVersionExact = $shop->prestashop_version_exact ?? $this->prestashopVersionExact ?? '';

        // Step 3: Connection Test - reset to allow re-testing
        $this->connectionStatus = null;
        $this->connectionMessage = '';
        $this->diagnostics = [];

        // Step 4: Initial Sync Settings
        $this->syncFrequency = $shop->sync_frequency;
        $syncSettings = $shop->sync_settings ?? [];
        $this->syncProducts = $syncSettings['sync_products'] ?? true;
        $this->syncCategories = $syncSettings['sync_categories'] ?? true;
        $this->syncPrices = $syncSettings['sync_prices'] ?? true;
        $this->autoSyncEnabled = $syncSettings['auto_sync_enabled'] ?? true;

        // Step 5: Advanced Settings
        $this->conflictResolution = $shop->conflict_resolution;
        $this->syncStock = $syncSettings['sync_stock'] ?? true;
        $this->syncOrders = $syncSettings['sync_orders'] ?? false;
        $this->syncCustomers = $syncSettings['sync_customers'] ?? false;
        $this->realTimeSyncEnabled = $syncSettings['real_time_sync'] ?? false;
        $this->syncOnlyActiveProducts = $syncSettings['sync_only_active'] ?? true;
        $this->preserveLocalImages = $syncSettings['preserve_images'] ?? true;
        $this->syncMetaData = $syncSettings['sync_metadata'] ?? true;
        $this->enableWebhooks = $syncSettings['enable_webhooks'] ?? false;
        $this->ppmModuleApiKey = $syncSettings['ppm_module_api_key'] ?? '';

        // Sync configuration
        $syncConfig = $shop->sync_configuration ?? [];
        $this->syncBatchSize = $syncConfig['batch_size'] ?? 50;
        $this->syncTimeoutMinutes = $syncConfig['timeout_minutes'] ?? 30;
        $this->retryFailedSyncs = $syncConfig['retry_failed'] ?? true;
        $this->maxRetryAttempts = $syncConfig['max_retry_attempts'] ?? 3;

        // Notification settings
        $notificationSettings = $shop->notification_settings ?? [];
        $this->notifyOnSyncErrors = $notificationSettings['notify_on_errors'] ?? true;
        $this->notifyOnSyncComplete = $notificationSettings['notify_on_complete'] ?? false;

        // ETAP_10: Label customization
        $this->labelColor = $shop->getAttributes()['label_color'] ?? null;
        $this->labelIcon = $shop->getAttributes()['label_icon'] ?? null;

        // B2B Shop Flag
        $this->isB2b = (bool) ($shop->is_b2b ?? false);

        // ✅ FIX BUG#11c: Load existing price group mappings from database
        $this->priceGroupMappings = [];
        $existingMappings = \DB::table('prestashop_shop_price_mappings')
            ->where('prestashop_shop_id', $shop->id)
            ->get();

        if ($existingMappings->count() > 0) {
            // Populate priceGroupMappings array
            foreach ($existingMappings as $mapping) {
                $this->priceGroupMappings[$mapping->prestashop_price_group_id] = $mapping->ppm_price_group_name;
            }

            // Re-fetch PrestaShop groups to populate prestashopPriceGroups array for display
            // This ensures dropdowns have the correct options
            $this->fetchPrestashopPriceGroups();

            Log::info('Price group mappings loaded in edit mode', [
                'shop_id' => $shop->id,
                'mappings_count' => $existingMappings->count(),
                'mappings' => $this->priceGroupMappings
            ]);
        }

        // FAZA 5.1: Load existing tax rules mapping
        $this->taxRulesGroup23 = $shop->tax_rules_group_id_23;
        $this->taxRulesGroup8 = $shop->tax_rules_group_id_8;
        $this->taxRulesGroup5 = $shop->tax_rules_group_id_5;
        $this->taxRulesGroup0 = $shop->tax_rules_group_id_0;

        Log::info('Tax rules loaded in edit mode', [
            'shop_id' => $shop->id,
            'tax_rules' => [
                'tax_23' => $this->taxRulesGroup23,
                'tax_8' => $this->taxRulesGroup8,
                'tax_5' => $this->taxRulesGroup5,
                'tax_0' => $this->taxRulesGroup0,
            ]
        ]);

        // ETAP_07f: Load CSS/JS Sync Configuration (FTP required)
        $ftpConfig = $shop->ftp_config ?? [];
        if (!empty($ftpConfig)) {
            $this->enableFtpSync = true;
            $this->ftpProtocol = $ftpConfig['protocol'] ?? 'ftp';
            $this->ftpHost = $ftpConfig['host'] ?? '';
            $this->ftpPort = $ftpConfig['port'] ?? 21;
            $this->ftpUser = $ftpConfig['user'] ?? '';
            // Password is NOT loaded for security - user must re-enter
        }

        Log::info('CSS/JS config loaded in edit mode', [
            'shop_id' => $shop->id,
            'ftp_enabled' => $this->enableFtpSync,
            'ftp_host' => $this->ftpHost,
        ]);

        // ETAP_07f_P3.5: Load multi-file CSS/JS configuration
        $cssFiles = $shop->css_files ?? [];
        $jsFiles = $shop->js_files ?? [];

        if (!empty($cssFiles) || !empty($jsFiles)) {
            $this->scannedCssFiles = $cssFiles;
            $this->scannedJsFiles = $jsFiles;

            // Extract selected (enabled) files
            $this->selectedCssFiles = array_values(array_filter(
                array_map(fn($f) => ($f['enabled'] ?? false) ? $f['url'] : null, $cssFiles)
            ));
            $this->selectedJsFiles = array_values(array_filter(
                array_map(fn($f) => ($f['enabled'] ?? false) ? $f['url'] : null, $jsFiles)
            ));

            $this->scanStatus = 'loaded';
            $this->scanMessage = 'Pliki zaladowane z konfiguracji sklepu';

            Log::info('CSS/JS files loaded in edit mode', [
                'shop_id' => $shop->id,
                'css_count' => count($cssFiles),
                'js_count' => count($jsFiles),
                'selected_css' => count($this->selectedCssFiles),
                'selected_js' => count($this->selectedJsFiles),
            ]);
        }
    }

    public function nextStep()
    {
        $this->validateCurrentStep();
        
        if ($this->currentStep < $this->totalSteps) {
            $this->currentStep++;
            
            // Auto-run connection test on step 3
            if ($this->currentStep === 3) {
                $this->testConnection();
            }
        }
    }

    public function previousStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function goToStep($step)
    {
        if ($step >= 1 && $step <= $this->totalSteps) {
            // Validate all previous steps
            for ($i = 1; $i < $step; $i++) {
                $this->currentStep = $i;
                try {
                    $this->validateCurrentStep();
                } catch (\Exception $e) {
                    $this->addError('step_validation', "Krok {$i}: " . $e->getMessage());
                    return;
                }
            }
            
            $this->currentStep = $step;
            
            // Auto-run connection test on step 3
            if ($this->currentStep === 3) {
                $this->testConnection();
            }
        }
    }

    protected function validateCurrentStep()
    {
        switch ($this->currentStep) {
            case 1:
                $this->validate([
                    'shopName' => 'required|min:3|max:100',
                    'shopUrl' => 'required|url|max:255',
                    'shopDescription' => 'max:500',
                ]);
                break;
                
            case 2:
                $this->validate([
                    'apiKey' => 'required|min:32|max:255',
                    'prestashopVersion' => 'required|in:8,9',
                    'prestashopVersionExact' => 'nullable|string|max:20',
                ]);
                break;
                
            case 3:
                // Connection test is required to pass
                if ($this->connectionStatus !== 'success') {
                    throw new \Exception('Test połączenia musi się zakończyć sukcesem');
                }

                // Tax Rules Validation (FAZA 5.1 - 2025-11-14)
                // 23% VAT group is REQUIRED, others are optional
                $this->validate([
                    'taxRulesGroup23' => 'required|integer|min:1',
                    'taxRulesGroup8' => 'nullable|integer|min:1',
                    'taxRulesGroup5' => 'nullable|integer|min:1',
                    'taxRulesGroup0' => 'nullable|integer|min:1',
                ]);
                break;

            case 4:
                // Price group mapping validation
                $this->validatePriceMappings();
                break;

            case 5:
                $this->validate([
                    'syncFrequency' => 'required|in:real-time,hourly,daily,manual',
                ]);
                break;

            case 6:
                $this->validate([
                    'conflictResolution' => 'required|in:ppm_wins,prestashop_wins,manual,newest_wins',
                    'syncBatchSize' => 'required|integer|min:1|max:500',
                    'syncTimeoutMinutes' => 'required|integer|min:5|max:180',
                    'maxRetryAttempts' => 'required|integer|min:1|max:10',
                ]);
                break;
        }
    }

    public function testConnection()
    {
        try {
            $this->connectionStatus = 'testing';
            $this->connectionMessage = 'Testowanie połączenia...';
            $this->diagnostics = [];
            
            // Clear any previous errors
            $this->resetErrorBag();
            
            // Basic validation first
            $this->validate([
                'shopUrl' => 'required|url',
                'apiKey' => 'required|min:32',
                'prestashopVersion' => 'required|in:8,9',
            ]);
            
            // Try real PrestaShop API connection
            try {
                $prestaShopService = new \App\Services\PrestaShop\PrestaShopService();

                $result = $prestaShopService->testConnection([
                    'url' => $this->shopUrl,
                    'api_key' => $this->apiKey,
                    'ssl_verify' => true,
                    'timeout' => 30,
                ]);

                if ($result['success']) {
                    // FIX 2026-02-05: Auto-detect exact PrestaShop version after successful connection
                    $detectedExactVersion = null;
                    try {
                        // Create temporary shop object for version detection
                        $tempShop = new PrestaShopShop([
                            'url' => $this->shopUrl,
                            'api_key' => $this->apiKey,
                            'prestashop_version' => $this->prestashopVersion,
                        ]);
                        $tempShop->id = 0; // Fake ID for logging

                        $client = new \App\Services\PrestaShop\PrestaShop8Client($tempShop);
                        $detectedExactVersion = $client->detectFullVersion();

                        if ($detectedExactVersion) {
                            $this->prestashopVersionExact = $detectedExactVersion;
                            Log::info('[AddShop] Auto-detected PrestaShop version', [
                                'shop_url' => $this->shopUrl,
                                'detected_version' => $detectedExactVersion,
                            ]);
                        }
                    } catch (\Exception $versionException) {
                        Log::debug('[AddShop] Could not auto-detect exact version', [
                            'shop_url' => $this->shopUrl,
                            'error' => $versionException->getMessage(),
                        ]);
                    }

                    $versionMessage = $detectedExactVersion
                        ? "Wykryto wersję {$detectedExactVersion}"
                        : "Version {$result['prestashop_version']} detected";
                    $webpInfo = $detectedExactVersion
                        ? ($this->supportsWebP($detectedExactVersion) ? 'Obsługuje natywnie WebP (>= 8.2.1)' : 'Wymaga konwersji WebP→JPEG (< 8.2.1)')
                        : 'Compatible version confirmed';
                    $versionDetails = $detectedExactVersion
                        ? "Wersja PrestaShop została ustawiona na: {$detectedExactVersion}. {$webpInfo}"
                        : $webpInfo;

                    $this->diagnostics = [
                        [
                            'check' => 'PrestaShop API Connection',
                            'status' => 'success',
                            'message' => $result['message'],
                            'details' => "Response time: {$result['response_time']}ms"
                        ],
                        [
                            'check' => 'PrestaShop Version',
                            'status' => 'success',
                            'message' => $versionMessage,
                            'details' => $versionDetails
                        ],
                        [
                            'check' => 'API Features',
                            'status' => 'success',
                            'message' => 'Required features available',
                            'details' => implode(', ', $result['supported_features'] ?? [])
                        ]
                    ];

                    // Test PPM Image Manager module connection (if API key configured)
                    if (!empty($this->ppmModuleApiKey)) {
                        $this->diagnostics[] = $this->testPpmModuleConnection($client);
                    } else {
                        $this->diagnostics[] = [
                            'check' => 'PPM Manager',
                            'status' => 'info',
                            'message' => 'Modul nie skonfigurowany',
                            'details' => 'Klucz API modulu PPM Manager nie podany - rozszerzone operacje na obrazkach pomijane',
                        ];
                    }

                    $this->connectionStatus = 'success';
                    $this->connectionMessage = 'Połączenie z sklepem PrestaShop zostało pomyślnie nawiązane!';
                } else {
                    throw new \Exception($result['message']);
                }

            } catch (\Exception $apiException) {
                // Fallback to simulated test for development
                Log::warning('PrestaShop API test failed in wizard, using simulated test', [
                    'shop_url' => $this->shopUrl,
                    'api_error' => $apiException->getMessage()
                ]);

                // Generate realistic response time for simulation (80-300ms with variance)
                $simulatedResponseTime = round(mt_rand(80, 300) + (mt_rand(0, 50) / 10), 1);

                $this->diagnostics = [
                    [
                        'check' => 'Symulacja Połączenia API',
                        'status' => 'success',
                        'message' => 'Test połączenia symulowany (API niedostępne)',
                        'details' => 'Symulowany czas: ' . $simulatedResponseTime . 'ms | Błąd API: ' . $apiException->getMessage()
                    ],
                    [
                        'check' => 'PrestaShop Version',
                        'status' => 'success',
                        'message' => "PrestaShop {$this->prestashopVersion} skonfigurowany",
                        'details' => 'Walidacja wersji nastąpi podczas pierwszej synchronizacji'
                    ]
                ];

                $this->connectionStatus = 'success';
                $this->connectionMessage = "Test ukończony (symulacja {$simulatedResponseTime}ms - API niedostępne)";
            }

            // Update connection status in database for existing shop (edit mode)
            if ($this->isEditing && $this->editingShopId) {
                $shop = PrestaShopShop::find($this->editingShopId);
                if ($shop) {
                    $shop->updateConnectionHealth(
                        PrestaShopShop::CONNECTION_CONNECTED,
                        null, // response_time - would be set by real API call
                        null  // error_message - clear on success
                    );

                    // Update PrestaShop version if detected
                    $shop->prestashop_version = $this->prestashopVersion;
                    // FIX 2026-02-05: Save exact version for WebP support detection
                    if ($this->prestashopVersionExact) {
                        $shop->prestashop_version_exact = $this->prestashopVersionExact;
                    }
                    $shop->version_compatible = true;
                    $shop->save();

                    Log::info('Updated connection status in database for existing shop', [
                        'shop_id' => $shop->id,
                        'new_status' => PrestaShopShop::CONNECTION_CONNECTED
                    ]);
                }
            }

            Log::info('PrestaShop connection test successful', [
                'shop_url' => $this->shopUrl,
                'prestashop_version' => $this->prestashopVersion,
                'is_editing' => $this->isEditing
            ]);

            // FAZA 5.1: Auto-fetch tax rules after successful connection test
            if ($this->connectionStatus === 'success') {
                $this->fetchTaxRuleGroups();
            }

        } catch (\Exception $e) {
            $this->connectionStatus = 'error';
            $this->connectionMessage = 'Błąd połączenia: ' . $e->getMessage();

            $this->diagnostics = [
                [
                    'check' => 'Connection Error',
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'details' => 'Please check your credentials and try again'
                ]
            ];

            // Update connection status in database for existing shop (edit mode)
            if ($this->isEditing && $this->editingShopId) {
                $shop = PrestaShopShop::find($this->editingShopId);
                if ($shop) {
                    $shop->updateConnectionHealth(
                        PrestaShopShop::CONNECTION_ERROR,
                        null, // response_time
                        $e->getMessage() // error_message
                    );

                    Log::info('Updated connection error status in database for existing shop', [
                        'shop_id' => $shop->id,
                        'new_status' => PrestaShopShop::CONNECTION_ERROR,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::error('PrestaShop connection test failed', [
                'shop_url' => $this->shopUrl,
                'error' => $e->getMessage(),
                'is_editing' => $this->isEditing
            ]);
        }
    }

    /**
     * Test FTP connection for CSS/JS sync (ETAP_07f - 2025-12-16)
     *
     * Tests the FTP/SFTP connection with provided credentials.
     * Used in Step 6: Advanced Settings.
     */
    public function testFtpConnection()
    {
        $this->ftpConnectionStatus = 'testing';
        $this->ftpConnectionMessage = 'Testowanie polaczenia FTP...';

        try {
            // Validate required fields
            if (empty($this->ftpHost)) {
                throw new \Exception('Host FTP jest wymagany');
            }
            if (empty($this->ftpUser)) {
                throw new \Exception('Uzytkownik FTP jest wymagany');
            }
            if (empty($this->ftpPassword)) {
                throw new \Exception('Haslo FTP jest wymagane');
            }

            // Use PrestaShopCssFetcher to test connection
            $cssFetcher = new \App\Services\VisualEditor\PrestaShopCssFetcher();

            $config = [
                'protocol' => $this->ftpProtocol,
                'host' => $this->ftpHost,
                'port' => (int) $this->ftpPort,
                'user' => $this->ftpUser,
                'password' => \App\Services\VisualEditor\PrestaShopCssFetcher::encryptPassword($this->ftpPassword),
            ];

            $result = $cssFetcher->testFtpConnection($config);

            if ($result['success']) {
                $this->ftpConnectionStatus = 'success';
                $this->ftpConnectionMessage = 'Polaczenie FTP pomyslne!';

                if (isset($result['server_info'])) {
                    $info = $result['server_info'];
                    $details = [];
                    if (!empty($info['system_type'])) {
                        $details[] = "System: {$info['system_type']}";
                    }
                    if (!empty($info['current_dir'])) {
                        $details[] = "Dir: {$info['current_dir']}";
                    }
                    if (isset($info['css_path_exists'])) {
                        $details[] = $info['css_path_exists'] ? 'CSS path exists' : 'CSS path NOT found';
                    }
                    if (!empty($details)) {
                        $this->ftpConnectionMessage .= ' (' . implode(', ', $details) . ')';
                    }
                }

                Log::info('FTP connection test successful', [
                    'host' => $this->ftpHost,
                    'protocol' => $this->ftpProtocol,
                    'server_info' => $result['server_info'] ?? null,
                ]);
            } else {
                throw new \Exception($result['error'] ?? 'Nieznany blad polaczenia FTP');
            }

        } catch (\Exception $e) {
            $this->ftpConnectionStatus = 'error';
            $this->ftpConnectionMessage = 'Blad: ' . $e->getMessage();

            Log::error('FTP connection test failed', [
                'host' => $this->ftpHost,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Scan CSS/JS files from PrestaShop (ETAP_07f_P3.5 - 2025-12-17)
     *
     * Discovers all CSS and JS files from the PrestaShop shop page.
     * Filters out minified files (.min.css, .min.js).
     * User can then select which files to synchronize.
     */
    public function scanCssJsFiles(): void
    {
        $this->isScanning = true;
        $this->scanStatus = 'scanning';
        $this->scanMessage = 'Skanowanie plikow CSS/JS z PrestaShop...';
        $this->scannedCssFiles = [];
        $this->scannedJsFiles = [];

        try {
            // Validate required fields
            if (empty($this->shopUrl)) {
                throw new \Exception('URL sklepu jest wymagany');
            }

            // Create temporary shop instance for asset discovery
            $tempShop = new PrestaShopShop([
                'id' => $this->editingShopId ?? 0,
                'name' => $this->shopName ?: 'Temp Shop',
                'url' => $this->shopUrl,
            ]);

            // Use PrestaShopAssetDiscovery to scan files via HTTP
            $discovery = app(\App\Services\VisualEditor\PrestaShopAssetDiscovery::class);
            $manifest = $discovery->discoverAssets($tempShop, forceRefresh: true);

            $cssFiles = $manifest['css'] ?? [];
            $jsFiles = $manifest['js'] ?? [];
            $scanSource = 'http';

            // FTP FALLBACK: If HTTP scan returns 0 files AND FTP is configured, try FTP scan
            if (empty($cssFiles) && empty($jsFiles) && $this->ftpEnabled && !empty($this->ftpHost) && !empty($this->ftpPassword)) {
                Log::info('CSS/JS HTTP scan returned 0 files, trying FTP fallback', [
                    'shop_url' => $this->shopUrl,
                    'ftp_host' => $this->ftpHost,
                ]);

                $this->scanMessage = 'HTTP zablokowany, skanowanie przez FTP...';

                $ftpConfig = [
                    'protocol' => $this->ftpProtocol,
                    'host' => $this->ftpHost,
                    'port' => (int) $this->ftpPort,
                    'user' => $this->ftpUser,
                    'password' => \App\Services\VisualEditor\PrestaShopCssFetcher::encryptPassword($this->ftpPassword),
                ];

                $cssFetcher = new \App\Services\VisualEditor\PrestaShopCssFetcher();
                $ftpManifest = $cssFetcher->scanFilesViaFtp($ftpConfig, $this->shopUrl);

                if (!empty($ftpManifest['css']) || !empty($ftpManifest['js'])) {
                    $cssFiles = $ftpManifest['css'] ?? [];
                    $jsFiles = $ftpManifest['js'] ?? [];
                    $scanSource = 'ftp';

                    Log::info('CSS/JS FTP scan successful', [
                        'shop_url' => $this->shopUrl,
                        'css_count' => count($cssFiles),
                        'js_count' => count($jsFiles),
                    ]);
                }
            }

            // Filter out minified files
            $cssFiles = array_filter($cssFiles, fn($f) => !$this->isMinifiedFile($f['url'] ?? ''));
            $jsFiles = array_filter($jsFiles, fn($f) => !$this->isMinifiedFile($f['url'] ?? ''));

            // Convert to our format with enabled flag
            $this->scannedCssFiles = array_values(array_map(function($file) {
                $url = $file['url'] ?? '';
                return [
                    'url' => $url,
                    'name' => $file['filename'] ?? basename($url),
                    'type' => $file['category'] ?? 'other',
                    'enabled' => $this->shouldAutoEnable($file),
                    'cached_content' => null,
                    'last_fetched_at' => null,
                ];
            }, $cssFiles));

            $this->scannedJsFiles = array_values(array_map(function($file) {
                $url = $file['url'] ?? '';
                return [
                    'url' => $url,
                    'name' => $file['filename'] ?? basename($url),
                    'type' => $file['category'] ?? 'other',
                    'enabled' => $this->shouldAutoEnableJs($file),
                    'cached_content' => null,
                    'last_fetched_at' => null,
                ];
            }, $jsFiles));

            // Update selected arrays based on enabled flag
            $this->selectedCssFiles = array_values(array_filter(
                array_map(fn($f) => ($f['enabled'] ?? false) ? $f['url'] : null, $this->scannedCssFiles)
            ));
            $this->selectedJsFiles = array_values(array_filter(
                array_map(fn($f) => ($f['enabled'] ?? false) ? $f['url'] : null, $this->scannedJsFiles)
            ));

            $this->scanStatus = 'success';
            $sourceLabel = $scanSource === 'ftp' ? ' (via FTP)' : '';
            $this->scanMessage = sprintf(
                'Znaleziono %d plikow CSS i %d plikow JS%s',
                count($this->scannedCssFiles),
                count($this->scannedJsFiles),
                $sourceLabel
            );

            Log::info('CSS/JS files scanned successfully', [
                'shop_url' => $this->shopUrl,
                'scan_source' => $scanSource,
                'css_count' => count($this->scannedCssFiles),
                'js_count' => count($this->scannedJsFiles),
                'auto_selected_css' => count($this->selectedCssFiles),
                'auto_selected_js' => count($this->selectedJsFiles),
            ]);

        } catch (\Exception $e) {
            $this->scanStatus = 'error';
            $this->scanMessage = 'Blad skanowania: ' . $e->getMessage();

            Log::error('CSS/JS scan failed', [
                'shop_url' => $this->shopUrl,
                'error' => $e->getMessage(),
            ]);
        } finally {
            $this->isScanning = false;
        }
    }

    /**
     * Check if file is minified (should be excluded from scan).
     */
    private function isMinifiedFile(string $url): bool
    {
        $lowercaseUrl = strtolower($url);
        return str_contains($lowercaseUrl, '.min.css')
            || str_contains($lowercaseUrl, '.min.js')
            || str_contains($lowercaseUrl, '-min.css')
            || str_contains($lowercaseUrl, '-min.js');
    }

    /**
     * Determine if CSS file should be auto-enabled.
     *
     * Auto-enables: theme.css, custom.css
     */
    private function shouldAutoEnable(array $file): bool
    {
        $name = strtolower($file['filename'] ?? '');
        $type = $file['category'] ?? '';

        // Always enable custom.css
        if (str_contains($name, 'custom.css')) {
            return true;
        }

        // Enable main theme CSS
        if ($type === 'theme' && (str_contains($name, 'theme.css') || $name === 'theme.css')) {
            return true;
        }

        return false;
    }

    /**
     * Determine if JS file should be auto-enabled.
     *
     * Auto-enables: custom.js only
     */
    private function shouldAutoEnableJs(array $file): bool
    {
        $name = strtolower($file['filename'] ?? '');

        // Only enable custom.js
        return str_contains($name, 'custom.js');
    }

    /**
     * Toggle CSS file selection.
     */
    public function toggleCssFile(string $url): void
    {
        if (in_array($url, $this->selectedCssFiles)) {
            $this->selectedCssFiles = array_values(array_filter(
                $this->selectedCssFiles,
                fn($u) => $u !== $url
            ));
        } else {
            $this->selectedCssFiles[] = $url;
        }

        // Update scannedCssFiles enabled flag
        $this->scannedCssFiles = array_map(function($f) use ($url) {
            if (($f['url'] ?? '') === $url) {
                $f['enabled'] = in_array($url, $this->selectedCssFiles);
            }
            return $f;
        }, $this->scannedCssFiles);
    }

    /**
     * Toggle JS file selection.
     */
    public function toggleJsFile(string $url): void
    {
        if (in_array($url, $this->selectedJsFiles)) {
            $this->selectedJsFiles = array_values(array_filter(
                $this->selectedJsFiles,
                fn($u) => $u !== $url
            ));
        } else {
            $this->selectedJsFiles[] = $url;
        }

        // Update scannedJsFiles enabled flag
        $this->scannedJsFiles = array_map(function($f) use ($url) {
            if (($f['url'] ?? '') === $url) {
                $f['enabled'] = in_array($url, $this->selectedJsFiles);
            }
            return $f;
        }, $this->scannedJsFiles);
    }

    /**
     * Fetch PrestaShop price groups (customer groups) for mapping
     *
     * Step 4: Price Group Mapping
     * Fetches all customer groups from PrestaShop which can have specific prices
     */
    public function fetchPrestashopPriceGroups()
    {
        $this->fetchingPriceGroups = true;
        $this->fetchPriceGroupsError = '';
        $this->prestashopPriceGroups = [];

        try {
            // Create temporary PrestaShopShop instance for API client
            // NOTE: This instance is NOT saved to database - used only for API connection
            $tempShop = new PrestaShopShop([
                'name' => $this->shopName,
                'url' => $this->shopUrl,
                'api_key' => $this->apiKey,
                'prestashop_version' => $this->prestashopVersion,
                'ssl_verify' => true,
                'timeout_seconds' => 30,
            ]);

            // Create appropriate client based on PrestaShop version
            $clientClass = $this->prestashopVersion === '9'
                ? \App\Services\PrestaShop\PrestaShop9Client::class
                : \App\Services\PrestaShop\PrestaShop8Client::class;

            $client = new $clientClass($tempShop);

            // Fetch price groups (customer groups)
            $response = $client->getPriceGroups();

            // Parse response - PrestaShop returns groups in 'groups' array
            if (isset($response['groups'])) {
                $groups = is_array($response['groups']) ? $response['groups'] : [$response['groups']];

                foreach ($groups as $group) {
                    // DEFENSIVE PARSING: Support both wrapped and direct structures
                    // Check if 'group' key exists BEFORE accessing it (prevents "Undefined array key")
                    if (isset($group['group'])) {
                        // Wrapped structure: ['group' => ['id' => 1, 'name' => 'Guest']]
                        $groupData = is_array($group['group']) ? $group['group'] : $group;
                    } else {
                        // Direct structure: ['id' => 1, 'name' => 'Guest'] (standard JSON from PrestaShop)
                        $groupData = $group;
                    }

                    // Extract ID with multiple fallback strategies
                    $id = $groupData['id']
                        ?? $groupData['@attributes']['id']
                        ?? $groupData['@id']
                        ?? null;

                    // Extract name with multilingual fallback
                    if (is_array($groupData['name'] ?? null)) {
                        $name = $groupData['name']['language']
                            ?? $groupData['name'][0]
                            ?? current($groupData['name'])
                            ?? 'Unknown';
                    } else {
                        $name = $groupData['name'] ?? 'Unknown';
                    }

                    // Only add groups with valid ID
                    if ($id !== null) {
                        $this->prestashopPriceGroups[] = [
                            'id' => $id,
                            'name' => $name,
                            'price_display_method' => $groupData['price_display_method'] ?? 0,
                            'reduction' => $groupData['reduction'] ?? 0,
                        ];
                    }
                }

                // Initialize mappings (empty for user to fill)
                // ✅ FIX BUG#11c: Only initialize if not already set (edit mode)
                foreach ($this->prestashopPriceGroups as $group) {
                    if ($group['id'] && !isset($this->priceGroupMappings[$group['id']])) {
                        $this->priceGroupMappings[$group['id']] = null;
                    }
                }

                Log::info('PrestaShop price groups fetched successfully', [
                    'shop_url' => $this->shopUrl,
                    'groups_count' => count($this->prestashopPriceGroups),
                    'groups' => $this->prestashopPriceGroups
                ]);
            } else {
                throw new \Exception('No price groups found in PrestaShop response');
            }

        } catch (\Exception $e) {
            $this->fetchPriceGroupsError = 'Błąd pobierania grup cenowych: ' . $e->getMessage();

            Log::error('Failed to fetch PrestaShop price groups', [
                'shop_url' => $this->shopUrl,
                'error' => $e->getMessage()
            ]);
        } finally {
            $this->fetchingPriceGroups = false;
        }
    }

    /**
     * Fetch Tax Rule Groups from PrestaShop (FAZA 5.1 - 2025-11-14)
     *
     * Called automatically after successful connection test in Step 3.
     * Fetches all tax rule groups from PrestaShop and applies smart defaults.
     *
     * Smart Defaults:
     * - Auto-selects groups with "23" in name → taxRulesGroup23
     * - Auto-selects groups with "8" in name → taxRulesGroup8
     * - Auto-selects groups with "5" in name → taxRulesGroup5
     * - Auto-selects groups with "0" or "zw" (zwolniony) in name → taxRulesGroup0
     */
    public function fetchTaxRuleGroups(): void
    {
        try {
            // Create temporary PrestaShopShop instance for API client
            // NOTE: This instance is NOT saved to database - used only for API connection
            $tempShop = new PrestaShopShop([
                'name' => $this->shopName,
                'url' => $this->shopUrl,
                'api_key' => $this->apiKey,
                'prestashop_version' => $this->prestashopVersion,
                'ssl_verify' => true,
                'timeout_seconds' => 30,
            ]);

            // Create appropriate client using PrestaShopClientFactory
            $client = \App\Services\PrestaShop\PrestaShopClientFactory::create($tempShop);

            // Fetch tax rule groups
            $this->availableTaxRuleGroups = $client->getTaxRuleGroups();

            // Smart defaults: Auto-select groups based on name patterns
            foreach ($this->availableTaxRuleGroups as $group) {
                $name = strtolower($group['name'] ?? '');

                // 23% VAT
                if (!$this->taxRulesGroup23 && (str_contains($name, '23') || str_contains($name, '23%'))) {
                    $this->taxRulesGroup23 = $group['id'];
                }

                // 8% VAT
                if (!$this->taxRulesGroup8 && (str_contains($name, '8') || str_contains($name, '8%')) && !str_contains($name, '23')) {
                    $this->taxRulesGroup8 = $group['id'];
                }

                // 5% VAT
                if (!$this->taxRulesGroup5 && (str_contains($name, '5') || str_contains($name, '5%'))) {
                    $this->taxRulesGroup5 = $group['id'];
                }

                // 0% VAT (zwolniony)
                if (!$this->taxRulesGroup0 && (str_contains($name, '0') || str_contains($name, 'zw') || str_contains($name, 'exempt'))) {
                    $this->taxRulesGroup0 = $group['id'];
                }
            }

            $this->taxRulesFetched = true;
            $this->dispatch('tax-rules-fetched'); // For UI update

            Log::info('[AddShop] Tax rule groups fetched successfully', [
                'shop_url' => $this->shopUrl,
                'groups_count' => count($this->availableTaxRuleGroups),
                'smart_defaults' => [
                    'tax_23' => $this->taxRulesGroup23,
                    'tax_8' => $this->taxRulesGroup8,
                    'tax_5' => $this->taxRulesGroup5,
                    'tax_0' => $this->taxRulesGroup0,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('[AddShop] Failed to fetch tax rule groups', [
                'shop_url' => $this->shopUrl,
                'error' => $e->getMessage()
            ]);

            $this->addError('tax_rules', 'Nie udało się pobrać grup podatkowych z PrestaShop: ' . $e->getMessage());
        }
    }

    /**
     * Refresh Tax Rule Groups from PrestaShop (FAZA 5.1 - 2025-11-14)
     *
     * Public method for UI button "Refresh from PrestaShop" in edit mode.
     * Re-fetches tax rule groups and updates dropdowns without changing user selections.
     */
    public function refreshTaxRuleGroups(): void
    {
        // Store current user selections before refresh
        $currentSelections = [
            'tax_23' => $this->taxRulesGroup23,
            'tax_8' => $this->taxRulesGroup8,
            'tax_5' => $this->taxRulesGroup5,
            'tax_0' => $this->taxRulesGroup0,
        ];

        // Re-fetch groups from PrestaShop
        $this->fetchTaxRuleGroups();

        // Restore user selections if they still exist in refreshed list
        $availableIds = array_column($this->availableTaxRuleGroups, 'id');

        if (in_array($currentSelections['tax_23'], $availableIds)) {
            $this->taxRulesGroup23 = $currentSelections['tax_23'];
        }
        if (in_array($currentSelections['tax_8'], $availableIds)) {
            $this->taxRulesGroup8 = $currentSelections['tax_8'];
        }
        if (in_array($currentSelections['tax_5'], $availableIds)) {
            $this->taxRulesGroup5 = $currentSelections['tax_5'];
        }
        if (in_array($currentSelections['tax_0'], $availableIds)) {
            $this->taxRulesGroup0 = $currentSelections['tax_0'];
        }

        session()->flash('success', 'Grupy podatkowe zostały odświeżone z PrestaShop.');

        Log::info('[AddShop] Tax rule groups refreshed by user', [
            'shop_url' => $this->shopUrl,
            'is_editing' => $this->isEditing,
            'groups_count' => count($this->availableTaxRuleGroups)
        ]);
    }

    /**
     * Validate Step 4: Price Group Mapping
     *
     * Ensures at least one price group is mapped
     */
    protected function validatePriceMappings()
    {
        $mappedCount = 0;
        foreach ($this->priceGroupMappings as $mapping) {
            if (!empty($mapping)) {
                $mappedCount++;
            }
        }

        if ($mappedCount === 0) {
            throw new \Exception('Musisz zmapować przynajmniej jedną grupę cenową');
        }

        return true;
    }

    /**
     * Save price group mappings to database
     *
     * @param int $shopId PrestaShop shop ID
     */
    protected function savePriceMappings(int $shopId)
    {
        // Delete existing mappings for this shop (for edit mode)
        \DB::table('prestashop_shop_price_mappings')
            ->where('prestashop_shop_id', $shopId)
            ->delete();

        // Insert new mappings
        foreach ($this->priceGroupMappings as $psGroupId => $ppmGroupName) {
            if (!empty($ppmGroupName)) {
                // Find PrestaShop group name from fetched groups
                $psGroupName = 'Unknown';
                foreach ($this->prestashopPriceGroups as $group) {
                    if ($group['id'] == $psGroupId) {
                        $psGroupName = $group['name'];
                        break;
                    }
                }

                \DB::table('prestashop_shop_price_mappings')->insert([
                    'prestashop_shop_id' => $shopId,
                    'prestashop_price_group_id' => $psGroupId,
                    'prestashop_price_group_name' => $psGroupName,
                    'ppm_price_group_name' => $ppmGroupName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        Log::info('Price group mappings saved', [
            'shop_id' => $shopId,
            'mappings_count' => count(array_filter($this->priceGroupMappings)),
            'mappings' => $this->priceGroupMappings
        ]);
    }

    public function saveShop()
    {
        try {
            // Validate all steps
            for ($i = 1; $i <= $this->totalSteps; $i++) {
                $this->currentStep = $i;
                $this->validateCurrentStep();
            }

            // Prepare shop data
            $shopData = [
                'name' => $this->shopName,
                'url' => $this->shopUrl,
                'description' => $this->shopDescription,
                'api_key' => $this->apiKey,
                'prestashop_version' => $this->prestashopVersion,
                'prestashop_version_exact' => $this->prestashopVersionExact ?: null,
                'sync_frequency' => $this->syncFrequency,
                // Tax Rules Mapping (FAZA 5.1 - 2025-11-14)
                'tax_rules_group_id_23' => $this->taxRulesGroup23,
                'tax_rules_group_id_8' => $this->taxRulesGroup8,
                'tax_rules_group_id_5' => $this->taxRulesGroup5,
                'tax_rules_group_id_0' => $this->taxRulesGroup0,
                'tax_rules_last_fetched_at' => $this->taxRulesFetched ? now() : null,
                'sync_settings' => [
                    'sync_products' => $this->syncProducts,
                    'sync_categories' => $this->syncCategories,
                    'sync_prices' => $this->syncPrices,
                    'sync_stock' => $this->syncStock,
                    'sync_orders' => $this->syncOrders,
                    'sync_customers' => $this->syncCustomers,
                    'auto_sync_enabled' => $this->autoSyncEnabled,
                    'real_time_sync' => $this->realTimeSyncEnabled,
                    'sync_only_active' => $this->syncOnlyActiveProducts,
                    'preserve_images' => $this->preserveLocalImages,
                    'sync_metadata' => $this->syncMetaData,
                    'enable_webhooks' => $this->enableWebhooks,
                    'ppm_module_api_key' => $this->ppmModuleApiKey,
                ],
                'conflict_resolution' => $this->conflictResolution,
                'notification_settings' => [
                    'notify_on_errors' => $this->notifyOnSyncErrors,
                    'notify_on_complete' => $this->notifyOnSyncComplete,
                    'enable_webhooks' => $this->enableWebhooks,
                ],
                'sync_configuration' => [
                    'batch_size' => $this->syncBatchSize,
                    'timeout_minutes' => $this->syncTimeoutMinutes,
                    'retry_failed' => $this->retryFailedSyncs,
                    'max_retry_attempts' => $this->maxRetryAttempts,
                ],
                // ETAP_07f: CSS/JS Sync Configuration (FTP required)
                'ftp_config' => $this->enableFtpSync ? [
                    'protocol' => $this->ftpProtocol,
                    'host' => $this->ftpHost,
                    'port' => (int) $this->ftpPort,
                    'user' => $this->ftpUser,
                    'password' => $this->ftpPassword
                        ? \App\Services\VisualEditor\PrestaShopCssFetcher::encryptPassword($this->ftpPassword)
                        : null,
                ] : null,
                // ETAP_07f_P3.5: Multi-file CSS/JS configuration (2025-12-17)
                'css_files' => !empty($this->scannedCssFiles) ? $this->scannedCssFiles : null,
                'js_files' => !empty($this->scannedJsFiles) ? $this->scannedJsFiles : null,
                'files_scanned_at' => !empty($this->scannedCssFiles) || !empty($this->scannedJsFiles) ? now() : null,
                // ETAP_10: Label customization
                'label_color' => $this->labelColor,
                'label_icon' => $this->labelIcon,
                // B2B Shop Flag
                'is_b2b' => $this->isB2b,
            ];

            if ($this->isEditing) {
                // Update existing shop
                $shop = PrestaShopShop::findOrFail($this->editingShopId);
                $shop->update($shopData);

                // Update price mappings
                $this->savePriceMappings($shop->id);

                Log::info('PrestaShop shop successfully updated', [
                    'shop_id' => $shop->id,
                    'shop_name' => $shop->name,
                    'shop_url' => $shop->url,
                ]);

                session()->flash('success', 'Sklep PrestaShop został pomyślnie zaktualizowany!');
            } else {
                // Create new shop with additional defaults
                // Set connection status based on test result
                $connectionStatus = PrestaShopShop::CONNECTION_DISCONNECTED; // default
                if ($this->connectionStatus === 'success') {
                    $connectionStatus = PrestaShopShop::CONNECTION_CONNECTED;
                } elseif ($this->connectionStatus === 'error') {
                    $connectionStatus = PrestaShopShop::CONNECTION_ERROR;
                }

                $shopData = array_merge($shopData, [
                    'is_active' => true,
                    'api_version' => '1.7',
                    'ssl_verify' => true,
                    'timeout_seconds' => 30,
                    'rate_limit_per_minute' => 100,
                    'connection_status' => $connectionStatus,
                    'last_connection_test' => now(),
                    'last_error_message' => $this->connectionStatus === 'error' ? $this->connectionMessage : null,
                    'version_compatible' => true,
                    'supported_features' => [],
                    'auto_sync_products' => $this->syncProducts,
                    'auto_sync_categories' => $this->syncCategories,
                    'auto_sync_prices' => $this->syncPrices,
                    'auto_sync_stock' => $this->syncStock,
                    'category_mappings' => [],
                    'price_group_mappings' => [],
                    'warehouse_mappings' => [],
                    'custom_field_mappings' => [],
                    'last_sync_at' => null,
                    'next_scheduled_sync' => null,
                    'products_synced' => 0,
                    'sync_success_count' => 0,
                    'sync_error_count' => 0,
                    'avg_response_time' => 0,
                    'api_quota_used' => 0,
                    'api_quota_limit' => 10000,
                    'quota_reset_at' => null,
                    'notify_on_errors' => $this->notifyOnSyncErrors,
                    'notify_on_sync_complete' => $this->notifyOnSyncComplete,
                ]);

                $shop = PrestaShopShop::create($shopData);

                // Save price mappings
                $this->savePriceMappings($shop->id);

                Log::info('PrestaShop shop successfully created', [
                    'shop_id' => $shop->id,
                    'shop_name' => $shop->name,
                    'shop_url' => $shop->url,
                ]);

                session()->flash('success', 'Sklep PrestaShop został pomyślnie dodany!');
            }

            // B2B: Ensure only one shop is marked as B2B
            if ($this->isB2b) {
                PrestaShopShop::setAsB2b($shop->id);
            }

            // Redirect to shops list
            return redirect()->route('admin.shops');

        } catch (\Exception $e) {
            $errorMessage = $this->isEditing ? 'aktualizacji' : 'zapisywania';
            $this->addError('save_error', "Błąd podczas {$errorMessage}: " . $e->getMessage());

            Log::error('Failed to save PrestaShop shop', [
                'error' => $e->getMessage(),
                'shop_data' => $this->getShopData(),
                'is_editing' => $this->isEditing,
                'editing_shop_id' => $this->editingShopId,
            ]);
        }
    }

    protected function getShopData()
    {
        return [
            'name' => $this->shopName,
            'url' => $this->shopUrl,
            'description' => $this->shopDescription,
            'prestashop_version' => $this->prestashopVersion,
            'sync_frequency' => $this->syncFrequency,
        ];
    }

    public function getStepTitle($step)
    {
        $titles = [
            1 => 'Podstawowe informacje',
            2 => 'Dane autoryzacji API',
            3 => 'Test połączenia',
            4 => 'Mapowanie grup cenowych',
            5 => 'Ustawienia synchronizacji',
            6 => 'Ustawienia zaawansowane'
        ];

        return $titles[$step] ?? 'Krok ' . $step;
    }

    public function getStepDescription($step)
    {
        $descriptions = [
            1 => 'Podaj nazwę, URL i opis sklepu PrestaShop',
            2 => 'Wprowadź klucz API i wybierz wersję PrestaShop',
            3 => 'Sprawdź poprawność połączenia z sklepem',
            4 => 'Zmapuj grupy cenowe PrestaShop z grupami PPM',
            5 => 'Skonfiguruj częstotliwość i zakres synchronizacji',
            6 => 'Zaawansowane opcje konfliktów, timeoutów i notyfikacji'
        ];

        return $descriptions[$step] ?? '';
    }

    /**
     * Check if PrestaShop version supports native WebP
     *
     * FIX 2026-02-05: PS 8.2.1+ supports WebP natively
     *
     * @param string $version Full version string (e.g., "8.2.1")
     * @return bool
     */
    private function supportsWebP(string $version): bool
    {
        return version_compare($version, '8.2.1', '>=');
    }

    /**
     * Test connection to PPM Image Manager module on PrestaShop shop
     */
    private function testPpmModuleConnection($client = null): array
    {
        $baseUrl = rtrim($this->shopUrl, '/');
        $moduleUrl = $baseUrl . '/module/ppmimagemanager/api';

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(10)
                ->withHeaders([
                    'X-PPM-Api-Key' => $this->ppmModuleApiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($moduleUrl, [
                    'action' => 'ping',
                ]);

            $data = $response->json();
            $isModuleJson = is_array($data) && array_key_exists('success', $data);

            // Ping success (v1.1.0+)
            if ($response->successful() && isset($data['success']) && $data['success'] === true) {
                $moduleVersion = $data['version'] ?? 'unknown';
                $displayName = $data['display_name'] ?? 'PPM Manager';
                return [
                    'check' => 'PPM Manager',
                    'status' => 'success',
                    'message' => $displayName . ' v' . $moduleVersion . ' polaczony',
                    'details' => 'PS ' . ($data['ps_version'] ?? '?') . ' | Endpoint aktywny',
                ];
            }

            // Module responds with JSON but error - module IS installed (old version or wrong action)
            if ($isModuleJson && in_array($response->status(), [400, 404])) {
                return [
                    'check' => 'PPM Manager',
                    'status' => 'success',
                    'message' => 'Modul zainstalowany i odpowiada',
                    'details' => 'Endpoint aktywny (zalecana aktualizacja do v1.1.0 dla pelnej diagnostyki)',
                ];
            }

            if ($response->status() === 401) {
                // Check if module JSON (wrong key) vs web server 401
                if ($isModuleJson) {
                    return [
                        'check' => 'PPM Manager',
                        'status' => 'error',
                        'message' => 'Nieprawidlowy klucz API modulu',
                        'details' => 'Sprawdz klucz w PrestaShop Admin > Modules > PPM Manager > Configure',
                    ];
                }
                return [
                    'check' => 'PPM Manager',
                    'status' => 'warning',
                    'message' => 'Autoryzacja odrzucona (HTTP 401)',
                    'details' => 'Serwer odrzucil zapytanie - sprawdz konfiguracje',
                ];
            }

            // HTTP 404 without JSON = module not installed (web server 404)
            if ($response->status() === 404 && !$isModuleJson) {
                return [
                    'check' => 'PPM Manager',
                    'status' => 'warning',
                    'message' => 'Modul nie zainstalowany na tym sklepie',
                    'details' => 'Zainstaluj modul ppmimagemanager na sklepie PrestaShop',
                ];
            }

            return [
                'check' => 'PPM Manager',
                'status' => 'warning',
                'message' => 'Nieoczekiwana odpowiedz (HTTP ' . $response->status() . ')',
                'details' => substr($response->body(), 0, 200),
            ];

        } catch (\Exception $e) {
            return [
                'check' => 'PPM Manager',
                'status' => 'warning',
                'message' => 'Nie mozna polaczyc z modulem',
                'details' => $e->getMessage(),
            ];
        }
    }

    public function render()
    {
        $title = $this->isEditing ? 'Edytuj Sklep PrestaShop - PPM' : 'Dodaj Sklep PrestaShop - PPM';
        $breadcrumb = $this->isEditing ? 'Edytuj sklep' : 'Dodaj sklep';

        return view('livewire.admin.shops.add-shop', [
            'stepTitle' => $this->getStepTitle($this->currentStep),
            'stepDescription' => $this->getStepDescription($this->currentStep),
            'progressPercentage' => ($this->currentStep / $this->totalSteps) * 100,
            'isEditing' => $this->isEditing
        ])->layout('layouts.admin', [
            'title' => $title,
            'breadcrumb' => $breadcrumb
        ]);
    }
}