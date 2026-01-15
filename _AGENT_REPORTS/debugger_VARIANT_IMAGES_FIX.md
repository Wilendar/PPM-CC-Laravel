# RAPORT DEBUGOWANIA: Obrazy Wariantów - Analiza Błędów

**Data**: 2025-12-04
**Agent**: debugger
**Zadanie**: Znalezienie root cause dla problemów z obrazami wariantów

---

## 🔍 EXECUTIVE SUMMARY

Zidentyfikowano **6 krytycznych bugów** powodujących nieprawidłowe działanie systemu obrazów wariantów:

1. ✅ **BUG #1-2**: Nieprawidłowe property names w `VariantCrudTrait.php` (`$media->path` → `$media->file_path`)
2. ✅ **BUG #3**: Brak ładowania `media_ids` w `loadVariantForEdit()`
3. ✅ **BUG #4-6**: Brak accessorów `thumbnail_url`/`url` w modelu `VariantImage`
4. ❌ **MISSING FEATURE**: Brak implementacji `+3` indicator dla wielokrotnych obrazów

---

## 🐛 SZCZEGÓŁOWA ANALIZA BUGÓW

### BUG #1-2: Nieprawidłowe Media Property Names (KRYTYCZNY)

**Lokalizacja:** `app/Http/Livewire/Products/Management/Traits/VariantCrudTrait.php`

**Problem:**
```php
// LINIA 971-972 (assignVariantImage metoda)
VariantImage::create([
    'variant_id' => $variant->id,
    'image_path' => $media->path ?? '',              // ❌ BŁĄD: Media nie ma property 'path'
    'image_thumb_path' => $media->thumbnail_path ?? null,  // ❌ BŁĄD: Media nie ma property 'thumbnail_path'
    'is_cover' => ($position === 0),
    'position' => $position,
]);

// LINIA 1212-1213 (assignVariantImagesFromIds metoda)
VariantImage::create([
    'variant_id' => $variant->id,
    'image_path' => $media->path ?? '',              // ❌ BŁĄD: Duplikat tego samego błędu
    'image_thumb_path' => $media->thumbnail_path ?? null,  // ❌ BŁĄD: Duplikat tego samego błędu
    'is_cover' => ($position === 0),
    'position' => $position,
]);
```

**Faktyczna Struktura Media Model:**
```php
// app/Models/Media.php
class Media extends Model
{
    protected $fillable = [
        'file_path',        // ✅ POPRAWNE property name
        // ...
    ];

    public function thumbnailUrl(): Attribute  // ✅ ACCESSOR, nie property
    {
        return Attribute::make(
            get: function (): string {
                return route('thumbnail', ['mediaId' => $this->id, 'w' => 200, 'h' => 200]);
            }
        );
    }
}
```

**Konsekwencje:**
- ❌ `$media->path` zwraca `null` (property nie istnieje)
- ❌ `$media->thumbnail_path` zwraca `null` (property nie istnieje)
- ❌ VariantImage zapisuje `image_path = ''` i `image_thumb_path = null`
- ❌ Po reload obrazy znikają (brak valid file paths)
- ❌ ProductList nie pokazuje miniaturek (invalid paths)

**Proponowana Poprawka:**
```php
// ✅ POPRAWNA WERSJA
VariantImage::create([
    'variant_id' => $variant->id,
    'image_path' => $media->file_path ?? '',          // ✅ POPRAWNE property
    'image_thumb_path' => $media->thumbnail_url ?? null,  // ✅ Accessor (generuje URL on-demand)
    'is_cover' => ($position === 0),
    'position' => $position,
]);
```

**⚠️ UWAGA:** `image_thumb_path` w VariantImage przechowuje URL (string), nie file path. To jest poprawne, bo thumbnail generowany jest on-demand przez `ThumbnailController`.

---

### BUG #3: Brak Ładowania media_ids w Edit Modal (KRYTYCZNY)

**Lokalizacja:** `app/Http/Livewire/Products/Management/Traits/VariantCrudTrait.php` (linia 837-862)

