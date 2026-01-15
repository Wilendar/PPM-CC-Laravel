# SKU Paste Modal - Refactoring z Two-Column Mode

**Data:** 2025-12-08
**ETAP:** 06 - System Importu Produktów
**FAZA:** 3 (Single-column) + 4 (Two-column mode)

---

## 📋 PODSUMOWANIE ZMIAN

### Główne zmiany:
1. ✅ Split SKUPasteModal na 3 pliki (główny + 2 traits)
2. ✅ Dodano two-column mode (SKU | Names oddzielnie)
3. ✅ Nowe properties: `$viewMode`, `$rawSkuInput`, `$rawNameInput`, `$separatorMode`, `$viewModeWarnings`
4. ✅ Zachowano backward compatibility (single-column mode działa jak poprzednio)

---

## 📁 STRUKTURA PLIKÓW

### Przed refactoringiem:
```
app/Http/Livewire/Products/Import/Modals/
└── SKUPasteModal.php (349 linii)
```

### Po refactoringu:
```
app/Http/Livewire/Products/Import/Modals/
├── SKUPasteModal.php (318 linii)
└── Traits/
    ├── SkuPasteParsingTrait.php (280 linii)
    └── SkuPasteViewModeTrait.php (178 linii)

TOTAL: 776 linii (było 349 - wzrost o 427 linii z powodu nowych features)
```

---

## 🔧 ZMIANY W SKUPasteModal.php

### Dodane properties:
```php
// View mode control
public string $viewMode = 'single_column';          // 'single_column' | 'two_columns'

// Two-column mode inputs
public string $rawSkuInput = '';                    // SKU textarea (two-column)
public string $rawNameInput = '';                   // Names textarea (two-column)

// View mode warnings
public array $viewModeWarnings = [];                // Count mismatch warnings

// Separator mode (future enhancement)
public string $separatorMode = 'auto';              // 'auto' | 'newline' | 'multi'
```

### Dodane traits:
```php
use SkuPasteParsingTrait;      // Parsing logic (single + two-column)
use SkuPasteViewModeTrait;     // View mode switching + helpers
```

### Zmodyfikowane metody:
- `resetState()`: Reset nowych properties
- `import()`: Loguje `view_mode` w Log::info

### Usunięte metody (przeniesione do traits):
- `parseInput()` → SkuPasteParsingTrait
- `updatedImportMode()` → SkuPasteParsingTrait
- `updatedSeparator()` → SkuPasteParsingTrait
- `resetParseResults()` → SkuPasteParsingTrait

---

## 🧩 NOWE TRAITS

### 1. SkuPasteParsingTrait (280 linii)

**Odpowiedzialność:**
- Parsowanie single-column input (`parseInput()`)
- Parsowanie two-column input (`parseInputTwoColumn()`)
- Watchers dla zmian inputu (`updatedRawInput()`, `updatedRawSkuInput()`, `updatedRawNameInput()`)
- Walidacja przeciwko istniejącym produktom (`applyParseResults()`)
- Reset parsowania (`resetParseResults()`)

**Kluczowe metody:**

```php
// Single-column parsing (existing logic)
public function parseInput(): void

// Two-column parsing (NEW)
public function parseInputTwoColumn(): void

// Watchers
public function updatedRawInput(): void
public function updatedRawSkuInput(): void
public function updatedRawNameInput(): void
public function updatedImportMode(): void
public function updatedSeparator(): void

// Helpers
protected function applyParseResults(array $result): void
protected function resetParseResults(): void
```

**Logika two-column parsing:**
1. Split SKU input na linie (`$parserService->splitLines()`)
2. Split Name input na linie
3. Check count mismatch → `$viewModeWarnings`
4. Pair SKU[i] z Name[i]
5. Validate SKU format
6. Check duplicates
7. Apply results (`applyParseResults()`)

---

### 2. SkuPasteViewModeTrait (178 linii)

**Odpowiedzialność:**
- Przełączanie między single-column a two-column mode (`updatedViewMode()`)
- Placeholder texts dla textarea (`getPlaceholderText()`)
- Count helpers dla SKU/Names (`getSkuCount()`, `getNameCount()`)
- Count mismatch detection (`hasCountMismatch()`, `getCountMismatchMessage()`)
- Switch mode warnings (`canSwitchToTwoColumn()`, `getSwitchModeWarning()`)

**Kluczowe metody:**

```php
// View mode switching
public function updatedViewMode(): void

// Placeholder helpers
public function getPlaceholderText(string $field = 'single'): string

// Count helpers
public function getSkuCount(): int
public function getNameCount(): int
public function hasCountMismatch(): bool
public function getCountMismatchMessage(): ?string

// UI helpers
public function getViewModeLabel(): string
public function canSwitchToTwoColumn(): bool
public function getSwitchModeWarning(): ?string
```

