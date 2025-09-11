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
        
        // Redirect based on user role
        if ($user->hasRole('Admin')) {
            return redirect()->intended('/admin');
        }
        
        return redirect()->intended(route('dashboard'));
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
    
    Route::put('/profile', function () {
        // Profile update logic
        return redirect()->route('profile.show');
    })->name('profile.update');
});

// ==========================================
// ADMIN ROUTES (tylko istniejące komponenty Livewire)
// ==========================================

Route::prefix('admin')->middleware(['auth'])->name('admin.')->group(function () {
    
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
    Route::get('/shops', \App\Http\Livewire\Admin\Shops\ShopManager::class)->name('shops.index');
    
    // ERP Integration Management - działający komponent
    Route::get('/integrations', \App\Http\Livewire\Admin\ERP\ERPManager::class)->name('integrations.index');
    
    // Basic admin routes (placeholders for missing components)
    Route::get('/users', function () {
        return view('admin.users.index');
    })->name('users');
    
    Route::get('/settings', function () {
        return view('admin.settings.index');
    })->name('settings');
});

// ==========================================
// TEST ROUTES
// ==========================================

// Test routes
Route::get('/test-dashboard', function () {
    return 'Dashboard test works!';
});

// 403 Forbidden page
Route::get('/forbidden', function () {
    return view('errors.403');
})->name('forbidden');

// Catch-all dla nieistniejących routes
Route::fallback(function () {
    return view('errors.404');
});