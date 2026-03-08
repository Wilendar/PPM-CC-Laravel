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
    ->where('mediaId', '[0-9]+')
    ->middleware('throttle:60,1');

// Variant Image thumbnail (on-demand, cached)
Route::get('/thumbnail/variant/{variantImageId}', [ThumbnailController::class, 'showVariant'])
    ->name('thumbnail.variant')
    ->where('variantImageId', '[0-9]+')
    ->middleware('throttle:60,1');

// Public Feed Access (token-based, no auth)
Route::prefix('feed')->group(function () {
    Route::get('/{token}', [\App\Http\Controllers\FeedController::class, 'show'])
        ->name('feed.show')
        ->middleware('throttle:60,1');
    Route::get('/{token}/download', [\App\Http\Controllers\FeedController::class, 'download'])
        ->name('feed.download')
        ->middleware('throttle:30,1');
});

Route::get('/', function () {
    if (Auth::check()) {
        return redirect('/admin');
    }
    return view('welcome');
})->name('home');

// Health check for hosting
Route::get('/up', function () {
    return response()->json(['status' => 'ok', 'timestamp' => now()]);
})->name('health');

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
        $user = Auth::user();

        // Sprawdz czy user ma wymuszone logowanie Microsoft
        if ($user->microsoft_only) {
            Auth::logout();
            $request->session()->invalidate();
            return back()->withErrors([
                'email' => 'Logowanie dla tego konta jest dostepne wylacznie przez Microsoft. Uzyj przycisku "Zaloguj przez Microsoft".'
            ])->withInput($request->only('email'));
        }

        $request->session()->regenerate();

        Log::info('Successful login for user: ' . $user->email);

        // Wszystkie zalogowane role trafiają na /admin
        return redirect()->intended('/admin');
    }
    
    return back()->withErrors([
        'email' => 'Podane dane logowania są nieprawidłowe.'
    ])->withInput($request->only('email'));
})->name('login.store')->middleware('throttle:5,1');

// OAuth approval pending page
Route::get('/approval/pending', function () {
    return view('auth.approval-pending');
})->name('approval.pending')->middleware('auth');

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect()->route('login');
})->name('logout');

Route::get('/logout', function () {
    return redirect()->route('login');
})->name('logout.redirect');

// Microsoft OAuth Routes
Route::prefix('auth/microsoft')->name('auth.microsoft.')->group(function () {
    Route::get('/redirect', [\App\Http\Controllers\Auth\MicrosoftAuthController::class, 'redirect'])
        ->name('redirect');
    Route::get('/callback', [\App\Http\Controllers\Auth\MicrosoftAuthController::class, 'callback'])
        ->name('callback');
});

// ==========================================
// AUTHENTICATED ROUTES
// ==========================================

Route::middleware(['auth'])->group(function () {
    
    // Dashboard - redirect do admin panel
    Route::get('/dashboard', function () {
        return redirect('/admin');
    })->name('dashboard');
    
    // Profile management
    Route::get('/profile', function () {
        return view('profile.show');
    })->name('profile.show');

    Route::get('/profile/edit', \App\Http\Livewire\Profile\EditProfile::class)
        ->name('profile.edit');

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

        // Only Admin can change email
        if ($user->hasRole('Admin') && isset($validated['email'])) {
            $user->email = $validated['email'];
        }

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
    })->name('profile.update')->middleware('throttle:5,1');

    Route::delete('/profile', function (\Illuminate\Http\Request $request) {
        $user = auth()->user();
        auth()->logout();
        $user->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'Konto zostalo usuniete.');
    })->name('profile.destroy');

    // Active Sessions Management
    Route::get('/profile/sessions', \App\Http\Livewire\Profile\UserSessions::class)
        ->name('profile.sessions');

    // Activity History
    Route::get('/profile/activity', \App\Http\Livewire\Profile\ActivityHistory::class)
        ->name('profile.activity');

    // Notification Preferences
    Route::get('/profile/notifications', \App\Http\Livewire\Profile\NotificationPreferences::class)
        ->name('profile.notifications');

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

// Admin routes - full security middleware stack
// DevAuthBypass middleware (in bootstrap/app.php) handles auto-login only in APP_ENV=local
// Auth + security middleware - autoryzacja per-zasób via $this->authorize() w komponentach
// Wszystkie role mogą wejść do /admin/*, ale każdy komponent sprawdza uprawnienia
$adminMiddleware = ['auth', 'check_account_lock', 'force_password_change', 'check.blocked.ip'];