**Logika przełączania trybów:**

```php
// Single → Two columns
if ($this->viewMode === 'two_columns') {
    $this->rawSkuInput = $this->rawInput;  // Move data to SKU textarea
    $this->rawNameInput = '';               // Empty names
    $this->rawInput = '';                   // Clear single-column
    $this->importMode = 'sku_name';         // Force SKU + Name mode
}

// Two → Single column
if ($this->viewMode === 'single_column') {
    $this->rawInput = $this->rawSkuInput;   // Copy SKU back
    $this->rawSkuInput = '';
    $this->rawNameInput = '';
    $this->importMode = 'sku_only';         // Force SKU only mode
}
```

---

## 🆕 NOWE FEATURES

### Feature 1: Two-Column Mode

**Use Case:** Użytkownik ma SKU w jednej kolumnie Excel, nazwy w drugiej

**Workflow:**
1. User przełącza `$viewMode` → `'two_columns'`
2. Modal pokazuje 2 textarea: SKU (lewa) | Names (prawa)
3. User wkleja SKU do lewej, nazwy do prawej
4. System paruje SKU[i] z Name[i]
5. Warning jeśli count mismatch

**Przykład:**

```
SKU Textarea:          Names Textarea:
SKU001                 Nazwa produktu 1
SKU002                 Nazwa produktu 2
SKU003                 Nazwa produktu 3

Result:
[
  {sku: 'SKU001', name: 'Nazwa produktu 1'},
  {sku: 'SKU002', name: 'Nazwa produktu 2'},
  {sku: 'SKU003', name: 'Nazwa produktu 3'}
]
```

**Count Mismatch Handling:**

```
SKU: 5 linii, Names: 3 linie
→ Warning: "Liczba SKU (5) nie zgadza się z liczbą nazw (3)"
→ Result: SKU[3] i SKU[4] mają name = null
```

---

### Feature 2: View Mode Switching

**Single → Two:**
- Kopiuje `$rawInput` → `$rawSkuInput`
- Czyści `$rawNameInput`
- Ustawia `$importMode = 'sku_name'`
- Trigger `parseInputTwoColumn()`

**Two → Single:**
- Kopiuje `$rawSkuInput` → `$rawInput`
- Czyści `$rawSkuInput`, `$rawNameInput`
- Ustawia `$importMode = 'sku_only'`
- Trigger `parseInput()`

**Warning System:**
- `getSwitchModeWarning()`: Ostrzega przed utratą danych przy przełączeniu
- `canSwitchToTwoColumn()`: Sprawdza, czy można bezpiecznie przełączyć

---

## 🔄 BACKWARD COMPATIBILITY

### Zachowane zachowanie (single-column mode):
✅ `parseInput()` działa jak poprzednio
✅ `updatedImportMode()` działa jak poprzednio
✅ `updatedSeparator()` działa jak poprzednio
✅ `import()` tworzy PendingProduct jak poprzednio
✅ Wszystkie watchers zachowane

### Nowe zachowanie (two-column mode):
✨ `parseInputTwoColumn()` - nowa logika
✨ `updatedRawSkuInput()` / `updatedRawNameInput()` - nowe watchers
✨ Count mismatch warnings
✨ View mode switching

---

## 📊 STATYSTYKI KODU

| Plik | Linie | Metody | Odpowiedzialność |
|------|-------|--------|------------------|
| **SKUPasteModal.php** | 318 | 8 | Main component, import logic, rendering |
| **SkuPasteParsingTrait.php** | 280 | 8 | Parsing (single + two-column), watchers |
| **SkuPasteViewModeTrait.php** | 178 | 9 | View mode switching, helpers, warnings |
| **TOTAL** | **776** | **25** | - |

**Przed refactoringiem:** 349 linii (1 plik)
**Po refactoringu:** 776 linii (3 pliki)
**Wzrost:** +427 linii (122% increase z powodu nowych features)

---

## 🎯 INTEGRACJA Z SkuParserService

### Istniejące metody (użyte):
```php
$parserService->parse($input, $mode, $separator)          // Single-column
$parserService->splitLines($input)                        // Line splitting
$parserService->validateSKUFormat($sku)                   // SKU validation
$parserService->checkDuplicatesInBatch($skus)             // Duplicate detection
$parserService->validateAgainstExisting($skus)            // Conflict checking
```

### Przyszłe enhancement (TODO):
```php
// FUTURE: Add to SkuParserService
$parserService->parseTwoColumn($skuInput, $nameInput)
// Dedykowana metoda dla two-column parsing
```

---

## 🧪 TESTING CHECKLIST

### Single-column mode (regression):
- [ ] Wklejenie listy SKU (sku_only mode)
- [ ] Wklejenie SKU + Nazwy z tabulatorem (sku_name mode)
- [ ] Auto-detekcja separatora
- [ ] Wykrywanie duplikatów w batch
- [ ] Walidacja SKU format
- [ ] Konflikty z istniejącymi produktami
- [ ] Import do PendingProduct

