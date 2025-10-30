<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| PPM-CC-Laravel Web Routes
| FAZA A: Spatie Setup + Middleware - Route Protection Implementation
|
| Hierarchia uprawnień:
| 1. Admin - pełny dostęp
| 2. Manager - zarządzanie produktami + eksport + import  
| 3. Editor - edycja opisów/zdjęć + eksport
| 4. Warehouseman - panel dostaw
| 5. Salesperson - rezerwacje z kontenera
| 6. Claims - panel reklamacji
| 7. User - odczyt + wyszukiwarka
|
*/

// ==========================================
// PUBLIC ROUTES (no auth required)
// ==========================================

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

// Laravel built-in auth routes będą dodane w FAZA B
// Na razie basic login/logout placeholders

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', function () {
    // Authentication logic będzie w FAZA B
    return redirect()->route('dashboard');
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
    
    Route::put('/profile', function () {
        // Profile update logic
        return redirect()->route('profile.show');
    })->name('profile.update');
});

// ==========================================
// ADMIN ROUTES (/admin prefix)
// ==========================================

Route::prefix('admin')->middleware(['admin'])->name('admin.')->group(function () {
    
    // Admin Dashboard - Livewire Component
    Route::get('/', \App\Http\Livewire\Dashboard\AdminDashboard::class)->name('dashboard');
    
    // User Management
    Route::get('/users', function () {
        return view('admin.users.index');
    })->name('users');
    
    Route::get('/users/create', function () {
        return view('admin.users.create');
    })->name('users.create');
    
    Route::get('/users/{user}', function ($user) {
        return view('admin.users.show', compact('user'));
    })->name('users.show');
    
    Route::get('/users/{user}/edit', function ($user) {
        return view('admin.users.edit', compact('user'));
    })->name('users.edit');
    
    // System Settings
    Route::get('/settings', function () {
        return view('admin.settings.index');
    })->name('settings');
    
    // Audit Logs
    Route::get('/audit-logs', function () {
        return view('admin.audit-logs.index');
    })->name('audit-logs.index');
    
    // Shop Management - FAZA B Livewire Components
    Route::get('/shops', \App\Http\Livewire\Admin\Shops\ShopManager::class)->name('shops.index');
    Route::get('/shops/create', \App\Http\Livewire\Admin\Shops\ShopManager::class)->name('shops.create');
    Route::get('/shops/{shop}', \App\Http\Livewire\Admin\Shops\ShopManager::class)->name('shops.show');
    Route::get('/shops/{shop}/edit', \App\Http\Livewire\Admin\Shops\ShopManager::class)->name('shops.edit');
    
    // ERP Integration Management - FAZA B Livewire Components
    Route::get('/integrations', \App\Http\Livewire\Admin\ERP\ERPManager::class)->name('integrations');
    Route::get('/integrations/create', \App\Http\Livewire\Admin\ERP\ERPManager::class)->name('integrations.create');
    Route::get('/integrations/{connection}', \App\Http\Livewire\Admin\ERP\ERPManager::class)->name('integrations.show');
    Route::get('/integrations/{connection}/edit', \App\Http\Livewire\Admin\ERP\ERPManager::class)->name('integrations.edit');
    
    // Sync Management - FAZA B
    Route::get('/sync', function () {
        return view('admin.sync.index');
    })->name('sync.index');
    
    // Integration Logs - FAZA B
    Route::get('/integration-logs', function () {
        return view('admin.integration-logs.index');
    })->name('integration-logs.index');
});

// ==========================================
// MANAGER ROUTES (/manager prefix)
// ==========================================

Route::prefix('manager')->middleware(['manager'])->name('manager.')->group(function () {
    
    // Manager Dashboard
    Route::get('/', function () {
        return view('manager.dashboard');
    })->name('dashboard');
    
    // Products Management
    Route::get('/products', function () {
        return view('manager.products.index');
    })->name('products.index');
    
    Route::get('/products/create', function () {
        return view('manager.products.create');
    })->name('products.create');
    
    Route::get('/products/{product}', function ($product) {
        return view('manager.products.show', compact('product'));
    })->name('products.show');
    
    Route::get('/products/{product}/edit', function ($product) {
        return view('manager.products.edit', compact('product'));
    })->name('products.edit');
    
    // Categories Management
    Route::get('/categories', function () {
        return view('manager.categories.index');
    })->name('categories.index');
    
    // Import/Export
    Route::get('/import', function () {
        return view('manager.import.index');
    })->name('import.index');
    
    Route::get('/export', function () {
        return view('manager.export.index');
    })->name('export.index');
    
    // Integrations Control
    Route::get('/integrations', function () {
        return view('manager.integrations.index');
    })->name('integrations.index');
});

// ==========================================
// EDITOR ROUTES (specific permissions)
// ==========================================

Route::prefix('editor')->middleware(['editor'])->name('editor.')->group(function () {
    
    // Editor Dashboard
    Route::get('/', function () {
        return view('editor.dashboard');
    })->name('dashboard');
    
    // Product Content Editing (no delete)
    Route::get('/products', function () {
        return view('editor.products.index');
    })->name('products.index');
    
    Route::get('/products/{product}/edit', function ($product) {
        return view('editor.products.edit', compact('product'));
    })->name('products.edit');
    
    // Media Management
    Route::get('/media', function () {
        return view('editor.media.index');
    })->name('media.index');
    
    // Export only
    Route::get('/export', function () {
        return view('editor.export.index');
    })->name('export.index');
});

