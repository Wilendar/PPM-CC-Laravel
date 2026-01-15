# SkuParserService - Przykłady Testów

## ETAP_06 FAZA 3 - Rozszerzone parsowanie SKU

### 1. Test: parseSkuOnlyMultiSeparator - Przecinki

```php
$parser = new SkuParserService();

$input = "SKU001, SKU002, SKU003";
$lines = $parser->splitLines($input);
$result = $parser->parseSkuOnlyMultiSeparator($lines);

// Expected
$expected = [
    ['sku' => 'SKU001', 'name' => null, 'line' => 1],
    ['sku' => 'SKU002', 'name' => null, 'line' => 1],
    ['sku' => 'SKU003', 'name' => null, 'line' => 1],
];

// Assertions
$this->assertCount(3, $result);
$this->assertEquals('SKU001', $result[0]['sku']);
$this->assertEquals('SKU002', $result[1]['sku']);
$this->assertEquals('SKU003', $result[2]['sku']);
$this->assertEquals(1, $result[0]['line']); // Wszystkie z linii 1
```

### 2. Test: parseSkuOnlyMultiSeparator - Średniki

```php
$parser = new SkuParserService();

$input = "SKU001;SKU002;SKU003";
$lines = $parser->splitLines($input);
$result = $parser->parseSkuOnlyMultiSeparator($lines);

// Assertions
$this->assertCount(3, $result);
$this->assertEquals('SKU001', $result[0]['sku']);
$this->assertEquals('SKU002', $result[1]['sku']);
$this->assertEquals('SKU003', $result[2]['sku']);
```

### 3. Test: parseSkuOnlyMultiSeparator - Spacje

```php
$parser = new SkuParserService();

$input = "SKU001 SKU002 SKU003";
$lines = $parser->splitLines($input);
$result = $parser->parseSkuOnlyMultiSeparator($lines);

// Assertions
$this->assertCount(3, $result);
$this->assertEquals('SKU001', $result[0]['sku']);
$this->assertEquals('SKU002', $result[1]['sku']);
$this->assertEquals('SKU003', $result[2]['sku']);
```

### 4. Test: parseSkuOnlyMultiSeparator - Mieszane separatory (wiele linii)

```php
$parser = new SkuParserService();

$input = "SKU001, SKU002\nSKU003; SKU004\nSKU005 SKU006";
$lines = $parser->splitLines($input);
$result = $parser->parseSkuOnlyMultiSeparator($lines);

// Expected
$expected = [
    ['sku' => 'SKU001', 'name' => null, 'line' => 1],
    ['sku' => 'SKU002', 'name' => null, 'line' => 1],
    ['sku' => 'SKU003', 'name' => null, 'line' => 2],
    ['sku' => 'SKU004', 'name' => null, 'line' => 2],
    ['sku' => 'SKU005', 'name' => null, 'line' => 3],
    ['sku' => 'SKU006', 'name' => null, 'line' => 3],
];

// Assertions
$this->assertCount(6, $result);
$this->assertEquals(1, $result[0]['line']);
$this->assertEquals(1, $result[1]['line']);
$this->assertEquals(2, $result[2]['line']);
$this->assertEquals(2, $result[3]['line']);
$this->assertEquals(3, $result[4]['line']);
$this->assertEquals(3, $result[5]['line']);
```

### 5. Test: hasInlineSeparators - Wykrywanie

```php
$parser = new SkuParserService();

// Inline separators detected
$input1 = "SKU001, SKU002, SKU003";
$this->assertTrue($parser->hasInlineSeparators($input1));

$input2 = "SKU001;SKU002;SKU003";
$this->assertTrue($parser->hasInlineSeparators($input2));

$input3 = "SKU001 SKU002 SKU003";
$this->assertTrue($parser->hasInlineSeparators($input3));

// NO inline separators (newline format)
$input4 = "SKU001\nSKU002\nSKU003";
$this->assertFalse($parser->hasInlineSeparators($input4));
```

### 6. Test: parseSkuOnlyIntelligent - Auto mode (inline separators)

```php
$parser = new SkuParserService();

// Auto-detect inline separators → parseSkuOnlyMultiSeparator
$input = "SKU001, SKU002, SKU003";
$lines = $parser->splitLines($input);
$result = $parser->parseSkuOnlyIntelligent($lines, 'auto');

// Assertions
$this->assertCount(3, $result);
$this->assertEquals('SKU001', $result[0]['sku']);
$this->assertEquals('SKU002', $result[1]['sku']);
$this->assertEquals('SKU003', $result[2]['sku']);
```

### 7. Test: parseSkuOnlyIntelligent - Auto mode (newline format)

```php
$parser = new SkuParserService();

// Auto-detect newline format → parseSkuOnly
$input = "SKU001\nSKU002\nSKU003";
$lines = $parser->splitLines($input);
$result = $parser->parseSkuOnlyIntelligent($lines, 'auto');

// Assertions
$this->assertCount(3, $result);
$this->assertEquals('SKU001', $result[0]['sku']);
$this->assertEquals('SKU002', $result[1]['sku']);
$this->assertEquals('SKU003', $result[2]['sku']);
$this->assertEquals(1, $result[0]['line']);
$this->assertEquals(2, $result[1]['line']);
$this->assertEquals(3, $result[2]['line']);
```

