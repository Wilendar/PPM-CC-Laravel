# PPM Enterprise Components Catalog

**Version:** 1.0.0
**Last Updated:** 2025-11-19

Complete reference of reusable enterprise UI components with implementation examples.

---

## Button System

### Primary Button (`.btn-enterprise-primary`)

**MPP Orange gradient - Main call-to-action**

```css
/* resources/css/admin/components.css */
.btn-enterprise-primary {
    padding: 10px 20px;
    border-radius: 6px;
    font-weight: 600;
    background: linear-gradient(135deg, var(--mpp-primary) 0%, var(--mpp-primary-dark) 50%, #c08449 100%);
    color: white;
    border: none;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
    position: relative;
    overflow: hidden;
}

.btn-enterprise-primary::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, transparent 100%);
    opacity: 0;
    transition: opacity 0.3s;
}

.btn-enterprise-primary:hover::before {
    opacity: 1;
}

.btn-enterprise-primary:active {
    transform: translateY(1px);
}
```

**Blade Usage:**
```blade
<button type="submit" class="btn-enterprise-primary">
    Zapisz produkt
</button>

<button type="button"
        wire:click="saveAndSync"
        class="btn-enterprise-primary">
    Zapisz i synchronizuj
</button>
```

---

### Secondary Button (`.btn-enterprise-secondary`)

**Neutral style - Secondary actions**

```css
.btn-enterprise-secondary {
    padding: 10px 20px;
    border-radius: 6px;
    font-weight: 500;
    background: var(--bg-card);
    color: var(--text-primary);
    border: 1px solid var(--border-default);
    cursor: pointer;
    transition: background 0.2s, border-color 0.2s;
}

.btn-enterprise-secondary:hover {
    background: var(--bg-card-hover);
    border-color: var(--border-hover);
}
```

**Blade Usage:**
```blade
<button type="button"
        wire:click="cancel"
        class="btn-enterprise-secondary">
    Anuluj
</button>
```

---

### Size Variants

```css
/* Small */
.btn-enterprise-sm {
    padding: 6px 12px;
    font-size: 0.875rem;
}

/* Large */
.btn-enterprise-lg {
    padding: 12px 24px;
    font-size: 1.125rem;
}
```

**Blade Usage:**
```blade
<button class="btn-enterprise-primary btn-enterprise-sm">
    Szybka akcja
</button>

<button class="btn-enterprise-primary btn-enterprise-lg">
    Główna akcja
</button>
```

---

### Icon Buttons

```css
.btn-icon {
    width: 40px;
    height: 40px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    border: 1px solid var(--border-default);
    background: var(--bg-card);
    color: var(--text-secondary);
    cursor: pointer;
    transition: all 0.2s;
}

.btn-icon:hover {
    background: var(--bg-card-hover);
    color: var(--text-primary);
    transform: scale(1.05);
}
```

**Blade Usage:**
```blade
<button type="button"
        wire:click="edit({{ $product->id }})"
        class="btn-icon"
        title="Edytuj">
    <i class="fas fa-edit"></i>
</button>
```

---

## Card System

### Base Card (`.enterprise-card`)

```css
.enterprise-card {
    background: var(--bg-card);
    border-radius: 8px;
    padding: 24px;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(255, 255, 255, 0.1);
    transition: border-color 0.2s, box-shadow 0.2s;
}

.enterprise-card:hover {
    border-color: rgba(255, 255, 255, 0.2);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.4);
}
```

**Blade Usage:**
```blade
<div class="enterprise-card">
    <h3 class="text-h3">Informacje o produkcie</h3>
    <p class="text-body">Zawartość karty...</p>
</div>
```

---

### Card Variants

```css
/* Warning - Orange/Yellow accent */
.enterprise-card-warning {
    border-left: 4px solid var(--color-warning);
    background: linear-gradient(90deg, rgba(245, 158, 11, 0.1), transparent);
}

/* Success - Green accent */
.enterprise-card-success {
    border-left: 4px solid var(--ppm-secondary);
    background: linear-gradient(90deg, rgba(var(--ppm-secondary-rgb), 0.1), transparent);
}

/* Error - Red accent */
.enterprise-card-error {
    border-left: 4px solid var(--ppm-accent);
    background: linear-gradient(90deg, rgba(var(--ppm-accent-rgb), 0.1), transparent);
}

/* Info - Blue accent */
.enterprise-card-info {
    border-left: 4px solid var(--ppm-primary);
    background: linear-gradient(90deg, rgba(var(--ppm-primary-rgb), 0.1), transparent);
}
```