**Problem:**
```php
public function loadVariantForEdit(int $variantId): void
{
    try {
        $variant = ProductVariant::with('attributes')->findOrFail($variantId);

        $this->editingVariantId = $variant->id;
        $this->variantData = [
            'sku' => $variant->sku,
            'name' => $variant->name,
            'is_active' => $variant->is_active,
            'is_default' => $variant->is_default,
            'position' => $variant->position,
            // ❌ BRAKUJE: 'media_ids' => [...]
        ];

        // Load attribute value_ids (not text values)
        $this->variantAttributes = [];
        foreach ($variant->attributes as $attr) {
            $this->variantAttributes[$attr->attribute_type_id] = $attr->value_id;
        }

        $this->showEditModal = true;
    } catch (\Exception $e) {
        Log::error('Load variant for edit failed', ['error' => $e->getMessage()]);
        session()->flash('error', 'Blad podczas ladowania wariantu.');
    }
}
```

**Konsekwencje:**
- ❌ Edit modal otwiera się bez zaznaczonych obrazów
- ❌ Użytkownik nie widzi które obrazy są przypisane do wariantu
- ❌ Musi zaznaczać obrazy ponownie (utrata danych)

**Proponowana Poprawka:**
```php
public function loadVariantForEdit(int $variantId): void
{
    try {
        // ✅ EAGER LOAD images relationship
        $variant = ProductVariant::with(['attributes', 'images'])->findOrFail($variantId);

        $this->editingVariantId = $variant->id;
        $this->variantData = [
            'sku' => $variant->sku,
            'name' => $variant->name,
            'is_active' => $variant->is_active,
            'is_default' => $variant->is_default,
            'position' => $variant->position,
            // ✅ DODANE: Konwersja VariantImages do media_ids z Product.media
            'media_ids' => $this->extractMediaIdsFromVariantImages($variant),
        ];

        // Load attribute value_ids (not text values)
        $this->variantAttributes = [];
        foreach ($variant->attributes as $attr) {
            $this->variantAttributes[$attr->attribute_type_id] = $attr->value_id;
        }

        $this->showEditModal = true;
    } catch (\Exception $e) {
        Log::error('Load variant for edit failed', ['error' => $e->getMessage()]);
        session()->flash('error', 'Blad podczas ladowania wariantu.');
    }
}

/**
 * ✅ NOWA METODA: Konwertuje VariantImages → Media IDs
 *
 * VariantImage przechowuje image_path (file path), musimy znaleźć
 * odpowiadające Media objects z Product gallery i zwrócić ich IDs.
 */
protected function extractMediaIdsFromVariantImages(ProductVariant $variant): array
{
    if ($variant->images->isEmpty()) {
        return [];
    }

    $mediaIds = [];

    foreach ($variant->images->sortBy('position') as $variantImage) {
        // Find matching Media by file_path
        $media = $this->product->media->first(function ($mediaItem) use ($variantImage) {
            return $mediaItem->file_path === $variantImage->image_path;
        });

        if ($media) {
            $mediaIds[] = $media->id;
        }
    }

    return $mediaIds;
}
```

**Alternatywne Rozwiązanie (prostsze, ale mniej wydajne):**
```php
// ✅ ALTERNATYWA: Store media_id directly in variant_images table
// Wymaga dodania kolumny `media_id` do variant_images migration
'media_ids' => $variant->images->pluck('media_id')->toArray(),
```

---

### BUG #4-6: Brak Accessorów w VariantImage Model (ŚREDNI)

**Lokalizacja:** `app/Models/VariantImage.php`

**Problem:**

Model `VariantImage` ma **metody** `getUrl()` i `getThumbUrl()`, ale Blade templates używają **accessorów** (snake_case):

```php
// VariantImage.php (AKTUALNA WERSJA)
class VariantImage extends Model
{
    // ✅ Metody istnieją (camelCase)
    public function getUrl(): string { ... }
    public function getThumbUrl(): ?string { ... }

    // ❌ BRAKUJE: Accessors (snake_case property)
    // $variantImage->url (nie działa!)
    // $variantImage->thumbnail_url (nie działa!)
}
```

