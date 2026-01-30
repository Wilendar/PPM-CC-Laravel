<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Admin\VehicleFeatureController;
use App\Http\Controllers\ThumbnailController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| PPM-CC-Laravel Web Routes - Simplified Version
| Only working routes without missing Livewire components
|
*/

// ==========================================
// PUBLIC ROUTES (no auth required)
// ==========================================

// Thumbnail generation route (on-demand, cached)
Route::get('/thumbnail/{mediaId}', [ThumbnailController::class, 'show'])
    ->name('thumbnail')
    ->where('mediaId', '[0-9]+');

// Variant Image thumbnail (on-demand, cached)
Route::get('/thumbnail/variant/{variantImageId}', [ThumbnailController::class, 'showVariant'])
    ->name('thumbnail.variant')
    ->where('variantImageId', '[0-9]+');

// Test CSS loading
Route::get('/test-css', function () {
    return view('test-css');
});

// POC: Color Picker with vanilla-colorful + Alpine.js (DISABLED - old POC)
// Route::get('/test-color-picker-poc', \App\Http\Livewire\Test\ColorPickerPOC::class)
//     ->middleware(['auth'])
//     ->name('test.color-picker-poc');

// ETAP_05b Phase 3: Production AttributeColorPicker Component Test
Route::get('/test-attribute-color-picker', function () {
    return view('test-attribute-color-picker');
})
    ->middleware(['auth'])
    ->name('test.attribute-color-picker');

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return view('welcome');
})->name('home');

// Health check for hosting
Route::get('/up', function () {
    return response()->json(['status' => 'ok', 'timestamp' => now()]);
})->name('health');

// Subiekt GT REST API Integration Test
Route::get('/test-subiekt-api', function () {
    try {
        $config = [
            'rest_api_url' => 'https://sapi.mpptrade.pl',
            'rest_api_key' => 'YHZ4AtJiNBrEFhez7AvPTGJK3XKCrX4NCyGLwrQpecqCyvP3XxxCGYRvjdmtGkRb',
            'rest_api_timeout' => 30,
            'rest_api_connect_timeout' => 10,
            'rest_api_verify_ssl' => false,
            'connection_mode' => 'rest_api',
        ];

        $client = new \App\Services\ERP\SubiektGT\SubiektRestApiClient([
            'base_url' => $config['rest_api_url'],
            'api_key' => $config['rest_api_key'],
            'timeout' => $config['rest_api_timeout'],
            'connect_timeout' => $config['rest_api_connect_timeout'],
            'verify_ssl' => $config['rest_api_verify_ssl'],
        ]);

        $results = ['tests' => []];

        // Test 1: Health check
        try {
            $health = $client->healthCheck();
            $results['tests']['health'] = [
                'success' => true,
                'data' => $health,
            ];
        } catch (\Exception $e) {
            $results['tests']['health'] = [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }

        // Test 2: Get products
        try {
            $products = $client->getProducts(['page' => 1, 'pageSize' => 3]);
            $results['tests']['products'] = [
                'success' => $products['success'] ?? false,
                'count' => count($products['data'] ?? []),
                'total' => $products['pagination']['total_items'] ?? 0,
                'sample' => array_slice($products['data'] ?? [], 0, 1),
            ];
        } catch (\Exception $e) {
            $results['tests']['products'] = [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }

        return response()->json([
            'status' => 'completed',
            'api_url' => $config['rest_api_url'],
            'results' => $results,
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
})->name('test.subiekt-api');

// ==========================================
// AUTHENTICATION ROUTES
// ==========================================

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required'
    ]);
    
    $credentials = $request->only('email', 'password');
    $remember = $request->boolean('remember');
    
    if (Auth::attempt($credentials, $remember)) {
        $request->session()->regenerate();
        $user = Auth::user();
        
        Log::info('Successful login for user: ' . $user->email);
        
        // Redirect based on user role – Admin do panelu, pozostali na dashboard
        if ($user->hasRole('Admin')) {
            return redirect()->intended('/admin');
        }

        return redirect()->intended('/dashboard');
    }
    
    return back()->withErrors([
        'email' => 'Podane dane logowania są nieprawidłowe.'
    ])->withInput($request->only('email'));
})->name('login.store');

Route::post('/logout', function () {
    Auth::logout();
    return redirect()->route('home');
})->name('logout');

// ==========================================
// AUTHENTICATED ROUTES
// ==========================================

Route::middleware(['auth'])->group(function () {
    
    // Dashboard - wszyscy zalogowani użytkownicy
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
    
    // Profile management
    Route::get('/profile', function () {
        return view('profile.show');
    })->name('profile.show');

    Route::get('/profile/edit', function () {
        return view('profile.edit');
    })->name('profile.edit');

    Route::patch('/profile', function (\Illuminate\Http\Request $request) {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'current_password' => ['nullable', 'current_password'],
            'password' => ['nullable', 'min:8', 'confirmed'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,gif,webp', 'max:2048'],
            'remove_avatar' => ['nullable', 'boolean'],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if (!empty($validated['password'])) {
            $user->password = bcrypt($validated['password']);
        }

        // Handle avatar removal
        if ($request->boolean('remove_avatar') && $user->avatar) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($user->avatar);
            $user->avatar = null;
        }

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($user->avatar) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->avatar);
            }

            // Store new avatar
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $path;
        }

        $user->save();

        return redirect()->route('profile.show')->with('success', 'Profil zaktualizowany pomyslnie.');
    })->name('profile.update');

    Route::delete('/profile', function (\Illuminate\Http\Request $request) {
        $user = auth()->user();
        auth()->logout();
        $user->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'Konto zostalo usuniete.');
    })->name('profile.destroy');

    // Additional profile routes
    // Active Sessions Management - ETAP_04 FAZA A (User Management - planned)
    Route::get('/profile/sessions', function () {
        return view('placeholder-page', [
            'title' => 'Aktywne Sesje',
            'message' => 'Panel zarządzania aktywnymi sesjami użytkownika z możliwością wylogowania z innych urządzeń będzie dostępny wkrótce.',
            'etap' => 'ETAP_04 FAZA A - User Management (zaplanowane)'
        ]);
    })->name('profile.sessions');

    // Activity History - ETAP_04 FAZA A (User Management - planned)
    Route::get('/profile/activity', function () {
        return view('placeholder-page', [
            'title' => 'Historia Aktywności',
            'message' => 'Timeline aktywności użytkownika (login/logout, zmiany produktów, akcje admin) będzie dostępny wkrótce.',
            'etap' => 'ETAP_04 FAZA A - User Management (zaplanowane)'
        ]);
    })->name('profile.activity');

    Route::get('/profile/notifications', function () {
        return view('placeholder-page', [
            'title' => 'Ustawienia Powiadomień',
            'message' => 'Panel ustawień powiadomień będzie dostępny w przyszłej wersji.',
            'etap' => null
        ]);
    })->name('profile.notifications');

    // Bug Reports - User's personal reports history
    Route::get('/profile/bug-reports', \App\Http\Livewire\BugReports\UserBugReports::class)
        ->name('profile.bug-reports');
});