**Blade Usage:**
```blade
<div class="enterprise-card enterprise-card-warning">
    <h4>⚠️ Uwaga</h4>
    <p>Produkt wymaga weryfikacji przed synchronizacją.</p>
</div>

<div class="enterprise-card enterprise-card-success">
    <h4>✅ Sukces</h4>
    <p>Produkt został pomyślnie zsynchronizowany.</p>
</div>
```

---

### Structured Card Layout

```css
.enterprise-card__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--border-default);
}

.enterprise-card__title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary);
}

.enterprise-card__body {
    color: var(--text-secondary);
    line-height: 1.6;
}

.enterprise-card__footer {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid var(--border-default);
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}
```

**Blade Usage:**
```blade
<div class="enterprise-card">
    <div class="enterprise-card__header">
        <h3 class="enterprise-card__title">Szczegóły synchronizacji</h3>
        <span class="badge-enterprise badge-enterprise--synced">Zsynchronizowany</span>
    </div>

    <div class="enterprise-card__body">
        <p>Ostatnia synchronizacja: {{ $product->last_sync_at }}</p>
        <p>Status: Wszystkie sklepy aktualne</p>
    </div>

    <div class="enterprise-card__footer">
        <button class="btn-enterprise-secondary btn-enterprise-sm">Wróć</button>
        <button class="btn-enterprise-primary btn-enterprise-sm">Synchronizuj ponownie</button>
    </div>
</div>
```

---

## Badge System

### Base Badge (`.badge-enterprise`)

```css
.badge-enterprise {
    display: inline-flex;
    align-items: center;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
```

---

### Status Badge Variants

```css
/* Synced - Green */
.badge-enterprise--synced {
    background: rgba(var(--ppm-secondary-rgb), 0.2);
    color: var(--ppm-secondary);
}

/* Pending - Yellow/Orange */
.badge-enterprise--pending {
    background: rgba(245, 158, 11, 0.2);
    color: #f6ad55;
}

/* Error - Red */
.badge-enterprise--error {
    background: rgba(var(--ppm-accent-rgb), 0.2);
    color: var(--ppm-accent);
}

/* Processing - Blue */
.badge-enterprise--processing {
    background: rgba(var(--ppm-primary-rgb), 0.2);
    color: var(--ppm-primary);
}

/* Inactive - Gray */
.badge-enterprise--inactive {
    background: rgba(100, 116, 139, 0.2);
    color: var(--text-muted);
}
```

**Blade Usage:**
```blade
<span class="badge-enterprise badge-enterprise--synced">Zsynchronizowany</span>
<span class="badge-enterprise badge-enterprise--pending">Oczekuje</span>
<span class="badge-enterprise badge-enterprise--error">Błąd</span>
<span class="badge-enterprise badge-enterprise--processing">Przetwarzanie</span>
<span class="badge-enterprise badge-enterprise--inactive">Nieaktywny</span>
```

---

### Animated Processing Badge

```css
.badge-enterprise--processing {
    position: relative;
    overflow: hidden;
}

.badge-enterprise--processing::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    animation: badge-shimmer 1.5s infinite;
}

@keyframes badge-shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}
```

---

## Form Components

### Input Field (`.form-input-enterprise`)

```css
.form-input-enterprise {
    width: 100%;
    padding: 10px 12px;
    border-radius: 6px;
    border: 1px solid var(--border-default);
    background: var(--bg-card);
    color: var(--text-primary);
    font-size: 0.875rem;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.form-input-enterprise:focus {
    outline: none;
    border-color: var(--mpp-primary);
    box-shadow: 0 0 0 2px rgba(var(--mpp-primary-rgb), 0.35);
}

.form-input-enterprise::placeholder {
    color: var(--text-muted);
}

.form-input-enterprise:disabled {
    background: var(--bg-card-hover);
    color: var(--text-disabled);
    cursor: not-allowed;
}
```

**Blade Usage:**
```blade
<input type="text"
       wire:model="product.name"
       class="form-input-enterprise"
       placeholder="Nazwa produktu">
```

---

### Checkbox (`.checkbox-enterprise`)

