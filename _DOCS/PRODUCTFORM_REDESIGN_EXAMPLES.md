# ProductForm Redesign - Code Examples

Przykładowe struktury plików dla nowej architektury.

---

## 1. MAIN FILE (product-form.blade.php)

```blade
{{-- resources/views/livewire/products/management/product-form.blade.php --}}
{{-- NOWA ARCHITEKTURA - CLEAN & MODULAR (~150 lines) --}}

{{-- Conditional wire:poll wrapper (UNCHANGED) --}}
@if($activeJobStatus && $activeJobStatus !== 'completed' && $activeJobStatus !== 'failed')
    <div wire:poll.5s="checkJobStatus">
@endif

{{-- Main page wrapper --}}
<div class="product-form-page"
     @redirect-to-product-list.window="window.skipBeforeUnload = true; window.location.href = '/admin/products'">

    {{-- HEADER: Breadcrumbs, title, actions --}}
    @include('livewire.products.management.partials.form-header', [
        'isEditMode' => $isEditMode,
        'name' => $name,
        'hasUnsavedChanges' => $hasUnsavedChanges
    ])

    {{-- MESSAGES: Session alerts, success, errors --}}
    @include('livewire.products.management.partials.form-messages', [
        'successMessage' => $successMessage
    ])

    {{-- FORM WRAPPER --}}
    <form wire:submit.prevent="save" class="product-form">

        {{-- 2-COLUMN GRID LAYOUT --}}
        <div class="product-form-layout">

            {{-- LEFT COLUMN: Main content --}}
            <main class="product-form-main">

                {{-- TAB NAVIGATION (outside enterprise-card!) --}}
                @include('livewire.products.management.partials.tab-navigation', [
                    'activeTab' => $activeTab
                ])

                {{-- MULTI-STORE MANAGEMENT (outside enterprise-card!) --}}
                @include('livewire.products.management.partials.shop-management', [
                    'activeShopId' => $activeShopId,
                    'exportedShops' => $exportedShops,
                    'availableShops' => $availableShops
                ])

                {{-- TAB CONTENT: Conditional rendering (ONE at a time!) --}}
                <div class="product-form-tabs">
                    @if($activeTab === 'basic')
                        @include('livewire.products.management.tabs.basic-tab')
                    @elseif($activeTab === 'description')
                        @include('livewire.products.management.tabs.description-tab')
                    @elseif($activeTab === 'physical')
                        @include('livewire.products.management.tabs.physical-tab')
                    @elseif($activeTab === 'attributes')
                        @include('livewire.products.management.tabs.attributes-tab')
                    @elseif($activeTab === 'prices')
                        @include('livewire.products.management.tabs.prices-tab')
                    @elseif($activeTab === 'stock')
                        @include('livewire.products.management.tabs.stock-tab')
                    @endif
                </div>

            </main>

            {{-- RIGHT COLUMN: Sticky sidebar --}}
            <aside class="product-form-sidebar">

                {{-- Quick Actions --}}
                @include('livewire.products.management.partials.quick-actions', [
                    'isEditMode' => $isEditMode,
                    'hasUnsavedChanges' => $hasUnsavedChanges
                ])

                {{-- Product Info (edit mode only) --}}
                @if($isEditMode)
                    @include('livewire.products.management.partials.product-info', [
                        'productId' => $productId,
                        'sku' => $sku,
                        'created_at' => $created_at,
                        'updated_at' => $updated_at
                    ])
                @endif

                {{-- Category Browser --}}
                @include('livewire.products.management.partials.category-browser')

            </aside>

        </div>{{-- Close .product-form-layout --}}

    </form>{{-- Close form --}}

</div>{{-- Close .product-form-page --}}

{{-- Close wire:poll wrapper --}}
@if($activeJobStatus && $activeJobStatus !== 'completed' && $activeJobStatus !== 'failed')
    </div>
@endif
```

---

## 2. PARTIALS

### 2.1 form-header.blade.php