// ==========================================
// HELP ROUTES
// ==========================================

Route::middleware(['auth'])->prefix('help')->name('help.')->group(function () {
    // Help Index - FUTURE (Help Module planned)
    Route::get('/', function () {
        return view('placeholder-page', [
            'title' => 'Pomoc',
            'message' => 'Centrum pomocy z dokumentacją, FAQ i video tutorials będzie dostępne wkrótce.',
            'etap' => 'FUTURE - zaplanowane'
        ]);
    })->name('index');

    // Documentation - FUTURE (Help Module planned)
    Route::get('/documentation', function () {
        return view('placeholder-page', [
            'title' => 'Dokumentacja',
            'message' => 'Dokumentacja użytkownika, FAQ i video tutorials będą dostępne wkrótce.',
            'etap' => 'FUTURE - zaplanowane'
        ]);
    })->name('documentation');

    // Keyboard Shortcuts - FUTURE (Help Module planned)
    Route::get('/shortcuts', function () {
        return view('placeholder-page', [
            'title' => 'Skróty Klawiszowe',
            'message' => 'Lista skrótów klawiszowych (Ctrl+K Quick Search, Ctrl+N Nowy Produkt, Ctrl+S Zapisz) będzie dostępna wkrótce.',
            'etap' => 'FUTURE - zaplanowane'
        ]);
    })->name('shortcuts');

    // Technical Support - FUTURE (Help Module planned)
    Route::get('/support', function () {
        return view('placeholder-page', [
            'title' => 'Wsparcie Techniczne',
            'message' => 'System zgłoszeń wsparcia technicznego będzie dostępny wkrótce.',
            'etap' => null
        ]);
    })->name('support');
});

