# TODO: Category Sync Issues (2025-11-18)

**Data utworzenia:** 2025-11-18
**Priorytet:** HIGH
**Status:** 🔴 NOWE (wymaga analizy i implementacji)

---

## 🐛 PROBLEM #1: Przycisk "Aktualizuj aktualny sklep" nie aktualizuje kategorii

**Symptom:**
- Przycisk "Aktualizuj aktualny sklep" (Pull from PrestaShop) NIE aktualizuje kategorii produktu
- Kategorie są aktualizowane TYLKO przez przycisk "Zapisz zmiany"

**Expected behavior:**
- Po kliknięciu "Aktualizuj aktualny sklep" system powinien:
  1. Pobrać dane produktu z PrestaShop (w tym kategorie)
  2. Zaktualizować kategorie w PPM (pivot table + cache)
  3. Odświeżyć UI (pokazać nowe kategorie)

**Current behavior:**
- Przycisk pobiera inne dane (np. ceny, stock) ale NIE kategorie
- Użytkownik musi manualnie zapisać produkt aby kategorie się zsynchronizowały

**Impact:** MEDIUM
- Użytkownicy muszą wykonać 2 akcje zamiast 1
- Brak real-time sync kategorii z PrestaShop

**Possible root cause:**
- `pullShopData()` method w ProductForm nie wywołuje sync kategorii
- Brak wywołania `CategoryMappingsConverter::fromPrestaShopFormat()` w pull flow
- Cache kategorii nie jest odświeżany po pull

**Files to investigate:**
- `app/Http/Livewire/Products/Management/ProductForm.php` (pullShopData method)
- `app/Services/PrestaShop/PrestaShopImportService.php` (import categories logic)
- `app/Services/CategoryMappingsConverter.php` (fromPrestaShopFormat method)

**TODO Tasks:**
- [ ] Przeanalizuj `pullShopData()` flow (czy pobiera kategorie?)
- [ ] Sprawdź czy `fromPrestaShopFormat()` jest wywoływany podczas pull
- [ ] Dodaj sync kategorii do pull flow (pivot table + cache)
- [ ] Dodaj refresh UI po pull (emit event do category picker)
- [ ] Przetestuj E2E: Pull → Verify categories updated
- [ ] Deploy + user testing

---

## 🐛 PROBLEM #2: Kategoria domyślna (primary) nie jest ustawiana w PrestaShop

**Symptom:**
- Kategoria oznaczona jako "główna" w PPM nie jest ustawiana jako default category w PrestaShop
- PrestaShop może mieć inną kategorię jako default lub żadną

**Expected behavior:**
- Kategoria z `is_primary = 1` w PPM → PrestaShop `id_category_default` w `ps_product`
- Podczas sync system powinien:
  1. Znaleźć primary category w pivot table (`is_primary = 1`)
  2. Zmapować PPM ID → PrestaShop ID via CategoryMapper
  3. Ustawić `id_category_default` w PrestaShop XML payload

**Current behavior:**
- Primary category NIE jest synchronizowana na PrestaShop
- PrestaShop używa pierwszej kategorii z listy jako default (arbitrary)

**Impact:** MEDIUM-HIGH
- URL produktu w PrestaShop może być nieprawidłowy (bazuje na default category)
- Breadcrumbs w PrestaShop pokazują złą główną kategorię
- SEO impact (canonical URLs)

**Possible root cause:**
- `ProductTransformer::buildCategoryAssociations()` nie ustawia `id_category_default`
- PrestaShop XML payload brak `<id_category_default>` node
- Primary category nie jest przekazywana do transformer

**Files to investigate:**
- `app/Services/PrestaShop/ProductTransformer.php` (buildProductXml, buildCategoryAssociations)
- `app/Services/CategoryMappingsConverter.php` (getPrimaryPrestaShopId method)
- PrestaShop XML schema (czy wspiera `<id_category_default>`?)

**TODO Tasks:**
- [ ] Przeanalizuj PrestaShop XML schema dla `id_category_default`
- [ ] Dodaj logic w ProductTransformer:
  - [ ] Znajdź primary category (pivot table `is_primary = 1`)
  - [ ] Map PPM ID → PrestaShop ID
  - [ ] Dodaj `<id_category_default>` do XML payload