### 8. Test: parseSkuOnlyIntelligent - Forced 'newline' mode

```php
$parser = new SkuParserService();

// Force newline mode (ignore inline separators)
$input = "SKU001, SKU002";
$lines = $parser->splitLines($input);
$result = $parser->parseSkuOnlyIntelligent($lines, 'newline');

// Expected: Treated as single SKU with comma in it (will fail validation)
$this->assertCount(1, $result);
$this->assertEquals('SKU001, SKU002', $result[0]['sku']);
```

### 9. Test: parseSkuOnlyIntelligent - Forced 'multi' mode

```php
$parser = new SkuParserService();

// Force multi-separator mode
$input = "SKU001\nSKU002\nSKU003";
$lines = $parser->splitLines($input);
$result = $parser->parseSkuOnlyIntelligent($lines, 'multi');

// Expected: Each line treated as single SKU (no inline splitting)
$this->assertCount(3, $result);
$this->assertEquals('SKU001', $result[0]['sku']);
$this->assertEquals('SKU002', $result[1]['sku']);
$this->assertEquals('SKU003', $result[2]['sku']);
```

### 10. Test: parseTwoColumn - Równa liczba SKU i nazw

```php
$parser = new SkuParserService();

$skuInput = "SKU001\nSKU002\nSKU003";
$nameInput = "Produkt 1\nProdukt 2\nProdukt 3";

$result = $parser->parseTwoColumn($skuInput, $nameInput);

// Assertions
$this->assertCount(3, $result['items']);
$this->assertEquals('SKU001', $result['items'][0]['sku']);
$this->assertEquals('Produkt 1', $result['items'][0]['name']);
$this->assertEquals('SKU002', $result['items'][1]['sku']);
$this->assertEquals('Produkt 2', $result['items'][1]['name']);
$this->assertEquals('SKU003', $result['items'][2]['sku']);
$this->assertEquals('Produkt 3', $result['items'][2]['name']);
$this->assertEquals(3, $result['stats']['paired_items']);
$this->assertEquals(0, $result['stats']['unpaired_skus']);
$this->assertEquals(0, $result['stats']['unpaired_names']);
$this->assertEmpty($result['warnings']);
```

### 11. Test: parseTwoColumn - Więcej SKU niż nazw

```php
$parser = new SkuParserService();

$skuInput = "SKU001\nSKU002\nSKU003";
$nameInput = "Produkt 1\nProdukt 2"; // Tylko 2 nazwy

$result = $parser->parseTwoColumn($skuInput, $nameInput);

// Assertions
$this->assertCount(3, $result['items']);
$this->assertEquals(2, $result['stats']['paired_items']);
$this->assertEquals(1, $result['stats']['unpaired_skus']);
$this->assertEquals(0, $result['stats']['unpaired_names']);

// SKU003 bez nazwy
$this->assertEquals('SKU003', $result['items'][2]['sku']);
$this->assertNull($result['items'][2]['name']);

// Warning: count mismatch + missing name
$this->assertCount(2, $result['warnings']);
$this->assertEquals('count_mismatch', $result['warnings'][0]['type']);
$this->assertEquals('missing_name', $result['warnings'][1]['type']);
$this->assertEquals('SKU003', $result['warnings'][1]['sku']);
```

### 12. Test: parseTwoColumn - Więcej nazw niż SKU

```php
$parser = new SkuParserService();

$skuInput = "SKU001\nSKU002"; // Tylko 2 SKU
$nameInput = "Produkt 1\nProdukt 2\nProdukt 3";

$result = $parser->parseTwoColumn($skuInput, $nameInput);

// Assertions
$this->assertCount(2, $result['items']); // Tylko paired items
$this->assertEquals(2, $result['stats']['paired_items']);
$this->assertEquals(0, $result['stats']['unpaired_skus']);
$this->assertEquals(1, $result['stats']['unpaired_names']);

// Warning: count mismatch + missing SKU
$this->assertCount(2, $result['warnings']);
$this->assertEquals('count_mismatch', $result['warnings'][0]['type']);
$this->assertEquals('missing_sku', $result['warnings'][1]['type']);
$this->assertEquals('Produkt 3', $result['warnings'][1]['name']);
```

### 13. Test: parseTwoColumn - SKU z multi-separators

```php
$parser = new SkuParserService();

$skuInput = "SKU001, SKU002, SKU003"; // Multi-separator (3 SKU w jednej linii)
$nameInput = "Produkt 1\nProdukt 2\nProdukt 3";

$result = $parser->parseTwoColumn($skuInput, $nameInput);

// Assertions
$this->assertCount(3, $result['items']);
$this->assertEquals('SKU001', $result['items'][0]['sku']);
$this->assertEquals('Produkt 1', $result['items'][0]['name']);
$this->assertEquals('SKU002', $result['items'][1]['sku']);
$this->assertEquals('Produkt 2', $result['items'][1]['name']);
$this->assertEquals('SKU003', $result['items'][2]['sku']);
$this->assertEquals('Produkt 3', $result['items'][2]['name']);
$this->assertEquals(3, $result['stats']['paired_items']);
```

