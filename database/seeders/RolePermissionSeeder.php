<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * FAZA D: Integration & System Tables
     * 5.1.2 Role i uprawnienia (Spatie Laravel Permission)
     * 
     * Konfiguruje 7-poziomowy system uprawnień PPM:
     * 1. Admin - pełne uprawnienia
     * 2. Manager - CRUD produktów + import/export  
     * 3. Editor - edycja opisów, zdjęć, kategorii
     * 4. Warehouseman - panel dostaw
     * 5. Salesperson - zamówienia + rezerwacje
     * 6. Claims - reklamacje
     * 7. User - tylko odczyt
     * 
     * Granularne uprawnienia dla każdego modułu systemu
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        
        // === TWORZENIE UPRAWNIEŃ ===
        $permissions = [
            // === PRODUKTY ===
            'products.create' => 'Tworzenie nowych produktów',
            'products.read' => 'Odczyt produktów', 
            'products.update' => 'Edycja produktów',
            'products.delete' => 'Usuwanie produktów',
            'products.export' => 'Eksport produktów',
            'products.import' => 'Import produktów',
            'products.variants' => 'Zarządzanie wariantami',
            
            // === KATEGORIE ===
            'categories.create' => 'Tworzenie kategorii',
            'categories.read' => 'Odczyt kategorii',
            'categories.update' => 'Edycja kategorii', 
            'categories.delete' => 'Usuwanie kategorii',
            'categories.tree' => 'Zarządzanie strukturą drzewa',
            
            // === MEDIA I PLIKI ===
            'media.create' => 'Dodawanie mediów',
            'media.read' => 'Odczyt mediów',
            'media.update' => 'Edycja mediów',
            'media.delete' => 'Usuwanie mediów',
            'media.upload' => 'Upload plików',
            
            // === CENY (tylko dla Admin/Manager) ===
            'prices.read' => 'Odczyt cen',
            'prices.update' => 'Edycja cen',
            'prices.groups' => 'Zarządzanie grupami cenowymi',
            'prices.cost' => 'Dostęp do cen zakupu',
            
            // === MAGAZYNY ===
            'stock.read' => 'Odczyt stanów magazynowych',
            'stock.update' => 'Aktualizacja stanów',
            'stock.reservations' => 'Zarządzanie rezerwacjami',
            'stock.delivery' => 'Panel dostaw',
            'stock.locations' => 'Lokalizacje magazynowe',
            
            // === INTEGRACJE ===
            'integrations.read' => 'Odczyt statusu integracji',
            'integrations.sync' => 'Synchronizacja systemów',
            'integrations.config' => 'Konfiguracja integracji',
            'integrations.prestashop' => 'Integracja PrestaShop',
            'integrations.erp' => 'Integracje ERP',
            
            // === ZAMÓWIENIA I SPRZEDAŻ ===
            'orders.read' => 'Odczyt zamówień',
            'orders.create' => 'Tworzenie zamówień', 
            'orders.update' => 'Edycja zamówień',
            'orders.reservations' => 'Rezerwacje z kontenera',
            
            // === REKLAMACJE ===
            'claims.read' => 'Odczyt reklamacji',
            'claims.create' => 'Tworzenie reklamacji',
            'claims.update' => 'Obsługa reklamacji',
            'claims.resolve' => 'Rozwiązywanie reklamacji',
            
            // === SYSTEM I ADMINISTRACJA ===
            'users.read' => 'Odczyt użytkowników',
            'users.create' => 'Tworzenie użytkowników',
            'users.update' => 'Edycja użytkowników',
            'users.delete' => 'Usuwanie użytkowników',
            'users.roles' => 'Zarządzanie rolami',
            
            // === RAPORTY I AUDYT ===
            'reports.read' => 'Odczyt raportów',
            'reports.export' => 'Eksport raportów',
            'audit.read' => 'Dostęp do audit logs',
            'system.config' => 'Konfiguracja systemu',
            'system.maintenance' => 'Konserwacja systemu'
        ];
        
        // Tworzenie uprawnień bez opisu (standardowa tabela Spatie)
        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate([
                'name' => $name, 
                'guard_name' => 'web'
            ]);
        }
        
        // === TWORZENIE RÓL I PRZYPISYWANIE UPRAWNIEŃ ===
        
        // 1. ADMIN - pełny dostęp do wszystkiego
        $adminRole = Role::firstOrCreate([
            'name' => 'Admin',
            'guard_name' => 'web'
        ]);
        $adminRole->givePermissionTo(Permission::all());
        
        // 2. MANAGER - CRUD produktów + import/export + zarządzanie
        $managerRole = Role::firstOrCreate([
            'name' => 'Manager', 
            'guard_name' => 'web'
        ]);
        $managerRole->givePermissionTo([
            // Produkty - pełny CRUD
            'products.create', 'products.read', 'products.update', 'products.delete',
            'products.export', 'products.import', 'products.variants',
            // Kategorie - pełny CRUD
            'categories.create', 'categories.read', 'categories.update', 'categories.delete', 'categories.tree',
            // Media - pełny dostęp
            'media.create', 'media.read', 'media.update', 'media.delete', 'media.upload',
            // Ceny - pełny dostęp
            'prices.read', 'prices.update', 'prices.groups', 'prices.cost',
            // Magazyny - pełny dostęp
            'stock.read', 'stock.update', 'stock.reservations', 'stock.delivery', 'stock.locations',
            // Integracje - sync i config
            'integrations.read', 'integrations.sync', 'integrations.config', 
            'integrations.prestashop', 'integrations.erp',
            // Zamówienia - pełny dostęp
            'orders.read', 'orders.create', 'orders.update', 'orders.reservations',
            // Raporty
            'reports.read', 'reports.export'
        ]);
        
        // 3. EDITOR - edycja opisów, zdjęć, kategorii
        $editorRole = Role::firstOrCreate([
            'name' => 'Editor',
            'guard_name' => 'web'
        ]);
        $editorRole->givePermissionTo([
            // Produkty - read + update (bez create/delete)
            'products.read', 'products.update', 'products.export',
            // Kategorie - read + update
            'categories.read', 'categories.update',
            // Media - pełny dostęp do zarządzania zdjęciami
            'media.create', 'media.read', 'media.update', 'media.delete', 'media.upload',
            // Stock - tylko read
            'stock.read',
            // Integracje - tylko read
            'integrations.read'
        ]);
        
        // 4. WAREHOUSEMAN - panel dostaw
        $warehousemanRole = Role::firstOrCreate([
            'name' => 'Warehouseman',
            'guard_name' => 'web'
        ]);
        $warehousemanRole->givePermissionTo([
            // Produkty - tylko read
            'products.read',
            // Magazyny - dostawa i lokalizacje
            'stock.read', 'stock.update', 'stock.delivery', 'stock.locations',
            // Brak dostępu do rezerwacji z kontenera
            // Integracje - tylko read
            'integrations.read'
        ]);
        
        // 5. SALESPERSON - zamówienia + rezerwacje
        $salespersonRole = Role::firstOrCreate([
            'name' => 'Salesperson',
            'guard_name' => 'web'
        ]);
        $salespersonRole->givePermissionTo([
            // Produkty - read (bez dostępu do cen zakupu)
            'products.read',
            // Ceny - read (bez cost prices)
            'prices.read',
            // Stock - read + reservations
            'stock.read', 'stock.reservations',
            // Zamówienia - pełny dostęp
            'orders.read', 'orders.create', 'orders.update', 'orders.reservations',
            // Integracje - tylko read
            'integrations.read'
        ]);
        
        // 6. CLAIMS - reklamacje
        $claimsRole = Role::firstOrCreate([
            'name' => 'Claims',
            'guard_name' => 'web'
        ]);
        $claimsRole->givePermissionTo([
            // Produkty - read
            'products.read',
            // Stock - read
            'stock.read',
            // Reklamacje - pełny dostęp
            'claims.read', 'claims.create', 'claims.update', 'claims.resolve',
            // Zamówienia - read
            'orders.read'
        ]);
        
        // 7. USER - tylko odczyt
        $userRole = Role::firstOrCreate([
            'name' => 'User',
            'guard_name' => 'web'
        ]);
        $userRole->givePermissionTo([
            // Podstawowy read-only dostęp
            'products.read',
            'categories.read', 
            'media.read',
            'stock.read',
            'integrations.read'
        ]);
        
        $this->command->info('✅ Role i uprawnienia zostały utworzone pomyślnie');
        $this->command->info('✅ Utworzono 7 ról: Admin, Manager, Editor, Warehouseman, Salesperson, Claims, User');
        $this->command->info('✅ Utworzono ' . count($permissions) . ' granularnych uprawnień');
    }
}