```blade
{{-- resources/views/livewire/products/management/partials/form-header.blade.php --}}
{{-- Header Section: Breadcrumbs, title, actions --}}

<div class="product-form-header">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            {{-- Title --}}
            <h1 class="text-2xl font-bold text-dark-primary mb-2">
                @if($isEditMode)
                    <i class="fas fa-edit text-mpp-orange mr-2"></i>
                    Edytuj produkt
                @else
                    <i class="fas fa-plus-circle text-green-400 mr-2"></i>
                    Nowy produkt
                @endif
            </h1>

            {{-- Breadcrumbs --}}
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb-dark flex items-center space-x-2 text-sm">
                    <li>
                        <a href="{{ route('admin.dashboard') }}" class="hover:text-mpp-orange">
                            <i class="fas fa-home"></i> Panel administracyjny
                        </a>
                    </li>
                    <li class="text-dark-muted">></li>
                    <li>
                        <a href="{{ route('admin.products.index') }}" class="hover:text-mpp-orange">
                            <i class="fas fa-box"></i> Produkty
                        </a>
                    </li>
                    <li class="text-dark-muted">></li>
                    <li class="text-dark-secondary">
                        @if($isEditMode)
                            Edycja: {{ $name ?? 'Produkt' }}
                        @else
                            Nowy produkt
                        @endif
                    </li>
                </ol>
            </nav>
        </div>

        {{-- Actions --}}
        <div class="flex gap-4">
            {{-- Unsaved Changes Badge --}}
            @if($hasUnsavedChanges)
                <span class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-full bg-orange-900/30 text-orange-200 border border-orange-700/50">
                    <i class="fas fa-exclamation-circle mr-1"></i>
                    Niezapisane zmiany
                </span>
            @endif

            {{-- Cancel Button --}}
            <a href="{{ route('admin.products.index') }}" class="btn-enterprise-secondary">
                <i class="fas fa-times"></i>
                Anuluj
            </a>
        </div>
    </div>
</div>
```

### 2.2 form-messages.blade.php

```blade
{{-- resources/views/livewire/products/management/partials/form-messages.blade.php --}}
{{-- Alert Messages: Session, success, errors --}}

<div class="product-form-messages">
    {{-- Session Success Message --}}
    @if (session()->has('message'))
        <div class="alert-dark-success flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            {{ session('message') }}
        </div>
    @endif

    {{-- Session Error Message --}}
    @if (session()->has('error'))
        <div class="alert-dark-error flex items-center">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            {{ session('error') }}
        </div>
    @endif

    {{-- Livewire Success Message (dismissable) --}}
    @if($successMessage)
        <div x-data="{ show: true }"
             x-show="show"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform translate-y-2"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             class="alert-dark-success flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                {{ $successMessage }}
            </div>
            <button @click="show = false" class="ml-4">
                <i class="fas fa-times"></i>
            </button>
        </div>
    @endif
</div>
```

### 2.3 tab-navigation.blade.php

```blade
{{-- resources/views/livewire/products/management/partials/tab-navigation.blade.php --}}
{{-- Tab Navigation: 6 tabs --}}

<div class="product-form-nav">
    <div class="tabs-enterprise">
        <button class="tab-enterprise {{ $activeTab === 'basic' ? 'active' : '' }}"
                type="button"
                wire:click="switchTab('basic')">
            <i class="fas fa-info-circle icon"></i>
            <span>Informacje podstawowe</span>
        </button>

        <button class="tab-enterprise {{ $activeTab === 'description' ? 'active' : '' }}"
                type="button"
                wire:click="switchTab('description')">
            <i class="fas fa-align-left icon"></i>
            <span>Opisy i SEO</span>
        </button>

        <button class="tab-enterprise {{ $activeTab === 'physical' ? 'active' : '' }}"
                type="button"
                wire:click="switchTab('physical')">
            <i class="fas fa-box icon"></i>
            <span>Właściwości fizyczne</span>
        </button>

        <button class="tab-enterprise {{ $activeTab === 'attributes' ? 'active' : '' }}"
                type="button"
                wire:click="switchTab('attributes')">
            <i class="fas fa-tags icon"></i>
            <span>Atrybuty</span>
        </button>

        <button class="tab-enterprise {{ $activeTab === 'prices' ? 'active' : '' }}"
                type="button"
                wire:click="switchTab('prices')">
            <i class="fas fa-dollar-sign icon"></i>
            <span>Ceny</span>
        </button>

        <button class="tab-enterprise {{ $activeTab === 'stock' ? 'active' : '' }}"
                type="button"
                wire:click="switchTab('stock')">
            <i class="fas fa-warehouse icon"></i>
            <span>Stany magazynowe</span>
        </button>
    </div>
</div>
```

### 2.4 shop-management.blade.php