**Blade Templates używają accessorów:**

```blade
{{-- resources/views/livewire/products/listing/product-list.blade.php (LINIA 773) --}}
<img src="{{ $coverImage->thumbnail_url ?? $coverImage->url ?? asset('images/placeholder.png') }}" />

{{-- resources/views/livewire/products/listing/partials/variant-row.blade.php (LINIA 27) --}}
<img src="{{ $coverImage->thumbnail_url ?? $coverImage->url ?? asset('images/placeholder.png') }}" />

{{-- resources/views/livewire/products/management/tabs/variants-tab.blade.php (LINIA 181) --}}
$imageUrl = $firstMedia?->thumbnail_url ?? $firstMedia?->url ?? null;
```

**Konsekwencje:**
- ❌ `$coverImage->thumbnail_url` zwraca `null` (accessor nie istnieje)
- ❌ `$coverImage->url` zwraca `null` (accessor nie istnieje)
- ❌ ProductList pokazuje placeholder zamiast faktycznych obrazów
- ❌ Variants tab (pending) nie pokazuje miniaturek

**Proponowana Poprawka:**

Dodać Laravel Accessors do `VariantImage`:

```php
// app/Models/VariantImage.php
use Illuminate\Database\Eloquent\Casts\Attribute;

class VariantImage extends Model
{
    // ... existing code ...

    /**
     * ✅ NOWY: Accessor dla URL (snake_case)
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function url(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->getUrl()
        );
    }

    /**
     * ✅ NOWY: Accessor dla thumbnail_url (snake_case)
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function thumbnailUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->getThumbUrl()
        );
    }

    // ✅ ZACHOWAJ: Existing methods (for backward compatibility)
    public function getUrl(): string { ... }
    public function getThumbUrl(): ?string { ... }
}
```

**Zaleta tego rozwiązania:**
- ✅ Blade templates działają bez zmian (`$image->url`, `$image->thumbnail_url`)
- ✅ PHP kod może dalej używać metod (`$image->getUrl()`)
- ✅ Backward compatibility

---

### MISSING FEATURE: Brak `+3` Indicator (NISKI PRIORYTET)

**Opis:**

W ProductList/VariantRow brak wskaźnika typu `+3` gdy wariant ma więcej niż 1 obraz.

**Obecne zachowanie:**
```blade
{{-- Pokazuje TYLKO cover image, brak info o dodatkowych zdjęciach --}}
<img src="{{ $coverImage->thumbnail_url }}" />
```

**Proponowana Funkcjonalność:**
```blade
{{-- ✅ NOWA WERSJA z +N indicator --}}
<div class="relative inline-block">
    <img src="{{ $coverImage->thumbnail_url }}" class="w-12 h-12 object-cover rounded" />

    @if($variant->images->count() > 1)
        <span class="absolute -top-1 -right-1 bg-blue-600 text-white text-xs font-semibold
                     px-1.5 py-0.5 rounded-full border border-gray-700">
            +{{ $variant->images->count() - 1 }}
        </span>
    @endif
</div>
```

**Lokalizacje do implementacji:**
1. `resources/views/livewire/products/listing/product-list.blade.php` (linia ~773)
2. `resources/views/livewire/products/listing/partials/variant-row.blade.php` (linia ~27)
3. `resources/views/livewire/products/management/tabs/variants-tab.blade.php` (linia ~180-181)

---

## 📁 PLIKI WYMAGAJĄCE POPRAWEK

### 1. Backend - VariantCrudTrait.php (KRYTYCZNE)

**Plik:** `app/Http/Livewire/Products/Management/Traits/VariantCrudTrait.php`

