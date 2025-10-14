# LIVEWIRE 3.x EVENT SYSTEM - emit() → dispatch()

**Status**: ⚠️ ONGOING - Migracja wymagana w całym projekcie
**Priorytet**: KRYTYCZNY - powoduje błędy "Method emit does not exist"
**Typ**: Framework Migration / Breaking Change

## 🚨 OPIS PROBLEMU

Livewire 3.x usunęło metodę `emit()` i zastąpiło ją systemem `dispatch()`, co powoduje błędy w aplikacji migrowanej z Livewire 2.x.

### Objawy problemu
- ❌ `BadMethodCallException: Method emit does not exist`
- ❌ `BadMethodCallException: Method emitTo does not exist`
- ❌ `BadMethodCallException: Method emitSelf does not exist`
- ❌ `BadMethodCallException: Method emitUp does not exist`
- ❌ Brak komunikacji między komponentami Livewire

### Przykład błędnego kodu (Livewire 2.x)
```php
// ❌ STARE API - nie działa w Livewire 3.x
$this->emit('eventName', $data);
$this->emitTo('ComponentName', 'eventName', $data);
$this->emitSelf('eventName', $data);
$this->emitUp('eventName', $data);
```

## ✅ ROZWIĄZANIE - MIGRACJA EVENT SYSTEM

### Nowe API Livewire 3.x
```php
// ✅ NOWE API - Livewire 3.x
$this->dispatch('eventName', $data);
$this->dispatch('eventName', $data)->to('ComponentName');
$this->dispatch('eventName', $data)->self();
$this->dispatch('eventName', $data)->up();
```

## 🔄 WZORCE MIGRACJI

### Event do wszystkich komponentów
```php
// PRZED (Livewire 2.x)
$this->emit('refreshShops');
$this->emit('shopUpdated', ['shopId' => $id]);

// PO (Livewire 3.x)
$this->dispatch('refreshShops');
$this->dispatch('shopUpdated', ['shopId' => $id]);
```

### Event do konkretnego komponentu
```php
// PRZED
$this->emitTo('ShopList', 'refresh');
$this->emitTo('ProductManager', 'productCreated', $productData);

// PO
$this->dispatch('refresh')->to('ShopList');
$this->dispatch('productCreated', $productData)->to('ProductManager');
```

### Event do samego siebie
```php
// PRZED
$this->emitSelf('updated');
$this->emitSelf('validationCompleted');

// PO
$this->dispatch('updated')->self();
$this->dispatch('validationCompleted')->self();
```

### Event do parent komponentu
```php
// PRZED
$this->emitUp('childUpdated');
$this->emitUp('modalClosed', ['result' => $data]);

// PO
$this->dispatch('childUpdated')->up();
$this->dispatch('modalClosed', ['result' => $data])->up();
```

## 🔍 ZNAJDOWANIE I NAPRAWIANIE

### Komendy diagnostyczne
```bash
# Znajdź wszystkie emit() w projekcie
grep -r "\$this->emit" app/Http/Livewire/
grep -r "emitTo\|emitSelf\|emitUp" app/Http/Livewire/

# Sprawdź na serwerze
grep -r "emit(" domains/ppm.mpptrade.pl/public_html/app/Http/Livewire/

# Sprawdź w blade templates
grep -r "emit(" resources/views/livewire/
```

### PowerShell search (Windows)
```powershell
# Znajdź pliki z emit()
Get-ChildItem -Path "app\Http\Livewire" -Recurse -Include "*.php" | Select-String -Pattern "\$this->emit"

# Znajdź wszystkie warianty emit
Get-ChildItem -Path "app\Http\Livewire" -Recurse -Include "*.php" | Select-String -Pattern "emit(To|Self|Up)?"
```

## 🛠️ MASOWE ZASTĘPOWANIE

### Regex patterns dla IDE
```regex
# Znajdź
\$this->emit\('([^']+)'(.*?)\)

# Zamień na
$this->dispatch('$1'$2)

# Znajdź emitTo
\$this->emitTo\('([^']+)',\s*'([^']+)'(.*?)\)

# Zamień na
$this->dispatch('$2'$3)->to('$1')

# Znajdź emitSelf
\$this->emitSelf\('([^']+)'(.*?)\)

# Zamień na
$this->dispatch('$1'$2)->self()

# Znajdź emitUp
\$this->emitUp\('([^']+)'(.*?)\)

# Zamień na
$this->dispatch('$1'$2)->up()
```

## 📋 PRZYKŁADY MIGRACJI Z PROJEKTU

