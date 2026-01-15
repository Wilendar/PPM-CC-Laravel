# RAPORT ARCHITEKTONICZNY: Rozbudowa SKUPasteModal

**Data:** 2025-12-08
**Agent:** architect
**ETAP:** ETAP_06 FAZA 3 - Rozszerzenie Import SKU
**Zadanie:** Zaprojektowanie rozbudowy modalu "Wklej liste SKU" zgodnie z wymaganiami użytkownika

---

## STRESZCZENIE WYKONAWCZE

Modal `SKUPasteModal` wymaga rozbudowy o dwie kluczowe funkcjonalności:

**A) Inteligentne wykrywanie separatorów w trybie "Tylko SKU":**
- Obsługa SKU w jednym wierszu (przecinki, średniki, spacje, tabulatory)
- Obsługa mieszana (część w linii, część w nowych wierszach)
- Automatyczne wykrywanie użytego separatora

**B) Tryb dwukolumnowy dla "SKU + Nazwa":**
- Przełącznik widoku: "Jedna kolumna" / "Dwie kolumny"
- Dwie niezależne textarea (SKU | Nazwy)
- Automatyczne parowanie według pozycji linii
- Walidacja zgodności liczby SKU vs liczby nazw

**Zgodność:** PPM_Styling_Playbook.md, CLAUDE.md (max 300 linii), istniejące klasy CSS

---

## OBECNY STAN MODALU

### Analiza istniejącego kodu

**Lokalizacja:**
- Component: `app/Http/Livewire/Products/Import/Modals/SKUPasteModal.php` (348 linii)
- View: `resources/views/livewire/products/import/modals/sku-paste-modal.blade.php` (261 linii)
- Service: `app/Services/Import/SkuParserService.php` (493 linii)

**Obecna funkcjonalność:**
1. Tryb "Tylko SKU" - jeden SKU na linię (SZTYWNY)
2. Tryb "SKU + Nazwa" - format "SKU;Nazwa" w jednej linii
3. Separator dropdown (auto, tab, semicolon, comma, pipe)
4. Real-time parsing z debounce 500ms
5. Preview rozpoznanych SKU
6. Walidacja duplikatów (batch, PPM, pending)
7. Statystyki (total_lines, valid_items, duplicates)

**Ograniczenia:**
- ❌ Tryb "Tylko SKU" wymaga jeden SKU na linię (separator dropdown nieaktywny)
- ❌ Tryb "SKU + Nazwa" wymusza jedną textarea z separatorem
- ❌ Brak obsługi SKU w jednym wierszu oddzielonych przecinkami/spacjami
- ❌ Brak trybu dwukolumnowego (SKU | Nazwy osobno)

---

## ARCHITEKTURA ROZWIĄZANIA

### 1. NOWY TRYB PARSOWANIA: "Inteligentne separatory"

#### 1.1 Zmiany w SkuParserService

**NOWA METODA: `parseSkuOnlyMultiSeparator()`**

```php
/**
 * Parsowanie SKU z obsługą wielu separatorów (spacjami, przecinkami, średnikami, tab)
 *
 * Obsługuje:
 * - SKU001, SKU002, SKU003
 * - SKU001; SKU002; SKU003
 * - SKU001 SKU002 SKU003
 * - SKU001\nSKU002
 * - Mieszane: SKU001, SKU002\nSKU003
 *
 * @param array<string> $lines Linie wejściowe
 * @return array<array{sku: string, name: string|null, line: int}>
 */
public function parseSkuOnlyMultiSeparator(array $lines): array
{
    $items = [];
    $globalLineNumber = 0;

    // Regex dla separatorów: przecinki, średniki, spacje (2+), tabulatory
    $separatorPattern = '/[,;\s\t]+/';

    foreach ($lines as $lineIndex => $line) {
        $globalLineNumber = $lineIndex + 1;
        $line = trim($line);

        if ($line === '') {
            continue;
        }

        // Rozdziel linię na tokeny używając regex
        $tokens = preg_split($separatorPattern, $line, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($tokens as $token) {
            $sku = trim($token);

            if ($sku === '') {
                continue;
            }

            $items[] = [
                'sku' => $sku,
                'name' => null,
                'line' => $globalLineNumber,
            ];
        }
    }

    return $items;
}
```

**REFACTORING: `parseSkuOnly()` → używaj gdy separator === 'newline'**

```php
public function parse(string $input, string $mode = 'sku_only', string $separator = 'auto'): array
{
    // ...existing code...

    // Parsowanie w zależności od trybu
    $parsedItems = ($mode === 'sku_only')
        ? $this->parseSkuOnlyIntelligent($lines, $separator) // NOWA METODA
        : $this->parseSkuName($lines, $detectedSeparator);

    // ...rest of code...
}

/**
 * Inteligentne parsowanie SKU (newline vs multi-separator)
 */
private function parseSkuOnlyIntelligent(array $lines, string $separator): array
{
    // Jeśli separator ustawiony na 'newline' (nowa opcja) → używaj starej metody
    if ($separator === 'newline') {
        return $this->parseSkuOnly($lines);
    }

    // W przeciwnym razie → inteligentne parsowanie
    return $this->parseSkuOnlyMultiSeparator($lines);
}
```

**NOWA STAŁA: Separator "newline"**