```blade
{{-- resources/views/livewire/products/management/partials/shop-management.blade.php --}}
{{-- Multi-Store Management Bar --}}

<div class="shop-management-bar">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
            <h4 class="text-sm font-semibold text-white">
                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                Zarządzanie sklepami
            </h4>

            {{-- Default Data Toggle --}}
            <button type="button"
                    wire:click="switchToShop(null)"
                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-full transition-colors duration-200 {{ $activeShopId === null ? 'bg-gradient-to-r from-orange-500 to-orange-600 text-white shadow-md' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 1v4" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 1v4" />
                </svg>
                Dane domyślne
            </button>
        </div>

        {{-- Add to Shop Button --}}
        <div class="flex items-center space-x-2">
            <button type="button"
                    wire:click="openShopSelector"
                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors duration-200">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Dodaj do sklepu
            </button>
        </div>
    </div>

    {{-- Exported Shops List --}}
    @if(!empty($exportedShops))
        <div class="mt-3">
            <div class="flex flex-wrap gap-2">
                @foreach($exportedShops as $shopId)
                    @php
                        $shop = collect($availableShops)->firstWhere('id', $shopId);
                    @endphp
                    @if($shop)
                        <button type="button"
                                wire:click="switchToShop({{ $shopId }})"
                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-full transition-colors duration-200 {{ $activeShopId === $shopId ? 'bg-gradient-to-r from-blue-500 to-blue-600 text-white shadow-md' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            {{ $shop['name'] }}
                        </button>
                    @endif
                @endforeach
            </div>
        </div>
    @endif
</div>
```

### 2.5 quick-actions.blade.php

```blade
{{-- resources/views/livewire/products/management/partials/quick-actions.blade.php --}}
{{-- Quick Actions Sidebar Panel --}}

<div class="enterprise-card p-6">
    <h4 class="text-lg font-bold text-dark-primary mb-6 flex items-center">
        <i class="fas fa-bolt text-mpp-orange mr-2"></i>
        Szybkie akcje
    </h4>

    <div class="space-y-4">
        {{-- Save Button --}}
        <button type="submit"
                class="btn-enterprise-primary w-full"
                wire:loading.attr="disabled"
                wire:loading.class="opacity-50 cursor-not-allowed">
            <span wire:loading.remove>
                <i class="fas fa-save"></i>
                @if($isEditMode)
                    Zapisz zmiany
                @else
                    Utwórz produkt
                @endif
            </span>
            <span wire:loading>
                <i class="fas fa-spinner fa-spin"></i>
                Zapisywanie...
            </span>
        </button>

        {{-- Save & Continue Button --}}
        @if(!$isEditMode)
            <button type="button"
                    wire:click="saveAndContinue"
                    class="btn-enterprise-secondary w-full">
                <i class="fas fa-save"></i>
                Zapisz i kontynuuj
            </button>
        @endif

        {{-- Preview Button (edit mode only) --}}
        @if($isEditMode && $productId)
            <a href="{{ route('admin.products.preview', $productId) }}"
               target="_blank"
               class="btn-enterprise-outline w-full inline-flex items-center justify-center">
                <i class="fas fa-eye"></i>
                Podgląd
            </a>
        @endif

        {{-- Delete Button (edit mode only) --}}
        @if($isEditMode && $productId)
            <button type="button"
                    wire:click="confirmDelete"
                    class="btn-enterprise-danger w-full"
                    wire:confirm="Czy na pewno chcesz usunąć ten produkt?">
                <i class="fas fa-trash"></i>
                Usuń produkt
            </button>
        @endif
    </div>
</div>
```

---

## 3. TAB EXAMPLES

### 3.1 basic-tab.blade.php (TEMPLATE)

```blade
{{-- resources/views/livewire/products/management/tabs/basic-tab.blade.php --}}
{{-- BASIC INFO TAB --}}

<div class="enterprise-card p-8" wire:key="basic-tab">
    <h3 class="text-xl font-bold text-dark-primary mb-6 flex items-center">
        <i class="fas fa-info-circle text-blue-400 mr-2"></i>
        Informacje podstawowe
    </h3>

    {{-- GRID LAYOUT --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- SKU --}}
        <div class="form-group">
            <label for="sku" class="form-label-dark required">SKU</label>
            <input type="text"
                   id="sku"
                   wire:model.defer="sku"
                   class="form-input-dark @error('sku') border-red-500 @enderror"
                   placeholder="Unikalny kod produktu"
                   required>
            @error('sku')
                <span class="form-error">{{ $message }}</span>
            @enderror
        </div>

        {{-- NAME --}}
        <div class="form-group">
            <label for="name" class="form-label-dark required">Nazwa produktu</label>
            <input type="text"
                   id="name"
                   wire:model.defer="name"
                   class="form-input-dark @error('name') border-red-500 @enderror"
                   placeholder="Pełna nazwa produktu"
                   required>
            @error('name')
                <span class="form-error">{{ $message }}</span>
            @enderror
        </div>

        {{-- EAN --}}
        <div class="form-group">
            <label for="ean" class="form-label-dark">EAN / Kod kreskowy</label>
            <input type="text"
                   id="ean"
                   wire:model.defer="ean"
                   class="form-input-dark"
                   placeholder="13-cyfrowy kod EAN">
        </div>

        {{-- MANUFACTURER --}}
        <div class="form-group">
            <label for="manufacturer" class="form-label-dark">Producent</label>
            <input type="text"
                   id="manufacturer"
                   wire:model.defer="manufacturer"
                   class="form-input-dark"
                   placeholder="Nazwa producenta">
        </div>

        {{-- STATUS --}}
        <div class="form-group">
            <label for="status" class="form-label-dark required">Status</label>
            <select id="status"
                    wire:model.defer="status"
                    class="form-input-dark @error('status') border-red-500 @enderror"
                    required>
                <option value="active">Aktywny</option>
                <option value="inactive">Nieaktywny</option>
                <option value="draft">Szkic</option>
            </select>
            @error('status')
                <span class="form-error">{{ $message }}</span>
            @enderror
        </div>

        {{-- MORE FIELDS... --}}

    </div>
</div>
```