// ==========================================
// ADMIN ROUTES (tylko istniejące komponenty Livewire)
// ==========================================

// DEV_AUTH_BYPASS: Controlled via SystemSettings or .env fallback
// Admin Panel → System Settings → Security → "Development Mode"
// WARNING: NEVER enable in production!
$devAuthBypass = false;
try {
    // Try database setting first, fallback to .env
    $devAuthBypass = \App\Models\SystemSetting::get('dev_auth_bypass', env('DEV_AUTH_BYPASS', false));
} catch (\Exception $e) {
    // Database not ready - use .env fallback
    $devAuthBypass = env('DEV_AUTH_BYPASS', false);
}
$adminMiddleware = $devAuthBypass ? [] : ['auth'];

Route::prefix('admin')->name('admin.')->middleware($adminMiddleware)->group(function () {
    
    // Admin Dashboard - działający komponent Livewire
    Route::get('/', \App\Http\Livewire\Dashboard\AdminDashboard::class)->name('dashboard');
    
    // System Settings - działający komponent
    Route::get('/system-settings', \App\Http\Livewire\Admin\Settings\SystemSettings::class)
         ->name('system-settings.index');
    
    // Backup Management - działający komponent
    Route::prefix('backup')->name('backup.')->group(function () {
        Route::get('/', \App\Http\Livewire\Admin\Backup\BackupManager::class)->name('index');
    });
    
    // Maintenance Management - działający komponent
    Route::get('/maintenance', \App\Http\Livewire\Admin\Maintenance\DatabaseMaintenance::class)
         ->name('maintenance.index');

    // Media Management - ETAP_07d Phase 8: Admin Media Panel
    Route::get('/media', \App\Http\Livewire\Admin\Media\MediaManager::class)
         ->name('media.index');

    // Shop Management - działający komponent
    Route::get('/shops', \App\Http\Livewire\Admin\Shops\ShopManager::class)->name('shops');
    
    // Add New Shop - wizard component
    Route::get('/shops/add', \App\Http\Livewire\Admin\Shops\AddShop::class)->name('shops.add');
    
    // Shop CSS Editor - ETAP_07h FAZA 8: Direct CSS editing
    Route::get('/shops/{shopId}/css-editor', \App\Http\Livewire\Admin\Shops\ShopCssEditor::class)->name('shops.css-editor');

    // Shop Synchronization Control - sync management panel
    Route::get('/shops/sync', \App\Http\Livewire\Admin\Shops\SyncController::class)->name('shops.sync');

    // Price Management - FAZA 4: PRICE MANAGEMENT SYSTEM
    Route::prefix('price-management')->name('price-management.')->group(function () {
        // Price Groups Management - działający komponent
        Route::get('/price-groups', \App\Http\Livewire\Admin\PriceManagement\PriceGroups::class)
             ->name('price-groups.index');

        // Product Prices Management - FUTURE (Price Management Module)
        Route::get('/product-prices', function () {
            return view('placeholder-page', [
                'title' => 'Ceny Produktów',
                'message' => 'System zarządzania cenami produktów z edycją inline i automatycznym wyliczaniem marży będzie dostępny w przyszłej wersji.',
                'etap' => 'FUTURE - zaplanowane (Price Management Module)'
            ]);
        })->name('product-prices.index');

        // Bulk Price Updates - FUTURE (Price Management Module)
        Route::get('/bulk-updates', function () {
            return view('placeholder-page', [
                'title' => 'Aktualizacja Masowa Cen',
                'message' => 'Wizard aktualizacji masowej cen (5-step wizard) z preview zmian będzie dostępny w przyszłej wersji.',
                'etap' => 'FUTURE - zaplanowane (Price Management Module)'
            ]);
        })->name('bulk-updates.index');
    });
    
    // Bulk Product Export - mass export to PrestaShop stores
    Route::get('/shops/export', \App\Http\Livewire\Admin\Shops\BulkExport::class)->name('shops.export');

    // Import Management - SEKCJA 2.2.2.2 Import Management
    Route::get('/shops/import', \App\Http\Livewire\Admin\Shops\ImportManager::class)->name('shops.import');

    // ==========================================
    // CSV IMPORT/EXPORT SYSTEM - FAZA 6
    // ==========================================

    // CSV Template Downloads
    Route::get('/csv/templates/{type}', [\App\Http\Controllers\Admin\CSVExportController::class, 'downloadTemplate'])
        ->name('csv.template')
        ->where('type', 'variants|features|compatibility');

    // Product-specific Exports
    Route::get('/products/{product}/export/variants', [\App\Http\Controllers\Admin\CSVExportController::class, 'exportVariants'])
        ->name('products.export.variants');
    Route::get('/products/{product}/export/features', [\App\Http\Controllers\Admin\CSVExportController::class, 'exportFeatures'])
        ->name('products.export.features');
    Route::get('/products/{product}/export/compatibility', [\App\Http\Controllers\Admin\CSVExportController::class, 'exportCompatibility'])
        ->name('products.export.compatibility');

    // Bulk Export (all products)
    Route::post('/csv/export/multiple', [\App\Http\Controllers\Admin\CSVExportController::class, 'exportMultipleProducts'])
        ->name('csv.export.multiple');

    // Import Preview Page
    Route::get('/csv/import/{type?}', \App\Http\Livewire\Admin\CSV\ImportPreview::class)
        ->name('csv.import')
        ->where('type', 'variants|features|compatibility');

    // ==========================================
    // EXPORTS DOWNLOAD - ETAP_07f Faza 6.2
    // ==========================================
    Route::get('/exports/download/{file}', [\App\Http\Controllers\Admin\ExportDownloadController::class, 'download'])
        ->name('exports.download');
    Route::delete('/exports/{file}', [\App\Http\Controllers\Admin\ExportDownloadController::class, 'delete'])
        ->name('exports.delete');

    // ERP Integration Management - działający komponent
    Route::get('/integrations', \App\Http\Livewire\Admin\ERP\ERPManager::class)->name('integrations');

    // ==========================================
    // SESSION MANAGEMENT - FAZA C User Management
    // ==========================================
    Route::get('/sessions', \App\Http\Livewire\Admin\Sessions::class)->name('sessions');

    // ==========================================
    // BUG REPORTS / HELPDESK SYSTEM
    // ==========================================
    Route::prefix('bug-reports')->name('bug-reports.')->group(function () {
        Route::get('/', \App\Http\Livewire\Admin\BugReports\BugReportList::class)->name('index');
        Route::get('/solutions', \App\Http\Livewire\Admin\BugReports\SolutionsLibrary::class)->name('solutions');
        Route::get('/{report}', \App\Http\Livewire\Admin\BugReports\BugReportDetail::class)->name('show');
    });

    // ==========================================
    // ETAP_05: PRODUCTS MODULE - CORE ROUTES
    // ==========================================

    // Product Management Routes
    Route::prefix('products')->name('products.')->group(function () {
        // Products list (main listing page) - Using blade wrapper for better compatibility
        Route::get('/', function () {
            return view('pages.product-list');
        })->name('index');

        // Product creation - Blade wrapper with admin layout
        Route::get('/create', function () {
            return view('pages.product-form-create');
        })->name('create');

        // Test route - Simple HTML form (diagnostic)
        Route::get('/create-test', function () {
            return view('pages.simple-product-create');
        })->name('create-test');

        // Diagnostic tool for /create issues
        Route::get('/create-diagnostic', function () {
            return view('pages.create-diagnostic');
        })->name('create-diagnostic');

        // ==========================================
        // ETAP_06: Product Import Panel
        // ==========================================
        Route::get('/import', function () {
            return view('pages.product-import');
        })->name('import');

        // Product editing - Blade wrapper with admin layout
        Route::get('/{product}/edit', function ($product) {
            $productModel = \App\Models\Product::find($product);
            return view('pages.product-form-edit', compact('product', 'productModel'));
        })->name('edit');

        // Product view (public preview) - TODO: Create ProductView component
        // Route::get('/{product}', \App\Http\Livewire\Products\Management\ProductView::class)->name('show');

        // Category Management - CategoryTree component READY + CategoryForm component
        Route::prefix('categories')->name('categories.')->group(function () {
            // Categories list - Using blade wrapper for better compatibility
            Route::get('/', function () {
                return view('pages.category-tree');
            })->name('index');

            // Simple test in categories group
            Route::get('/test', function () {
                return 'CATEGORIES GROUP TEST WORKS!';
            })->name('test');

            // Category creation - Using blade wrapper like other routes
            Route::get('/create', function () {
                return view('pages.category-form-create');
            })->name('create');

            // Category editing - Using blade wrapper for better compatibility
            Route::get('/{category}/edit', function ($category) {
                $categoryModel = \App\Models\Category::find($category);
                return view('pages.category-form-edit', compact('category', 'categoryModel'));
            })->name('edit');
        });

        // Product Types Management - ETAP_05 FAZA 4: Editable Product Types
        Route::get('/types', \App\Http\Livewire\Admin\Products\ProductTypeManager::class)->name('types.index');

        // Bulk Operations - TODO: Create BulkOperations component
        // Route::prefix('bulk')->name('bulk.')->group(function () {
        //     Route::get('/', \App\Http\Livewire\Products\Management\BulkOperations::class)->name('index');
        // });

        // Media Management - TODO: Create MediaGallery component
        // Route::prefix('media')->name('media.')->group(function () {
        //     Route::get('/', \App\Http\Livewire\Products\Media\MediaGallery::class)->name('index');
        // });
    });

    // ==========================================
    // ETAP_07f: VISUAL DESCRIPTION EDITOR
    // ==========================================

    // Visual Editor Routes - FAZA 9 Admin Panel
    // Using blade wrapper pattern for Livewire 3.x compatibility
    Route::prefix('visual-editor')->name('visual-editor.')->group(function () {
        // Block Manager - admin panel for managing blocks (FAZA 9.1)
        Route::get('/blocks', fn() => view('admin.visual-editor.blocks'))
            ->name('blocks');

        // Template Manager - admin panel for managing templates (FAZA 5)
        Route::get('/templates', fn() => view('admin.visual-editor.templates'))
            ->name('templates');

        // Styleset Editor - admin panel for CSS stylesets (FAZA 5 + 9.2)
        Route::get('/styleset', fn() => view('admin.visual-editor.styleset'))
            ->name('styleset');
        Route::get('/styleset/{shop}', fn($shop) => view('admin.visual-editor.styleset-shop', compact('shop')))
            ->name('styleset.shop');

        // Visual Description Editor - product description editor (ETAP_07f Faza 6)
        Route::get('/product/{product}/shop/{shop}', fn($product, $shop) => view('admin.visual-editor.product-editor', compact('product', 'shop')))
            ->name('product');

        // Unified Visual Editor (UVE) - ETAP_07f_P5
        Route::get('/uve/{product}/shop/{shop}', fn($product, $shop) => view('admin.visual-editor.unified-editor', compact('product', 'shop')))
            ->name('uve');
    });

    // ==========================================
    // USER MANAGEMENT - ETAP_04 FAZA A (ACTIVE)
    // ==========================================
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', \App\Http\Livewire\Admin\Users\UserList::class)->name('index');
        // UserForm używa wrapper views z powodu problemów z Livewire 3 full-page component routing
        Route::get('/create', [\App\Http\Controllers\Admin\UserFormController::class, 'create'])->name('create');
        Route::get('/{user}', \App\Http\Livewire\Admin\Users\UserDetail::class)->name('show')->whereNumber('user');
        Route::get('/{user}/edit', [\App\Http\Controllers\Admin\UserFormController::class, 'edit'])->name('edit')->whereNumber('user');
    });

    // ==========================================
    // ROLES & PERMISSIONS MANAGEMENT
    // ==========================================
    Route::get('/roles', \App\Http\Livewire\Admin\Roles\RoleList::class)->name('roles.index');
    Route::get('/permissions', \App\Http\Livewire\Admin\Permissions\PermissionMatrix::class)->name('permissions.index');

    // ==========================================
    // SECURITY DASHBOARD & AUDIT LOGS
    // ==========================================
    Route::get('/security', \App\Http\Livewire\Admin\Security\SecurityDashboard::class)->name('security.index');
    Route::get('/activity-log', \App\Http\Livewire\Admin\AuditLogs::class)->name('activity-log.index');
    // Legacy alias for backward compatibility
    Route::get('/users-legacy', function () {
        return redirect()->route('admin.users.index');
    })->name('users');

    // FAZA D: Advanced Features Routes - TODO: Upload components to server
    // Notification Center - działający komponent
    // Route::get('/notifications', \App\Http\Livewire\Admin\Notifications\NotificationCenter::class)->name('notifications');

    // Reports & Analytics - działający komponent
    // Route::get('/reports', \App\Http\Livewire\Admin\Reports\ReportsDashboard::class)->name('reports');

    // API Management - działający komponent
    // Route::get('/api', \App\Http\Livewire\Admin\Api\ApiManagement::class)->name('api');

    // System Info - placeholder route
    Route::get('/system-info', function () {
        return view('admin.system-info');
    })->name('system-info');

    // Settings alias – przekierowanie do system settings
    Route::get('/settings', function () {
        return redirect()->route('admin.system-settings.index');
    })->name('settings');

    // ==========================================
    // PLACEHOLDER PAGES - Menu v2.0 unimplemented sections
    // ==========================================

    // Panel Zarządzania Parametrami Produktu (Atrybuty, Marki, Magazyny, Typy)
    // Unified panel with tabs - replaces old /variants route
    Route::get('/product-parameters', fn() => view('admin.product-parameters'))
        ->name('product-parameters');

    // Supplier Management Panel (ETAP_15)
    Route::get('/suppliers', \App\Http\Livewire\Admin\Suppliers\BusinessPartnerPanel::class)
        ->name('suppliers.index');

    // ETAP_07g: Manufacturer Management Panel
    // Dedicated panel for manufacturer/brand management with PrestaShop sync
    Route::get('/manufacturers', fn() => view('admin.manufacturers'))
        ->name('manufacturers.index');

    // Legacy redirect (keep old route working)
    Route::get('/variants', fn() => redirect('/admin/product-parameters?tab=attributes'))
        ->name('variants.index');

    // Variant Panel Redesign - 3-Panel Layout with Product Search
    // NEW: Search products by variant attribute values (OR/AND filtering)
    // Using blade wrapper pattern for Livewire 3.x compatibility
    Route::get('/variants-panel', fn() => view('admin.variants.panel'))->name('variants.panel');

    // Vehicle Features Management (Phase 2 - ETAP_05a)
    // DEVELOPMENT: Auth disabled (consistent with other ETAP_05a routes)
    Route::get('/features/vehicles', [VehicleFeatureController::class, 'index'])
        ->name('admin.features.vehicles.index')
        ->withoutMiddleware(['auth']);

    // ETAP_05d FAZA 1: Global Compatibility Management Panel
    // DEVELOPMENT: Auth disabled (consistent with other ETAP_05a routes)
    // Using blade wrapper pattern for Livewire 3.x compatibility
    // NOTE: Inside admin prefix group, so actual path is /admin/compatibility
    Route::get('/compatibility', function () {
        return view('admin.compatibility-management');
    })->name('compatibility.index');

    // ETAP_06 FAZA 2 - Panel Importu Produktow (UKOŃCZONE)
    Route::get('/products/import', fn() => view('pages.product-import'))
        ->name('products.import');

    Route::get('/products/import-history', function () {
        return view('placeholder-page', [
            'title' => 'Historie Importów',
            'message' => 'Panel historii importów jest w trakcie implementacji. Będzie dostępny wkrótce.',
            'etap' => 'ETAP_06 (95% ukończone)'
        ]);
    })->name('products.import.history');

    // ETAP_09 (not started) - Wyszukiwarka
    Route::get('/products/search', function () {
        return view('placeholder-page', [
            'title' => 'Szybka Wyszukiwarka',
            'message' => 'Inteligentna wyszukiwarka z autosugestiami i tolerancją błędów będzie dostępna w ETAP_09.',
            'etap' => 'ETAP_09 - zaplanowane'
        ]);
    })->name('products.search');

    // ETAP_10 (not started) - Dostawy
    Route::prefix('deliveries')->name('deliveries.')->group(function () {
        Route::get('/', function () {
            return view('placeholder-page', [
                'title' => 'Lista Dostaw',
                'message' => 'System dostaw i kontenerów będzie dostępny w ETAP_10.',
                'etap' => 'ETAP_10 - zaplanowane'
            ]);
        })->name('index');

        Route::get('/containers', function () {
            return view('placeholder-page', [
                'title' => 'Kontenery',
                'message' => 'Panel zarządzania kontenerami będzie dostępny w ETAP_10.',
                'etap' => 'ETAP_10 - zaplanowane'
            ]);
        })->name('containers');

        Route::get('/receiving', function () {
            return view('placeholder-page', [
                'title' => 'Przyjęcia Magazynowe',
                'message' => 'System przyjęć magazynowych będzie dostępny w ETAP_10.',
                'etap' => 'ETAP_10 - zaplanowane'
            ]);
        })->name('receiving');

        Route::get('/documents', function () {
            return view('placeholder-page', [
                'title' => 'Dokumenty Odpraw',
                'message' => 'System dokumentów odpraw celnych będzie dostępny w ETAP_10.',
                'etap' => 'ETAP_10 - zaplanowane'
            ]);
        })->name('documents');
    });

    // FUTURE (planned) - Zamówienia
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', function () {
            return view('placeholder-page', [
                'title' => 'Lista Zamówień',
                'message' => 'System zarządzania zamówieniami będzie dostępny w przyszłej wersji aplikacji.',
                'etap' => null
            ]);
        })->name('index');

        Route::get('/reservations', function () {
            return view('placeholder-page', [
                'title' => 'Rezerwacje z Kontenera',
                'message' => 'System rezerwacji towarów z kontenera będzie dostępny wkrótce.',
                'etap' => null
            ]);
        })->name('reservations');

        Route::get('/history', function () {
            return view('placeholder-page', [
                'title' => 'Historia Zamówień',
                'message' => 'Panel historii zamówień będzie dostępny w przyszłej wersji.',
                'etap' => null
            ]);
        })->name('history');
    });

    // FUTURE (planned) - Reklamacje
    Route::prefix('claims')->name('claims.')->group(function () {
        Route::get('/', function () {
            return view('placeholder-page', [
                'title' => 'Lista Reklamacji',
                'message' => 'System zarządzania reklamacjami będzie dostępny w przyszłej wersji aplikacji.',
                'etap' => null
            ]);
        })->name('index');

        Route::get('/create', function () {
            return view('placeholder-page', [
                'title' => 'Nowa Reklamacja',
                'message' => 'Formularz zgłaszania reklamacji będzie dostępny wkrótce.',
                'etap' => null
            ]);
        })->name('create');

        Route::get('/archive', function () {
            return view('placeholder-page', [
                'title' => 'Archiwum Reklamacji',
                'message' => 'Archiwum reklamacji będzie dostępne w przyszłej wersji.',
                'etap' => null
            ]);
        })->name('archive');
    });

    // FUTURE (planned) - Raporty & Statystyki
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/products', function () {
            return view('placeholder-page', [
                'title' => 'Raporty Produktowe',
                'message' => 'System raportów produktowych będzie dostępny w przyszłej wersji.',
                'etap' => null
            ]);
        })->name('products');

        Route::get('/financial', function () {
            return view('placeholder-page', [
                'title' => 'Raporty Finansowe',
                'message' => 'System raportów finansowych będzie dostępny wkrótce.',
                'etap' => null
            ]);
        })->name('financial');

        Route::get('/warehouse', function () {
            return view('placeholder-page', [
                'title' => 'Raporty Magazynowe',
                'message' => 'System raportów magazynowych będzie dostępny w przyszłej wersji.',
                'etap' => null
            ]);
        })->name('warehouse');

        Route::get('/export', function () {
            return view('placeholder-page', [
                'title' => 'Eksport Raportów',
                'message' => 'System eksportu raportów będzie dostępny wkrótce.',
                'etap' => null
            ]);
        })->name('export');
    });

    // FUTURE (planned) - System
    Route::get('/logs', function () {
        return view('placeholder-page', [
            'title' => 'Logi Systemowe',
            'message' => 'Przeglądarka logów systemowych będzie dostępna w przyszłej wersji.',
            'etap' => null
        ]);
    })->name('logs.index');

    Route::get('/monitoring', function () {
        return view('placeholder-page', [
            'title' => 'Monitoring Systemu',
            'message' => 'Dashboard monitoringu systemu będzie dostępny wkrótce.',
            'etap' => null
        ]);
    })->name('monitoring.index');

    Route::get('/api', function () {
        return view('placeholder-page', [
            'title' => 'API Management',
            'message' => 'Panel zarządzania API będzie dostępny w przyszłej wersji.',
            'etap' => null
        ]);
    })->name('api.index');
});

