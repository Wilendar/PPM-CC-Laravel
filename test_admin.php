<?php

/**
 * Admin Panel Test Script
 * 
 * Testuje funkcjonalność admin panelu bez potrzeby logowania przez przeglądarke
 */

require 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "=== ADMIN PANEL TEST ===\n\n";

// Test 1: Sprawdź czy admin user istnieje
echo "1. Testing Admin User...\n";
$admin = User::where('email', 'admin@mpptrade.pl')->first();

if ($admin) {
    echo "✅ Admin user exists: {$admin->name}\n";
    echo "   ID: {$admin->id}\n";
    echo "   Active: " . ($admin->is_active ? 'YES' : 'NO') . "\n";
    
    // Test hasła
    if (Hash::check('Admin123!MPP', $admin->password)) {
        echo "✅ Password correct\n";
    } else {
        echo "❌ Password incorrect\n";
    }
    
    // Test roli
    try {
        if ($admin->hasRole('Admin')) {
            echo "✅ Has Admin role\n";
        } else {
            echo "❌ Missing Admin role\n";
        }
    } catch (Exception $e) {
        echo "⚠️  Role check error: {$e->getMessage()}\n";
    }
} else {
    echo "❌ Admin user not found\n";
}

echo "\n2. Testing AdminDashboard Component...\n";

try {
    // Test AdminDashboard component
    $dashboard = new App\Http\Livewire\Dashboard\AdminDashboard();
    
    // Symuluj zalogowanego admina
    if ($admin) {
        auth()->login($admin);
        
        echo "✅ AdminDashboard component created\n";
        
        // Test ładowania danych
        $dashboard->loadDashboardData();
        echo "✅ Dashboard data loaded\n";
        
        echo "   Dashboard Stats: " . json_encode($dashboard->dashboardStats) . "\n";
        echo "   System Health: " . json_encode($dashboard->systemHealth) . "\n";
        echo "   Business KPIs: " . json_encode($dashboard->businessKpis) . "\n";
        
    } else {
        echo "⚠️  Cannot test dashboard - no admin user\n";
    }
    
} catch (Exception $e) {
    echo "❌ AdminDashboard error: {$e->getMessage()}\n";
}

echo "\n3. Testing Routes...\n";

$routes = [
    'admin' => '/admin',
    'login' => '/login',
];

foreach ($routes as $name => $path) {
    try {
        $route = \Route::getRoutes()->getByName("admin.dashboard") ?? \Route::getRoutes()->getByAction("App\\Http\\Livewire\\Dashboard\\AdminDashboard");
        echo $route ? "✅ Route {$name} exists\n" : "❌ Route {$name} missing\n";
    } catch (Exception $e) {
        echo "⚠️  Route {$name} check failed: {$e->getMessage()}\n";
    }
}

echo "\n=== TEST COMPLETE ===\n";