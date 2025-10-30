<?php

// FAZA B Routes - Shop & ERP Management
// To be added to existing routes/web.php

// Shop Management Routes (within admin prefix)
Route::prefix('admin')->middleware(['admin'])->name('admin.')->group(function () {
    
    // Shop Management - Livewire Components
    Route::get('/shops', \App\Http\Livewire\Admin\Shops\ShopManager::class)->name('shops');
    Route::get('/shops/create', \App\Http\Livewire\Admin\Shops\ShopManager::class)->name('shops.create');
    Route::get('/shops/{shop}', \App\Http\Livewire\Admin\Shops\ShopManager::class)->name('shops.show');
    Route::get('/shops/{shop}/edit', \App\Http\Livewire\Admin\Shops\ShopManager::class)->name('shops.edit');
    
    // ERP Integration Management - Livewire Components
    Route::get('/integrations', \App\Http\Livewire\Admin\ERP\ERPManager::class)->name('integrations.index');
    Route::get('/integrations/create', \App\Http\Livewire\Admin\ERP\ERPManager::class)->name('integrations.create');
    Route::get('/integrations/{connection}', \App\Http\Livewire\Admin\ERP\ERPManager::class)->name('integrations.show');
    Route::get('/integrations/{connection}/edit', \App\Http\Livewire\Admin\ERP\ERPManager::class)->name('integrations.edit');
    
    // Sync Management
    Route::get('/sync', function () {
        return view('admin.sync.index');
    })->name('sync.index');
    
    // Integration Logs
    Route::get('/integration-logs', function () {
        return view('admin.integration-logs.index');
    })->name('integration-logs.index');
    
});