// ==========================================
// LEGACY REDIRECTS (unify products under /admin)
// ==========================================

// Redirect legacy public products routes to admin module to avoid duplicates
Route::get('/products', function () {
    return redirect()->to('/admin/products');
})->name('products.index');

Route::get('/products/create', function () {
    return redirect()->to('/admin/products/create');
})->name('products.create');

Route::get('/products/{product}/edit', function ($product) {
    return redirect()->to("/admin/products/{$product}/edit");
})->name('products.edit');

// ==========================================
// TEST ROUTES
// ==========================================

// Test routes
Route::get('/test-dashboard', function () {
    return 'Dashboard test works!';
});

// UI Test page for category fixes
Route::get('/test-category-ui', function () {
    return view('pages.category-ui-test');
})->name('test.category-ui');

// Dropdown Debug page - Alpine x-teleport test
Route::get('/test-dropdown-debug', function () {
    return view('pages.dropdown-debug');
})->name('test.dropdown-debug');

// CategoryForm Debug page
Route::get('/debug-category-form', function () {
    return view('debug-category');
})->name('debug.category-form');

// Simple Test Page
Route::get('/simple-test', function () {
    return view('simple-test');
})->name('simple.test');

// Route Test Page - test what happens with admin routes
Route::get('/route-test-categories-create', function () {
    return view('route-test');
})->name('route.test.categories');

