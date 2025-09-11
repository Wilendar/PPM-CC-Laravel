<?php

namespace App\Http\Livewire\Admin\Customization;

use App\Models\AdminTheme as AdminThemeModel;
use App\Services\ThemeService;
use Livewire\Component;
use Livewire\WithFileUploads;

class AdminTheme extends Component
{
    use WithFileUploads;

    // Theme properties
    public $currentTheme;
    public $themeName = '';
    public $primaryColor = '#3b82f6';
    public $secondaryColor = '#64748b';
    public $accentColor = '#10b981';
    public $layoutDensity = 'normal';
    public $sidebarPosition = 'left';
    public $headerStyle = 'fixed';
    public $customCss = '';
    public $companyName = 'PPM Admin';
    public $companyLogo;
    public $companyColors = [];
    
    // Widget layout properties
    public $widgetLayout = [];
    public $dashboardSettings = [];
    
    // UI state
    public $activeTab = 'colors';
    public $isCreatingNew = false;
    public $availableThemes = [];
    public $previewMode = false;
    public $showCssEditor = false;
    
    // File uploads
    public $logoFile;
    public $importFile;

    protected $listeners = [
        'themeUpdated' => 'refreshTheme',
        'widgetLayoutChanged' => 'handleWidgetLayoutChange',
    ];

    protected $rules = [
        'themeName' => 'required|string|max:100',
        'primaryColor' => 'required|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
        'secondaryColor' => 'required|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
        'accentColor' => 'required|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
        'layoutDensity' => 'required|in:compact,normal,spacious',
        'sidebarPosition' => 'required|in:left,right',
        'headerStyle' => 'required|in:fixed,static,floating',
        'companyName' => 'required|string|max:100',
        'logoFile' => 'nullable|image|max:2048|mimes:jpg,jpeg,png,svg,webp',
        'importFile' => 'nullable|file|mimes:json|max:1024',
    ];

    public function mount(ThemeService $themeService)
    {
        $this->loadCurrentTheme($themeService);
        $this->loadAvailableThemes($themeService);
    }

    public function render()
    {
        return view('livewire.admin.customization.admin-theme', [
            'availableThemes' => $this->availableThemes,
        ])->layout('layouts.admin');
    }

    /**
     * Load current active theme
     */
    public function loadCurrentTheme(ThemeService $themeService)
    {
        $this->currentTheme = $themeService->getActiveTheme(auth()->user());
        
        // Populate form fields
        $this->themeName = $this->currentTheme->theme_name;
        $this->primaryColor = $this->currentTheme->primary_color;
        $this->secondaryColor = $this->currentTheme->secondary_color;
        $this->accentColor = $this->currentTheme->accent_color;
        $this->layoutDensity = $this->currentTheme->layout_density;
        $this->sidebarPosition = $this->currentTheme->sidebar_position;
        $this->headerStyle = $this->currentTheme->header_style;
        $this->customCss = $this->currentTheme->custom_css ?? '';
        $this->companyName = $this->currentTheme->company_name;
        $this->companyColors = $this->currentTheme->company_colors ?? [];
        $this->widgetLayout = $this->currentTheme->widget_layout ?? [];
        $this->dashboardSettings = $this->currentTheme->dashboard_settings ?? [];
    }

    /**
     * Load available themes
     */
    public function loadAvailableThemes(ThemeService $themeService)
    {
        $this->availableThemes = $themeService->getAvailableThemes(auth()->user())->toArray();
    }

