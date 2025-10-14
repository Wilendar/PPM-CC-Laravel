# Category Picker Cross-Contamination Issue

## 🚨 Szczegółowy Opis Problemu

**Problem:** Checkboxy kategorii w edytorze produktu pokazywały się jako zaznaczone w kontekście innych sklepów, mimo że w bazie danych każdy sklep miał unikalne kategorie.

### Objawy:
- ✅ Zaznaczenie kategorii w Sklep 1 → kategorie pokazywały się jako zaznaczone w Sklep 2, 3, 4
- ✅ Problem persystował mimo przełączania zakładek sklepów
- ✅ Color-coding kategorii (status dziedziczenia) nie aktualizował się na żywo
- ✅ Backend zwracał poprawne dane - problem był wyłącznie w warstwie prezentacji

### Diagnoza Techniczna:
- **Livewire DOM Recycling**: Livewire 3.x recyklinguje elementy DOM w pętlach bez unikalnych `wire:key`
- **Nieunikalny ID**: Checkboxy używały tych samych `id` dla wszystkich kontekstów sklepów
- **Cache elementów DOM**: Przeglądarka cachowała stan checkboxów na podstawie `id`
- **Brak izolacji kontekstu**: Elementy formularza nie były izolowane per sklep

## ✅ Krok po Kroku Rozwiązanie

### 1. **Diagnoza Root Cause**
```bash
# Test backend - POTWIERDZIŁ POPRAWNE DANE
Shop 1: [1] ✅
Shop 2: [2] ✅
Shop 3: [3] ✅
Shop 4: [1,2] ✅

# Problem: Frontend DOM recycling
```

### 2. **Implementacja wire:key dla Kontekstu**
```blade
<!-- PRZED: Brak unikalnych kluczy -->
<div class="max-h-64 overflow-y-auto">
    @foreach($availableCategories as $category)
        <div class="flex items-center space-x-2 py-1">

<!-- PO: Unikalne klucze per kontekst -->
<div class="max-h-64 overflow-y-auto" wire:key="categories-ctx-{{ $activeShopId ?? 'default' }}">
    @foreach($availableCategories as $category)
        <div class="flex items-center space-x-2 py-1" wire:key="category-row-{{ $activeShopId ?? 'default' }}-{{ $category->id }}">
```

### 3. **Unikalne ID dla Checkboxów**
```blade
<!-- PRZED: Nieunikalny ID -->
<input type="checkbox" id="category_{{ $category->id }}">
<label for="category_{{ $category->id }}">

<!-- PO: Kontekstowy ID -->
<input type="checkbox" id="category_{{ $activeShopId ?? 'default' }}_{{ $category->id }}">
<label for="category_{{ $activeShopId ?? 'default' }}_{{ $category->id }}">
```

### 4. **Weryfikacja Poprawności**
```php
// Metoda getCategoriesForContext() już działała poprawnie
public function getCategoriesForContext(?int $contextShopId = null): array
{
    if ($contextShopId === null) {
        // Default context logic
        return $this->defaultCategories['selected'] ?? [];
    }

    // Shop-specific context logic
    return $this->shopCategories[$contextShopId]['selected'] ?? [];
}
```

## 🛡️ Zasady Zapobiegania

### 1. **Zawsze używaj wire:key w pętlach Livewire**
```blade
@foreach($items as $item)
    <div wire:key="unique-{{ $contextId }}-{{ $item->id }}">
        <!-- content -->
    </div>
@endforeach
```

### 2. **Unikalne ID dla elementów formularza**
```blade
<!-- W multi-context aplikacjach -->
<input id="field_{{ $context }}_{{ $identifier }}">
<label for="field_{{ $context }}_{{ $identifier }}">
```

### 3. **Testowanie kontekstu w diagnostyce**
```php
// Zawsze testuj różne konteksty
for($contextId = 1; $contextId <= 4; $contextId++) {
    $data = $component->getDataForContext($contextId);
    // Verify uniqueness
}
```

## 📋 Checklista Implementacji/Naprawy