**Lokalizacje zmian:**
- **Linia 971-972**: `assignVariantImage()` - poprawić `$media->path` → `$media->file_path`
- **Linia 1212-1213**: `assignVariantImagesFromIds()` - poprawić `$media->path` → `$media->file_path`
- **Linia 837-862**: `loadVariantForEdit()` - dodać ładowanie `media_ids`
- **NOWA METODA**: `extractMediaIdsFromVariantImages()` - konwersja VariantImages → Media IDs

**Priorytet:** 🔴 KRYTYCZNY (blokuje save/edit flow)

---

### 2. Backend - VariantImage.php (ŚREDNI)

**Plik:** `app/Models/VariantImage.php`

**Zmiany:**
- Dodać accessor `url(): Attribute`
- Dodać accessor `thumbnailUrl(): Attribute`
- Zachować istniejące metody `getUrl()` i `getThumbUrl()` (backward compatibility)

**Priorytet:** 🟡 ŚREDNI (obrazy nie renderują się, ale workaround możliwy)

---

### 3. Frontend - ProductList Blade (NISKI - ENHANCEMENT)

**Pliki:**
- `resources/views/livewire/products/listing/product-list.blade.php` (linia ~770-777)
- `resources/views/livewire/products/listing/partials/variant-row.blade.php` (linia ~25-28)
- `resources/views/livewire/products/management/tabs/variants-tab.blade.php` (linia ~180-181)

**Zmiany:**
- Dodać `+N` indicator dla wariantów z wieloma obrazami
- Owinąć thumbnail w `<div class="relative">` container
- Dodać badge z count'em dodatkowych obrazów

**Priorytet:** 🟢 NISKI (enhancement UX, nie bug)

---

## 🔧 PLAN NAPRAWY (REKOMENDOWANA KOLEJNOŚĆ)

### FAZA 1: Krytyczne Fixy (MUSZĄ być naprawione TERAZ)

**1.1 Poprawić Media Property Names**
```bash
# Edytować: app/Http/Livewire/Products/Management/Traits/VariantCrudTrait.php
# Linia 971: $media->path → $media->file_path
# Linia 972: $media->thumbnail_path → $media->thumbnail_url
# Linia 1212: $media->path → $media->file_path
# Linia 1213: $media->thumbnail_path → $media->thumbnail_url
```

**1.2 Dodać Ładowanie media_ids w Edit Modal**
```bash
# Edytować: app/Http/Livewire/Products/Management/Traits/VariantCrudTrait.php
# Linia 840: ProductVariant::with('attributes') → with(['attributes', 'images'])
# Linia 848: Dodać 'media_ids' => $this->extractMediaIdsFromVariantImages($variant),
# Linia 862+: Dodać nową metodę extractMediaIdsFromVariantImages()
```

**1.3 Dodać Accessory do VariantImage**
```bash
# Edytować: app/Models/VariantImage.php
# Po linii 177: Dodać url() accessor
# Po url(): Dodać thumbnailUrl() accessor
```

---

### FAZA 2: Enhancement UX (OPCJONALNE)

**2.1 Implementować +N Indicator**
```bash
# Edytować 3 blade files:
# - product-list.blade.php
# - variant-row.blade.php
# - variants-tab.blade.php
# Dodać badge z count'em dodatkowych obrazów
```

---

## 🧪 PLAN TESTOWANIA

### Test Case #1: Tworzenie Wariantu z Obrazami

**Kroki:**
1. Otwórz ProductForm → Warianty tab
2. Kliknij "Dodaj wariant"
3. Zaznacz 2-3 obrazy z galerii produktu
4. Wypełnij dane wariantu, kliknij "Zapisz"
5. **Zapisz produkt** (commit pending variants)
6. Reload strony

**Oczekiwany Rezultat:**
- ✅ Wariant zapisany w DB z `variant_images` records
- ✅ `variant_images.image_path` zawiera valid file path (NOT NULL, NOT '')
- ✅ `variant_images.image_thumb_path` zawiera thumbnail URL
- ✅ Po reload obrazy nadal widoczne w wariant card

