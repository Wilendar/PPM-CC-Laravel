# RAPORT: Bug miniaturek wariantów w zakładce Warianty

**Data:** 2025-12-04
**Agent:** frontend-specialist
**Priorytet:** HIGH
**Status:** 🔍 ZDIAGNOZOWANY - Wymaga poprawki

---

## 🐛 OPIS PROBLEMU

Miniaturki zdjęć wariantów NIE WYŚWIETLAJĄ SIĘ w tabeli wariantów (zakładka Warianty w ProductForm).

**Objawy:**
- ✅ Modal tworzenia wariantu: wybór zdjęć działa
- ✅ Wariant zapisuje się z media_ids
- ❌ Po utworzeniu: miniaturka pokazuje tylko szary placeholder (ikona)
- ❌ Existing variants też mają placeholder zamiast miniaturki

---

## 🔍 ROOT CAUSE ANALYSIS

### Problem 1: Pending Variants NIE MAJĄ dostępu do zdjęć

**Lokalizacja:** `variants-tab.blade.php` linie 176-180

```php
// ❌ PROBLEMATIC CODE
@php
    // Skip getCoverImage() for pending creates (they don't have DB model)
    $coverImage = $isPendingCreate ? null : ($variant->getCoverImage() ?? null);
    $imageUrl = $coverImage?->getUrl() ?? $coverImage?->getThumbUrl();
@endphp
```

**Analiza:**
1. **Pending variants** (negative ID) są CAŁKOWICIE POMIJANE (`$coverImage = null`)
2. Kod zakłada: "no DB model = no images"
3. **BŁĄD:** Pending variants MAJĄ `media_ids` w `$pendingVariantCreates`!

**Dane dostępne dla pending variants:**
```php
// VariantCrudTrait.php linia 324-333
$this->pendingVariantCreates[$tempId] = [
    'sku' => '...',
    'name' => '...',
    'media_ids' => [15, 23, 42], // ✅ ARRAY of selected Media IDs!
    // ...
];
```

**Przygotowanie do wyświetlenia (linia 1163-1179):**
```php
// getAllVariantsForDisplay() - tworzy object dla UI
$pendingVariant = (object) [
    'id' => $tempId,
    'sku' => $data['sku'],
    'name' => $data['name'],
    // ...
    'attributes' => collect(), // Empty
    'images' => collect(),     // ❌ EMPTY! Powinny być Media objects
];
```

**Brakuje:** Konwersja `media_ids` → `Media` objects

---

### Problem 2: Existing Variants - Brak eager loading relacji `images`

**Lokalizacja:** `ProductVariant` model line 66-71

```php
protected $with = [
    'attributes',
    'prices',
    'stock',
    'images',  // ✅ Zdefiniowane w $with
];
```

**Relacja images (linia 114-117):**
```php
public function images(): HasMany
{
    return $this->hasMany(VariantImage::class, 'variant_id')->orderBy('position');
}
```

**Metoda getCoverImage (linia 242-247):**
```php
public function getCoverImage(): ?VariantImage
{
    return $this->images()->where('is_cover', true)->first()
        ?? $this->images()->first();
}
```

**Problem:** `getCoverImage()` wykonuje **nowe query** zamiast użyć eager loaded `$this->images`

**Query performance:**
- ❌ Current: N+1 problem (1 query per variant)
- ✅ Should: Use loaded collection (0 queries)

---

### Problem 3: VariantImage - Poprawne metody, ale nieużywane

**Model VariantImage:**
- ✅ `getUrl()` - zwraca URL z storage (linia 137-140)
- ✅ `getThumbUrl()` - zwraca thumbnail URL (linia 168-177)
- ✅ Metody działają poprawnie

**Blade używa:**
```php
$imageUrl = $coverImage?->getUrl() ?? $coverImage?->getThumbUrl();
```

**Problem:** Dla pending variants `$coverImage === null` (Problem 1)

---

## 📊 FLOW ANALIZY

### Existing Variant (ID > 0):

```
ProductVariant loaded
    ↓
Blade: $variant->getCoverImage()  [NEW QUERY!]
    ↓
VariantImage::where('variant_id', X)->where('is_cover', true)->first()
    ↓ (success)
VariantImage object
    ↓
Blade: $coverImage->getUrl()
    ↓
Storage::url('variants/...')
    ↓
✅ Display thumbnail
```

**Issue:** Extra query (N+1), ale **DZIAŁA** jeśli relacja `images` została zapisana w DB

