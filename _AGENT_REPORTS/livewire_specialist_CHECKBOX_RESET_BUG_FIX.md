# RAPORT: Livewire CategoryTree - Checkbox Reset Bug Fix

**Data**: 2025-12-09
**Typ**: Bug Fix - State Management
**Poziom**: CRITICAL - UX defect

---

## DIAGNOZA PROBLEMU

### Symptomy
- Po bulk delete kategorii, checkboxy pozostają "zaznaczone"
- Zaznaczenia przenoszą się na inne kategorie (indeksy się pomieszały)
- Stan `$selectedCategories` nie synchronizuje się z UI

### Root Cause Analysis

#### Problem 1: Brakujący wire:key na elementach listy
**Lokalizacja**: `resources/views/livewire/products/categories/category-tree-ultra-clean.blade.php:182`

```blade
@forelse($categories as $category)
    <tr class="...">
        <!-- BRAK wire:key! -->
        <td class="px-3 py-4">
            <input type="checkbox"
                   wire:click="toggleSelection({{ $category->id }})"
                   {{ in_array($category->id, $selectedCategories) ? 'checked' : '' }}
                   class="category-checkbox">
        </td>
    </tr>
@endforelse
```

**Dlaczego to problem (Livewire 3.x)**:
- Bez `wire:key`, Livewire używa pozycji w tablicy (indeksu) do identyfikacji elementu
- Gdy usuwamy kategorię z bazy, pozostałe przesuwają się na pozycje wyższych indeksów
- Checkboxy pozostają na tych samych pozycjach → mogą się "przeskoczyć" na inne kategorie
- Livewire 3.x wymaga `wire:key` dla prawidłowego DOM diffing

#### Problem 2: Array mutation w method toggleSelection
**Lokalizacja**: `app/Http/Livewire/Products/Categories/CategoryTree.php:481-488`

```php
public function toggleSelection(int $categoryId): void
{
    if (in_array($categoryId, $this->selectedCategories)) {
        $this->selectedCategories = array_diff($this->selectedCategories, [$categoryId]);
        // PROBLEM: array_diff() zwraca array z preserved keys!
        // Jeśli miałeś [1 => 5, 2 => 10], array_diff usuwając 5 zwraca [2 => 10]
        // Spowalnia reactivity jeśli keys są "sparse"
    } else {
        $this->selectedCategories[] = $categoryId;
    }
}
```

**Lepsze praktyki**:
- Powinno resetować keys po operacji: `array_values(array_diff(...))`
- Lub używać filtering z `array_filter()`

#### Problem 3: Brak wire:key w tbody
**Lokalizacja**: `category-tree-ultra-clean.blade.php:176`

```blade
<tbody class="..."
       style="overflow: visible !important;"
       @if($viewMode === 'tree')
           x-data="categoryDragDrop"
           x-init="initSortable()"
       @endif>
    <!-- Brak wire:key na tbody! -->
</tbody>
```

---

## LIVEWIRE 3.x BEST PRACTICES

### Wymagania dla list z checkboxami

**1. wire:key na repeating elements (MANDATORY)**
```blade
@foreach($items as $item)
    <tr wire:key="item-{{ $item->id }}">
        <td><input type="checkbox" wire:model="selected.{{ $item->id }}"></td>
    </tr>
@endforeach
```

**2. Array keys management**
```php
// ❌ BŁĘDY - sparse keys
$this->selected = array_diff($this->selected, [$id]); // Keys: [1=>5, 3=>15]

// ✅ POPRAWNIE - reset keys
$this->selected = array_values(array_diff($this->selected, [$id])); // Keys: [0=>5, 1=>15]

// ✅ POPRAWNIE - ALTERNATYWA z filter
$this->selected = array_values(array_filter($this->selected, fn($item) => $item !== $id));
```

**3. State synchronization pattern**
```php
// Po operacji usuwającej elementy:
public function bulkDelete(): void {
    // ... delete logic ...
    $this->selectedCategories = []; // ZAWSZE reset!
}

// ALTERNATYWA: update selected do istniejących
$deletedIds = [1, 5, 10];
$this->selectedCategories = array_values(
    array_filter($this->selectedCategories, fn($id) => !in_array($id, $deletedIds))
);
```

---

## PROPOSED FIXES

### Fix 1: Dodaj wire:key do tbody i tr

**Plik**: `resources/views/livewire/products/categories/category-tree-ultra-clean.blade.php`

**Linia 176 + 182** - Dodaj wire:key:

```blade
<tbody class="bg-gray-800 divide-y divide-gray-700 sortable-tbody"
       style="overflow: visible !important;"
       wire:key="category-list-{{ $viewMode }}"
       @if($viewMode === 'tree')
           x-data="categoryDragDrop"
           x-init="initSortable()"
       @endif>
    @forelse($categories as $category)
        <tr wire:key="category-row-{{ $category->id }}"
            class="transition-colors category-row {{ in_array($category->id, $selectedCategories) ? 'category-row-selected' : 'bg-gray-800 hover:bg-gray-700/50' }} {{ $viewMode === 'tree' && ($category->level ?? 0) > 0 ? 'category-level-border' : '' }}"
            data-category-id="{{ $category->id }}"
            data-level="{{ $category->level ?? 0 }}">
```

**Dlaczego**: Livewire będzie identyfikować elementy po ID, nie po pozycji

---

### Fix 2: Popraw toggleSelection w PHP

**Plik**: `app/Http/Livewire/Products/Categories/CategoryTree.php:481-488`

**PRZED:**
```php
public function toggleSelection(int $categoryId): void
{
    if (in_array($categoryId, $this->selectedCategories)) {
        $this->selectedCategories = array_diff($this->selectedCategories, [$categoryId]);
    } else {
        $this->selectedCategories[] = $categoryId;
    }
}
```

**PO:**
```php
/**
 * Select/deselect category for bulk operations
 *
 * @param int $categoryId
 */
public function toggleSelection(int $categoryId): void
{
    if (in_array($categoryId, $this->selectedCategories)) {
        // Remove category from selection
        // array_values() resets numeric keys (required for Livewire 3.x)
        $this->selectedCategories = array_values(
            array_filter($this->selectedCategories, fn($id) => $id !== $categoryId)
        );
    } else {
        // Add category to selection
        $this->selectedCategories[] = $categoryId;
    }
}
```

**Dlaczego:**
- `array_filter()` + `array_values()` zachowuje cleaner code path
- Gwarantuje ciągłe keys [0, 1, 2, ...] zamiast sparse [0, 2, 4, ...]
- Unika potencjalnych problemów z Livewire reactivity

---

### Fix 3: Optional - Refactor selectedCategories to array_map

**ALTERNATYWNY PATTERN (bardziej deklaratywny):**

```php
// Można również refaktorować na dedykowaną metodę:
public function isSelected(int $categoryId): bool
{
    return in_array($categoryId, $this->selectedCategories);
}

// I użyć w blade:
<input type="checkbox"
       wire:click="toggleSelection({{ $category->id }})"
       {{ $this->isSelected($category->id) ? 'checked' : '' }}
       class="category-checkbox">
```

**Korzyści:**
- Lepszy readability
- Cache-friendly (single source of truth)
- Ułatwia testing

---

## VALIDATION CHECKLIST

Przed/Po fixach sprawdzić:

### Bez Fix (AKTUALNE)
```
✅ Zaznaczam kategorie A, B, C
✅ Wciskam "Usuń wybrane"
❌ Po usunięciu - checkboxy na innych kategoriach są zaznaczone
❌ $selectedCategories zawiera stare IDs
```

### Z Fix
```
✅ Zaznaczam kategorie A, B, C
✅ Wciskam "Usuń wybrane"
✅ Po usunięciu - ŻADNE checkboxy nie są zaznaczone
✅ $selectedCategories = [] (pusty)
✅ Mogę od nowa zaznaczyć kategorie bez artefaktów
```

---

## IMPLEMENTACJA CHECKLIST

- [ ] **Fix 1**: Dodaj `wire:key="category-row-{{ $category->id }}"` do `<tr>`
- [ ] **Fix 1**: Dodaj `wire:key="category-list-{{ $viewMode }}"` do `<tbody>`
- [ ] **Fix 2**: Refaktoruj `toggleSelection()` - zmień `array_diff` na `array_filter`
- [ ] **Fix 2**: Dodaj `array_values()` do resetowania keys
- [ ] Deploy + Test w Chrome DevTools (sprawdzić brak wire:snapshot)
- [ ] Verify: Po bulk delete, checkboxy są czyste
- [ ] Verify: Można zaznaczyć pozostałe kategorie bez problemów

---

## REFERENCIAS

**Livewire 3.x Docs:**
- [Livewire Lists and Keys](https://livewire.laravel.com/docs/understanding-livewire)
- [Component Lifecycle - Key Binding](https://livewire.laravel.com/docs/lifecycle)

**Problem Pattern:**
- ISSUE: `_ISSUES_FIXES/LIVEWIRE_WIRE_SNAPSHOT_ISSUE.md` - Brak wire:key w lists
- PATTERN: ETAP05d Category System - pokazuje prawidłowe wire:key usage

---

## STATUS

🛠️ **READY FOR IMPLEMENTATION**

Kod fixów jest prosty i nie wymaga refaktoringu całego komponentu. Można implementować incrementally:
1. Najpierw wire:key (10 minut)
2. Potem toggleSelection refactor (5 minut)
3. Test (5 minut)