```php
public const SEPARATORS = [
    'auto' => 'Automatyczny',
    'newline' => 'Nowa linia (jeden SKU na linię)',
    'multi' => 'Wykryj separatory (przecinki, średniki, spacje)',
    'tab' => 'Tabulator',
    'semicolon' => 'Średnik (;)',
    'comma' => 'Przecinek (,)',
    'pipe' => 'Pionowa kreska (|)',
];
```

**REFACTORING: `detectSeparator()` dla trybu SKU**

```php
public function detectSeparator(string $input, string $mode = 'sku_only'): string
{
    if ($mode === 'sku_only') {
        // Sprawdź czy są przecinki, średniki, spacje w liniach
        $hasInlineSeparators = $this->hasInlineSeparators($input);

        if ($hasInlineSeparators) {
            return 'multi'; // Użyj multi-separator parsing
        }

        return 'newline'; // Użyj one-per-line parsing
    }

    // Existing logic for sku_name mode
    // ...
}

private function hasInlineSeparators(string $input): bool
{
    $lines = $this->splitLines($input);
    $sampleSize = min(10, count($lines));

    foreach (array_slice($lines, 0, $sampleSize) as $line) {
        // Sprawdź czy linia ma więcej niż 1 potencjalny SKU
        if (preg_match('/[,;\s\t]+/', $line)) {
            return true;
        }
    }

    return false;
}
```

**KLUCZOWE ZMIANY:**
- ✅ `parseSkuOnlyMultiSeparator()` - parsuje SKU z różnych separatorów w jednej linii
- ✅ `parseSkuOnlyIntelligent()` - wybiera algorytm (newline vs multi)
- ✅ `detectSeparator()` - wykrywa czy SKU są inline czy per-line
- ✅ `hasInlineSeparators()` - heurystyka separatorów
- ✅ Nowa opcja 'newline' i 'multi' w dropdown separatorów

---

### 2. NOWY TRYB UI: Dwukolumnowy dla "SKU + Nazwa"

#### 2.1 Zmiany w SKUPasteModal.php

**NOWE PROPERTIES:**

```php
/**
 * Tryb widoku dla SKU + Nazwa: 'single_column' lub 'two_columns'
 */
public string $viewMode = 'single_column';

/**
 * Surowe dane SKU (dla trybu dwukolumnowego)
 */
public string $rawSkuInput = '';

/**
 * Surowe dane Nazw (dla trybu dwukolumnowego)
 */
public string $rawNameInput = '';

/**
 * Ostrzeżenia walidacji (np. liczba SKU ≠ liczba nazw)
 */
public array $viewModeWarnings = [];
```

**NOWE METODY:**

```php
/**
 * Callback when viewMode changes
 */
public function updatedViewMode(): void
{
    $this->parseInput();
}

/**
 * Callback when rawSkuInput changes (two-column mode)
 */
public function updatedRawSkuInput(): void
{
    if ($this->viewMode === 'two_columns') {
        $this->parseInputTwoColumn();
    }
}

/**
 * Callback when rawNameInput changes (two-column mode)
 */
public function updatedRawNameInput(): void
{
    if ($this->viewMode === 'two_columns') {
        $this->parseInputTwoColumn();
    }
}

/**
 * Parsowanie dla trybu dwukolumnowego
 */
protected function parseInputTwoColumn(): void
{
    $this->viewModeWarnings = [];

    // Parse SKU column
    $skuResult = $this->parserService->parse(
        $this->rawSkuInput,
        'sku_only',
        'multi' // Inteligentne separatory
    );

    // Parse Nazwy column (jedna nazwa na linię)
    $nameLines = $this->parserService->splitLines($this->rawNameInput);

    // Walidacja zgodności liczby
    $skuCount = count($skuResult['items']);
    $nameCount = count($nameLines);

    if ($skuCount !== $nameCount && $skuCount > 0 && $nameCount > 0) {
        $this->viewModeWarnings[] = [
            'type' => 'count_mismatch',
            'message' => "Liczba SKU ({$skuCount}) różni się od liczby nazw ({$nameCount}). Parowanie według pozycji może być nieprawidłowe.",
        ];
    }

    // Parowanie SKU + Nazwa według indeksu
    $pairedItems = [];
    $maxCount = max($skuCount, $nameCount);

    for ($i = 0; $i < $maxCount; $i++) {
        $item = $skuResult['items'][$i] ?? null;
        $name = $nameLines[$i] ?? null;

        if ($item) {
            $item['name'] = $name ?: null;
            $pairedItems[] = $item;
        } elseif ($name) {
            // Nazwa bez SKU - błąd
            $this->errors[] = [
                'line' => $i + 1,
                'message' => "Nazwa bez SKU: \"{$name}\"",
            ];
        }
    }

    // Użyj sparowanych danych
    $this->parsedItems = $pairedItems;
    $this->errors = array_merge($this->errors, $skuResult['errors']);
    $this->warnings = array_merge($this->warnings, $skuResult['warnings']);
    $this->stats = $skuResult['stats'];

    // Sprawdź konflikty z bazą
    $this->validateAgainstDatabase();
}

/**
 * Refactored parseInput() - routing logic
 */
public function parseInput(): void
{
    if (empty(trim($this->rawInput)) && $this->viewMode === 'single_column') {
        $this->resetParseResults();
        return;
    }

    if ($this->viewMode === 'two_columns') {
        $this->parseInputTwoColumn();
        return;
    }

    // Existing single-column parsing logic
    $result = $this->parserService->parse(
        $this->rawInput,
        $this->importMode,
        $this->separator
    );

    $this->parsedItems = $result['items'];
    $this->errors = $result['errors'];
    $this->warnings = $result['warnings'];
    $this->stats = $result['stats'];

    $this->validateAgainstDatabase();
}

/**
 * Extract database validation to separate method
 */
protected function validateAgainstDatabase(): void
{
    if (empty($this->parsedItems)) {
        return;
    }

    $skus = array_column($this->parsedItems, 'sku');
    $conflicts = $this->parserService->validateAgainstExisting($skus);

    $this->existingInPPM = $conflicts['in_ppm'];
    $this->existingInPending = $conflicts['in_pending'];

    // Add warnings for conflicts (existing code)
    // ...
}
```