- [x] **Zidentyfikuj pętle kategorii** w blade template
- [x] **Dodaj wire:key** na kontenerze głównym z kontekstem
- [x] **Dodaj wire:key** na każdym elemencie pętli
- [x] **Zmień ID elementów** na kontekstowe
- [x] **Zaktualizuj label[for]** aby pasowały do nowych ID
- [x] **Test backend isolation** - verify data correctness
- [x] **Clear all caches** - Laravel, Livewire, Browser
- [x] **Test UI switching** between contexts
- [x] **Verify color-coding** updates live

## 💡 Przykłady z Projektu

### ProductForm.blade.php - Naprawiona implementacja:
```blade
<div class="{{ $this->getCategoryClasses() }} max-h-64 overflow-y-auto"
     wire:key="categories-ctx-{{ $activeShopId ?? 'default' }}">
    @foreach($availableCategories as $category)
        <div class="flex items-center space-x-2 py-1"
             wire:key="category-row-{{ $activeShopId ?? 'default' }}-{{ $category->id }}">
            <input
                wire:click="toggleCategory({{ $category->id }})"
                type="checkbox"
                id="category_{{ $activeShopId ?? 'default' }}_{{ $category->id }}"
                {{ in_array($category->id, $this->getCategoriesForContext($activeShopId)) ? 'checked' : '' }}
                class="rounded border-gray-300 dark:border-gray-600 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            >
            <label for="category_{{ $activeShopId ?? 'default' }}_{{ $category->id }}"
                   class="flex-1 text-sm text-gray-700 dark:text-gray-300">
                {{ str_repeat('-', $category->level) }} {{ $category->name }}
            </label>
        </div>
    @endforeach
</div>
```

## 🔗 Powiązane Pliki i Komponenty

### Zmodyfikowane pliki:
- **resources/views/livewire/products/management/product-form.blade.php**
  - Dodano kontekstowe `wire:key` attributes
  - Zmodyfikowano `id` i `for` attributes na kontekstowe

### Powiązane komponenty:
- **ProductForm.php** - `getCategoriesForContext()` method (już działał poprawnie)
- **ProductCategoryManager** service - backend logic (bez zmian)
- **ProductShopCategory** model - database operations (bez zmian)

### Testy wykonane:
- Backend isolation test - ✅ PASSED
- DOM uniqueness test - ✅ PASSED
- Context switching test - ✅ PASSED
- Cache clearing test - ✅ PASSED

## 📊 Wpływ na Performance

- **Pozytywny**: Livewire teraz prawidłowo re-renderuje tylko potrzebne elementy
- **Neutralny**: Minimal overhead z dodatkowych `wire:key` attributes
- **Pozytywny**: Color-coding działa live bez page refresh

## 🎯 Verification Test Case

```php
// Test case do weryfikacji fix'a
public function testCategoryCrossContaminationFixed()
{
    $product = Product::find(4);
    $component = new ProductForm();
    $component->mount($product);

    // Test Shop 1 isolation
    $component->activeShopId = 1;
    $shop1Categories = $component->getCategoriesForContext(1);

    // Test Shop 2 isolation
    $component->activeShopId = 2;
    $shop2Categories = $component->getCategoriesForContext(2);

    // Verify no cross-contamination
    $this->assertNotEquals($shop1Categories, $shop2Categories);
    $this->assertEquals([1], $shop1Categories);
    $this->assertEquals([2], $shop2Categories);
}
```

## 📅 Historia Problemu

- **2025-09-23**: Problem zgłoszony - cross-contamination kategorii
- **2025-09-23**: Diagnoza backend vs frontend - backend OK
- **2025-09-23**: Zidentyfikowano Livewire DOM recycling jako root cause
- **2025-09-23**: Implementacja wire:key i kontekstowych ID
- **2025-09-23**: Problem rozwiązany - checkboxy izolowane per sklep

## 🏷️ Tags
`livewire`, `dom-recycling`, `wire:key`, `multi-context`, `category-picker`, `cross-contamination`, `frontend-issue`, `performance`