```css
.checkbox-enterprise {
    width: 18px;
    height: 18px;
    accent-color: var(--mpp-primary);
    cursor: pointer;
}

.checkbox-enterprise:disabled {
    cursor: not-allowed;
    opacity: 0.5;
}

/* Variant - Danger */
.checkbox-danger {
    accent-color: var(--ppm-accent);
}
```

**Blade Usage:**
```blade
<label class="flex items-center gap-2 cursor-pointer">
    <input type="checkbox"
           wire:model="product.active"
           class="checkbox-enterprise">
    <span class="text-sm text-secondary">Produkt aktywny</span>
</label>
```

---

### Select Dropdown (`.select-enterprise`)

```css
.select-enterprise {
    width: 100%;
    padding: 10px 36px 10px 12px;
    border-radius: 6px;
    border: 1px solid var(--border-default);
    background: var(--bg-card);
    color: var(--text-primary);
    font-size: 0.875rem;
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 8px center;
    background-size: 20px;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.select-enterprise:focus {
    outline: none;
    border-color: var(--mpp-primary);
    box-shadow: 0 0 0 2px rgba(var(--mpp-primary-rgb), 0.35);
}
```

**Blade Usage:**
```blade
<select wire:model="product.category_id" class="select-enterprise">
    <option value="">Wybierz kategorię</option>
    @foreach($categories as $category)
        <option value="{{ $category->id }}">{{ $category->name }}</option>
    @endforeach
</select>
```

---

## Progress Bars

### Linear Progress (`.progress-enterprise`)

```css
.progress-enterprise {
    width: 100%;
    height: 8px;
    border-radius: 4px;
    background: var(--bg-card-hover);
    overflow: hidden;
    position: relative;
}

.progress-enterprise__fill {
    height: 100%;
    background: linear-gradient(90deg, var(--ppm-primary), var(--ppm-primary-dark));
    border-radius: 4px;
    transition: transform 0.3s ease;
    transform: scaleX(var(--progress, 0));
    transform-origin: left;
}

/* Animated variant */
.progress-enterprise__fill--animated {
    animation: progress-shimmer 1.5s infinite;
}

@keyframes progress-shimmer {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}
```

**Blade + Alpine.js:**
```blade
<div class="progress-enterprise"
     x-data="{ progress: @entangle('syncProgress') }"
     x-effect="$el.style.setProperty('--progress', progress / 100)">
    <div class="progress-enterprise__fill"></div>
</div>

{{-- With label --}}
<div class="space-y-2">
    <div class="flex justify-between text-sm">
        <span class="text-secondary">Synchronizacja w trakcie...</span>
        <span class="text-primary" x-text="progress + '%'"></span>
    </div>
    <div class="progress-enterprise"
         x-data="{ progress: @entangle('syncProgress') }"
         x-effect="$el.style.setProperty('--progress', progress / 100)">
        <div class="progress-enterprise__fill progress-enterprise__fill--animated"></div>
    </div>
</div>
```

---

## Tab System

### Tabs Enterprise (`.tabs-enterprise`)

```css
.tabs-enterprise {
    display: flex;
    border-bottom: 2px solid var(--border-default);
    gap: 4px;
}

.tab-enterprise {
    padding: 12px 24px;
    font-weight: 500;
    color: var(--text-secondary);
    border: none;
    background: transparent;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
}

.tab-enterprise:hover {
    color: var(--text-primary);
}

.tab-enterprise.active {
    color: var(--mpp-primary);
}

.tab-enterprise.active::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    right: 0;
    height: 2px;
    background-color: var(--mpp-primary);
}

/* With badge count */
.tab-enterprise__badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    border-radius: 10px;
    background: var(--bg-card-hover);
    color: var(--text-secondary);
    font-size: 0.75rem;
    font-weight: 600;
    margin-left: 8px;
}

.tab-enterprise.active .tab-enterprise__badge {
    background: rgba(var(--mpp-primary-rgb), 0.2);
    color: var(--mpp-primary);
}
```

