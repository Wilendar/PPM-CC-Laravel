# ProductForm – reguły strony

---

## REFACTORING NOTICE (2025-11-21)

**STATUS:** ✅ **REFACTORING COMPLETED** - Struktura monolityczna (2200 linii) → Modularny system TABS + PARTIALS

**ARCHITEKTURA:**
- **Main orchestrator:** `product-form.blade.php` (~100 linii)
- **6 TABS** (conditional rendering): `tabs/basic-tab.blade.php`, `description-tab.blade.php`, `physical-tab.blade.php`, `attributes-tab.blade.php`, `prices-tab.blade.php`, `stock-tab.blade.php`
- **9 PARTIALS** (always included): `partials/form-header.blade.php`, `form-messages.blade.php`, `tab-navigation.blade.php`, `shop-management.blade.php`, `quick-actions.blade.php`, `product-info.blade.php`, `category-tree-item.blade.php`, `category-browser.blade.php`, `product-shop-tab.blade.php`

**KATALOGI:**
```
resources/views/livewire/products/management/
├── product-form.blade.php          # Main orchestrator
├── tabs/                           # Conditional (only 1 in DOM)
└── partials/                       # Always included (reusable)
```

**FUNKCJONALNOŚĆ:** Reguły opisane poniżej NADAL obowiązują - refactoring zmienił TYLKO strukturę plików (separation of concerns), NIE logikę biznesową.

**GDZIE SĄ FUNKCJE:**
- **Kontekst/Pending changes:** `ProductForm.php` (backend Livewire component) - bez zmian
- **Statusy pól:** `ProductForm.php::getFieldClasses()`, `getFieldStatusIndicator()` - używane we wszystkich tabs
- **Kategorie:** `tabs/basic-tab.blade.php` (Categories Section lines 813-856) + `partials/category-tree-item.blade.php` (recursive tree)
- **Shop sync:** `partials/shop-management.blade.php` (dropdown wyboru sklepu + badge status)
- **Job monitoring:** `product-form.blade.php` główny kontener (wire:poll) + `partials/quick-actions.blade.php` (przyciski sync)
- **Sidebar:** `partials/quick-actions.blade.php` + `partials/product-info.blade.php`

**SZCZEGÓŁY REFACTORINGU:**
- 📖 [`ProductForm_REFACTORING_2025-11-22.md`](ProductForm_REFACTORING_2025-11-22.md) - Pełna dokumentacja: architektura, critical bug case study, 5 lessons learned, mandatory rules
- 📖 [`_DOCS/Struktura_Plikow_Projektu.md`](../Struktura_Plikow_Projektu.md) - Struktury katalogów + tabele odpowiedzialności TABS/PARTIALS

---

## Kontekst formularza i pending changes
- `switchToShop` zapisuje bieżący stan do `pendingChanges`, czyści cache walidacji kategorii i ładuje dane tylko dla aktywnego kontekstu (app/Http/Livewire/Products/Management/ProductForm.php:2187); w trybie sklepu przed danymi ładowane są tax rule groups.
- `savePendingChanges` trzyma pełny snapshot pól per kontekst (w tym `contextCategories`, `tax_rate_override`), aby uniknąć mieszania danych między zakładkami (ProductForm.php:2579).
- `markFormAsChanged` podnosi `hasUnsavedChanges` i automatycznie zapisuje `pendingChanges` na każde `updated`, z pominięciem pól technicznych (ProductForm.php:2732,3339+); badge „Niezapisane zmiany” w headerze i `beforeunload` w widoku opierają się na tym stanie (resources/views/livewire/products/management/product-form.blade.php:10-170).
- Reset kontekstu (`resetToDefaults`) usuwa pending tylko dla aktywnego kontekstu i ładuje dane z DB/`defaultData` (ProductForm.php:2749).

## Statusy pól i blokady pending sync
- Status pola (`default`/`inherited`/`same`/`different`) liczony względem `defaultData` lub wartości dziedziczonych; puste wartości traktowane jako dziedziczone, `tax_rate` ma osobną ścieżkę z `shopTaxRateOverrides` (ProductForm.php:2798).
- Klasy pól (`getFieldClasses`) doklejają priorytetowo `field-pending-sync` przy `sync_status='pending'` oraz `field-status-*` dla kolorów statusu (ProductForm.php:3207); widok używa ich na wszystkich inputach (product-form.blade.php:538-740).
- Badge statusu (`getFieldStatusIndicator`) zwraca `pending-sync-badge` przy pending sync, inaczej `status-label-*` (ProductForm.php:3239). CSS: `field-status-*` i `status-label-*` w resources/css/admin/components.css:4893-5007; `field-pending-sync`/`pending-sync-badge` w resources/css/products/product-form.css:5-36 (dublet również w components.css sekcja „Pending Sync Visual States”).
- `isPendingSyncForShop` sprawdza tylko `ProductShopData::STATUS_PENDING` z DB; gdy pending, wszystkie pola kontekstu dostają blokadę `field-pending-sync` niezależnie od listy `pending_fields` (ProductForm.php:3298).