### ShopManager.php
```php
// PRZED
public function deleteShop($shopId)
{
    // Delete logic...
    $this->emit('shopDeleted', ['shopId' => $shopId]);
    $this->emitTo('ShopList', 'refresh');
}

// PO
public function deleteShop($shopId)
{
    // Delete logic...
    $this->dispatch('shopDeleted', ['shopId' => $shopId]);
    $this->dispatch('refresh')->to('ShopList');
}
```

### ProductForm.php
```php
// PRZED
public function saveProduct()
{
    // Save logic...
    $this->emitSelf('productSaved');
    $this->emitUp('formSubmitted', ['productId' => $this->product->id]);
}

// PO
public function saveProduct()
{
    // Save logic...
    $this->dispatch('productSaved')->self();
    $this->dispatch('formSubmitted', ['productId' => $this->product->id])->up();
}
```

### Modal Components
```php
// PRZED
public function closeModal()
{
    $this->showModal = false;
    $this->emitUp('modalClosed');
    $this->emit('refreshParent');
}

// PO
public function closeModal()
{
    $this->showModal = false;
    $this->dispatch('modalClosed')->up();
    $this->dispatch('refreshParent');
}
```

## 📋 CHECKLIST MIGRACJI

- [ ] Wyszukaj wszystkie `$this->emit(` w projekcie
- [ ] Zastąp `emit()` → `dispatch()`
- [ ] Zastąp `emitTo()` → `dispatch()->to()`
- [ ] Zastąp `emitSelf()` → `dispatch()->self()`
- [ ] Zastąp `emitUp()` → `dispatch()->up()`
- [ ] Przetestuj komunikację między komponentami
- [ ] Sprawdź czy eventy są odbierane (listeners)
- [ ] Upewnij się że nie ma błędów w console
- [ ] Przetestuj na serwerze

## 🔍 LISTENERY - BEZ ZMIAN

Event listeners pozostają bez zmian:
```php
// Słuchanie eventów - bez zmian w Livewire 3.x
protected $listeners = [
    'refreshShops' => 'loadShops',
    'shopUpdated' => 'handleShopUpdate',
    'productCreated' => 'refreshProductList'
];

// Lub dynamicznie
public function getListeners()
{
    return [
        'refreshData' => 'loadData',
        "shopDeleted.{$this->shopId}" => 'handleShopDeletion'
    ];
}
```

## 💡 DODATKOWE ZMIANY W LIVEWIRE 3.x

### JavaScript Events
```javascript
// PRZED (Livewire 2.x)
Livewire.emit('eventName', data);

// PO (Livewire 3.x)
Livewire.dispatch('eventName', data);
```

### Alpine.js Integration
```javascript
// PRZED
<button x-on:click="$wire.emit('buttonClicked')">

// PO
<button x-on:click="$wire.dispatch('buttonClicked')">
```

## 🎯 PRIORYTETY MIGRACJI

### Krytyczne (natychmiast)
1. `ShopManager.php` - zarządzanie sklepami
2. `ProductForm.php` - tworzenie/edycja produktów
3. `AddShop.php` - kreator sklepów

### Wysokie (w najbliższym czasie)
1. `ERPManager.php` - integracje zewnętrzne
2. `BackupManager.php` - operacje backup
3. Modal components w `resources/views/livewire/`

### Średnie (po krytycznych)
1. Pozostałe komponenty admin panelu
2. Notification components
3. Search/filter components

## 🔗 POWIĄZANE PLIKI

**Do sprawdzenia i migracji:**
- `app/Http/Livewire/**/*.php` - wszystkie komponenty
- `resources/views/livewire/**/*.blade.php` - templates z JS events
- `resources/js/` - JavaScript Livewire calls

**Priorytetowe komponenty:**
- `ShopManager.php` - ✅ już naprawione
- `ProductForm.php` - wymaga sprawdzenia
- `AddShop.php` - wymaga migracji
- `ERPManager.php` - wymaga migracji

## 🚀 AUTOMATYZACJA MIGRACJI

### PowerShell script do mass replacement
```powershell
$files = Get-ChildItem -Path "app\Http\Livewire" -Recurse -Include "*.php"

foreach ($file in $files) {
    $content = Get-Content $file.FullName -Raw

    # Replace patterns
    $content = $content -replace '\$this->emit\(', '$this->dispatch('
    $content = $content -replace '\$this->emitTo\(([^,]+),\s*', '$this->dispatch('
    $content = $content -replace '\$this->emitSelf\(', '$this->dispatch('
    $content = $content -replace '\$this->emitUp\(', '$this->dispatch('

    Set-Content -Path $file.FullName -Value $content
}
```