// Test admin categories create - bez middleware
Route::get('/test-admin-categories-create', function () {
    return view('test-admin-categories-create');
})->name('test.admin.categories.create');

// Debug route for products issue
Route::get('/debug-products', function () {
    try {
        $productList = new \App\Http\Livewire\Products\Listing\ProductList();
        $productList->mount();
        return 'ProductList component mount() OK';
    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' line ' . $e->getLine();
    }
});

// Debug route for ProductForm
Route::get('/debug-productform', function () {
    try {
        $productForm = new \App\Http\Livewire\Products\Management\ProductForm();
        $productForm->mount();
        return 'ProductForm component mount() OK - CREATE MODE';
    } catch (\Exception $e) {
        return 'ProductForm Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' line ' . $e->getLine();
    }
});

// Test route with /create pattern
Route::get('/test-create', \App\Http\Livewire\Products\Management\ProductForm::class)->name('test.create');

// Debug route for Livewire render
Route::get('/debug-livewire-products', function () {
    try {
        $test = app('livewire')->test(\App\Http\Livewire\Products\Listing\ProductList::class);
        return 'Livewire test OK - check if it renders: ' . $test->getViewData()['name'];
    } catch (\Exception $e) {
        return 'Livewire Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' line ' . $e->getLine();
    }
});

