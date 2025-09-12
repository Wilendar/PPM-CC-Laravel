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
| PPM-CC-Laravel Web Routes - Clean Version for New Admin Panel
| Only basic routes, admin panel routes will be built from scratch
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
    
    Route::put('/profile', function () {
        // Profile update logic
        return redirect()->route('profile.show');
    })->name('profile.update');
});

// ==========================================
// ADMIN ROUTES - ETAP_04 FAZA A: Dashboard Core & Monitoring
// ==========================================
// TEMPORARY: Middleware disabled for development testing

Route::prefix('admin')->name('admin.')->group(function () {
    
    // Admin Dashboard - FAZA A: Dashboard Core & Monitoring
    Route::get('/', \App\Http\Livewire\Dashboard\AdminDashboard::class)->name('dashboard');
    
    // TEMPORARY: Dummy routes to prevent 500 errors from admin layout navigation
    Route::get('/users', function () { return 'Users - Coming soon'; })->name('users');
    Route::get('/integrations', function () { return 'Integrations - Coming soon'; })->name('integrations'); 
    Route::get('/settings', function () { return 'Settings - Coming soon'; })->name('settings');
    Route::get('/shops', function () { return 'Shops - Coming soon'; })->name('shops');
    Route::get('/system-settings', function () { return 'System Settings - Coming soon'; })->name('system-settings.index');
    Route::get('/backup', function () { return 'Backup - Coming soon'; })->name('backup.index');
    Route::get('/maintenance', function () { return 'Maintenance - Coming soon'; })->name('maintenance.index');
    
});

// ==========================================
// TEST ROUTES
// ==========================================

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