**Blade Usage:**
```blade
<div class="tabs-enterprise">
    <button type="button"
            wire:click="$set('activeTab', 0)"
            class="tab-enterprise {{ $activeTab === 0 ? 'active' : '' }}">
        Informacje podstawowe
    </button>
    <button type="button"
            wire:click="$set('activeTab', 1)"
            class="tab-enterprise {{ $activeTab === 1 ? 'active' : '' }}">
        Kategorie
        <span class="tab-enterprise__badge">{{ $categoriesCount }}</span>
    </button>
    <button type="button"
            wire:click="$set('activeTab', 2)"
            class="tab-enterprise {{ $activeTab === 2 ? 'active' : '' }}">
        Warianty
        <span class="tab-enterprise__badge">{{ $variantsCount }}</span>
    </button>
</div>

<div class="mt-6">
    @if($activeTab === 0)
        {{-- Basic info content --}}
    @elseif($activeTab === 1)
        {{-- Categories content --}}
    @elseif($activeTab === 2)
        {{-- Variants content --}}
    @endif
</div>
```

---

## Icon Chips

### Icon Chip (`.icon-chip`)

```css
.icon-chip {
    width: 3rem;
    height: 3rem;
    border-radius: 0.75rem;
    background: rgba(var(--mpp-primary-rgb), 0.18);
    color: var(--mpp-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

/* Variants */
.icon-chip--secondary {
    background: rgba(var(--ppm-primary-rgb), 0.18);
    color: var(--ppm-primary);
}

.icon-chip--success {
    background: rgba(var(--ppm-secondary-rgb), 0.18);
    color: var(--ppm-secondary);
}

.icon-chip--danger {
    background: rgba(var(--ppm-accent-rgb), 0.18);
    color: var(--ppm-accent);
}
```

**Blade Usage:**
```blade
<div class="flex items-center gap-4">
    <div class="icon-chip">
        <i class="fas fa-box"></i>
    </div>
    <div>
        <h4 class="text-h4">Produkty</h4>
        <p class="text-small text-muted">Zarządzanie katalogiem</p>
    </div>
</div>
```

---

## Layer System (Z-Index)

```css
/* Z-Index Layers - NEVER use arbitrary values */
.layer-base { z-index: 1; }
.layer-panel { z-index: 10; }
.layer-sticky { z-index: 20; }
.layer-modal { z-index: 100; }
.layer-overlay { z-index: 200; }
.layer-tooltip { z-index: 300; }

/* Development Only */
.layer-debug { z-index: 999; }
```

**Blade Usage:**
```blade
{{-- Modal system --}}
<div class="modal-overlay layer-overlay">
    <div class="modal-content layer-modal">
        Modal content
    </div>
</div>

{{-- Dropdown --}}
<div class="dropdown-menu layer-panel">
    Dropdown items
</div>

{{-- Sticky header --}}
<header class="admin-header layer-sticky">
    Header content
</header>
```

---

## Notification Toast

```css
.notification-toast {
    position: fixed;
    top: 20px;
    right: 20px;
    min-width: 300px;
    max-width: 500px;
    background: var(--bg-card);
    border-radius: 8px;
    padding: 16px;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.5);
    border-left: 4px solid var(--mpp-primary);
    z-index: var(--z-tooltip, 300);
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.notification-toast--success { border-left-color: var(--ppm-secondary); }
.notification-toast--error { border-left-color: var(--ppm-accent); }
.notification-toast--warning { border-left-color: var(--color-warning); }

.notification-toast__icon {
    font-size: 1.25rem;
    flex-shrink: 0;
}

.notification-toast__content {
    flex: 1;
}

.notification-toast__title {
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 4px;
}

.notification-toast__message {
    font-size: 0.875rem;
    color: var(--text-secondary);
}

.notification-toast__close {
    color: var(--text-muted);
    cursor: pointer;
    transition: color 0.2s;
}

.notification-toast__close:hover {
    color: var(--text-primary);
}
```

**Blade + Alpine.js:**
```blade
<div x-data="{ show: true }"
     x-show="show"
     x-transition
     class="notification-toast notification-toast--success">
    <div class="notification-toast__icon">
        <i class="fas fa-check-circle" style="color: var(--ppm-secondary);"></i>
    </div>
    <div class="notification-toast__content">
        <div class="notification-toast__title">Sukces!</div>
        <div class="notification-toast__message">Produkt został pomyślnie zapisany.</div>
    </div>
    <button @click="show = false" class="notification-toast__close">
        <i class="fas fa-times"></i>
    </button>
</div>
```

---

**Last Updated:** 2025-11-19
**Maintained By:** PPM Frontend Team
**Reference:** `_DOCS/PPM_Styling_Playbook.md` (sections 2-6)