---

### Pending Variant (ID < 0):

```
Pending create array from VariantCrudTrait
    ↓
getAllVariantsForDisplay() converts to object
    ↓
Object has: 'images' => collect()  [EMPTY!]
    ↓
Blade: $isPendingCreate ? null : ...
    ↓
❌ $coverImage = null (forced)
    ↓
❌ Display placeholder icon
```

**Issue:**
1. `media_ids` array NOT converted to Media objects
2. Blade code explicitly skips pending variants

---

## 🛠️ PROPOZYCJA ROZWIĄZANIA

### Fix 1: getAllVariantsForDisplay() - Populate images for pending variants

**Lokalizacja:** `VariantCrudTrait.php` linia 1163-1180

**Przed:**
```php
$pendingVariant = (object) [
    'id' => $tempId,
    'sku' => $data['sku'],
    // ...
    'images' => collect(), // ❌ Empty
];
```

**Po:**
```php
// Convert media_ids to Media objects
$mediaObjects = collect();
if (!empty($data['media_ids'])) {
    $mediaObjects = \App\Models\Media::whereIn('id', $data['media_ids'])
        ->orderByRaw('FIELD(id, ' . implode(',', $data['media_ids']) . ')')
        ->get();
}

$pendingVariant = (object) [
    'id' => $tempId,
    'sku' => $data['sku'],
    'name' => $data['name'],
    // ...
    'images' => $mediaObjects, // ✅ Collection of Media models
];
```

---

### Fix 2: Blade - Handle pending variants with images

**Lokalizacja:** `variants-tab.blade.php` linia 176-180

**Przed:**
```php
@php
    $coverImage = $isPendingCreate ? null : ($variant->getCoverImage() ?? null);
    $imageUrl = $coverImage?->getUrl() ?? $coverImage?->getThumbUrl();
@endphp
```

**Po:**
```php
@php
    if ($isPendingCreate) {
        // Pending variant: use first image from images collection
        $coverImage = $variant->images->first();
    } else {
        // Existing variant: use getCoverImage() (eager loaded)
        $coverImage = $variant->images->where('is_cover', true)->first()
            ?? $variant->images->first();
    }

    // Pending uses Media model, existing uses VariantImage model
    // Both have path/thumbnail_path attributes
    if ($coverImage) {
        // Check if Media (pending) or VariantImage (existing)
        if ($coverImage instanceof \App\Models\Media) {
            // Media model uses 'path' and 'thumbnail_path'
            $imageUrl = Storage::disk('public')->url($coverImage->thumbnail_path ?? $coverImage->path);
        } else {
            // VariantImage uses getUrl() and getThumbUrl()
            $imageUrl = $coverImage->getThumbUrl() ?? $coverImage->getUrl();
        }
    } else {
        $imageUrl = null;
    }
@endphp
```

---

### Fix 3: Optimize getCoverImage() - Use eager loaded collection

**Lokalizacja:** `ProductVariant.php` linia 242-247

**Przed:**
```php
public function getCoverImage(): ?VariantImage
{
    return $this->images()->where('is_cover', true)->first()  // ❌ NEW QUERY
        ?? $this->images()->first();                           // ❌ NEW QUERY
}
```

**Po:**
```php
public function getCoverImage(): ?VariantImage
{
    // Use loaded collection (0 queries)
    if ($this->relationLoaded('images')) {
        return $this->images->where('is_cover', true)->first()  // ✅ In-memory
            ?? $this->images->first();                           // ✅ In-memory
    }

    // Fallback: query if not loaded (backwards compatibility)
    return $this->images()->where('is_cover', true)->first()
        ?? $this->images()->first();
}
```

---

## 📁 PLIKI DO MODYFIKACJI

1. **app/Http/Livewire/Products/Management/Traits/VariantCrudTrait.php**
   - Metoda: `getAllVariantsForDisplay()` (linia 1130-1184)
   - Zmiana: Dodaj konwersję `media_ids` → `Media` objects

2. **resources/views/livewire/products/management/tabs/variants-tab.blade.php**
   - Sekcja: Image Thumbnail (linia 174-198)
   - Zmiana: Obsługa pending variants + Media/VariantImage distinction

3. **app/Models/ProductVariant.php** (opcjonalne - performance)
   - Metoda: `getCoverImage()` (linia 242-247)
   - Zmiana: Użyj loaded collection zamiast nowego query

---

## 🧪 TEST CASES