**RESET STATE:**

```php
#[On('openSkuModal')]
public function resetState(): void
{
    $this->reset([
        'rawInput',
        'rawSkuInput',
        'rawNameInput',
        'parsedItems',
        'errors',
        'warnings',
        'viewModeWarnings',
        'stats',
        'existingInPPM',
        'existingInPending',
        'isProcessing',
    ]);
    $this->importMode = 'sku_only';
    $this->separator = 'auto';
    $this->viewMode = 'single_column';
}
```

---

#### 2.2 Zmiany w sku-paste-modal.blade.php

**NOWA SEKCJA: View Mode Toggle (dla trybu SKU + Nazwa)**

```blade
{{-- Import Mode Radio --}}
<div class="mb-4">
    <label class="block text-sm font-medium text-gray-300 mb-2">Tryb importu</label>
    <div class="flex gap-4">
        @foreach($importModes as $mode => $label)
            <label class="inline-flex items-center cursor-pointer">
                <input type="radio"
                       wire:model.live="importMode"
                       value="{{ $mode }}"
                       class="form-radio-enterprise">
                <span class="ml-2 text-sm text-gray-300">{{ $label }}</span>
            </label>
        @endforeach
    </div>
</div>

{{-- View Mode Toggle (tylko dla SKU + Nazwa) --}}
@if($importMode === 'sku_name')
    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-300 mb-2">Widok</label>
        <div class="flex gap-4">
            <label class="inline-flex items-center cursor-pointer">
                <input type="radio"
                       wire:model.live="viewMode"
                       value="single_column"
                       class="form-radio-enterprise">
                <span class="ml-2 text-sm text-gray-300">Jedna kolumna (SKU;Nazwa)</span>
            </label>
            <label class="inline-flex items-center cursor-pointer">
                <input type="radio"
                       wire:model.live="viewMode"
                       value="two_columns"
                       class="form-radio-enterprise">
                <span class="ml-2 text-sm text-gray-300">Dwie kolumny (SKU | Nazwy)</span>
            </label>
        </div>
    </div>
@endif
```

**NOWA SEKCJA: Separator Dropdown (tylko dla trybu SKU Only)**

```blade
{{-- Separator Dropdown (tylko dla SKU Only) --}}
@if($importMode === 'sku_only')
    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-300 mb-2">
            Format SKU
            <span class="text-gray-500 font-normal text-xs">
                (wybierz jak SKU są rozdzielone)
            </span>
        </label>
        <select wire:model.live="separator" class="form-select-dark w-full">
            @foreach($separators as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>
@endif

{{-- Separator Dropdown (tylko dla SKU + Nazwa w trybie single_column) --}}
@if($importMode === 'sku_name' && $viewMode === 'single_column')
    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-300 mb-2">
            Separator kolumn
            <span class="text-gray-500 font-normal text-xs">
                (znak oddzielający SKU od nazwy)
            </span>
        </label>
        <select wire:model.live="separator" class="form-select-dark w-full">
            @foreach(['tab' => 'Tabulator', 'semicolon' => 'Średnik (;)', 'comma' => 'Przecinek (,)', 'pipe' => 'Pionowa kreska (|)'] as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>
@endif
```

**NOWA SEKCJA: Textarea Layout (conditional)**

```blade
{{-- Textarea: Single Column Mode --}}
@if($viewMode === 'single_column')
    <div>
        <label class="block text-sm font-medium text-gray-300 mb-2">
            Wklej dane
            <span class="text-gray-500 font-normal">
                @if($importMode === 'sku_only')
                    @if($separator === 'newline')
                        (jeden SKU na linię)
                    @else
                        (SKU oddzielone przecinkami, średnikami lub spacjami)
                    @endif
                @else
                    (SKU + nazwa na linię, separator: {{ $separators[$separator] ?? 'auto' }})
                @endif
            </span>
        </label>
        <textarea
            wire:model.live.debounce.500ms="rawInput"
            wire:input.debounce.500ms="parseInput"
            class="form-textarea-dark w-full h-64 font-mono text-sm"
            placeholder="{{ $this->getPlaceholderText() }}"
        ></textarea>
    </div>
@endif

{{-- Textarea: Two Column Mode --}}
@if($viewMode === 'two_columns')
    <div class="grid grid-cols-2 gap-4">
        {{-- Kolumna SKU --}}
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-2">
                SKU
                <span class="text-gray-500 font-normal text-xs">
                    (jeden na linię lub oddzielone przecinkami/średnikami)
                </span>
            </label>
            <textarea
                wire:model.live.debounce.500ms="rawSkuInput"
                class="form-textarea-dark w-full h-64 font-mono text-sm"
                placeholder="SKU001
SKU002, SKU003
SKU004; SKU005"
            ></textarea>
        </div>

        {{-- Kolumna Nazwy --}}
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-2">
                Nazwy
                <span class="text-gray-500 font-normal text-xs">
                    (jedna nazwa na linię)
                </span>
            </label>
            <textarea
                wire:model.live.debounce.500ms="rawNameInput"
                class="form-textarea-dark w-full h-64 font-mono text-sm"
                placeholder="Nazwa produktu 1
Nazwa produktu 2
Nazwa produktu 3
Nazwa produktu 4
Nazwa produktu 5"
            ></textarea>
        </div>
    </div>
@endif
```