**SQL Weryfikacja:**
```sql
SELECT vi.id, vi.variant_id, vi.image_path, vi.image_thumb_path, vi.is_cover, vi.position
FROM variant_images vi
JOIN product_variants pv ON pv.id = vi.variant_id
WHERE pv.product_id = [TEST_PRODUCT_ID]
ORDER BY vi.variant_id, vi.position;

-- Oczekiwane:
-- image_path: 'products/...' (NOT NULL, NOT '')
-- image_thumb_path: 'http://...' lub NULL (OK)
```

---

### Test Case #2: Edycja Wariantu - Ładowanie Obrazów

**Kroki:**
1. Otwórz ProductForm dla produktu z wariantem (który ma przypisane obrazy)
2. Warianty tab → kliknij "Edytuj" na wariancie z obrazami
3. Modal się otwiera

**Oczekiwany Rezultat:**
- ✅ Sekcja "Zdjęcia wariantu" pokazuje **zaznaczone** obrazy (niebieska ramka)
- ✅ Badge "Wybrano: N" pokazuje poprawny count
- ✅ Obrazy są w poprawnej kolejności (position)

**Debug Check:**
```javascript
// Browser Console
$wire.variantData.media_ids
// Oczekiwane: [123, 456, 789] (array of Media IDs)
```

---

### Test Case #3: ProductList Thumbnails

**Kroki:**
1. Przejdź do /admin/products (lista produktów)
2. Rozwiń warianty produktu (który ma warianty z obrazami)
3. Sprawdź kolumnę "Thumbnail"

**Oczekiwany Rezultat:**
- ✅ Każdy wariant pokazuje miniaturkę cover image
- ✅ Obrazy się ładują (nie placeholder)
- ✅ URL obrazków: `http://.../thumbnail?mediaId=...&w=200&h=200`

**Network Tab Check:**
```
GET /thumbnail?mediaId=123&w=200&h=200
Status: 200 OK
Content-Type: image/jpeg
```

---

### Test Case #4: +N Indicator (po implementacji Phase 2)

**Kroki:**
1. Stwórz wariant z 3+ obrazami
2. Przejdź do ProductList
3. Rozwiń warianty

**Oczekiwany Rezultat:**
- ✅ Cover image z badge'em "+2" (lub "+N" w zależności od liczby dodatkowych zdjęć)
- ✅ Badge w prawym górnym rogu miniaturki
- ✅ Niebieski kolor badge (bg-blue-600)

---

## 📊 ROOT CAUSE ANALYSIS

### Dlaczego te bugi powstały?

**1. Inconsistency w konwencjach nazewnictwa:**
- Media model: `file_path` (database column name)
- Developer assumption: `path` (shorthand)
- **Lekcja:** Zawsze weryfikuj faktyczne column names w migration/model

**2. Brak accessorów w VariantImage:**
- Media model: Ma accessors (`url()`, `thumbnailUrl()`)
- VariantImage model: Ma tylko metody (`getUrl()`, `getThumbUrl()`)
- Blade templates: Używają accessors (snake_case)
- **Lekcja:** Consistency między models (albo wszędzie accessors, albo wszędzie metody)

**3. Niekompletna implementacja Edit flow:**
- Create flow: Zapisuje `media_ids` → VariantImages
- Edit flow: **NIE** ładuje z powrotem VariantImages → `media_ids`
- **Lekcja:** CRUD operations muszą być symetryczne (save ↔ load)

**4. Brak testów:**
- Brak automated tests dla variant images flow
- Brak manual test checklist
- **Lekcja:** Krytyczne features wymagają test coverage

---

## 🎯 REKOMENDACJE DŁUGOTERMINOWE

### 1. Database Schema Refactoring (OPCJONALNE)

**Problem:** VariantImage przechowuje `image_path` (duplicated data z Media), co powoduje:
- Data inconsistency (jeśli Media.file_path się zmieni)
- Trudność w reverse mapping (VariantImage → Media)

**Rozwiązanie:**
```sql
-- Add column to variant_images
ALTER TABLE variant_images ADD COLUMN media_id INT UNSIGNED AFTER variant_id;
ALTER TABLE variant_images ADD CONSTRAINT fk_variant_images_media
    FOREIGN KEY (media_id) REFERENCES media(id) ON DELETE CASCADE;
```