    /**
     * Switch to different tab
     */
    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        
        if ($tab === 'css') {
            $this->showCssEditor = true;
        }
    }

    /**
     * Update theme colors
     */
    public function updateColors()
    {
        $this->validate([
            'primaryColor' => $this->rules['primaryColor'],
            'secondaryColor' => $this->rules['secondaryColor'],
            'accentColor' => $this->rules['accentColor'],
        ]);
        
        try {
            $themeService = app(ThemeService::class);
            
            $this->currentTheme = $themeService->updateTheme(auth()->user(), [
                'primary_color' => $this->primaryColor,
                'secondary_color' => $this->secondaryColor,
                'accent_color' => $this->accentColor,
            ]);
            
            $this->emit('themeColorsUpdated');
            session()->flash('message', 'Kolory motywu zostały zaktualizowane.');
            
        } catch (\Exception $e) {
            $this->addError('colors', 'Błąd podczas aktualizacji kolorów: ' . $e->getMessage());
        }
    }

    /**
     * Update layout settings
     */
    public function updateLayout()
    {
        $this->validate([
            'layoutDensity' => $this->rules['layoutDensity'],
            'sidebarPosition' => $this->rules['sidebarPosition'],
            'headerStyle' => $this->rules['headerStyle'],
        ]);
        
        try {
            $themeService = app(ThemeService::class);
            
            $this->currentTheme = $themeService->updateTheme(auth()->user(), [
                'layout_density' => $this->layoutDensity,
                'sidebar_position' => $this->sidebarPosition,
                'header_style' => $this->headerStyle,
            ]);
            
            $this->emit('themeLayoutUpdated');
            session()->flash('message', 'Ustawienia layoutu zostały zaktualizowane.');
            
        } catch (\Exception $e) {
            $this->addError('layout', 'Błąd podczas aktualizacji layoutu: ' . $e->getMessage());
        }
    }

    /**
     * Update branding settings
     */
    public function updateBranding()
    {
        $this->validate([
            'companyName' => $this->rules['companyName'],
            'logoFile' => $this->rules['logoFile'],
        ]);
        
        try {
            $themeService = app(ThemeService::class);
            
            $data = [
                'company_name' => $this->companyName,
                'company_colors' => $this->companyColors,
            ];
            
            if ($this->logoFile) {
                $data['company_logo'] = $this->logoFile;
            }
            
            $this->currentTheme = $themeService->updateTheme(auth()->user(), $data);
            
            $this->emit('themeBrandingUpdated');
            session()->flash('message', 'Ustawienia brandingu zostały zaktualizowane.');
            
            // Reset file upload
            $this->logoFile = null;
            
        } catch (\Exception $e) {
            $this->addError('branding', 'Błąd podczas aktualizacji brandingu: ' . $e->getMessage());
        }
    }

    /**
     * Update custom CSS
     */
    public function updateCustomCss()
    {
        try {
            $themeService = app(ThemeService::class);
            
            $this->currentTheme = $themeService->updateTheme(auth()->user(), [
                'custom_css' => $this->customCss,
            ]);
            
            $this->emit('themeCustomCssUpdated');
            session()->flash('message', 'Custom CSS został zaktualizowany.');
            
        } catch (\Exception $e) {
            $this->addError('customCss', 'Błąd podczas aktualizacji CSS: ' . $e->getMessage());
        }
    }

    /**
     * Create new theme
     */
    public function createNewTheme()
    {
        $this->validate();
        
        try {
            $themeService = app(ThemeService::class);
            
            $data = [
                'theme_name' => $this->themeName,
                'primary_color' => $this->primaryColor,
                'secondary_color' => $this->secondaryColor,
                'accent_color' => $this->accentColor,
                'layout_density' => $this->layoutDensity,
                'sidebar_position' => $this->sidebarPosition,
                'header_style' => $this->headerStyle,
                'custom_css' => $this->customCss,
                'company_name' => $this->companyName,
                'company_colors' => $this->companyColors,
            ];
            
            if ($this->logoFile) {
                $data['company_logo'] = $this->logoFile;
            }
            
            $newTheme = $themeService->createTheme(auth()->user(), $data);
            
            $this->loadAvailableThemes($themeService);
            $this->isCreatingNew = false;
            
            session()->flash('message', 'Nowy motyw został utworzony.');
            
        } catch (\Exception $e) {
            $this->addError('create', 'Błąd podczas tworzenia motywu: ' . $e->getMessage());
        }
    }

    /**
     * Switch active theme
     */
    public function switchTheme($themeId)
    {
        try {
            $theme = AdminThemeModel::findOrFail($themeId);
            $themeService = app(ThemeService::class);
            
            $this->currentTheme = $themeService->switchTheme(auth()->user(), $theme);
            $this->loadCurrentTheme($themeService);
            
            $this->emit('themeChanged');
            session()->flash('message', 'Motyw został przełączony.');
            
        } catch (\Exception $e) {
            $this->addError('switch', 'Błąd podczas przełączania motywu: ' . $e->getMessage());
        }
    }

    /**
     * Delete theme
     */
    public function deleteTheme($themeId)
    {
        try {
            $theme = AdminThemeModel::findOrFail($themeId);
            $themeService = app(ThemeService::class);
            
            $themeService->deleteTheme(auth()->user(), $theme);
            $this->loadAvailableThemes($themeService);
            
            session()->flash('message', 'Motyw został usunięty.');
            
        } catch (\Exception $e) {
            $this->addError('delete', 'Błąd podczas usuwania motywu: ' . $e->getMessage());
        }
    }

    /**
     * Export theme
     */
    public function exportTheme($themeId)
    {
        try {
            $theme = AdminThemeModel::findOrFail($themeId);
            $themeService = app(ThemeService::class);
            
            $config = $themeService->exportTheme($theme);
            $filename = 'ppm_theme_' . $theme->theme_name . '_' . date('Y-m-d') . '.json';
            
            return response()->streamDownload(function () use ($config) {
                echo json_encode($config, JSON_PRETTY_PRINT);
            }, $filename, ['Content-Type' => 'application/json']);
            
        } catch (\Exception $e) {
            $this->addError('export', 'Błąd podczas eksportu motywu: ' . $e->getMessage());
        }
    }

    /**
     * Import theme
     */
    public function importTheme()
    {
        $this->validate([
            'importFile' => $this->rules['importFile'],
        ]);
        
        try {
            $content = file_get_contents($this->importFile->getPathname());
            $config = json_decode($content, true);
            
            if (!$config) {
                throw new \Exception('Invalid JSON file');
            }
            
            $themeService = app(ThemeService::class);
            $newTheme = $themeService->importTheme(auth()->user(), $config);
            
            $this->loadAvailableThemes($themeService);
            $this->importFile = null;
            
            session()->flash('message', 'Motyw został zaimportowany pomyślnie.');
            
        } catch (\Exception $e) {
            $this->addError('import', 'Błąd podczas importu motywu: ' . $e->getMessage());
        }
    }

    /**
     * Handle widget layout changes
     */
    public function handleWidgetLayoutChange($layout)
    {
        try {
            $themeService = app(ThemeService::class);
            $themeService->updateWidgetLayout(auth()->user(), $layout);
            
            $this->widgetLayout = $layout;
            session()->flash('message', 'Layout widgetów został zaktualizowany.');
            
        } catch (\Exception $e) {
            $this->addError('widgets', 'Błąd podczas aktualizacji layoutu: ' . $e->getMessage());
        }
    }

    /**
     * Update dashboard settings
     */
    public function updateDashboardSettings()
    {
        try {
            $themeService = app(ThemeService::class);
            $themeService->updateDashboardSettings(auth()->user(), $this->dashboardSettings);
            
            session()->flash('message', 'Ustawienia dashboard zostały zaktualizowane.');
            
        } catch (\Exception $e) {
            $this->addError('dashboard', 'Błąd podczas aktualizacji ustawień: ' . $e->getMessage());
        }
    }

    /**
     * Toggle preview mode
     */
    public function togglePreview()
    {
        $this->previewMode = !$this->previewMode;
        $this->emit('previewModeToggled', $this->previewMode);
    }

    /**
     * Reset to default theme
     */
    public function resetToDefault()
    {
        try {
            $themeService = app(ThemeService::class);
            $defaultTheme = $themeService->createDefaultTheme(auth()->user());
            
            $this->loadCurrentTheme($themeService);
            $this->loadAvailableThemes($themeService);
            
            $this->emit('themeReset');
            session()->flash('message', 'Motyw został zresetowany do domyślnego.');
            
        } catch (\Exception $e) {
            $this->addError('reset', 'Błąd podczas resetowania motywu: ' . $e->getMessage());
        }
    }

    /**
     * Add company color
     */
    public function addCompanyColor($color)
    {
        if (!in_array($color, $this->companyColors)) {
            $this->companyColors[] = $color;
        }
    }

    /**
     * Remove company color
     */
    public function removeCompanyColor($index)
    {
        unset($this->companyColors[$index]);
        $this->companyColors = array_values($this->companyColors);
    }

    /**
     * Generate CSS preview
     */
    public function getCssPreview()
    {
        $tempTheme = new AdminThemeModel([
            'primary_color' => $this->primaryColor,
            'secondary_color' => $this->secondaryColor,
            'accent_color' => $this->accentColor,
            'layout_density' => $this->layoutDensity,
            'sidebar_position' => $this->sidebarPosition,
            'header_style' => $this->headerStyle,
            'custom_css' => $this->customCss,
        ]);
        
        return $tempTheme->toCss();
    }

    /**
     * Refresh theme data
     */
    public function refreshTheme()
    {
        $themeService = app(ThemeService::class);
        $this->loadCurrentTheme($themeService);
        $this->emit('themeRefreshed');
    }

    /**
     * Get widget name by ID
     */
    public function getWidgetName($widgetId)
    {
        $widgets = $this->getAvailableWidgets();
        return $widgets[$widgetId]['name'] ?? 'Unknown Widget';
    }

    /**
     * Get available widgets for the library
     */
    public function getAvailableWidgets()
    {
        return [
            'stats-overview' => [
                'name' => 'Przegląd Statystyk',
                'description' => 'Podstawowe metryki systemu',
                'icon' => '<svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>',
                'size' => '12×2',
            ],
            'recent-activity' => [
                'name' => 'Ostatnia Aktywność',
                'description' => 'Lista ostatnich działań w systemie',
                'icon' => '<svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                'size' => '6×4',
            ],
            'system-health' => [
                'name' => 'Stan Systemu',
                'description' => 'Monitoring zdrowia aplikacji',
                'icon' => '<svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>',
                'size' => '6×4',
            ],
            'integration-status' => [
                'name' => 'Status Integracji',
                'description' => 'Monitoring połączeń ERP i sklepów',
                'icon' => '<svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>',
                'size' => '8×3',
            ],
            'quick-actions' => [
                'name' => 'Szybkie Akcje',
                'description' => 'Najczęściej używane funkcje',
                'icon' => '<svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>',
                'size' => '4×3',
            ],
            'user-activity' => [
                'name' => 'Aktywność Użytkowników',
                'description' => 'Analiza aktywności użytkowników',
                'icon' => '<svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>',
                'size' => '6×3',
            ],
        ];
    }

    /**
     * Add widget to layout
     */
    public function addWidget($widgetId)
    {
        $widgets = $this->widgetLayout['widgets'] ?? [];
        
        // Find empty position
        $x = 0;
        $y = count($widgets) > 0 ? max(array_column($widgets, 'y')) + 1 : 0;
        
        // Default widget size
        $w = 6;
        $h = 3;
        
        // Adjust size based on widget type
        $availableWidgets = $this->getAvailableWidgets();
        if (isset($availableWidgets[$widgetId])) {
            $size = explode('×', $availableWidgets[$widgetId]['size']);
            $w = (int) $size[0];
            $h = (int) $size[1];
        }
        
        $widgets[] = [
            'id' => $widgetId,
            'x' => $x,
            'y' => $y,
            'w' => $w,
            'h' => $h,
        ];
        
        $this->widgetLayout['widgets'] = $widgets;
        
        try {
            $themeService = app(ThemeService::class);
            $themeService->updateWidgetLayout(auth()->user(), $this->widgetLayout);
            
            session()->flash('message', 'Widget został dodany do dashboard.');
        } catch (\Exception $e) {
            $this->addError('widgets', 'Błąd podczas dodawania widgetu: ' . $e->getMessage());
        }
    }

    /**
     * Remove widget from layout
     */
    public function removeWidget($index)
    {
        if (isset($this->widgetLayout['widgets'][$index])) {
            unset($this->widgetLayout['widgets'][$index]);
            $this->widgetLayout['widgets'] = array_values($this->widgetLayout['widgets']);
            
            try {
                $themeService = app(ThemeService::class);
                $themeService->updateWidgetLayout(auth()->user(), $this->widgetLayout);
                
                session()->flash('message', 'Widget został usunięty z dashboard.');
            } catch (\Exception $e) {
                $this->addError('widgets', 'Błąd podczas usuwania widgetu: ' . $e->getMessage());
            }
        }
    }

    /**
     * Get data sources status
     */
    public function getDataSources()
    {
        return [
            [
                'name' => 'Baza Danych',
                'status' => 'active',
                'latency' => 12,
            ],
            [
                'name' => 'PrestaShop API',
                'status' => 'active',
                'latency' => 245,
            ],
            [
                'name' => 'Baselinker API',
                'status' => 'active',
                'latency' => 180,
            ],
            [
                'name' => 'Cache Redis',
                'status' => 'active',
                'latency' => 3,
            ],
        ];
    }

    /**
     * Get widget templates
     */
    public function getWidgetTemplates()
    {
        return [
            [
                'id' => 'minimal',
                'name' => 'Minimalny Dashboard',
                'description' => 'Podstawowe widgety dla prostego overview',
                'widgets' => ['stats-overview', 'recent-activity', 'quick-actions'],
            ],
            [
                'id' => 'full-monitoring',
                'name' => 'Pełny Monitoring',
                'description' => 'Kompletny zestaw widgetów monitoringu',
                'widgets' => ['stats-overview', 'system-health', 'integration-status', 'user-activity'],
            ],
            [
                'id' => 'business-focus',
                'name' => 'Business Intelligence',
                'description' => 'Widgety skupione na aspektach biznesowych',
                'widgets' => ['stats-overview', 'user-activity', 'integration-status'],
            ],
        ];
    }

    /**
     * Get CSS examples for templates
     */
    public function getCSSExamples()
    {
        return [
            [
                'id' => 'gradient-buttons',
                'name' => 'Gradient Buttons',
                'description' => 'Kolorowe przyciski z gradientem',
                'code' => '.btn-gradient {
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    border: none;
    color: white;
    transition: transform 0.2s ease;
}
.btn-gradient:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}',
            ],
            [
                'id' => 'rounded-widgets',
                'name' => 'Rounded Widgets',
                'description' => 'Bardziej zaokrąglone widgety',
                'code' => '.widget {
    border-radius: 16px;
    border: 1px solid rgba(var(--primary-color), 0.1);
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}',
            ],
            [
                'id' => 'animated-hover',
                'name' => 'Animated Hover',
                'description' => 'Animacje hover dla elementów',
                'code' => '.hover-lift {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.hover-lift:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.12);
}',
            ],
        ];
    }

    /**
     * Apply widget template
     */
    public function applyTemplate($templateId)
    {
        $templates = $this->getWidgetTemplates();
        $template = collect($templates)->firstWhere('id', $templateId);
        
        if (!$template) {
            $this->addError('widgets', 'Szablon nie został znaleziony.');
            return;
        }
        
        // Clear existing widgets and apply template
        $widgets = [];
        $y = 0;
        
        foreach ($template['widgets'] as $widgetId) {
            $availableWidgets = $this->getAvailableWidgets();
            if (isset($availableWidgets[$widgetId])) {
                $size = explode('×', $availableWidgets[$widgetId]['size']);
                $w = (int) $size[0];
                $h = (int) $size[1];
                
                $widgets[] = [
                    'id' => $widgetId,
                    'x' => 0,
                    'y' => $y,
                    'w' => $w,
                    'h' => $h,
                ];
                
                $y += $h;
            }
        }
        
        $this->widgetLayout['widgets'] = $widgets;
        
        try {
            $themeService = app(ThemeService::class);
            $themeService->updateWidgetLayout(auth()->user(), $this->widgetLayout);
            
            session()->flash('message', "Szablon '{$template['name']}' został zastosowany.");
        } catch (\Exception $e) {
            $this->addError('widgets', 'Błąd podczas stosowania szablonu: ' . $e->getMessage());
        }
    }
}