**NOWA SEKCJA: View Mode Warnings**

```blade
{{-- View Mode Warnings (dla trybu dwukolumnowego) --}}
@if(count($viewModeWarnings) > 0)
    <div class="bg-yellow-900/30 border border-yellow-700/50 rounded-lg p-3">
        <h4 class="text-sm font-medium text-yellow-400 mb-2 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            Ostrzeżenia parowania
        </h4>
        <ul class="text-xs text-yellow-300 space-y-1">
            @foreach($viewModeWarnings as $warning)
                <li>{{ $warning['message'] }}</li>
            @endforeach
        </ul>
    </div>
@endif
```

**HELPER METHOD w Component:**

```php
public function getPlaceholderText(): string
{
    if ($this->importMode === 'sku_only') {
        if ($this->separator === 'newline') {
            return "SKU001\nSKU002\nSKU003";
        }
        return "SKU001, SKU002, SKU003\nSKU004; SKU005\nSKU006 SKU007";
    }

    $sep = match($this->separator) {
        'tab' => "\t",
        'semicolon' => ';',
        'comma' => ',',
        'pipe' => '|',
        default => ';'
    };

    return "SKU001{$sep}Nazwa produktu 1\nSKU002{$sep}Nazwa produktu 2";
}
```

---

### 3. FLOW UŻYTKOWNIKA

#### 3.1 Scenariusz A: Import SKU (tylko SKU) - Multi-separator

```
1. User klika "Wklej SKU" w ProductImportPanel
   ↓
2. Modal otwiera się z:
   - Radio: [x] Tylko SKU  [ ] SKU + Nazwa
   - Dropdown Format: [Automatyczny ▼]
   ↓
3. User wkleja dane:
   "SKU001, SKU002, SKU003
    SKU004; SKU005
    SKU006"
   ↓
4. parseInput() wywołane (debounce 500ms)
   ↓
5. detectSeparator() wykrywa 'multi' (inline separators)
   ↓
6. parseSkuOnlyMultiSeparator() parsuje:
   - Linia 1: SKU001, SKU002, SKU003 (przecinki)
   - Linia 2: SKU004, SKU005 (średniki)
   - Linia 3: SKU006 (newline)
   ↓
7. Preview pokazuje 6 SKU z numerami linii
   ↓
8. User klika "Importuj 6 pozycji"
   ↓
9. PendingProduct rekordy tworzone
   ↓
10. Modal zamyka się, dispatch 'products-imported'
```

#### 3.2 Scenariusz B: Import SKU + Nazwa - Tryb dwukolumnowy

```
1. User klika "Wklej SKU" w ProductImportPanel
   ↓
2. Modal otwiera się z:
   - Radio: [ ] Tylko SKU  [x] SKU + Nazwa
   - Radio View: [x] Jedna kolumna  [ ] Dwie kolumny
   ↓
3. User przełącza na "Dwie kolumny"
   ↓
4. Layout zmienia się na 2 textarea (SKU | Nazwy)
   ↓
5. User wkleja SKU (lewa textarea):
   "SKU001, SKU002
    SKU003"
   ↓
6. User wkleja Nazwy (prawa textarea):
   "Produkt 1
    Produkt 2
    Produkt 3"
   ↓
7. parseInputTwoColumn() wywołane
   ↓
8. Parsowanie SKU: 3 SKU wykryte (multi-separator)
   ↓
9. Parsowanie Nazw: 3 nazwy wykryte (newline)
   ↓
10. Parowanie według indeksu:
    - SKU001 + Produkt 1
    - SKU002 + Produkt 2
    - SKU003 + Produkt 3
   ↓
11. Preview pokazuje 3 sparowane pozycje
   ↓
12. User klika "Importuj 3 pozycji"
   ↓
13. PendingProduct rekordy tworzone z nazwami
   ↓
14. Modal zamyka się
```

#### 3.3 Scenariusz C: Walidacja count mismatch

```
1. User w trybie dwukolumnowym wkleja:
   - SKU: "SKU001, SKU002, SKU003" (3 SKU)
   - Nazwy: "Produkt 1\nProdukt 2" (2 nazwy)
   ↓
2. parseInputTwoColumn() wykrywa niezgodność
   ↓
3. viewModeWarnings zawiera:
   "Liczba SKU (3) różni się od liczby nazw (2)"
   ↓
4. Preview pokazuje:
   - SKU001 + Produkt 1 ✅
   - SKU002 + Produkt 2 ✅
   - SKU003 (brak nazwy) ⚠️
   ↓
5. User może zdecydować:
   - Dodać brakującą nazwę
   - Usunąć nadmiarowy SKU
   - Importować z brakującymi danymi
```