- [ ] Przetestuj E2E: Ustaw primary w PPM → Sync → Verify w PrestaShop DB
- [ ] Deploy + user testing
- [ ] Update dokumentacji (CATEGORY_EXPORT_USER_GUIDE.md)

**PrestaShop Database Verification:**
```sql
-- Sprawdź default category produktu
SELECT id_product, id_category_default, reference
FROM ps_product
WHERE reference = '[SKU]';

-- Sprawdź wszystkie kategorie produktu
SELECT pc.*, c.name
FROM ps_category_product pc
JOIN ps_category_lang c ON pc.id_category = c.id_category
WHERE pc.id_product = [id]
  AND c.id_lang = 1;
```

---

## ⚡ PROBLEM #3: Auto-pobieranie kategorii z PrestaShop przy wejściu na TAB sklepu (Performance Concern)

**Request:**
- Auto-fetch kategorii z PrestaShop gdy użytkownik wchodzi na zakładkę sklepu w ProductForm
- Problem: Może zabić wydajność (API call na każde otwarcie tab)

**Performance Concerns:**
1. **API Latency:** PrestaShop API call = 200-500ms (zależnie od serwera)
2. **Rate Limiting:** Zbyt częste requesty mogą przekroczyć limit API (default: 2 req/s)
3. **UX:** User experience - UI freeze podczas fetch
4. **Scale:** 100+ produktów × 5 sklepów = 500 API calls w krótkim czasie

**Proposed Solutions:**

### Option A: Lazy Loading z Cache (RECOMMENDED)

**Workflow:**
1. User wchodzi na tab sklepu → Sprawdź cache
2. **IF cache valid** (< 15 min) → Użyj cache, skip API
3. **IF cache stale** → Async fetch w tle, show stale data + spinner
4. **IF no cache** → Fetch synchronicznie (first time only)

**Implementation:**
```php
// ProductForm::switchToShop()
public function switchToShop(int $shopId)
{
    $this->selectedShopId = $shopId;

    // Check cache
    $cacheKey = "shop_categories_{$shopId}";
    $cached = Cache::get($cacheKey);

    if ($cached && $cached['timestamp'] > now()->subMinutes(15)) {
        // Use cached categories (fast path)
        $this->shopCategories[$shopId] = $cached['data'];
    } else {
        // Async fetch in background
        $this->dispatch('fetch-shop-categories', shopId: $shopId);

        // Show stale data if available
        if ($cached) {
            $this->shopCategories[$shopId] = $cached['data'];
        }
    }
}
```

**Cache Strategy:**
- TTL: 15 minut (balance between freshness + performance)
- Storage: Redis (preferred) or database (fallback)
- Invalidation: Manual refresh button + auto-refresh on save

**Pros:**
- ✅ Fast response (cache hit = <10ms)
- ✅ Minimal API calls
- ✅ Good UX (no freeze)

**Cons:**
- ⚠️ Data może być stale (max 15 min)
- ⚠️ Complexity (cache management)

---

### Option B: On-Demand Fetch (Click to Load)

**Workflow:**
1. User wchodzi na tab sklepu → Show placeholder
2. Show button: "📥 Pobierz kategorie z PrestaShop"
3. User clicks → Fetch categories → Update UI

**Implementation:**
```php
<div x-show="selectedShop === {{ $shopId }}">
    @if(!isset($shopCategories[$shopId]))
        <div class="text-center py-4">
            <button wire:click="fetchShopCategories({{ $shopId }})"
                    wire:loading.attr="disabled"
                    class="btn-primary">
                <span wire:loading.remove>📥 Pobierz kategorie z PrestaShop</span>
                <span wire:loading>⏳ Pobieranie...</span>
            </button>
        </div>
    @else
        <!-- Category picker -->
    @endif
</div>
```

**Pros:**
- ✅ Explicit user action (no surprise API calls)
- ✅ Simple implementation
- ✅ User control

**Cons:**
- ⚠️ Extra click required
- ⚠️ Poor UX (friction)

---

### Option C: Background Pre-fetch (Product Load)

**Workflow:**
1. User otwiera ProductForm → Fetch kategorii dla WSZYSTKICH sklepów w tle
2. Store in component state
3. Switch tab → Instant (już pobrane)