Route::prefix('admin')->name('admin.')->middleware($adminMiddleware)->group(function () {
    
    // Admin Dashboard - działający komponent Livewire
    Route::get('/', \App\Http\Livewire\Dashboard\AdminDashboard::class)->name('dashboard')->middleware('permission:dashboard.read');
    
    // System Settings - działający komponent
    Route::get('/system-settings', \App\Http\Livewire\Admin\Settings\SystemSettings::class)
         ->name('system-settings.index')->middleware('permission:system.manage');
    
    // Backup Management - działający komponent
    Route::prefix('backup')->name('backup.')->middleware('permission:backup.manage')->group(function () {
        Route::get('/', \App\Http\Livewire\Admin\Backup\BackupManager::class)->name('index');
    });
    
    // Maintenance Management - działający komponent
    Route::get('/maintenance', \App\Http\Livewire\Admin\Maintenance\DatabaseMaintenance::class)
         ->name('maintenance.index')->middleware('permission:maintenance.manage');

    // Media Management - ETAP_07d Phase 8: Admin Media Panel
    Route::get('/media', \App\Http\Livewire\Admin\Media\MediaManager::class)
         ->name('media.index')->middleware('permission:media.read');

    // Shop Management - działający komponent
    Route::get('/shops', \App\Http\Livewire\Admin\Shops\ShopManager::class)->name('shops')->middleware('permission:shops.read');
    
    // Add New Shop - wizard component
    Route::get('/shops/add', \App\Http\Livewire\Admin\Shops\AddShop::class)->name('shops.add')->middleware('permission:shops.create');
    
    // Shop CSS Editor - ETAP_07h FAZA 8: Direct CSS editing
    Route::get('/shops/{shopId}/css-editor', \App\Http\Livewire\Admin\Shops\ShopCssEditor::class)->name('shops.css-editor')->middleware('permission:shops.css_edit');

    // Shop Synchronization Control - sync management panel
    Route::get('/shops/sync', \App\Http\Livewire\Admin\Shops\SyncController::class)->name('shops.sync')->middleware('permission:shops.sync');

    // Price Management - FAZA 4: PRICE MANAGEMENT SYSTEM
    Route::prefix('price-management')->name('price-management.')->group(function () {
        // Price Groups Management - działający komponent
        Route::get('/price-groups', \App\Http\Livewire\Admin\PriceManagement\PriceGroups::class)
             ->name('price-groups.index')->middleware('permission:price_groups.read');

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
    Route::get('/shops/export', \App\Http\Livewire\Admin\Shops\BulkExport::class)->name('shops.export')->middleware('permission:shops.export');

    // Import Management - SEKCJA 2.2.2.2 Import Management
    Route::get('/shops/import', \App\Http\Livewire\Admin\Shops\ImportManager::class)->name('shops.import')->middleware('permission:shops.import');

    // ==========================================
    // CSV IMPORT/EXPORT SYSTEM - FAZA 6
    // ==========================================

    // CSV Template Downloads
    Route::get('/csv/templates/{type}', [\App\Http\Controllers\Admin\CSVExportController::class, 'downloadTemplate'])
        ->name('csv.template')
        ->where('type', 'variants|features|compatibility')
        ->middleware('permission:products.export');

    // Product-specific Exports
    Route::get('/products/{product}/export/variants', [\App\Http\Controllers\Admin\CSVExportController::class, 'exportVariants'])
        ->name('products.export.variants')->middleware('permission:products.export');
    Route::get('/products/{product}/export/features', [\App\Http\Controllers\Admin\CSVExportController::class, 'exportFeatures'])
        ->name('products.export.features')->middleware('permission:products.export');
    Route::get('/products/{product}/export/compatibility', [\App\Http\Controllers\Admin\CSVExportController::class, 'exportCompatibility'])
        ->name('products.export.compatibility')->middleware('permission:products.export');

    // Bulk Export (all products)
    Route::post('/csv/export/multiple', [\App\Http\Controllers\Admin\CSVExportController::class, 'exportMultipleProducts'])
        ->name('csv.export.multiple')->middleware('permission:products.export');

    // Import Preview Page
    Route::get('/csv/import/{type?}', \App\Http\Livewire\Admin\CSV\ImportPreview::class)
        ->name('csv.import')
        ->where('type', 'variants|features|compatibility')
        ->middleware('permission:products.import');

    // ==========================================
    // EXPORTS DOWNLOAD - ETAP_07f Faza 6.2
    // ==========================================
    Route::get('/exports/download/{file}', [\App\Http\Controllers\Admin\ExportDownloadController::class, 'download'])
        ->name('exports.download')->middleware('permission:products.export');
    Route::delete('/exports/{file}', [\App\Http\Controllers\Admin\ExportDownloadController::class, 'delete'])
        ->name('exports.delete')->middleware('permission:products.export');

    // ==========================================
    // EXPORT PROFILES - Feed & Export Management
    // ==========================================
    Route::prefix('export')->name('export.')->group(function () {
        Route::get('/', \App\Http\Livewire\Admin\Export\ExportManager::class)->name('index');
        Route::get('/create', \App\Http\Livewire\Admin\Export\ExportProfileForm::class)->name('create');
        Route::get('/{profile}/edit', \App\Http\Livewire\Admin\Export\ExportProfileForm::class)->name('edit');
    });

    // ERP Integration Management - działający komponent
    Route::get('/integrations', \App\Http\Livewire\Admin\ERP\ERPManager::class)->name('integrations')->middleware('permission:integrations.read');

    // ==========================================
    // PRODUCT SCAN SYSTEM - ETAP_10
    // ==========================================

    // Cross-Source Matrix Panel (replaces old ScanProductsPanel)
    Route::get('/scan-products', \App\Http\Livewire\Admin\Scan\CrossSourceMatrixPanel::class)
        ->name('scan-products')->middleware('permission:scan.read');

    // Legacy Scan Panel (backup)
    Route::get('/scan-products/legacy', \App\Http\Livewire\Admin\Scan\ScanProductsPanel::class)
        ->name('scan-products.legacy')->middleware('permission:scan.read');

    // ==========================================
    // SESSION MANAGEMENT - FAZA C User Management
    // ==========================================
    Route::get('/sessions', \App\Http\Livewire\Admin\Sessions::class)->name('sessions')->middleware('permission:sessions.read');

    // ==========================================
    // BUG REPORTS / HELPDESK SYSTEM
    // ==========================================
    Route::prefix('bug-reports')->name('bug-reports.')->middleware('permission:bug-reports.read')->group(function () {
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
        })->name('index')->middleware('permission:products.read');

        // Product creation - Blade wrapper with admin layout
        Route::get('/create', function () {
            return view('pages.product-form-create');
        })->name('create')->middleware('permission:products.create');

        // ==========================================
        // ETAP_06: Product Import Panel
        // ==========================================
        Route::get('/import', function () {
            return view('pages.product-import');
        })->name('import')->middleware('permission:products.import');

        // Product editing - Blade wrapper with admin layout
        Route::get('/{product}/edit', function ($product) {
            $productModel = \App\Models\Product::find($product);
            return view('pages.product-form-edit', compact('product', 'productModel'));
        })->name('edit')->middleware('permission:products.read');

        // Product view (public preview) - TODO: Create ProductView component
        // Route::get('/{product}', \App\Http\Livewire\Products\Management\ProductView::class)->name('show');

        // Category Management - CategoryTree component READY + CategoryForm component
        Route::prefix('categories')->name('categories.')->group(function () {
            // Categories list - Using blade wrapper for better compatibility
            Route::get('/', function () {
                return view('pages.category-tree');
            })->name('index')->middleware('permission:categories.read');

            // Category creation - Using blade wrapper like other routes
            Route::get('/create', function () {
                return view('pages.category-form-create');
            })->name('create')->middleware('permission:categories.create');

            // Category editing - Using blade wrapper for better compatibility
            Route::get('/{category}/edit', function ($category) {
                $categoryModel = \App\Models\Category::find($category);
                return view('pages.category-form-edit', compact('category', 'categoryModel'));
            })->name('edit')->middleware('permission:categories.read');
        });

        // Product Types Management - ETAP_05 FAZA 4: Editable Product Types
        Route::get('/types', \App\Http\Livewire\Admin\Products\ProductTypeManager::class)->name('types.index')->middleware('permission:parameters.read');

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
    Route::prefix('visual-editor')->name('visual-editor.')->middleware('permission:visual-editor.read')->group(function () {
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
    Route::prefix('users')->name('users.')->middleware('permission:users.read')->group(function () {
        Route::get('/', \App\Http\Livewire\Admin\Users\UserList::class)->name('index');
        // UserForm używa wrapper views z powodu problemów z Livewire 3 full-page component routing
        Route::get('/create', [\App\Http\Controllers\Admin\UserFormController::class, 'create'])->name('create')->middleware('permission:users.create');
        Route::get('/{user}', \App\Http\Livewire\Admin\Users\UserDetail::class)->name('show')->whereNumber('user');
        Route::get('/{user}/edit', [\App\Http\Controllers\Admin\UserFormController::class, 'edit'])->name('edit')->whereNumber('user')->middleware('permission:users.update');
    });

    // ==========================================
    // ROLES & PERMISSIONS MANAGEMENT
    // ==========================================
    Route::get('/roles', \App\Http\Livewire\Admin\Roles\RoleList::class)->name('roles.index')->middleware('permission:users.roles');
    Route::get('/permissions', \App\Http\Livewire\Admin\Permissions\PermissionMatrix::class)->name('permissions.index')->middleware('permission:users.roles');

    // ==========================================
    // SECURITY DASHBOARD & AUDIT LOGS
    // ==========================================
    Route::get('/security', \App\Http\Livewire\Admin\Security\SecurityDashboard::class)->name('security.index')->middleware('permission:system.manage');
    Route::get('/activity-log', \App\Http\Livewire\Admin\AuditLogs::class)->name('activity-log.index')->middleware('permission:audit.read');
    // Legacy alias for backward compatibility
    Route::get('/users-legacy', function () {
        return redirect()->route('admin.users.index');
    })->name('users');

    // FAZA D: Advanced Features Routes - TODO: Upload components to server
    // Notification Center - działający komponent
    // Route::get('/notifications', \App\Http\Livewire\Admin\Notifications\NotificationCenter::class)->name('notifications');

    // Reports & Analytics - działający komponent
    Route::get('/reports', \App\Http\Livewire\Admin\Reports\ReportsDashboard::class)->name('reports')->middleware('permission:reports.read');

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
        ->name('product-parameters')->middleware('permission:parameters.read');

    // Supplier Management Panel (ETAP_15)
    Route::get('/suppliers', \App\Http\Livewire\Admin\Suppliers\BusinessPartnerPanel::class)
        ->name('suppliers.index')->middleware('permission:suppliers.read');

    // ETAP_07g: Manufacturer Management Panel
    // Dedicated panel for manufacturer/brand management with PrestaShop sync
    Route::get('/manufacturers', fn() => view('admin.manufacturers'))
        ->name('manufacturers.index')->middleware('permission:parameters.read');

    // Legacy redirect (keep old route working)
    Route::get('/variants', fn() => redirect('/admin/product-parameters?tab=attributes'))
        ->name('variants.index');

    // Variant Panel Redesign - 3-Panel Layout with Product Search
    // NEW: Search products by variant attribute values (OR/AND filtering)
    // Using blade wrapper pattern for Livewire 3.x compatibility
    Route::get('/variants-panel', fn() => view('admin.variants.panel'))->name('variants.panel')->middleware('permission:parameters.read');

    // Vehicle Features Management (Phase 2 - ETAP_05a)
    Route::get('/features/vehicles', [VehicleFeatureController::class, 'index'])
        ->name('admin.features.vehicles.index')->middleware('permission:vehicle_features.browser.read');

    // ETAP_05d FAZA 1: Global Compatibility Management Panel
    // DEVELOPMENT: Auth disabled (consistent with other ETAP_05a routes)
    // Using blade wrapper pattern for Livewire 3.x compatibility
    // NOTE: Inside admin prefix group, so actual path is /admin/compatibility
    Route::get('/compatibility', function () {
        return view('admin.compatibility-management');
    })->name('compatibility.index')->middleware('permission:compatibility.read');

    // ETAP_06 FAZA 2 - Panel Importu Produktow (UKOŃCZONE)
    Route::get('/products/import', fn() => view('pages.product-import'))
        ->name('products.import')->middleware('permission:import.read');

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
    Route::prefix('deliveries')->name('deliveries.')->middleware('permission:deliveries.read')->group(function () {
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
    Route::prefix('orders')->name('orders.')->middleware('permission:orders.read')->group(function () {
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
    Route::prefix('claims')->name('claims.')->middleware('permission:claims.read')->group(function () {
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
    Route::prefix('reports')->name('reports.')->middleware('permission:reports.read')->group(function () {
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

// 403 Forbidden page
Route::get('/forbidden', function () {
    return view('errors.403');
})->name('forbidden');

// Catch-all dla nieistniejących routes
Route::fallback(function () {
    return view('errors.404');
});