---

### 4. MODYFIKACJE PROPERTIES I METOD

#### 4.1 SKUPasteModal.php - Pełna lista zmian

**NOWE PROPERTIES:**
```php
public string $viewMode = 'single_column';        // 'single_column' | 'two_columns'
public string $rawSkuInput = '';                  // SKU input (two-column mode)
public string $rawNameInput = '';                 // Names input (two-column mode)
public array $viewModeWarnings = [];              // Warnings for count mismatch
```

**NOWE METODY:**
```php
updatedViewMode(): void                           // React to view mode changes
updatedRawSkuInput(): void                        // React to SKU input changes
updatedRawNameInput(): void                       // React to Name input changes
parseInputTwoColumn(): void                       // Parse two-column layout
validateAgainstDatabase(): void                   // Extract DB validation logic
getPlaceholderText(): string                      // Dynamic placeholder text
```

**ZMODYFIKOWANE METODY:**
```php
parseInput(): void                                // Add routing for single vs two-column
resetState(): void                                // Add new properties to reset
```

**USUNIĘTE:**
- Brak (backward compatible)

---

#### 4.2 SkuParserService.php - Pełna lista zmian

**NOWE METODY:**
```php
parseSkuOnlyMultiSeparator(array $lines): array   // Parse SKU with multi-separator support
parseSkuOnlyIntelligent(array $lines, string $separator): array  // Routing logic
hasInlineSeparators(string $input): bool          // Heuristics for inline separators
```

**ZMODYFIKOWANE METODY:**
```php
parse(string $input, string $mode, string $separator): array
    // Use parseSkuOnlyIntelligent() instead of parseSkuOnly()

detectSeparator(string $input, string $mode = 'sku_only'): string
    // Add mode parameter, detect 'multi' vs 'newline' for sku_only
```

**NOWE STAŁE:**
```php
public const SEPARATORS = [
    'auto' => 'Automatyczny',
    'newline' => 'Nowa linia (jeden SKU na linię)',  // NEW
    'multi' => 'Wykryj separatory (przecinki, średniki, spacje)',  // NEW
    'tab' => 'Tabulator',
    'semicolon' => 'Średnik (;)',
    'comma' => 'Przecinek (,)',
    'pipe' => 'Pionowa kreska (|)',
];
```

---

### 5. DIAGRAMY ARCHITEKTURY

#### 5.1 Diagram Component Structure

```
SKUPasteModal (Livewire Component)
├── Properties
│   ├── $rawInput (single-column mode)
│   ├── $rawSkuInput (two-column mode)
│   ├── $rawNameInput (two-column mode)
│   ├── $importMode ('sku_only' | 'sku_name')
│   ├── $viewMode ('single_column' | 'two_columns')
│   ├── $separator ('auto' | 'newline' | 'multi' | 'tab' | ...)
│   ├── $parsedItems (array)
│   ├── $errors (array)
│   ├── $warnings (array)
│   ├── $viewModeWarnings (array)
│   └── $stats (array)
│
├── Methods
│   ├── parseInput() → router
│   │   ├── single_column → SkuParserService::parse()
│   │   └── two_columns → parseInputTwoColumn()
│   │
│   ├── parseInputTwoColumn()
│   │   ├── Parse SKU → SkuParserService::parse($rawSkuInput, 'sku_only', 'multi')
│   │   ├── Parse Names → splitLines($rawNameInput)
│   │   ├── Pair by index
│   │   └── Validate count match
│   │
│   ├── validateAgainstDatabase()
│   │   ├── checkExistingInPPM()
│   │   └── checkExistingInPending()
│   │
│   └── import() → create PendingProduct records
│
└── View (sku-paste-modal.blade.php)
    ├── Import Mode Radio (Tylko SKU | SKU + Nazwa)
    ├── View Mode Toggle (only for SKU + Nazwa)
    ├── Separator Dropdown (conditional)
    ├── Textarea Layout
    │   ├── Single Column → 1 textarea
    │   └── Two Columns → 2 textarea (grid-cols-2)
    ├── View Mode Warnings (count mismatch)
    ├── Errors/Warnings sections
    └── Preview Table
```

#### 5.2 Diagram Parsing Flow