// ==========================================
// ROLE-SPECIFIC ROUTES
// ==========================================

// Warehouseman - panel dostaw
Route::prefix('warehouse')->middleware(['role:Admin,Manager,Warehouseman'])->name('warehouse.')->group(function () {
    Route::get('/', function () {
        return view('warehouse.dashboard');
    })->name('dashboard');
    
    Route::get('/deliveries', function () {
        return view('warehouse.deliveries.index');
    })->name('deliveries.index');
});

// Salesperson - rezerwacje z kontenera  
Route::prefix('sales')->middleware(['role:Admin,Manager,Salesperson'])->name('sales.')->group(function () {
    Route::get('/', function () {
        return view('sales.dashboard');
    })->name('dashboard');
    
    Route::get('/reservations', function () {
        return view('sales.reservations.index');
    })->name('reservations.index');
});

// Claims - panel reklamacji
Route::prefix('claims')->middleware(['role:Admin,Manager,Claims'])->name('claims.')->group(function () {
    Route::get('/', function () {
        return view('claims.dashboard');
    })->name('dashboard');
    
    Route::get('/complaints', function () {
        return view('claims.complaints.index');
    })->name('complaints.index');
});

// ==========================================
// PUBLIC SEARCH & CATALOG (all authenticated users)
// ==========================================

Route::middleware(['auth'])->group(function () {
    
    // Product Search & Catalog
    Route::get('/products', function () {
        return view('products.index');
    })->name('products.index');
    
    Route::get('/products/{sku}', function ($sku) {
        return view('products.show', compact('sku'));
    })->name('products.show');
    
    // Search functionality
    Route::get('/search', function () {
        return view('search.index');
    })->name('search.index');
    
    Route::post('/search', function () {
        return view('search.results');
    })->name('search.results');
    
    // Categories browse
    Route::get('/categories', function () {
        return view('categories.index');
    })->name('categories.index');
    
    Route::get('/categories/{category}', function ($category) {
        return view('categories.show', compact('category'));
    })->name('categories.show');
});

// ==========================================
// PERMISSION-BASED ROUTES (specific actions)
// ==========================================

// Product management z specific permissions
Route::middleware(['permission:products.create'])->group(function () {
    Route::post('/products', function () {
        // Create product logic
        return redirect()->route('manager.products.index');
    })->name('products.store');
});

Route::middleware(['permission:products.edit'])->group(function () {
    Route::put('/products/{product}', function ($product) {
        // Update product logic
        return redirect()->route('products.show', $product);
    })->name('products.update');
});

Route::middleware(['permission:products.delete'])->group(function () {
    Route::delete('/products/{product}', function ($product) {
        // Delete product logic (only Admin/Manager)
        return redirect()->route('manager.products.index');
    })->name('products.destroy');
});

// Import/Export specific permissions
Route::middleware(['permission:import.xlsx'])->group(function () {
    Route::post('/import/xlsx', function () {
        // XLSX import logic
        return redirect()->route('manager.import.index');
    })->name('import.xlsx');
});

Route::middleware(['permission:export.all'])->group(function () {
    Route::get('/export/{format}', function ($format) {
        // Export logic
        return response()->download('export.xlsx');
    })->name('export.download');
});

// ==========================================
// FALLBACK & ERROR ROUTES
// ==========================================

// 403 Forbidden page
Route::get('/forbidden', function () {
    return view('errors.403');
})->name('forbidden');

// ==========================================
// OAUTH2 ROUTES INCLUSION
// ==========================================

// Include OAuth2 routes from separate file (disabled until controllers are implemented)
// require __DIR__.'/oauth.php';

// Test route
Route::get('/test-dashboard', function () {
    return 'Dashboard test works!';
});

// Test admin dashboard without middleware
Route::get('/test-admin', function () {
    return view('admin-dashboard-test');
});

// FAZA B TEST ROUTES (temporary for deployment verification)
Route::get('/test-faza-b', function () {
    return response()->json(['message' => 'FAZA B components uploaded successfully!', 'timestamp' => now()]);
})->name('test.faza-b');

Route::get('/test-shops', function () {
    try {
        $component = new \App\Http\Livewire\Admin\Shops\ShopManager();
        return response()->json(['status' => 'success', 'message' => 'ShopManager component loaded successfully!']);
    } catch (Exception $e) {
        return response()->json(['status' => 'error', 'message' => 'Error loading ShopManager: ' . $e->getMessage()]);
    }
})->name('test.shops');

Route::get('/test-erp', function () {
    try {
        $component = new \App\Http\Livewire\Admin\ERP\ERPManager();
        return response()->json(['status' => 'success', 'message' => 'ERPManager component loaded successfully!']);
    } catch (Exception $e) {
        return response()->json(['status' => 'error', 'message' => 'Error loading ERPManager: ' . $e->getMessage()]);
    }
})->name('test.erp');

Route::get('/test-tables', function () {
    try {
        $pdo = DB::connection()->getPdo();
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return response()->json(['status' => 'success', 'tables' => $tables]);
    } catch (Exception $e) {
        return response()->json(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
})->name('test.tables');

// Catch-all dla nieistniejących routes
Route::fallback(function () {
    return view('errors.404');
});