**Implementation:**
```php
// ProductForm::mount()
public function mount($productId)
{
    $this->product = Product::findOrFail($productId);

    // Pre-fetch categories for all linked shops
    $this->dispatch('prefetch-all-shop-categories',
        shopIds: $this->product->linkedShops->pluck('id')->toArray()
    );
}
```

**Pros:**
- ✅ Zero latency on tab switch
- ✅ Best UX

**Cons:**
- ⚠️ High upfront cost (N × API calls on load)
- ⚠️ Wasteful (user może nie odwiedzić wszystkich tabs)
- ⚠️ Rate limiting risk

---

### RECOMMENDATION: **Option A (Lazy Loading z Cache)**

**Reasoning:**
- Best balance performance + UX
- Minimizes API calls (cache hit rate ~80-90%)
- Graceful degradation (stale data better than no data)
- Easy to add "refresh" button for manual invalidation

**Implementation Plan:**
1. **Phase 1:** Cache infrastructure
   - [ ] Add cache layer (Redis/Database)
   - [ ] Implement cache get/set/invalidate
   - [ ] TTL: 15 minutes

2. **Phase 2:** ProductForm integration
   - [ ] Modify `switchToShop()` to check cache
   - [ ] Add async fetch for cache miss
   - [ ] Show stale data + spinner during refresh

3. **Phase 3:** Cache invalidation
   - [ ] Manual refresh button (force fetch)
   - [ ] Auto-invalidate on product save
   - [ ] Admin panel: "Clear shop categories cache"

4. **Phase 4:** Monitoring
   - [ ] Log cache hit/miss rates
   - [ ] Monitor API call frequency
   - [ ] Alert on cache failures

**Performance Targets:**
- Cache hit rate: >80%
- Tab switch latency: <100ms (cache hit)
- API calls per hour: <50 (for 100 active users)

---

## 📊 PRIORITY & EFFORT ESTIMATION

| Problem | Priority | Effort | Impact | Risk |
|---------|----------|--------|--------|------|
| #1: Pull button nie aktualizuje kategorii | HIGH | 4h | MEDIUM | LOW |
| #2: Primary category nie sync do PrestaShop | HIGH | 6h | HIGH | MEDIUM |
| #3: Auto-fetch kategorii (performance) | MEDIUM | 12h | MEDIUM | HIGH |

**Total Effort:** ~22h (~3 days)

**Recommended Order:**
1. Problem #2 (Primary category) - Highest impact, medium effort
2. Problem #1 (Pull button) - Quick win, low risk
3. Problem #3 (Auto-fetch) - Complex, needs careful planning

---

## 🔗 RELATED DOCUMENTATION

- User guide: `_DOCS/CATEGORY_EXPORT_USER_GUIDE.md`
- Architecture: `_DOCS/CATEGORY_MAPPINGS_ARCHITECTURE.md`
- Issue report: `_ISSUES_FIXES/CATEGORY_SYNC_STALE_CACHE_ISSUE.md`
- Compliance: `_AGENT_REPORTS/COMPLIANCE_REPORT_category_sync_stale_cache_fixes_2025-11-18.md`

---

## ✅ ACCEPTANCE CRITERIA

### Problem #1 (Pull Button)
- [ ] "Aktualizuj aktualny sklep" pobiera kategorie z PrestaShop
- [ ] Kategorie aktualizują się w pivot table (`product_categories`)
- [ ] Cache synchronizowany (`category_mappings`)
- [ ] UI odświeża category picker automatycznie
- [ ] E2E test passing

### Problem #2 (Primary Category)
- [ ] Primary category (`is_primary = 1`) synchronizuje się na PrestaShop
- [ ] `id_category_default` w `ps_product` ustawiony poprawnie
- [ ] Weryfikacja w PrestaShop admin panel
- [ ] Database check passing (SQL verification)
- [ ] Documentation updated

### Problem #3 (Auto-fetch Performance)
- [ ] Cache infrastructure implemented (Redis/Database)
- [ ] TTL: 15 minutes
- [ ] Cache hit rate: >80%
- [ ] Tab switch latency: <100ms (cache hit)
- [ ] Manual refresh button działa
- [ ] Monitoring + logging implemented
- [ ] Performance benchmarks met

---

**Następne kroki:** Analizuj problemy → Implementuj w kolejności priority → Test → Deploy → User feedback