```
USER INPUT
    │
    ├─── [Tylko SKU + Single Column] ───────────────────────┐
    │    User wkleja: "SKU001, SKU002\nSKU003"              │
    │                                                        ▼
    │                                           parseInput() routing
    │                                                        │
    │                                           SkuParserService::parse()
    │                                                        │
    │                                           detectSeparator(input, 'sku_only')
    │                                                        │
    │                                           hasInlineSeparators() → TRUE
    │                                                        │
    │                                           return 'multi'
    │                                                        │
    │                                           parseSkuOnlyIntelligent(lines, 'multi')
    │                                                        │
    │                                           parseSkuOnlyMultiSeparator(lines)
    │                                                        │
    │                                           regex split: /[,;\s\t]+/
    │                                                        │
    │                                           return [
    │                                               {sku: 'SKU001', line: 1},
    │                                               {sku: 'SKU002', line: 1},
    │                                               {sku: 'SKU003', line: 2}
    │                                           ]
    │
    ├─── [SKU + Nazwa + Two Columns] ───────────────────────┐
    │    User wkleja SKU: "SKU001\nSKU002"                  │
    │    User wkleja Nazwy: "Produkt 1\nProdukt 2"          │
    │                                                        ▼
    │                                           parseInputTwoColumn()
    │                                                        │
    │                                           ├─ Parse SKU (SkuParserService::parse())
    │                                           │  → ['SKU001', 'SKU002']
    │                                           │
    │                                           ├─ Parse Names (splitLines())
    │                                           │  → ['Produkt 1', 'Produkt 2']
    │                                           │
    │                                           ├─ Pair by index:
    │                                           │  → [
    │                                           │      {sku: 'SKU001', name: 'Produkt 1'},
    │                                           │      {sku: 'SKU002', name: 'Produkt 2'}
    │                                           │    ]
    │                                           │
    │                                           └─ Validate count match
    │                                              → if (skuCount != nameCount) add warning
    │
    └─── PARSED ITEMS ──────────────────────────────────────┐
                                                             │
                                                             ▼
                                               validateAgainstDatabase()
                                                             │
                                               ├─ checkExistingInPPM()
                                               └─ checkExistingInPending()
                                                             │
                                                             ▼
                                               UI Preview + Stats + Warnings
```

---

### 6. PRZYKŁADY UŻYCIA

#### 6.1 Przykład A: Tylko SKU - Multi-separator

**INPUT:**
```
SKU001, SKU002, SKU003
SKU004; SKU005
SKU006 SKU007 SKU008
SKU009
```

**PARSING:**
```php
$result = $parserService->parse($input, 'sku_only', 'auto');

// detectSeparator() wykrywa 'multi' (inline separators)
// parseSkuOnlyMultiSeparator() parsuje każdą linię

// OUTPUT:
[
    'items' => [
        ['sku' => 'SKU001', 'name' => null, 'line' => 1],
        ['sku' => 'SKU002', 'name' => null, 'line' => 1],
        ['sku' => 'SKU003', 'name' => null, 'line' => 1],
        ['sku' => 'SKU004', 'name' => null, 'line' => 2],
        ['sku' => 'SKU005', 'name' => null, 'line' => 2],
        ['sku' => 'SKU006', 'name' => null, 'line' => 3],
        ['sku' => 'SKU007', 'name' => null, 'line' => 3],
        ['sku' => 'SKU008', 'name' => null, 'line' => 3],
        ['sku' => 'SKU009', 'name' => null, 'line' => 4],
    ],
    'errors' => [],
    'warnings' => [],
    'stats' => [
        'total_lines' => 4,
        'valid_items' => 9,
        'skipped_empty' => 0,
        'duplicates_in_batch' => 0
    ]
]
```

#### 6.2 Przykład B: SKU + Nazwa - Dwukolumnowy

**INPUT (SKU textarea):**
```
SKU001, SKU002
SKU003
```

**INPUT (Nazwy textarea):**
```
Produkt A
Produkt B
Produkt C
```

**PARSING:**
```php
// parseInputTwoColumn() wywołane

// Parse SKU
$skuResult = $parserService->parse($rawSkuInput, 'sku_only', 'multi');
// → ['SKU001', 'SKU002', 'SKU003']

// Parse Nazwy
$nameLines = $parserService->splitLines($rawNameInput);
// → ['Produkt A', 'Produkt B', 'Produkt C']

// Parowanie według indeksu (0→0, 1→1, 2→2)
$pairedItems = [
    ['sku' => 'SKU001', 'name' => 'Produkt A', 'line' => 1],
    ['sku' => 'SKU002', 'name' => 'Produkt B', 'line' => 1],
    ['sku' => 'SKU003', 'name' => 'Produkt C', 'line' => 2],
];

// Walidacja: 3 SKU === 3 Nazwy ✅
// viewModeWarnings = []
```

#### 6.3 Przykład C: Count Mismatch Warning

**INPUT (SKU textarea):**
```
SKU001
SKU002
SKU003
SKU004
```

**INPUT (Nazwy textarea):**
```
Produkt 1
Produkt 2
```

**PARSING:**
```php
// skuCount = 4, nameCount = 2

// viewModeWarnings:
[
    [
        'type' => 'count_mismatch',
        'message' => 'Liczba SKU (4) różni się od liczby nazw (2). Parowanie według pozycji może być nieprawidłowe.'
    ]
]

// pairedItems:
[
    ['sku' => 'SKU001', 'name' => 'Produkt 1', 'line' => 1],
    ['sku' => 'SKU002', 'name' => 'Produkt 2', 'line' => 2],
    ['sku' => 'SKU003', 'name' => null, 'line' => 3],  // Missing name
    ['sku' => 'SKU004', 'name' => null, 'line' => 4],  // Missing name
]
```

---

### 7. STYLE CSS - WYMAGANE KLASY

**Status:** ✅ Wszystkie wymagane klasy już istnieją w `resources/css/admin/components.css`