---

## 4. CSS LAYOUT (product-form-layout.css)

```css
/* resources/css/products/product-form-layout.css */

/* ========================================
   PRODUCT FORM PAGE LAYOUT
   Clean 2-column grid with sticky sidebar
   ======================================== */

/* Page wrapper */
.product-form-page {
    width: 100%;
    padding: 1.5rem;
    background: var(--color-bg-primary, #0f172a);
}

/* Form wrapper */
.product-form {
    width: 100%;
    max-width: 1920px;
    margin: 0 auto;
}

/* ========================================
   2-COLUMN GRID LAYOUT
   Main content (left) + Sticky sidebar (right)
   ======================================== */

.product-form-layout {
    display: grid;
    grid-template-columns: 1fr 400px; /* Main + Sidebar */
    gap: 1.5rem;
    align-items: start; /* CRITICAL: Prevent sidebar stretching */
}

/* Main content area (left column) */
.product-form-main {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    min-width: 0; /* Fix overflow in grid */
}

/* Sticky sidebar (right column) */
.product-form-sidebar {
    position: sticky;
    top: 1rem; /* Stick to top with 1rem offset */
    display: flex;
    flex-direction: column;
    gap: 1rem;
    max-height: calc(100vh - 2rem);
    overflow-y: auto; /* Scroll if content too tall */
    overflow-x: hidden;
}

/* Custom scrollbar for sidebar */
.product-form-sidebar::-webkit-scrollbar {
    width: 6px;
}

.product-form-sidebar::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 3px;
}

.product-form-sidebar::-webkit-scrollbar-thumb {
    background: rgba(224, 172, 126, 0.3);
    border-radius: 3px;
}

.product-form-sidebar::-webkit-scrollbar-thumb:hover {
    background: rgba(224, 172, 126, 0.5);
}

/* ========================================
   HEADER & MESSAGES
   ======================================== */

.product-form-header {
    margin-bottom: 1.5rem;
}

.product-form-messages {
    margin-bottom: 1rem;
}

/* ========================================
   TAB NAVIGATION (OUTSIDE CARD!)
   ======================================== */

.product-form-nav {
    background: var(--color-bg-secondary, #1e293b);
    border: 1px solid var(--color-border, #334155);
    border-radius: 0.75rem;
    padding: 0.75rem;
}

/* Reuse existing .tabs-enterprise styles */
.product-form-nav .tabs-enterprise {
    /* NO changes needed - existing classes work */
}

/* ========================================
   SHOP MANAGEMENT BAR
   ======================================== */

.shop-management-bar {
    background: var(--color-bg-secondary, #1e293b);
    border: 1px solid var(--color-border, #334155);
    border-radius: 0.75rem;
    padding: 1rem;
}

/* ========================================
   TAB CONTENT WRAPPER
   ======================================== */

.product-form-tabs {
    /* No additional styles - each tab is .enterprise-card */
}

/* ========================================
   RESPONSIVE DESIGN
   ======================================== */

/* Stack layout on tablets and mobile */
@media (max-width: 1280px) {
    .product-form-layout {
        grid-template-columns: 1fr; /* Single column */
    }

    .product-form-sidebar {
        position: relative; /* No sticky on mobile */
        top: 0;
        max-height: none;
    }
}

/* Smaller padding on mobile */
@media (max-width: 768px) {
    .product-form-page {
        padding: 1rem;
    }

    .product-form-nav {
        padding: 0.5rem;
    }

    .shop-management-bar {
        padding: 0.75rem;
    }
}
```