### 14. Test: parse() main method - Integration test (auto multi-separator)

```php
$parser = new SkuParserService();

$input = "SKU001, SKU002, SKU003";
$result = $parser->parse($input, 'sku_only', 'auto');

// Assertions
$this->assertCount(3, $result['items']);
$this->assertEquals('SKU001', $result['items'][0]['sku']);
$this->assertEquals('SKU002', $result['items'][1]['sku']);
$this->assertEquals('SKU003', $result['items'][2]['sku']);
$this->assertEmpty($result['errors']);
$this->assertEquals(3, $result['stats']['valid_items']);
```

### 15. Test: parse() main method - Integration test (forced newline)

```php
$parser = new SkuParserService();

$input = "SKU001\nSKU002\nSKU003";
$result = $parser->parse($input, 'sku_only', 'newline');

// Assertions
$this->assertCount(3, $result['items']);
$this->assertEquals('SKU001', $result['items'][0]['sku']);
$this->assertEquals('SKU002', $result['items'][1]['sku']);
$this->assertEquals('SKU003', $result['items'][2]['sku']);
$this->assertEmpty($result['errors']);
$this->assertEquals(3, $result['stats']['valid_items']);
```

### 16. Test: parse() main method - Invalid SKU with multi-separator

```php
$parser = new SkuParserService();

// SKU z niedozwolonymi znakami
$input = "SKU001, SKU@002, SKU003";
$result = $parser->parse($input, 'sku_only', 'auto');

// Assertions
$this->assertCount(2, $result['items']); // Tylko valid
$this->assertCount(1, $result['errors']); // SKU@002 rejected
$this->assertEquals('SKU@002', $result['errors'][0]['sku']);
$this->assertStringContainsString('niedozwolone znaki', $result['errors'][0]['message']);
```

## Podsumowanie Nowych Metod

### 1. `hasInlineSeparators(string $input): bool`
- Wykrywa czy input zawiera inline separatory (przecinki, średniki, spacje)
- Zwraca `true` jeśli wykryje multi-separator format
- Zwraca `false` dla formatu newline (jeden SKU na linię)

### 2. `parseSkuOnlyMultiSeparator(array $lines): array`
- Parsuje SKU z wielu separatorów w jednej linii
- Obsługuje: przecinki, średniki, spacje/tabulatory
- Zwraca: `[['sku' => 'SKU001', 'name' => null, 'line' => 1], ...]`
- Line mapping: wszystkie SKU z tej samej linii mają ten sam numer linii

### 3. `parseSkuOnlyIntelligent(array $lines, string $separator = 'auto'): array`
- Router do odpowiedniej metody parsowania
- `separator = 'auto'`: auto-detekcja inline separators
- `separator = 'newline'`: force parseSkuOnly (jeden na linię)
- `separator = 'multi'`: force parseSkuOnlyMultiSeparator
- Zwraca: `[['sku' => 'SKU001', 'name' => null, 'line' => 1], ...]`

### 4. `parseTwoColumn(string $skuInput, string $nameInput): array`
- Parsuje dwie niezależne listy (SKU + Nazwy) i paruje je 1-to-1
- SKU input może zawierać multi-separatory
- Name input zawsze jeden na linię
- Zwraca: `['items' => [...], 'errors' => [...], 'warnings' => [...], 'stats' => [...]]`
- Warnings: count_mismatch, missing_name, missing_sku
- Stats: total_skus, total_names, paired_items, unpaired_skus, unpaired_names

### 5. `parse()` - Zmodyfikowana
- Dodano parametr `$separator`: 'auto', 'newline', 'multi', lub konkretny separator
- Integracja z `parseSkuOnlyIntelligent()` dla mode = 'sku_only'
- Backward compatible z obecnym API

## Przykłady użycia w Livewire Component

```php
// Use case 1: User wkleił SKU z przecinkami
$input = "SKU001, SKU002, SKU003";
$result = $this->parser->parse($input, 'sku_only', 'auto');
// Result: 3 SKU parsed

// Use case 2: User wkleił SKU jeden na linię
$input = "SKU001\nSKU002\nSKU003";
$result = $this->parser->parse($input, 'sku_only', 'auto');
// Result: 3 SKU parsed

// Use case 3: Two-column paste (SKU w jednej kolumnie, nazwy w drugiej)
$skuInput = "SKU001\nSKU002\nSKU003";
$nameInput = "Produkt 1\nProdukt 2\nProdukt 3";
$result = $this->parser->parseTwoColumn($skuInput, $nameInput);
// Result: 3 paired items

// Use case 4: Two-column z mismatch
$skuInput = "SKU001, SKU002, SKU003"; // Multi-separator
$nameInput = "Produkt 1\nProdukt 2"; // Tylko 2 nazwy
$result = $this->parser->parseTwoColumn($skuInput, $nameInput);
// Result: 2 paired + 1 warning (SKU003 bez nazwy)
```