## Kategorie – dziedziczenie, mapowania, blokady
- Status kategorii (`getCategoryStatus`/`getCategoryStatusIndicator`) porównuje kontekst z danymi domyślnymi; statusy `inherited`/`same`/`different` mapują się na klasy `category-status-*`, a przy wykryciu oczekujących zmian zwracany jest badge `status-label-pending` (ProductForm.php:2954,3076-3088). `status-label-pending` nie ma definicji w CSS – do uzupełnienia.
- Blokada edycji kategorii (`isCategoryEditingDisabled`) działa przy `isSaving` lub `sync_status='pending'` dla aktywnego sklepu; UI ustawia `@disabled` na checkboxach i „Ustaw główną” (category-tree-item.blade.php:45,65). `getCategoryClasses` zwraca wtedy `category-status-pending` + żółte tony (ProductForm.php:3147-3182), ale klasa `category-status-pending` nie jest zdefiniowana w CSS – brakujący styl.
- Kategorie są kontekstowe i przechowują mapowania PrestaShop→PPM (Option A). `getPrestaShopCategoryIdsForContext`/`getPrimaryPrestaShopCategoryIdForContext` wykonują lazy-load mapowań z `product_shop_data.category_mappings` (ProductForm.php:1571-1676). `convertPrestaShopIdToPpmId` używane przy wyborze w drzewie (ProductForm.php:1687).
- `loadShopDataToForm` omija reload kategorii, gdy `sync_status='pending'`, aby nie nadpisać świeżo zapisanych zmian przed zakończeniem joba (ProductForm.php:2299-2342).
- `loadProductDataFromPrestaShop` przy pending sync ładuje tylko kategorie (`$loadCategoriesOnly=true`), mapuje je na PPM, zapisuje Option A do DB i odświeża UI/Alpine (ProductForm.php:6251-6440).

## Integracja z PrestaShop i monitoring jobów
- Główny kontener ma `wire:poll.5s="checkJobStatus"` z auto-stopem przy braku joba lub statusach completed/failed (product-form.blade.php:10-11). `jobCountdown` JS animuje `btn-job-*` (resources/views/livewire/products/management/product-form.blade.php:1800+; style w resources/css/admin/components.css sekcja „ETAP_13: JOB COUNTDOWN ANIMATIONS”).
- `checkJobStatus` obsługuje single/bulk sync/pull, ustawia `activeJobStatus`/`jobResult` i po sukcesie triggeruje `pullShopData` dla aktywnego sklepu w celu auto-refreshu kategorii (ProductForm.php:3942-4096).
- `pullShopData` (przycisk „Wczytaj z aktualnego sklepu”) najpierw zapisuje pending dla bieżącego kontekstu, blokuje pobieranie gdy `sync_status='pending'`, a po pobraniu zapisuje `category_mappings` Option A i przeładowuje UI + `reloadCleanShopCategories` (ProductForm.php:4317-4707).
- `loadProductDataFromPrestaShop` korzysta z `PrestaShopClientFactory` i `CategoryMappingsValidator/Converter`; przy pending sync nie pobiera pól tekstowych/cen, żeby nie nadpisać zmian (ProductForm.php:6251-6380).

## Style i komponenty powiązane
- `category-status-*`, `pending-sync-badge`, `field-pending-sync`, `shop-tab-*`, `status-badge` itp. są zdefiniowane w resources/css/products/product-form.css (sekcje Pending Sync, Category Status Indicators, Shop Tabs) oraz resources/css/admin/components.css (sekcje Product Form Field Status Styles, Status Label Badges).
- Kategorie renderuje makro `category-tree-item.blade.php` (checkbox + „Ustaw główną”) i bazuje na `isCategoryEditingDisabled` oraz `getPrestaShopCategoryIdsForContext`; drzewo korzysta z `category-manager` i Resource tree przekazywanego przez `getShopCategories()` w widoku (product-form.blade.php:1068-1125).
- Shop tab sidepanel (`product-shop-tab.blade.php`) pokazuje badge pending (`shopData->sync_status === 'pending'`) i listy `pending_fields` (resources/views/livewire/products/management/partials/product-shop-tab.blade.php:1-180); style w product-form.css sekcja Shop Tabs.

## Luki do poprawy (wykryte podczas audytu kodu)
- Brak styli dla `status-label-pending` użytej w `getCategoryStatusIndicator` (ProductForm.php:3088) – badge pending kategorii renderuje się bez kolorystyki; dodać w existing CSS (np. resources/css/admin/components.css obok `status-label-*`).
- Brak styli dla `category-status-pending` zwracanej w `getCategoryClasses` przy blokadzie edycji (ProductForm.php:3179); UI używa klasy, ale nie istnieje w żadnym CSS (resources/css/products/product-form.css / admin/components.css) – należy uzupełnić, aby wizualnie odróżniać stan „pending”.
