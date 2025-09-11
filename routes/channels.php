<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| PPM-CC-Laravel Broadcasting Channels
| FAZA A: Spatie Setup + Middleware - Broadcasting foundation
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// ==========================================
// USER PRESENCE CHANNELS
// ==========================================

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// ==========================================
// ROLE-BASED CHANNELS
// ==========================================

// Admin-only channel dla system notifications
Broadcast::channel('admin-notifications', function ($user) {
    return $user->hasRole('Admin');
});

// Manager+ channel dla product updates  
Broadcast::channel('manager-updates', function ($user) {
    return $user->hasAnyRole(['Admin', 'Manager']);
});

// ==========================================
// PRODUCT-RELATED CHANNELS  
// ==========================================

// Product updates channel (Editor+)
Broadcast::channel('product-updates', function ($user) {
    return $user->hasAnyRole(['Admin', 'Manager', 'Editor']);
});

// Import/Export progress channel (per user)
Broadcast::channel('import-export.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId && 
           $user->hasPermissionTo('import.xlsx') || $user->hasPermissionTo('export.all');
});

// ==========================================
// SYNC STATUS CHANNELS
// ==========================================

// Prestashop sync status (Manager+)
Broadcast::channel('prestashop-sync', function ($user) {
    return $user->hasAnyRole(['Admin', 'Manager']);
});

// ERP sync status (Manager+)
Broadcast::channel('erp-sync', function ($user) {
    return $user->hasAnyRole(['Admin', 'Manager']);
});

// ==========================================
// NOTIFICATION CHANNELS
// ==========================================

// General notifications per role
Broadcast::channel('notifications.{role}', function ($user, $role) {
    return $user->hasRole(ucfirst($role));
});

// Stock alerts (Warehouseman+)
Broadcast::channel('stock-alerts', function ($user) {
    return $user->hasAnyRole(['Admin', 'Manager', 'Warehouseman']);
});

// Delivery notifications (Warehouseman+)
Broadcast::channel('delivery-notifications', function ($user) {
    return $user->hasAnyRole(['Admin', 'Manager', 'Warehouseman']);
});

// Claims notifications (Claims+)
Broadcast::channel('claims-notifications', function ($user) {
    return $user->hasAnyRole(['Admin', 'Manager', 'Claims']);
});

// Sales notifications (Salesperson+)
Broadcast::channel('sales-notifications', function ($user) {
    return $user->hasAnyRole(['Admin', 'Manager', 'Salesperson']);
});

// ==========================================
// SYSTEM STATUS CHANNELS
// ==========================================

// System health monitoring (Admin only)
Broadcast::channel('system-health', function ($user) {
    return $user->hasRole('Admin');
});

// Queue status monitoring (Admin+Manager)
Broadcast::channel('queue-status', function ($user) {
    return $user->hasAnyRole(['Admin', 'Manager']);
});

// ==========================================
// FUTURE CHANNELS (placeholders)
// ==========================================

/*
// Real-time collaboration channels (future)
Broadcast::channel('product-editing.{productSku}', function ($user, $productSku) {
    return $user->hasPermissionTo('products.edit');
});

// Chat support channels (future)
Broadcast::channel('support-chat.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId || $user->hasRole('Admin');
});
*/