---

## 5. APP.CSS IMPORTS

```css
/* resources/css/app.css */

/* Base styles */
@import 'base.css';

/* Admin layout */
@import 'admin/layout.css';
@import 'admin/components.css';
@import 'admin/queue-jobs.css';

/* Product form styles */
@import 'products/product-form-layout.css'; /* NEW - Grid layout */
@import 'products/product-form.css'; /* EXISTING - Field styles, shop tab, etc. */
@import 'products/category-form.css'; /* EXISTING - Category picker */
@import 'products/variant-management.css';

/* Component styles */
@import 'components/category-picker.css';
@import 'components/category-preview-modal.css';
```

---

## 6. MIGRATION SCRIPT EXAMPLE

```powershell
# migrate-productform.ps1
# Automated migration script for ProductForm architecture redesign

param(
    [switch]$DryRun = $false,
    [switch]$Backup = $true
)

$ErrorActionPreference = "Stop"
$ProjectRoot = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "ProductForm Architecture Migration" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Phase 1: Backup
if ($Backup) {
    Write-Host "[1/7] Creating backup..." -ForegroundColor Yellow
    $timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
    $backupDir = "$ProjectRoot\_BACKUP\productform_$timestamp"
    New-Item -ItemType Directory -Force -Path $backupDir | Out-Null

    Copy-Item "$ProjectRoot\resources\views\livewire\products\management\product-form.blade.php" `
        -Destination "$backupDir\product-form_original.blade.php"
    Copy-Item "$ProjectRoot\resources\css\products\product-form.css" `
        -Destination "$backupDir\product-form_original.css"

    Write-Host "   Backup saved to: $backupDir" -ForegroundColor Green
}

# Phase 2: Create directory structure
Write-Host "[2/7] Creating directory structure..." -ForegroundColor Yellow
New-Item -ItemType Directory -Force -Path "$ProjectRoot\resources\views\livewire\products\management\partials" | Out-Null
New-Item -ItemType Directory -Force -Path "$ProjectRoot\resources\views\livewire\products\management\tabs" | Out-Null
Write-Host "   Directories created" -ForegroundColor Green

# Phase 3-5: Extract files (manual step)
Write-Host "[3/7] Extracting partials..." -ForegroundColor Yellow
Write-Host "   MANUAL STEP: Use split-productform.ps1" -ForegroundColor Red

# Phase 6: Create new CSS
Write-Host "[6/7] Creating product-form-layout.css..." -ForegroundColor Yellow
# ... (copy CSS from examples above)

# Phase 7: Build assets
Write-Host "[7/7] Building assets..." -ForegroundColor Yellow
Set-Location $ProjectRoot
npm run build
Write-Host "   Build complete" -ForegroundColor Green

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Migration script complete!" -ForegroundColor Green
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "1. Manually split product-form.blade.php into partials" -ForegroundColor White
Write-Host "2. Test locally: php artisan serve" -ForegroundColor White
Write-Host "3. Deploy to production" -ForegroundColor White
Write-Host "========================================" -ForegroundColor Cyan
```

---

## 7. TESTING CHECKLIST

```markdown
## Local Testing Checklist

### Layout Tests
- [ ] Sidebar sticky (scroll main content, sidebar stays)
- [ ] 2-column grid (main + sidebar side-by-side)
- [ ] Responsive (< 1280px: sidebar below main)
- [ ] Tab navigation outside enterprise-card
- [ ] Shop management bar outside enterprise-card

### Functionality Tests
- [ ] Tab switching (wire:click="switchTab")
- [ ] Form submission (wire:submit.prevent="save")
- [ ] Field bindings (all wire:model.defer work)
- [ ] Shop switching (wire:click="switchToShop")
- [ ] Category picker (wire:click="openCategoryPicker")
- [ ] Validation errors (display per tab)

### Performance Tests
- [ ] DOM nodes < 500 (check Chrome DevTools)
- [ ] Page load time < 2s
- [ ] Tab switch instant (< 100ms)
- [ ] No console errors

### Visual Tests
- [ ] Screenshot ALL tabs (basic, description, physical, attributes, prices, stock)
- [ ] Screenshot responsive (desktop, tablet, mobile)
- [ ] Screenshot sidebar sticky behavior
```

---

**Next Steps:**
1. Review examples
2. Implement Phase 1 (Backup & Preparation)
3. Extract partials using templates above
4. Test thoroughly before production deployment