**Użyte klasy:**
```css
/* Form elements */
.form-radio-enterprise        /* Radio buttons (existing) */
.form-select-dark             /* Dropdown separatorów (existing) */
.form-textarea-dark           /* Textarea (existing) */

/* Modal */
.modal-backdrop-enterprise    /* Modal backdrop (existing) */
.modal-enterprise             /* Modal container (existing) */
.modal-enterprise-lg          /* Large modal size (existing) */
.modal-header-enterprise      /* Modal header (existing) */
.modal-body-enterprise        /* Modal body (existing) */
.modal-footer-enterprise      /* Modal footer (existing) */

/* Buttons */
.btn-enterprise-primary       /* Importuj button (existing) */
.btn-enterprise-ghost         /* Anuluj button (existing) */

/* Grid */
.grid.grid-cols-2.gap-4       /* Two-column layout (Tailwind) */
```

**NOWA KLASA (opcjonalna dla count mismatch visual):**
```css
/* resources/css/admin/components.css */

/* Two-column textarea highlight on mismatch */
.textarea-mismatch-warning {
    border-color: rgb(202, 138, 4) !important;
    box-shadow: 0 0 0 2px rgba(202, 138, 4, 0.2);
}
```

**Użycie w Blade:**
```blade
<textarea
    wire:model.live.debounce.500ms="rawSkuInput"
    @class([
        'form-textarea-dark w-full h-64 font-mono text-sm',
        'textarea-mismatch-warning' => count($viewModeWarnings) > 0
    ])
></textarea>
```

---

### 8. PLAN IMPLEMENTACJI

#### 8.1 Faza 1: Inteligentne separatory dla "Tylko SKU" (4h)

**✅ KROK 1.1: Modyfikacja SkuParserService.php**
- Dodaj `parseSkuOnlyMultiSeparator()` - parsowanie z regex
- Dodaj `hasInlineSeparators()` - heurystyka
- Dodaj `parseSkuOnlyIntelligent()` - routing newline vs multi
- Zmodyfikuj `detectSeparator()` - dodaj mode parameter
- Zmodyfikuj `parse()` - użyj intelligent routing
- Dodaj nowe opcje do `SEPARATORS` const

**✅ KROK 1.2: Testy jednostkowe**
- Test `parseSkuOnlyMultiSeparator()` z różnymi separatorami
- Test mieszanego formatu (inline + newline)
- Test `hasInlineSeparators()` heurystyki
- Test backward compatibility dla `parseSkuOnly()`

**✅ KROK 1.3: Update UI - Separator Dropdown**
- Conditional rendering separator dropdown (tylko sku_only)
- Dodaj nowe opcje do dropdown (newline, multi)
- Update placeholder text dynamicznie
- Update help text dla użytkownika

#### 8.2 Faza 2: Tryb dwukolumnowy dla "SKU + Nazwa" (5h)

**✅ KROK 2.1: Modyfikacja SKUPasteModal.php**
- Dodaj nowe properties: `$viewMode`, `$rawSkuInput`, `$rawNameInput`, `$viewModeWarnings`
- Dodaj `updatedViewMode()`, `updatedRawSkuInput()`, `updatedRawNameInput()`
- Dodaj `parseInputTwoColumn()` - parowanie logic
- Zmodyfikuj `parseInput()` - routing single vs two-column
- Dodaj `validateAgainstDatabase()` - extract DB logic
- Dodaj `getPlaceholderText()` - dynamic placeholder
- Zmodyfikuj `resetState()` - reset new properties

**✅ KROK 2.2: Modyfikacja sku-paste-modal.blade.php**
- Dodaj View Mode Toggle (radio buttons)
- Conditional rendering textarea (single vs two-column)
- Two-column layout: grid-cols-2 gap-4
- Dodaj view mode warnings section
- Update label texts dynamicznie
- Update preview table (bez zmian)

**✅ KROK 2.3: Testy jednostkowe**
- Test `parseInputTwoColumn()` z równą liczbą SKU/nazw
- Test count mismatch warning
- Test parowania według indeksu
- Test walidacji DB w trybie dwukolumnowym

#### 8.3 Faza 3: Deployment i weryfikacja (2h)

**✅ KROK 3.1: Deployment**
- `npm run build` (compile CSS/JS)
- Upload zmienionych plików (SKUPasteModal.php, SkuParserService.php, blade)
- Upload manifest.json (ROOT build directory)
- Clear cache: `php artisan view:clear && cache:clear`

**✅ KROK 3.2: Chrome DevTools MCP Verification**
- Navigate: `/admin/products/import`
- Click "Wklej SKU" button
- Test Scenariusz A (multi-separator SKU)
- Test Scenariusz B (dwukolumnowy)
- Test Scenariusz C (count mismatch warning)
- Screenshot verification

**✅ KROK 3.3: User Acceptance Testing**
- Test z real-world data (100+ SKU)
- Test mieszanych formatów
- Test edge cases (empty lines, special chars)
- Performance check (parsing time < 500ms)

---

### 9. BACKWARD COMPATIBILITY

**KRYTYCZNE:** Wszystkie zmiany są backward compatible

**Istniejące flow zachowane:**
- Tryb "Tylko SKU" z separator='newline' → używa starego `parseSkuOnly()`
- Tryb "SKU + Nazwa" z single_column → używa starego `parseSkuName()`
- Wszystkie istniejące properties zachowane
- Wszystkie istniejące metody zachowane

**Nowe flow (opt-in):**
- Tryb "Tylko SKU" z separator='auto'/'multi' → używa nowego `parseSkuOnlyMultiSeparator()`
- Tryb "SKU + Nazwa" z viewMode='two_columns' → używa nowego `parseInputTwoColumn()`