### Two-column mode (new):
- [ ] Przełączenie single → two-column
- [ ] Wklejenie SKU + Names (równa liczba linii)
- [ ] Count mismatch warning (SKU: 5, Names: 3)
- [ ] SKU bez nazwy (name = null)
- [ ] Przełączenie two → single (kopiowanie SKU)
- [ ] Parsing w two-column mode
- [ ] Import z two-column mode

### Edge cases:
- [ ] Puste textarea (single + two)
- [ ] Tylko SKU, bez Names (two-column)
- [ ] Tylko Names, bez SKU (powinno być ignored)
- [ ] Przełączanie trybów z danymi
- [ ] Przełączanie trybów bez danych

---

## 📝 NOTATKI IMPLEMENTACYJNE

### Debounce w Blade:
```blade
{{-- Single-column --}}
<textarea wire:model.live.debounce.500ms="rawInput"></textarea>

{{-- Two-column --}}
<textarea wire:model.live.debounce.500ms="rawSkuInput"></textarea>
<textarea wire:model.live.debounce.500ms="rawNameInput"></textarea>
```

### View mode switcher (UI):
```blade
<select wire:model.live="viewMode">
    <option value="single_column">Jedna kolumna</option>
    <option value="two_columns">Dwie kolumny (SKU | Nazwy)</option>
</select>

@if($this->hasCountMismatch())
    <div class="alert alert-warning">
        {{ $this->getCountMismatchMessage() }}
    </div>
@endif
```

### Warnings display:
```blade
{{-- View mode warnings (count mismatch) --}}
@foreach($viewModeWarnings as $warning)
    <div class="alert alert-warning">
        {{ $warning['message'] }}
    </div>
@endforeach

{{-- Parse warnings (duplicates, conflicts) --}}
@foreach($warnings as $warning)
    <div class="alert alert-info">
        {{ $warning['message'] }}
    </div>
@endforeach
```

---

## 🚀 FUTURE ENHANCEMENTS

1. **Separator mode for two-column:**
   - Currently: Newline-based tylko
   - Future: Multi-separator (CSV w każdej kolumnie)

2. **Drag-and-drop Excel:**
   - Drop XLSX → auto-extract columns → fill two-column mode

3. **Column mapping:**
   - User wybiera, która kolumna to SKU, która to Name

4. **Preview table:**
   - Pokaż paired SKU + Name w tabeli przed importem

5. **Bulk edit names:**
   - Edit nazw przed importem (w two-column mode)

---

## ✅ CHECKLIST DEPLOYMENTU

- [ ] Backup obecnego SKUPasteModal.php
- [ ] Deploy 3 pliki (main + 2 traits)
- [ ] Clear autoloader cache: `composer dump-autoload`
- [ ] Test single-column mode (regression)
- [ ] Test two-column mode (new feature)
- [ ] Update Blade view (dodać UI dla viewMode switcher)
- [ ] Update dokumentację użytkownika

---

## 📖 DOKUMENTACJA UŻYTKOWNIKA

### Tryb jedna kolumna (Single-column):

**Use Case:** SKU + Nazwy w jednej kolumnie z separatorem

**Format:**
```
SKU001    Nazwa produktu 1
SKU002    Nazwa produktu 2
```

**Kroki:**
1. Wybierz tryb: "Jedna kolumna"
2. Wybierz import mode: "SKU + Nazwa"
3. Wybierz separator (lub Automatyczny)
4. Wklej dane
5. Sprawdź preview
6. Kliknij "Importuj"

---

### Tryb dwie kolumny (Two-column):

**Use Case:** SKU w jednej kolumnie Excel, nazwy w drugiej

**Format:**
```
SKU Textarea:          Names Textarea:
SKU001                 Nazwa produktu 1
SKU002                 Nazwa produktu 2
```

**Kroki:**
1. Wybierz tryb: "Dwie kolumny (SKU | Nazwy)"
2. Wklej SKU do lewej textarea
3. Wklej nazwy do prawej textarea
4. System automatycznie paruje SKU[i] z Name[i]
5. Sprawdź warning jeśli count mismatch
6. Sprawdź preview
7. Kliknij "Importuj"

**Warning:** Jeśli liczba SKU ≠ liczba Names → SKU bez Name dostaną `null`

---

## 🔗 RELATED FILES

- `app/Services/Import/SkuParserService.php` - Parsing service
- `app/Models/PendingProduct.php` - Target model
- `app/Models/ImportSession.php` - Session tracking
- `resources/views/livewire/products/import/modals/sku-paste-modal.blade.php` - View (TODO: update)

---

**KONIEC DOKUMENTACJI**
