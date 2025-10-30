<?php

use Illuminate\Support\Facades\Route;

// Test route without middleware for FAZA B verification
Route::get('/test-faza-b', function () {
    return view('welcome', ['message' => 'FAZA B components uploaded successfully!']);
})->name('test.faza-b');

// Test Livewire ShopManager without middleware
Route::get('/test-shops', function () {
    try {
        $component = new \App\Http\Livewire\Admin\Shops\ShopManager();
        return 'ShopManager component loaded successfully!';
    } catch (Exception $e) {
        return 'Error loading ShopManager: ' . $e->getMessage();
    }
})->name('test.shops');

// Test Livewire ERPManager without middleware  
Route::get('/test-erp', function () {
    try {
        $component = new \App\Http\Livewire\Admin\ERP\ERPManager();
        return 'ERPManager component loaded successfully!';
    } catch (Exception $e) {
        return 'Error loading ERPManager: ' . $e->getMessage();
    }
})->name('test.erp');

// Test database tables
Route::get('/test-tables', function () {
    try {
        $pdo = DB::connection()->getPdo();
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return 'Database tables: ' . implode(', ', $tables);
    } catch (Exception $e) {
        return 'Database error: ' . $e->getMessage();
    }
})->name('test.tables');