### Test 1: Pending Variant - Jedno zdjęcie wybrane
```
1. ProductForm → Warianty tab
2. Kliknij "Dodaj wariant"
3. Wybierz JEDNO zdjęcie z galerii
4. Wypełnij SKU/Name
5. Kliknij "Utwórz wariant"

✅ EXPECTED: Miniaturka wybranego zdjęcia w tabeli
❌ CURRENT: Szary placeholder
```

### Test 2: Pending Variant - Wiele zdjęć wybranych
```
1-4. Jak Test 1
5. Wybierz 3 zdjęcia (pierwsze = cover)
6. Kliknij "Utwórz wariant"

✅ EXPECTED: Miniaturka PIERWSZEGO zdjęcia (cover)
❌ CURRENT: Szary placeholder
```

### Test 3: Existing Variant - Po zapisie
```
1. Pending variant z Test 1
2. Kliknij "Zapisz zmiany" (ProductForm save)
3. commitPendingVariants() wykonuje się
4. Wariant zapisany do DB z VariantImage records

✅ EXPECTED: Miniaturka nadal widoczna (teraz z VariantImage)
⚠️ CURRENT: Może działać jeśli Fix 3 applied
```

### Test 4: Existing Variant - Load from DB
```
1. Product z zapisanymi wariantami (DB)
2. Edit product
3. Przejdź do zakładki Warianty

✅ EXPECTED: Wszystkie miniaturki widoczne
❌ CURRENT: Placeholders (jeśli brak VariantImage records)
```

---

## 📊 IMPACT ASSESSMENT

**Severity:** HIGH
**User Impact:** Krytyczne - nie widać zdjęć wariantów (core feature)
**Frequency:** 100% (każdy pending variant)

**Business Impact:**
- ❌ User nie widzi miniaturek przy tworzeniu wariantów
- ❌ Brak visual feedback = confusion
- ❌ Wygląda jak bug/broken feature

**Technical Debt:**
- N+1 query problem (Fix 3)
- Incomplete data preparation (Fix 1)
- Model type inconsistency (pending vs existing)

---

## ✅ SUGGESTED PRIORITY ORDER

1. **Fix 1** (VariantCrudTrait) - CRITICAL - Enables images for pending
2. **Fix 2** (Blade template) - CRITICAL - Displays images correctly
3. **Fix 3** (ProductVariant) - NICE-TO-HAVE - Performance optimization

**Estimated effort:** 30-45 minutes (all fixes)

---

## 🔗 RELATED FILES

**Models:**
- `app/Models/ProductVariant.php` - Relacja images, getCoverImage()
- `app/Models/VariantImage.php` - getUrl(), getThumbUrl()
- `app/Models/Media.php` - path, thumbnail_path attributes

**Traits:**
- `app/Http/Livewire/Products/Management/Traits/VariantCrudTrait.php` - Pending system

**Views:**
- `resources/views/livewire/products/management/tabs/variants-tab.blade.php` - Tabela

**Migrations:**
- `*_create_variant_images_table.php` - Schema (image_path, image_thumb_path)
- `*_create_media_table.php` - Schema (path, thumbnail_path)

---

## 📝 NOTATKI

1. **Pending vs Existing distinction:**
   - Pending: `media_ids` array → `Media` objects → `path`/`thumbnail_path`
   - Existing: `VariantImage` objects → `image_path`/`image_thumb_path`

2. **Storage disk:**
   - Both use `Storage::disk('public')`
   - Media: auto-generates URLs via accessor
   - VariantImage: manual URL generation in methods

3. **Why separate VariantImage table:**
   - Allows variant-specific image metadata (is_cover, position)
   - Decouples from product's Media table
   - Supports future: variant-specific watermarks, crops

4. **Future enhancement:**
   - Consider: unified Media polymorphic for variants too
   - Would eliminate Media/VariantImage distinction
   - Requires: migration + refactor

---

## 🚀 NEXT STEPS

**Dla implementacji:**
1. Apply Fix 1 (VariantCrudTrait)
2. Apply Fix 2 (Blade template)
3. Test both pending and existing variants
4. Apply Fix 3 if performance issues observed
5. Deploy + verify production

**Dla testowania:**
1. Create pending variant with 1 image
2. Create pending variant with 3 images
3. Save product → verify thumbnails persist
4. Edit existing product → verify thumbnails load

---

**Raport wygenerowany przez:** frontend-specialist agent
**Review required by:** livewire-specialist, coding-style-agent