**Migration path:**
- Użytkownicy automatycznie dostają nowe opcje w dropdown
- Domyślne wartości zachowane: `separator='auto'`, `viewMode='single_column'`
- Stare zachowanie dostępne przez explicit selection: separator='newline'

---

### 10. POTENCJALNE PROBLEMY I ROZWIĄZANIA

#### Problem 1: Regex performance dla dużych plików (>10k SKU)

**Rozwiązanie:**
- Chunking: przetwarzaj 1000 linii na raz
- Progress bar dla dużych plików
- Async parsing z wire:poll dla UI feedback

**Implementacja (opcjonalna, jeśli potrzeba):**
```php
public function parseInputLarge(): void
{
    $lines = $this->parserService->splitLines($this->rawInput);

    if (count($lines) > 1000) {
        // Dispatch job dla background processing
        $this->dispatchBrowserEvent('show-progress-modal');
        // ... queue job logic
    } else {
        // Normal parsing
        $this->parseInput();
    }
}
```

#### Problem 2: Count mismatch - jak user powinien naprawić?

**Rozwiązanie:**
- Clear warning message z instrukcjami
- Highlight problematic rows w preview
- Opcja "Importuj mimo to" (z null dla missing names)

**Implementacja (już w architekturze):**
```php
// viewModeWarnings już zawiera clear message
// Preview table pokazuje missing names jako "-"
// Import działa mimo warning (user decision)
```

#### Problem 3: Różne encoding dla wklejonych danych

**Rozwiązanie:**
- UTF-8 normalizacja przed parsowaniem
- Strip BOM characters
- Convert Windows line endings

**Implementacja:**
```php
private function normalizeInput(string $input): string
{
    // Remove BOM
    $input = str_replace("\xEF\xBB\xBF", '', $input);

    // Normalize line endings
    $input = str_replace(["\r\n", "\r"], "\n", $input);

    // UTF-8 validation
    if (!mb_check_encoding($input, 'UTF-8')) {
        $input = mb_convert_encoding($input, 'UTF-8', 'auto');
    }

    return $input;
}
```

---

### 11. SUCCESS CRITERIA

**Implementacja uznana za udaną gdy:**

1. **Funkcjonalność:**
   - ✅ SKU z przecinkami/średnikami/spacjami są poprawnie parsowane
   - ✅ Tryb dwukolumnowy pozwala wkleić SKU i nazwy osobno
   - ✅ Parowanie według linii działa poprawnie
   - ✅ Count mismatch warning wyświetla się gdy potrzeba
   - ✅ Backward compatibility zachowane (stare flow działa)

2. **UX:**
   - ✅ Clear labels i help text dla każdego trybu
   - ✅ Real-time parsing z debounce (500ms)
   - ✅ Preview pokazuje poprawne dane
   - ✅ Errors/warnings są zrozumiałe dla użytkownika

3. **Performance:**
   - ✅ Parsing 1000 SKU < 1 sekunda
   - ✅ UI rendering smooth (no lag)
   - ✅ Debounce działa poprawnie

4. **Code Quality:**
   - ✅ Max 300 linii per plik (zgodnie z CLAUDE.md)
   - ✅ Tylko CSS classes (zero inline styles)
   - ✅ PHPDoc dla wszystkich nowych metod
   - ✅ Testy jednostkowe pokrywają nowe flow

5. **Deployment:**
   - ✅ Chrome DevTools MCP verification passed
   - ✅ Production deployment successful
   - ✅ No console errors
   - ✅ User acceptance testing completed

---

### 12. NASTĘPNE KROKI

**Po zatwierdzeniu architektury przez użytkownika:**

1. **Implementacja Faza 1** (inteligentne separatory)
   - Coding: 3h
   - Testing: 1h
   - TOTAL: 4h

2. **Implementacja Faza 2** (tryb dwukolumnowy)
   - Coding: 4h
   - Testing: 1h
   - TOTAL: 5h

3. **Implementacja Faza 3** (deployment + verification)
   - Build & Deploy: 1h
   - Chrome DevTools Verification: 0.5h
   - UAT: 0.5h
   - TOTAL: 2h

**ŁĄCZNY CZAS:** 11h

**DELEGACJA:**
- **laravel-expert** → Implementacja SkuParserService changes
- **livewire-specialist** → Implementacja SKUPasteModal changes + UI
- **frontend-specialist** → CSS adjustments (jeśli potrzeba)
- **deployment-specialist** → Deployment + Chrome DevTools verification
- **coding-style-agent** → Final code review przed deployment

---

## PODSUMOWANIE

Rozbudowa SKUPasteModal jest dobrze zaprojektowana zgodnie z enterprise best practices:

✅ **Backward compatible** - stare flow zachowane
✅ **Modular** - nowe metody oddzielne, clear separation of concerns
✅ **Testable** - każda nowa metoda ma unit test coverage
✅ **User-friendly** - clear UI flow, helpful warnings
✅ **Performant** - regex parsing, debounce, chunking dla dużych plików
✅ **CSS compliant** - istniejące klasy, zero inline styles
✅ **Enterprise-grade** - PHPDoc, error handling, validation

**READY FOR IMPLEMENTATION** po zatwierdzeniu przez użytkownika.

---

**Koniec raportu**
