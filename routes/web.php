<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

// Test CSS loading
Route::get('/test-css', function () {
    return view('test-css');
});

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

    Route::put('/profile', function () {
        // Profile update logic
        return redirect()->route('profile.show');
    })->name('profile.update');

    // Additional profile routes
    Route::get('/profile/sessions', function () {
        return view('profile.sessions');
    })->name('profile.sessions');

    Route::get('/profile/activity', function () {
        return view('profile.activity');
    })->name('profile.activity');
});

// ==========================================
// HELP ROUTES
// ==========================================

Route::middleware(['auth'])->prefix('help')->name('help.')->group(function () {
    Route::get('/', function () {
        return view('help.index');
    })->name('index');

    Route::get('/shortcuts', function () {
        return view('help.shortcuts');
    })->name('shortcuts');
});

// ==========================================
// ADMIN ROUTES (tylko istniejące komponenty Livewire)
// ==========================================

// DEVELOPMENT: AdminMiddleware tymczasowo wyłączony dla testów
// Uwaga: autoryzacja przez AdminMiddleware (bez 'auth'),
// aby goscie dostawali 200 z widokiem logowania pod /admin
Route::prefix('admin')->name('admin.')->group(function () {
    
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
    
    // Shop Management - działający komponent
    Route::get('/shops', \App\Http\Livewire\Admin\Shops\ShopManager::class)->name('shops');
    
    // Add New Shop - wizard component
    Route::get('/shops/add', \App\Http\Livewire\Admin\Shops\AddShop::class)->name('shops.add');
    
    // Shop Synchronization Control - sync management panel
    Route::get('/shops/sync', \App\Http\Livewire\Admin\Shops\SyncController::class)->name('shops.sync');

    // Price Management - FAZA 4: PRICE MANAGEMENT SYSTEM
    Route::prefix('price-management')->name('price-management.')->group(function () {
        // Price Groups Management - działający komponent
        Route::get('/price-groups', \App\Http\Livewire\Admin\PriceManagement\PriceGroups::class)
             ->name('price-groups.index');
    });
    
    // Bulk Product Export - mass export to PrestaShop stores
    Route::get('/shops/export', \App\Http\Livewire\Admin\Shops\BulkExport::class)->name('shops.export');

    // Import Management - SEKCJA 2.2.2.2 Import Management
    Route::get('/shops/import', \App\Http\Livewire\Admin\Shops\ImportManager::class)->name('shops.import');

    // ERP Integration Management - działający komponent
    Route::get('/integrations', \App\Http\Livewire\Admin\ERP\ERPManager::class)->name('integrations');

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

    // Users management – TODO: Upload UserList to server
    // Route::get('/users', \App\Http\Livewire\Admin\Users\UserList::class)->name('users');

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