// Debug route for ProductForm rendering
Route::get('/debug-livewire-productform', function () {
    try {
        $test = app('livewire')->test(\App\Http\Livewire\Products\Management\ProductForm::class);
        return 'ProductForm Livewire test OK - component can be rendered';
    } catch (\Exception $e) {
        return 'ProductForm Livewire Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' line ' . $e->getLine();
    }
});

// Debug Livewire component discovery
Route::get('/debug-component-discovery', function () {
    try {
        $manager = app(\Livewire\Mechanisms\ComponentRegistry::class);
        $componentName = 'products.management.product-form';

        $output = "=== LIVEWIRE COMPONENT DISCOVERY DEBUG ===\n";
        $output .= "Checking component: $componentName\n";

        // Check if component is registered
        try {
            $class = $manager->getClass($componentName);
            $output .= "✅ Component resolved to class: $class\n";
        } catch (\Exception $e) {
            $output .= "❌ Component resolution failed: " . $e->getMessage() . "\n";
        }

        // Test direct class resolution
        $directClass = \App\Http\Livewire\Products\Management\ProductForm::class;
        $output .= "Direct class: $directClass\n";
        $output .= "Class exists: " . (class_exists($directClass) ? 'YES' : 'NO') . "\n";

        return response($output)->header('Content-Type', 'text/plain');
    } catch (\Exception $e) {
        return 'Discovery Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' line ' . $e->getLine();
    }
});

// Direct products route test
Route::get('/test-products-direct', \App\Http\Livewire\Products\Listing\ProductList::class)->name('test.products');

// Test: Swap ProductList with ProductForm in working route pattern
Route::get('/test-swap-productform', \App\Http\Livewire\Products\Management\ProductForm::class)->name('test.swap');

// Direct ProductForm route test (clone of working route above)
Route::get('/test-productform-direct', \App\Http\Livewire\Products\Management\ProductForm::class)->name('test.productform');

// Test ProductForm with simplified render
Route::get('/test-productform-simple', function () {
    try {
        $component = new \App\Http\Livewire\Products\Management\ProductForm();
        $component->mount();

        // Test if render() method works
        $view = $component->render();
        return 'ProductForm render() works - view name: ' . $view->getName();
    } catch (\Exception $e) {
        return 'ProductForm render() Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' line ' . $e->getLine();
    }
});

// 403 Forbidden page
Route::get('/forbidden', function () {
    return view('errors.403');
})->name('forbidden');

// Catch-all dla nieistniejących routes
Route::fallback(function () {
    return view('errors.404');
});