**Zalety:**
- ✅ Single source of truth (Media table)
- ✅ Łatwa konwersja w `loadVariantForEdit()`: `$variant->images->pluck('media_id')`
- ✅ Automatyczne cascading deletes

**Wady:**
- ⚠️ Wymaga migration + data migration (fill media_id from image_path)
- ⚠️ Breaking change dla istniejących danych

---

### 2. Automated Testing

**Rekomendacja:** Dodać Feature Tests

```php
// tests/Feature/VariantImagesTest.php

/** @test */
public function variant_images_persist_after_save()
{
    $product = Product::factory()->create();
    $media = Media::factory()->count(3)->create([
        'mediable_type' => Product::class,
        'mediable_id' => $product->id,
    ]);

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'sku' => 'TEST-V001',
        // ... other fields
    ]);

    // Assign images
    foreach ($media as $index => $mediaItem) {
        VariantImage::create([
            'variant_id' => $variant->id,
            'image_path' => $mediaItem->file_path,
            'image_thumb_path' => $mediaItem->thumbnail_url,
            'is_cover' => ($index === 0),
            'position' => $index,
        ]);
    }

    // Reload from database
    $variant->refresh();

    // Assertions
    $this->assertCount(3, $variant->images);
    $this->assertNotEmpty($variant->images->first()->image_path);
    $this->assertNotEquals('', $variant->images->first()->image_path);
}

/** @test */
public function edit_modal_loads_existing_images()
{
    // ... setup variant with images ...

    Livewire::test(ProductForm::class, ['productId' => $product->id])
        ->call('loadVariantForEdit', $variant->id)
        ->assertSet('variantData.media_ids', $media->pluck('id')->toArray());
}
```

---

### 3. Code Documentation

**Rekomendacja:** Dodać PHPDoc komentarze wyjaśniające konwersję

```php
/**
 * Assign selected images to variant from product gallery
 *
 * IMPORTANT: VariantImage stores image_path (file path from Media.file_path),
 * NOT media_id. This is by design for data independence, but requires
 * reverse mapping when loading for edit (see extractMediaIdsFromVariantImages).
 *
 * @param ProductVariant $variant
 * @return void
 */
protected function assignVariantImage(ProductVariant $variant): void
{
    // ...
}
```

---

## ✅ PODSUMOWANIE

**Znalezione Bugi:**
- 🔴 **KRYTYCZNE**: 3 bugi (nieprawidłowe property names + brak ładowania media_ids)
- 🟡 **ŚREDNIE**: 3 bugi (brak accessorów w VariantImage)
- 🟢 **NISKIE**: 1 missing feature (+N indicator)

**Wpływ na System:**
- Obrazy wariantów NIE zapisują się poprawnie (empty paths)
- Edit modal NIE pokazuje przypisanych obrazów
- ProductList NIE renderuje miniaturek wariantów

**Priorytet Naprawy:**
1. **TERAZ**: Poprawić Media property names (BUG #1-2)
2. **TERAZ**: Dodać ładowanie media_ids (BUG #3)
3. **TERAZ**: Dodać accessory do VariantImage (BUG #4-6)
4. **PÓŹNIEJ**: Implementować +N indicator (enhancement)

**Czas Naprawy (szacowany):**
- FAZA 1: ~30-45 min (krytyczne fixy)
- FAZA 2: ~15-20 min (enhancement UX)
- Testing: ~20-30 min

**Następne Kroki:**
1. Implementować fixy z FAZA 1
2. Deploy do produkcji
3. Przeprowadzić manual testing (Test Cases #1-3)
4. Opcjonalnie: Implementować FAZA 2 (enhancement)

---

**Raport Zakończony**: 2025-12-04
**Agent**: debugger
**Status**: ✅ ROOT CAUSE ZIDENTYFIKOWANY - Gotowy do implementacji fixów
