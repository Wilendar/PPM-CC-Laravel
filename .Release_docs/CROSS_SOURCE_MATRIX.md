# PPM - Cross-Source Matrix Panel Documentation

> **Wersja:** 1.1.0
> **Data:** 2026-02-26
> **Status:** Production Ready
> **Changelog:** v1.1.0 - Gmail "Select All Matching" pattern dla bulk operations z infinite scroll

---

## Spis tresci

1. [Overview](#1-overview)
2. [Architektura Plikow](#2-architektura-plikow)
3. [Schema Bazy Danych](#3-schema-bazy-danych)
4. [Properties](#4-properties)
5. [Metody Publiczne](#5-metody-publiczne)
6. [Cell Status System](#6-cell-status-system)
7. [Quick Matrix (Instant View)](#7-quick-matrix-instant-view)
8. [Chunked AJAX Scan](#8-chunked-ajax-scan)
9. [Cell Actions & Bulk Operations](#9-cell-actions--bulk-operations)
10. [Brand Suggestions](#10-brand-suggestions)
11. [Diff Viewer](#11-diff-viewer)
12. [Export XLSX/CSV](#12-export-xlsxcsv)
13. [UI - Blade Views](#13-ui---blade-views)
14. [CSS Styling](#14-css-styling)
15. [Eventy i Polling](#15-eventy-i-polling)
16. [Troubleshooting](#16-troubleshooting)
17. [Changelog](#17-changelog)

---

## 1. Overview

### 1.1 Opis modulu

**Cross-Source Matrix Panel** to panel macierzowy zastepujacy stary `ScanProductsPanel`. Wyswietla status kazdego produktu we WSZYSTKICH zrodlach naraz (PrestaShop shops + ERP connections) w jednym widoku tabelarycznym. Umozliwia natychmiastowy przeglad (Quick Matrix bez skanu), gleboki skan konfliktow (Chunked AJAX), one-click akcje na komorkach, bulk operacje, eksport i inteligentne sugestie marek.

**URL Panelu:** `/admin/scan-products`

### 1.2 Statystyki

| Metryka | Wartosc |
|---------|---------|
| Komponent Livewire | 1 (CrossSourceMatrixPanel) |
| Traits | 5 (MatrixData, MatrixFilters, MatrixActions, ChunkedScan, ScanSources) |
| Services | 2 (CrossSourceMatrixService, ChunkedScanEngine) |
| Modele | 1 nowy (DismissedBrandSuggestion) |
| Blade Views | 10 (1 shell + 8 partiali + 1 row template) |
| Export | 1 (ScanMatrixExport - XLSX/CSV) |
| Migracje | 2 |
| CSS | 1 dedykowany (cross-source-matrix.css, ~480 LOC) |
| Laczna ilosc LOC | ~4,117 |

### 1.3 Kluczowe funkcjonalnosci

- **Cross-Source Matrix** - jeden widok ze statusem produktu we WSZYSTKICH zrodlach (PS shops + ERP)
- **Quick Matrix** - natychmiastowy widok bez skanu (LEFT JOINs na `product_shop_data` + `product_erp_data`)
- **Chunked AJAX Scan** - gleboki skan 500 produktow/chunk z progress barem i ETA
- **8 statusow komorek** - linked, not_linked, not_found, unknown, ignored, conflict, brand_not_allowed, pending_sync
- **CSS Hover Actions** - ikony akcji pojawiaja sie na hover nad komorka (bez klikania)
- **Cell Popup** - kontekstowe menu z akcjami per status komorki
- **Bulk Operations** - zaznaczanie wierszy + masowe: link, publish, ignore
- **Select All Matching** - Gmail pattern: zaznaczanie WSZYSTKICH produktow pasujacych do filtrow (nie tylko zaladowanych)
- **Diff Viewer** - rozwijany wiersz z porownaniem PPM vs Source per pole
- **Brand Suggestions** - inteligentne sugestie dodania marki do dozwolonych per sklep
- **Export** - XLSX/CSV z aktywnych filtrow macierzy
- **Infinite Scroll** - doladowywanie 50 produktow per scroll
- **Grouped View** - opcjonalne grupowanie po marce z podsumowaniami
- **Source Visibility** - ukrywanie/pokazywanie kolumn zrodel
- **Scan Resume** - wznawianie przerwanego skanu po zamknieciu przegladarki
- **Auto-Refresh** - wire:poll.5s gdy sa komorki `pending_sync`

### 1.4 Workflow

```
                    CROSS-SOURCE MATRIX WORKFLOW

   ┌─────────────────────────────────────────────────────────┐
   │  WARSTWA 1: Quick Matrix (instant, BEZ skanu)          │
   │  LEFT JOINs na product_shop_data + product_erp_data    │
   │  Statusy: linked / missing / pending_sync / brand_not  │
   └──────────────────────┬──────────────────────────────────┘
                          │
                          ▼
   ┌─────────────────────────────────────────────────────────┐
   │  WARSTWA 2: Deep Scan (chunked AJAX, on-demand)        │
   │  Porownanie danych z API zrodel (name, ean, mfr)       │
   │  Wykrywa konflikty + roznice danych                     │
   └──────────────────────┬──────────────────────────────────┘
                          │
                          ▼
   ┌─────────────────────────────────────────────────────────┐
   │  AKCJE: Link / Publish / Ignore / Unlink / Force Sync  │
   │  Bulk: Zaznacz wiersze → masowa operacja               │
   │  Export: XLSX / CSV z aktywnych filtrow                 │
   └─────────────────────────────────────────────────────────┘
```

### 1.5 Dwuwarstwowy model danych

| Warstwa | Zrodlo danych | Czas ladowania | Wykrywa konflikty? |
|---------|--------------|----------------|-------------------|
| Quick Matrix | LEFT JOINs (DB) | Natychmiast | Nie |
| Deep Scan | API zrodel + porownanie | ~0.5s/chunk (500 prod.) | Tak |

---

## 2. Architektura Plikow

### 2.1 Backend - Livewire Component + Traits

| Plik | LOC | Opis |
|------|-----|------|
| `app/Http/Livewire/Admin/Scan/CrossSourceMatrixPanel.php` | 148 | Glowny komponent (mount, render, 2 zakladki) |
| `app/Http/Livewire/Admin/Scan/Traits/MatrixDataTrait.php` | 232 | Ladowanie sources, Quick Matrix data, statystyki, selectAllMatching helpers |
| `app/Http/Livewire/Admin/Scan/Traits/MatrixFiltersTrait.php` | 234 | Filtry, sortowanie, visibility, paginacja, reset selekcji |
| `app/Http/Livewire/Admin/Scan/Traits/MatrixActionsTrait.php` | 702 | Akcje na komorkach, bulk, popup, diff, brand suggestions, selectAllMatching |
| `app/Http/Livewire/Admin/Scan/Traits/ChunkedScanTrait.php` | 381 | Chunked scan orchestration (start/process/finalize/cancel/resume) |
| `app/Http/Livewire/Admin/Scan/Traits/ScanSourcesTrait.php` | ~80 | Reused - getAvailableSources(), getGroupedSources() |

### 2.2 Backend - Services

| Plik | LOC | Opis |
|------|-----|------|
| `app/Services/Scan/CrossSourceMatrixService.php` | 455 | Quick Matrix query, resolveCell, summary stats, brand suggestions, filtered count/IDs |
| `app/Services/Scan/ChunkedScanEngine.php` | 450 | Prefetch source data, chunk processing, diff detection |

### 2.3 Backend - Model & Export

| Plik | LOC | Opis |
|------|-----|------|
| `app/Models/DismissedBrandSuggestion.php` | 79 | Model dismissed brand suggestions per user+shop |
| `app/Exports/ScanMatrixExport.php` | 127 | XLSX/CSV export z FromArray + WithStyles |

### 2.4 Frontend - Blade Views

| Plik | LOC | Opis |
|------|-----|------|
| `resources/views/livewire/admin/scan/cross-source-matrix-panel.blade.php` | 57 | Shell: x-data, tabs, includes |
| `resources/views/livewire/admin/scan/matrix/summary-bar.blade.php` | 106 | Karty statystyk + export buttons |
| `resources/views/livewire/admin/scan/matrix/brand-suggestions.blade.php` | 101 | Sugestie marek (active + dismissed) |
| `resources/views/livewire/admin/scan/matrix/toolbar.blade.php` | 133 | Filtry, sort, scan button |
| `resources/views/livewire/admin/scan/matrix/bulk-actions-bar.blade.php` | 92 | Sticky bulk action bar + selectAllMatching banner |
| `resources/views/livewire/admin/scan/matrix/scan-progress.blade.php` | 93 | Progress bar + ETA + resume banner |
| `resources/views/livewire/admin/scan/matrix/table.blade.php` | 260 | Tabela macierzowa (flat + grouped view) |
| `resources/views/livewire/admin/scan/matrix/_product-row.blade.php` | 166 | Wiersz produktu z cell rendering + hover actions |
| `resources/views/livewire/admin/scan/matrix/cell-popup.blade.php` | 220 | Popup kontekstowy per komorka |
| `resources/views/livewire/admin/scan/matrix/diff-viewer.blade.php` | 217 | Rozwijany wiersz porownania PPM vs Source |

### 2.5 CSS

| Plik | LOC | Opis |
|------|-----|------|
| `resources/css/admin/cross-source-matrix.css` | 480 | Kompletny styling macierzy |

### 2.6 Migracje

| Plik | LOC | Opis |
|------|-----|------|
| `database/migrations/2026_02_25_100000_add_chunk_tracking_to_scan_sessions.php` | 65 | Kolumny chunk tracking w `product_scan_sessions` |
| `database/migrations/2026_02_25_130000_create_dismissed_brand_suggestions_table.php` | 29 | Nowa tabela `dismissed_brand_suggestions` |

### 2.7 Reused (istniejace pliki BEZ zmian)

| Plik | Co reuse'ujemy |
|------|---------------|
| `app/Services/Scan/ProductScanService.php` | getScanSource(), matchBySku(), calculateDiff(), bulkLink/Create/Ignore |
| `app/Services/Scan/Contracts/ScanSourceInterface.php` | getAllSkus(), getProductBySku(), getProductsBatch() |
| `app/Services/Scan/Sources/*.php` | 4 adaptery zrodel (SubiektGT, PrestaShop, Baselinker, Dynamics) |
| `app/Models/SmartSyncBrandRule.php` | forShop(), allowed(), cache invalidation |
| `app/Models/ProductScanSession.php` | Session tracking (+ nowe kolumny z migracji) |
| `app/Models/ProductScanResult.php` | Wyniki skanu, diff_data |
| `resources/views/livewire/admin/scan/partials/history-list.blade.php` | Zakladka Historia |

---

## 3. Schema Bazy Danych

### 3.1 Tabela: `product_scan_sessions` (rozszerzona)

Nowe kolumny dodane migracja `2026_02_25_100000`:

| Kolumna | Typ | Default | Opis |
|---------|-----|---------|------|
| `total_chunks` | int, nullable | null | Calkowita liczba chunkow do przetworzenia |
| `processed_chunks` | int | 0 | Liczba przetworzonych chunkow |
| `chunk_size` | int | 500 | Rozmiar chunka (produktow per chunk) |
| `source_cache_key` | string, nullable | null | JSON config dla prefetch cache |
| `scan_mode` | string | 'legacy' | 'legacy' (stary) lub 'chunked' (nowy) |

### 3.2 Tabela: `dismissed_brand_suggestions` (nowa)

| Kolumna | Typ | Opis |
|---------|-----|------|
| `id` | bigint, PK | Auto-increment |
| `user_id` | FK -> users | Uzytkownik ktory odrzucil sugestie (cascade delete) |
| `shop_id` | int | ID sklepu PrestaShop |
| `brand` | string | Nazwa marki |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Indexy:**
- Unique: `(user_id, shop_id, brand)`
- Index: `(shop_id, brand)`

### 3.3 Istniejace tabele (uzywane)

| Tabela | Rola w macierzy |
|--------|----------------|
| `products` | Glowna tabela produktow (SKU, name, manufacturer_id) |
| `product_shop_data` | Linki PPM -> PrestaShop (status: linked/pending_sync) |
| `product_erp_data` | Linki PPM -> ERP (status: linked/pending_sync) |
| `product_scan_results` | Wyniki skanu (match_status, diff_data, resolution_status) |
| `smart_sync_brand_rules` | Reguly marek per sklep (is_allowed: true/false) |
| `manufacturers` | Tabela marek (id, name) |

---

## 4. Properties

### 4.1 CrossSourceMatrixPanel.php

| Property | Typ | Default | Opis |
|----------|-----|---------|------|
| `$activeTab` | string | `'matrix'` | Aktywna zakladka: 'matrix' lub 'history' |

### 4.2 MatrixDataTrait

| Property | Typ | Default | Opis |
|----------|-----|---------|------|
| `$sources` | array | `[]` | Lista zrodel [{type, id, name, icon, color, is_shop}] |
| `$sourceColumns` | array | `[]` | Kolumny tabeli zbudowane ze zrodel |

### 4.3 MatrixFiltersTrait

| Property | Typ | Default | URL-bound | Opis |
|----------|-----|---------|-----------|------|
| `$search` | string | `''` | `#[Url]` | Szukaj po SKU/nazwie |
| `$statusFilter` | string | `'all'` | `#[Url]` | Filtr statusu komorki |
| `$brandFilter` | ?int | `null` | `#[Url]` | Filtr manufacturer_id |
| `$groupedView` | bool | `false` | `#[Url]` | Grupowanie po marce |
| `$sortField` | string | `'sku'` | `#[Url]` | Pole sortowania |
| `$sortDirection` | string | `'asc'` | `#[Url]` | Kierunek sortowania |
| `$visibleSources` | array | `[]` | | Ukryte kolumny zrodel |
| `$perPage` | int | `50` | | Rozmiar strony |
| `$loadedCount` | int | `0` | | Dodatkowe zaladowane (infinite scroll) |
| `$hasMoreProducts` | bool | `true` | | Czy sa wiecej produktow |

### 4.4 MatrixActionsTrait

| Property | Typ | Default | Opis |
|----------|-----|---------|------|
| `$expandedDiffs` | array | `[]` | ID produktow z otwartym diff viewerem |
| `$activePopup` | ?array | `null` | {productId, sourceKey} aktywnego popup |
| `$selectedProducts` | array | `[]` | Zaznaczone produkty (widoczne checkboxy) |
| `$selectAll` | bool | `false` | Zaznacz wszystkie widoczne |
| `$selectAllMatching` | bool | `false` | Tryb "zaznacz wszystkie pasujace" (Gmail pattern) |
| `$excludedProducts` | array | `[]` | Wykluczone produkty w trybie selectAllMatching |
| `$totalMatchingCount` | int | `0` | Calkowita liczba produktow pasujacych do filtrow |
| `$showDismissedSuggestions` | bool | `false` | Pokaz odrzucone sugestie |

### 4.5 ChunkedScanTrait

| Property | Typ | Default | Opis |
|----------|-----|---------|------|
| `$activeScanSessionId` | ?int | `null` | ID aktywnej sesji skanu |
| `$scanPhase` | string | `'idle'` | Faza: idle/prefetching/scanning/finalizing |
| `$scanProgress` | int | `0` | Progress 0-100% |
| `$processedChunks` | int | `0` | Przetworzone chunki |
| `$totalChunks` | int | `0` | Calkowita liczba chunkow |
| `$estimatedTimeRemaining` | ?string | `null` | ETA do wyswietlenia |
| `$scanStats` | array | `[]` | {total_products, conflicts_found, processed} |

---

## 5. Metody Publiczne

### 5.1 CrossSourceMatrixPanel

| Metoda | Return | Opis |
|--------|--------|------|
| `mount()` | void | Laduje zrodla + wykrywa aktywny skan |
| `render()` | View | Renderuje z danymi zalezymi od aktywnej zakladki |
| `setTab(string $tab)` | void | Przelacza zakladke (matrix/history) |
| `getScanProgress()` | array | Proxy do ChunkedScanTrait::getScanProgressData() |
| `hasPendingSyncCells()` | bool | Czy sa komorki pending_sync (wire:poll trigger) |
| `pollPendingSync()` | void | Odswiezenie macierzy (wire:poll callback) |

### 5.2 MatrixDataTrait

| Metoda | Return | Opis |
|--------|--------|------|
| `loadSources()` | void | Laduje dostepne zrodla z CrossSourceMatrixService |
| `getMatrixData()` | LengthAwarePaginator | Zwraca paginowane dane macierzy z infinite scroll |
| `loadMore()` | void | Laduje +50 produktow (infinite scroll sentinel), auto-selects w trybie selectAllMatching |
| `getTotalMatchingCount()` | int | Zwraca calkowita liczbe produktow pasujacych do filtrow (bez paginacji) |
| `getAllMatchingProductIds()` | array | Zwraca WSZYSTKIE ID produktow pasujacych do filtrow |
| `getSummaryStatsData()` | array | Statystyki: linked, not_linked, not_found, etc. |
| `loadBrandSuggestions()` | array{active, dismissed} | Sugestie marek per sklep |
| `getAvailableBrands()` | Collection | Lista dostepnych marek do filtrowania |
| `refreshMatrix()` | void | Odswiezenie danych macierzy |

### 5.3 MatrixFiltersTrait

| Metoda | Return | Opis |
|--------|--------|------|
| `updatedSearch()` | void | Reset infinite scroll + selekcji przy zmianie szukania |
| `updatedStatusFilter()` | void | Reset infinite scroll + selekcji przy zmianie filtra statusu |
| `updatedBrandFilter()` | void | Reset infinite scroll + selekcji przy zmianie filtra marki |
| `toggleGroupedView()` | void | Toggle grupowania po marce |
| `resetFilters()` | void | Reset wszystkich filtrow do domyslnych |
| `toggleSourceVisibility(string $key)` | void | Pokaz/ukryj kolumne zrodla |
| `sortBy(string $field)` | void | Sortowanie + toggle kierunku |
| `showAllSources()` | void | Reset widocznosci kolumn |
| `isSourceVisible(string $key)` | bool | Czy kolumna zrodla jest widoczna |

### 5.4 MatrixActionsTrait

| Metoda | Return | Opis |
|--------|--------|------|
| `cellAction(int $productId, string $type, int $id, string $action)` | void | Dispatcher akcji na komorce |
| `linkProduct(int $productId, string $type, int $id)` | void | Tworzy link PPM <-> Source |
| `publishToSource(int $productId, string $type, int $id)` | void | Eksport produktu do zrodla |
| `publishToPrestaShop(Product $product, int $shopId)` | void | Eksport do PS z category sync |
| `publishToErp(Product $product, string $erpType, int $erpId)` | void | Eksport do ERP |
| `ignoreProduct(int $productId, string $type, int $id)` | void | Oznacz jako zignorowany |
| `unignoreProduct(int $productId, string $type, int $id)` | void | Przywroc z ignored |
| `unlinkProduct(int $productId, string $type, int $id)` | void | Usun link |
| `forceSync(int $productId, string $type, int $id)` | void | Wymus ponowna synchronizacje |
| `bulkAction(string $action, ?string $sourceKey)` | void | Masowa operacja na zaznaczonych (obsluguje tryb selectAllMatching z chunking) |
| `enableSelectAllMatching()` | void | Wchodzi w tryb "zaznacz wszystkie pasujace" (Gmail pattern) |
| `clearSelection()` | void | Czysci cala selekcje (wyjscie z trybu selectAllMatching) |
| `getEffectiveSelectedCount()` | int | Zwraca efektywna liczbe zaznaczonych (total - excluded w trybie selectAllMatching) |
| `toggleDiffViewer(int $productId)` | void | Rozwin/zwin porownanie danych |
| `openPopup(int $productId, string $sourceKey)` | void | Otworz popup komorki |
| `closePopup()` | void | Zamknij popup |
| `dismissBrandSuggestion(string $brand, int $shopId)` | void | Odrzuc sugestie marki |
| `restoreBrandSuggestion(string $brand, int $shopId)` | void | Przywroc sugestie marki |
| `addBrandToAllowed(string $brand, int $shopId)` | void | Dodaj marke do dozwolonych |
| `exportMatrix(string $format)` | StreamedResponse | Export XLSX/CSV |

### 5.5 ChunkedScanTrait

| Metoda | Return | Opis |
|--------|--------|------|
| `startChunkedScan()` | void | Rozpocznij skan (prefetch + session) |
| `processNextChunk()` | void | Przetwarz nastepny chunk (~500 produktow) |
| `finalizeScanSession()` | void | Zakoncz sesje skanu |
| `cancelScan()` | void | Anuluj aktywny skan |
| `resumeScan()` | void | Wznow przerwany skan |
| `detectActiveChunkedScan()` | void | Wykryj niedokonczony skan na mount() |
| `checkScanStatus()` | void | Status polling (wire:poll) |
| `getScanProgressData()` | array | Dane progressu dla Alpine.js |

---

## 6. Cell Status System

### 6.1 Statusy komorek

| Status | Ikona | Kolor | Znaczenie | Zrodlo danych |
|--------|-------|-------|-----------|--------------|
| `linked` | Checkmark | Zielony | Produkt polaczony, dane zsynchronizowane | product_shop_data / product_erp_data |
| `not_linked` | Link | Niebieski | Produkt istnieje w zrodle ale nie polaczony z PPM | product_scan_results (match_status: matched/conflict/multiple) |
| `not_found` | X | Czerwony | Produkt nie istnieje w zrodle | product_scan_results (match_status: unmatched) |
| `unknown` | ? | Szary | Brak danych skanu (nie skanowano) | Brak rekordu w scan_results |
| `ignored` | Eye-off | Ciemny szary | Recznie zignorowany | product_scan_results (resolution_status: ignored) |
| `conflict` | Warning | Zolty | Wykryto roznice danych miedzy PPM a zrodlem | Deep scan (diff_data) |
| `brand_not_allowed` | Minus | Bursztynowy | Marka niedozwolona w regulach sklepu | SmartSyncBrandRule (is_allowed: false) |
| `pending_sync` | Spinner | Niebieski | Oczekuje na synchronizacje (job w kolejce) | product_shop_data.sync_status IN (pending, syncing) |

### 6.2 Priorytet statusow (resolveCell)

```
1. brand_not_allowed  (SmartSyncBrandRule)
2. linked / pending_sync  (product_shop_data / product_erp_data istnieje)
3. ignored  (resolution_status = 'ignored')
4. not_linked / not_found  (wynik skanu)
5. unknown  (brak danych)
```

### 6.3 Akcje per status

| Status | Hover Actions | Popup Actions |
|--------|--------------|---------------|
| `linked` | - (klik otwiera popup) | Otworz produkt, Force sync, Unlink |
| `not_linked` | Link, Ignore | Link, Ignore, Otworz produkt |
| `not_found` | Publish, Ignore | Publish/Export, Ignore, Otworz produkt |
| `unknown` | Ignore | Ignore |
| `ignored` | Restore | Restore |
| `conflict` | - (klik otwiera popup) | Show diff, Nadpisz danymi PPM |
| `brand_not_allowed` | - (klik otwiera popup) | Dodaj marke do dozwolonych |
| `pending_sync` | - (klik otwiera popup) | Force sync |

---

## 7. Quick Matrix (Instant View)

### 7.1 Jak dziala

Quick Matrix laduje dane natychmiast bez skanowania. Uzywa LEFT JOINow na istniejacych tabelach linkow:

```php
// CrossSourceMatrixService::getQuickMatrixData()

Product::whereNotNull('sku')->where('sku', '!=', '')
    ->with(['manufacturerRelation:id,name'])
    ->paginate($perPage);

// Dla kazdego produktu:
// 1. loadLinks() - batch query product_shop_data + product_erp_data
// 2. loadScanResults() - batch query product_scan_results (najnowsze per produkt+zrodlo)
// 3. resolveUnlinkedStatus() - mapuje scan result na cell status
```

### 7.2 Logika resolveUnlinkedStatus

```
scan_result === null                    → UNKNOWN (szary ?)
resolution_status === 'ignored'         → IGNORED (ciemny szary)
match_status IN (matched, conflict,
  multiple, already_linked)             → NOT_LINKED (niebieski link)
match_status === 'unmatched'            → NOT_FOUND (czerwony X)
default                                 → UNKNOWN
```

### 7.3 Persistencja danych

Dane Quick Matrix SA trwale zapisane w bazie:
- `product_shop_data` - linki do PrestaShop (przetrwa F5)
- `product_erp_data` - linki do ERP (przetrwa F5)
- `product_scan_results` - wyniki skanow (przetrwa F5)

Po eksporcie/linkowaniu:
- Tworzy sie rekord w `product_shop_data` → komorka zmienia sie z czerwonej na niebieska/zielona
- Dane sa trwale - nie trzeba skanowac ponownie

---

## 8. Chunked AJAX Scan

### 8.1 Flow skanu

```
User klika "Skanuj konflikty"
  │
  ▼ Phase 1: prefetchSourceData() [1 Livewire call]
  │  Dla KAZDEGO source: adapter->getAllSkus()
  │  Cache::put("scan_{sessionId}_{type}_{id}", skuMaps, 3600)
  │  Return: {sessionId, totalProducts, totalChunks}
  │
  ▼ Phase 2: Alpine for-loop [N Livewire calls]
  │  while (scanPhase === 'scanning') {
  │      await $wire.processNextChunk()
  │      await sleep(200ms)
  │  }
  │
  ▼ Phase 3: finalizeScan() [1 Livewire call]
     Mark session completed, clear cache
     Return final stats, dispatch 'scan-completed'
```

### 8.2 Performance

| Produkty | Chunks (500/chunk) | Szacowany czas |
|----------|-------------------|----------------|
| 5K | 10 | ~5-10s |
| 20K | 40 | ~20-40s |
| 40K | 80 | ~40-80s |
| 70K | 140 | ~70-140s |

Kazdy chunk: ~400ms (100ms DB + 10ms comparison + 200ms bulk insert + 80ms HTTP overhead)

### 8.3 Resume po przerwaniu

Jesli uzytkownik zamknie przegladarke mid-scan:
1. `detectActiveChunkedScan()` w `mount()` szuka `ProductScanSession` ze statusem 'in_progress' i `scan_mode = 'chunked'`
2. Jesli znajdzie - pokazuje banner "Wznow skan (62%)" z przyciskiem Resume
3. `resumeScan()` odtwarza stan sesji i kontynuuje od ostatniego przetworzonego chunka
4. Cancel tez jest mozliwy z poziomu bannera

### 8.4 Cache strategy

- Klucz: `scan_{sessionId}_{sourceType}_{sourceId}`
- TTL: 1 godzina
- Zawartosc: Mapa SKU -> dane produktu ze zrodla
- Czyszczony po: finalizeScan(), cancelScan()

---

## 9. Cell Actions & Bulk Operations

### 9.1 Pojedyncze akcje (cellAction dispatcher)

```php
cellAction(int $productId, string $sourceType, int $sourceId, string $action)
// action: 'link' | 'publish' | 'ignore' | 'unignore' | 'unlink' | 'add_brand' | 'force_sync'
```

### 9.2 Publish to PrestaShop (eksport)

Flow:
1. `publishToPrestaShop(Product, shopId)`
2. `syncCategoriesForExport()` - synchronizuje kategorie produktu do PrestaShop via `CategorySyncService`
3. `buildMediaChanges()` - zbiera zdjecia produktu do synchronizacji
4. Tworzy `ProductShopData` z `sync_status = 'pending'`
5. Dispatch `SyncProductToPrestaShop` job
6. Komorka zmienia sie na `pending_sync` (spinner)
7. wire:poll.5s sprawdza czy job sie zakonczyl
8. Po ukonczeniu komorka zmienia sie na `linked` (checkmark)

### 9.3 Bulk Operations

Dostepne via `bulkAction(string $action, ?string $sourceKey)`:

| Akcja | Opis | Wymaga sourceKey |
|-------|------|-----------------|
| `link` | Powiaz zaznaczone produkty | Tak (per source) |
| `publish` | Eksportuj zaznaczone | Tak (per source) |
| `ignore` | Ignoruj zaznaczone | Tak (per source) |
| `export_xlsx` | Export do XLSX | Nie |
| `export_csv` | Export do CSV | Nie |

### 9.4 Select All Matching (Gmail Pattern)

**Problem:** Infinite scroll laduje 50 produktow naraz. Checkbox "zaznacz wszystkie" w naglowku zaznacza TYLKO widoczne (zaladowane) produkty, ignorujac reszte.

**Rozwiazanie:** Dwufazowy wzorzec Gmail:

```
Faza 1: Klik "Select All" checkbox w naglowku
  → Zaznacza widoczne produkty (np. 50)
  → Banner: "Zaznaczono 50 na tej stronie. Zaznacz wszystkie 319 pasujacych"

Faza 2: Klik "Zaznacz wszystkie 319 pasujacych"
  → $selectAllMatching = true
  → Banner: "✓ Zaznaczono wszystkie 319 pasujacych produktow" + "Wyczysc zaznaczenie"
  → Bulk actions operuja na WSZYSTKICH pasujacych (query DB, nie tablica ID)
```

**Kluczowe mechanizmy:**

| Mechanizm | Opis |
|-----------|------|
| `$selectAllMatching` | Flag trybu "wszystkie pasujace" |
| `$excludedProducts` | Lista ID odznaczonych produktow (inverse selection) |
| `$totalMatchingCount` | Calkowita liczba pasujacych produktow (z DB) |
| `getEffectiveSelectedCount()` | `totalMatchingCount - count(excludedProducts)` |
| Chunked processing | W trybie selectAllMatching bulk operacje przetwarzaja ALL matching IDs w chunkach po 200 |
| Auto-select on scroll | `loadMore()` automatycznie zaznacza nowo zaladowane produkty |
| Filter reset | Zmiana filtra (search/status/brand) czysci selekcje |

**Livewire property/method naming:**
> W Livewire 3 NIE mozna miec property i metody o tej samej nazwie!
> Property `$selectAllMatching` (bool) + metoda `enableSelectAllMatching()` (NIE `selectAllMatching()`)

**UI stany bulk-actions-bar:**

| Stan | Warunek | Wyglad |
|------|---------|--------|
| Normalna selekcja | `count($selectedProducts) > 0` | "X zaznaczonych" + przyciski akcji |
| Wszystkie na stronie | `$selectAll && !$selectAllMatching` | Banner: "Zaznaczono X na tej stronie. **Zaznacz wszystkie Y pasujacych**" |
| Select All Matching | `$selectAllMatching` | Zielony checkmark + "Zaznaczono wszystkie Y pasujacych produktow" + "Wyczysc zaznaczenie" |

### 9.5 CSS Hover Actions

Komorki z `$hasDirectAction` (not_linked, not_found, unknown, ignored) maja dwie warstwy:
1. `.matrix-cell-icon` - domyslna ikona statusu (ukrywana na hover)
2. `.matrix-cell-actions` - overlay z przyciskami akcji (pokazywany na hover)

Implementacja czysto CSS (bez Alpine/JS):
```css
.matrix-cell-hover-parent:hover .matrix-cell-icon { display: none; }
.matrix-cell-hover-parent:hover .matrix-cell-actions { display: flex; }
```

---

## 10. Brand Suggestions

### 10.1 Logika sugestii

`CrossSourceMatrixService::getBrandSuggestions(shopId, minProducts=5)`:
1. Zlicza produkty per marka ktore SA w PPM ale NIE MAJA linka do sklepu
2. Filtruje: minimum 5 produktow zeby sugestia miala sens
3. Odrzuca marki ktore JUZ MAJA regule SmartSyncBrandRule (allowed lub blocked)
4. Rezultat: "45 produktow Honda bez publikacji na B2B - dodac do dozwolonych?"

### 10.2 Akcje na sugestiach

| Akcja | Metoda | Efekt |
|-------|--------|-------|
| Pokaz | `updatedBrandFilter($manufacturerId)` | Filtruje macierz po marce |
| Dodaj | `addBrandToAllowed(brand, shopId)` | Tworzy SmartSyncBrandRule(is_allowed=true) |
| Odrzuc | `dismissBrandSuggestion(brand, shopId)` | Tworzy DismissedBrandSuggestion (per user) |
| Przywroc | `restoreBrandSuggestion(brand, shopId)` | Usuwa DismissedBrandSuggestion |

### 10.3 Persistencja odrzucen

Model `DismissedBrandSuggestion`:
- Per uzytkownik (user_id) + per sklep (shop_id) + per marka (brand)
- Unique constraint zapobiega duplikatom
- `isDismissed(userId, shopId, brand)` - szybkie sprawdzenie

---

## 11. Diff Viewer

### 11.1 Opis

Diff Viewer to rozwijany wiersz pod produktem pokazujacy porownanie danych pole-po-polu miedzy PPM a kazdym zrodlem.

### 11.2 Zrodlo danych

```php
// diff-viewer.blade.php
ProductScanResult::where('ppm_product_id', $product->id)
    ->whereIn('match_status', ['matched', 'conflict', 'multiple', 'already_linked'])
    ->latest()
    ->get()
    // Deduplikacja per source (najnowszy wynik)
```

### 11.3 Wyswietlane pola

| Pole | Label |
|------|-------|
| name | Nazwa |
| ean | EAN |
| manufacturer | Producent |
| price | Cena |
| reference | Referencja |
| description | Opis |
| description_short | Krotki opis |
| weight | Waga |
| quantity | Ilosc |

### 11.4 Format

3-kolumnowa tabela:
- **Pole** (lewa) - nazwa pola
- **PPM** (srodek, zielony) - wartosc w PPM
- **Source** (prawa, czerwony) - wartosc w zrodle

Roznice sa wyroznianie kolorystycznie. Puste wartosci pokazywane jako "brak".

---

## 12. Export XLSX/CSV

### 12.1 ScanMatrixExport

Implementuje: `FromArray`, `WithHeadings`, `WithStyles`, `WithTitle`, `WithEvents`

### 12.2 Kolumny

| Kolumna | Zrodlo |
|---------|--------|
| SKU | product.sku |
| Nazwa | product.name |
| Marka | manufacturer.name |
| [Source 1 Status] | matrix_cell status (po polsku) |
| [Source 2 Status] | matrix_cell status (po polsku) |
| ... | ... |

### 12.3 Tlumaczenie statusow

| Status | Label w eksporcie |
|--------|-------------------|
| linked | Powiazany |
| not_linked | Nie powiazany |
| not_found | Brak |
| unknown | Nieznany |
| ignored | Zignorowany |
| conflict | Konflikt |
| brand_not_allowed | Marka niedozwolona |
| pending_sync | Oczekuje sync |

### 12.4 Styling

- Naglowki: bold, bialy tekst, ciemne tlo (#374151)
- Auto-width kolumn
- Tytul arkusza: "Macierz Produktow"

---

## 13. UI - Blade Views

### 13.1 Shell View (cross-source-matrix-panel.blade.php)

```
┌──────────────────────────────────────────┐
│ Macierz Produktow (h1)                   │
│ Przegladaj status produktow...           │
├──────────────────────────────────────────┤
│ [Macierz] [Historia]  ← tabs            │
├──────────────────────────────────────────┤
│ @if matrix:                              │
│   summary-bar                            │
│   brand-suggestions                      │
│   toolbar                                │
│   bulk-actions-bar                       │
│   scan-progress                          │
│   table                                  │
│   cell-popup                             │
│ @else:                                   │
│   history-list                           │
└──────────────────────────────────────────┘
```

### 13.2 Summary Bar

6 kart statystyk z gradientami:
- Powiazane (zielony) - `matrix-stat-linked`
- Nie powiazane (niebieski) - `matrix-stat-not-linked`
- Nie znalezione (czerwony) - `matrix-stat-not-found`
- Nieznane (szary) - `matrix-stat-unknown`
- Konflikty (zolty) - `matrix-stat-conflicts`
- Marka zablok. (bursztynowy) - `matrix-stat-blocked`

Przyciski eksportu: XLSX / CSV

### 13.3 Toolbar

```
┌──────────────────────────────────────────────────────────────┐
│ [Search...] [Status ▼] [Marka ▼] [Zrodla ▼] [Grup.] [Reset]│
│                                                    [Skanuj ▶]│
└──────────────────────────────────────────────────────────────┘
```

- Search: `wire:model.live.debounce.300ms`
- Status: dropdown z 9 opcjami (all + 8 statusow)
- Marka: dropdown dynamiczny z DB
- Zrodla: dropdown z checkboxami per zrodlo
- Grouped: toggle grupowania po marce
- Scan: przycisk z 3 stanami (idle/preparing/scanning)

### 13.4 Matrix Table

```
┌────┬──────────┬──────────┬────────┬────────┬────────┬────────┐
│ ☐  │ SKU ↕    │ Nazwa ↕  │ Marka  │ Shop1  │ Shop2  │ ERP1   │
│    │ (sticky) │          │   ↕    │        │        │        │
├────┼──────────┼──────────┼────────┼────────┼────────┼────────┤
│ ☐  │ 00A001   │ Produkt1 │ Honda  │  ✅    │  ❌    │  ✅    │
│ ☐  │ 00A002   │ Produkt2 │ KAYO   │  🔗    │  ❌    │  ❓    │
│ ☐  │ 00A003   │ Produkt3 │ Honda  │  ✅    │  ⏳    │  ✅    │
├────┴──────────┴──────────┴────────┴────────┴────────┴────────┤
│ ← Infinite scroll sentinel (IntersectionObserver)            │
└──────────────────────────────────────────────────────────────┘
```

Cechy:
- Sticky kolumna SKU (lewa)
- Horizontal scroll dla wielu zrodel
- Sortowalne naglowki (SKU, Nazwa, Marka)
- Checkbox per wiersz + "Zaznacz wszystkie"
- Ikona rozwijania diff per produkt
- Infinite scroll z IntersectionObserver (50 produktow/krok)
- Empty state z przyciskiem "Resetuj filtry"

### 13.5 Grouped View (opcja)

```
┌──────────────────────────────────────────────────────────────┐
│ ▼ Honda (156 produktow)                                      │
│   120 ✅ | 23 ❌ | 8 ⚠️ | 5 ➖                              │
│   ┌──────────────────────────────────────────────────────┐   │
│   │ (tabela produktow Honda)                             │   │
│   └──────────────────────────────────────────────────────┘   │
│ ▶ KAYO (89 produktow)  [zwiniete]                            │
│ ▶ YCF (45 produktow)   [zwiniete]                            │
└──────────────────────────────────────────────────────────────┘
```

### 13.6 Scan Progress

```
┌──────────────────────────────────────────────────────────────┐
│ ⚡ Skan w toku...                                            │
│ ████████████████░░░░░░░░  67%  (34/51 chunks)               │
│ Szacowany czas: ~45s                                         │
│ Konflikty: 12 | Przetworzone: 17,000                        │
│                                               [Anuluj]       │
└──────────────────────────────────────────────────────────────┘
```

Resume banner (po przerwaniu):
```
┌──────────────────────────────────────────────────────────────┐
│ 🔄 Niedokonczony skan (62%) - 31/51 chunkow                 │
│                                    [Wznow] [Anuluj]          │
└──────────────────────────────────────────────────────────────┘
```

---

## 14. CSS Styling

### 14.1 Plik: `resources/css/admin/cross-source-matrix.css` (~480 LOC)

### 14.2 Glowne klasy

| Prefix | Przeznaczenie |
|--------|--------------|
| `.matrix-table-*` | Struktura tabeli, sticky kolumny |
| `.matrix-cell--*` | Statusy komorek (linked, not_found, etc.) |
| `.matrix-cell-hover-parent` | Hover trigger dla akcji |
| `.matrix-cell-icon` | Domyslna ikona (ukrywana na hover) |
| `.matrix-cell-actions` | Overlay akcji (pokazywany na hover) |
| `.matrix-popup*` | Popup kontekstowy |
| `.matrix-diff-*` | Diff viewer |
| `.matrix-progress-*` | Progress bar z animacja |
| `.matrix-summary-card` | Karty statystyk |
| `.matrix-stat-*` | 6 wariantow kolorow kart |
| `.matrix-brand-*` | Chipy sugestii marek |
| `.matrix-tab-active` | Aktywna zakladka (orange gradient) |
| `.matrix-sticky-col` | Sticky SKU kolumna |
| `.matrix-source-filter-badge` | Badge licznika filtrow |

### 14.3 Responsive breakpoints

| Breakpoint | Zmiany |
|------------|--------|
| < 768px (mobile) | Summary bar w kolumnie, mniejsze karty |
| < 1024px (tablet) | Reduced padding, compact toolbar |
| >= 1024px (desktop) | Pelny layout, horizontal scroll dla zrodel |

### 14.4 Animacje

- `matrix-progress-stripes` - animowane paski progressu
- `transition-colors duration-100` - plynne hover na komorkach
- `animate-spin` - spinner na pending_sync

### 14.5 Zmienne CSS

- `--source-color` - dynamiczny kolor per zrodlo (ustawiany inline na headerze kolumny)

---

## 15. Eventy i Polling

### 15.1 Livewire Events

| Event | Emiter | Listener | Opis |
|-------|--------|----------|------|
| `scan-started` | ChunkedScanTrait | Alpine (startPolling) | Rozpoczal sie skan |
| `scan-completed` | ChunkedScanTrait | Alpine (stopPolling) | Skan zakonczony |
| `cell-updated` | MatrixActionsTrait | - | Komorka zaktualizowana |

### 15.2 Alpine Polling (scan)

```javascript
// cross-source-matrix-panel.blade.php
x-data="{
    scanPolling: false,
    async startPolling() {
        this.scanPolling = true;
        while (this.scanPolling && $wire.scanPhase === 'scanning') {
            await $wire.processNextChunk();
            await new Promise(r => setTimeout(r, 200));
        }
    },
    stopPolling() { this.scanPolling = false; }
}"
```

### 15.3 wire:poll (pending_sync auto-refresh)

```php
// Warunkowy poll - TYLKO gdy sa komorki pending_sync
@if($this->hasPendingSyncCells())
    <div wire:poll.5s="pollPendingSync"></div>
@endif
```

Logika:
1. Po eksporcie/force_sync komorka staje sie `pending_sync`
2. `hasPendingSyncCells()` zwraca true → wire:poll.5s aktywne
3. Co 5s: `pollPendingSync()` → `refreshMatrix()`
4. Gdy job sie zakonczy → komorka zmienia sie na `linked`
5. `hasPendingSyncCells()` zwraca false → wire:poll znika

---

## 16. Troubleshooting

### 16.1 Quick Matrix pokazuje same szare komorki (unknown)

**Przyczyna:** Nigdy nie wykonano skanu dla tych produktow/zrodel.
**Rozwiazanie:** Kliknij "Skanuj konflikty" aby Deep Scan zidentyfikowal statusy.

### 16.2 Po eksporcie komorka zostaje pending_sync

**Przyczyna:** Job synchronizacji nie zakonczyl sie.
**Sprawdz:**
```bash
php artisan queue:work --once  # Przetworz jeden job reczne
tail -n 50 storage/logs/laravel.log  # Sprawdz logi
```

### 16.3 Blad 500 przy eksporcie (CategoryMappingsCast)

**Przyczyna:** `metadata.source` ma niedozwolona wartosc.
**Dozwolone wartosci:** `manual, pull, sync, migration, import, import_build, import_root_sync, prestashop_direct`
**Fix:** Uzyj `'sync'` jako source w `syncCategoriesForExport()`.

### 16.4 Skan nie wznawia sie po F5

**Przyczyna:** `detectActiveChunkedScan()` nie znalazl sesji.
**Sprawdz:**
- Czy sesja w DB ma `status = 'in_progress'` i `scan_mode = 'chunked'`
- Czy cache SKU nadal istnieje (TTL 1h)

### 16.5 Hover actions nie pojawiaja sie

**Przyczyna:** CSS nie zaladowany lub inny element przechwytuje hover.
**Sprawdz:**
- Czy `cross-source-matrix.css` jest w `resources/css/app.css` (`@import`)
- Czy `npm run build` zostal wykonany po zmianach CSS
- Czy manifest.json jest aktualny na produkcji

### 16.6 Infinite scroll nie laduje wiecej

**Przyczyna:** IntersectionObserver nie widzi sentinela.
**Sprawdz:**
- Czy sentinel jest widoczny w DOM (nie `display:none`)
- Czy `$hasMoreProducts` jest true
- Czy `wire:key` na sentinelu jest unikalny

### 16.7 "Zaznacz wszystkie" nie przechodzi w tryb selectAllMatching

**Przyczyna:** W Livewire 3 property i metoda NIE MOGA miec tej samej nazwy. `wire:click="selectAllMatching"` probuje togglowac bool property zamiast wywolac metode.
**Rozwiazanie:** Metoda MUSI miec inna nazwe niz property. Uzywamy `enableSelectAllMatching()` (metoda) + `$selectAllMatching` (property).

### 16.8 Brand suggestions nie pokazuja sie

**Przyczyna:** Brak produktow spelniajacych kryteria (min 5 produktow per marka bez linka do sklepu).
**Sprawdz:**
- Czy istnieja produkty z `manufacturer_id` nie-null
- Czy te produkty nie maja juz linkow w `product_shop_data`
- Czy marka nie ma juz reguly w `smart_sync_brand_rules`

---

## 17. Changelog

### v1.1.0 (2026-02-26)

**Select All Matching (Gmail pattern):**
- Dwufazowe zaznaczanie: najpierw widoczne, potem WSZYSTKIE pasujace do filtrow
- Inverse selection via `$excludedProducts` - odznaczanie pojedynczych w trybie "wszystkie"
- Chunked bulk processing (200 produktow/chunk) dla operacji na tysiacach produktow
- Auto-select nowo zaladowanych produktow przy infinite scroll
- Reset selekcji przy zmianie filtrow (search, status, brand)
- UI: banner informacyjny z 3 stanami (normalna/strona/wszystkie)
- Bugfix: Livewire 3 name collision - property i metoda nie moga miec tej samej nazwy

**Zmienione pliki:**
- `MatrixActionsTrait.php` (577 -> 702 LOC): +3 properties, +3 methods, zmodyfikowane `updatedSelectAll/updatedSelectedProducts/bulkAction`
- `MatrixDataTrait.php` (191 -> 232 LOC): +`getTotalMatchingCount()`, +`getAllMatchingProductIds()`, +`syncSelectedWithMatching()`
- `MatrixFiltersTrait.php` (223 -> 234 LOC): +`resetScrollAndSelection()` helper
- `CrossSourceMatrixService.php` (424 -> 455 LOC): +`getFilteredProductCount()`, +`getFilteredProductIds()`, +`ids` filter
- `bulk-actions-bar.blade.php` (75 -> 92 LOC): 3-stanowy banner selectAllMatching

### v1.0.0 (2026-02-26)

**Nowy panel:**
- Kompletna implementacja Cross-Source Matrix Panel
- Quick Matrix (instant view bez skanu)
- Chunked AJAX Deep Scan z progress barem i ETA
- 8 statusow komorek z dedykowanymi ikonami i kolorami
- CSS hover actions na komorkach
- Cell popup z akcjami kontekstowymi per status
- Diff viewer (rozwijane porownanie PPM vs Source)
- Brand suggestions z dismiss/restore per user
- Bulk operations (link, publish, ignore)
- Export XLSX/CSV
- Infinite scroll
- Grouped view (po marce)
- Source visibility filter
- URL-bound filters (search, status, brand, sort)
- Scan resume po przerwaniu
- Auto-refresh via wire:poll.5s dla pending_sync
- Responsive CSS (480 LOC)
- 2 migracje (chunk tracking + dismissed suggestions)

**Zastepuje:** Stary `ScanProductsPanel` (dostepny pod `/admin/scan-products/legacy`)

---

## Appendix A: Diagram architektury

```
┌──────────────────────────────────────────────────────────────┐
│                CrossSourceMatrixPanel.php                      │
│  ┌─────────────┐ ┌─────────────┐ ┌─────────────┐            │
│  │MatrixData   │ │MatrixFilters│ │MatrixActions│            │
│  │  Trait      │ │  Trait      │ │  Trait      │            │
│  └──────┬──────┘ └─────────────┘ └──────┬──────┘            │
│         │                                │                    │
│  ┌──────┴──────┐                  ┌──────┴──────┐            │
│  │ChunkedScan  │                  │ScanSources  │            │
│  │  Trait      │                  │  Trait      │            │
│  └──────┬──────┘                  └─────────────┘            │
│         │                                                     │
└─────────┼─────────────────────────────────────────────────────┘
          │
    ┌─────┴─────────────────────────┐
    │                               │
┌───▼───────────────┐  ┌───────────▼─────────────┐
│ CrossSourceMatrix │  │ ChunkedScanEngine       │
│ Service           │  │                         │
│ - getQuickMatrix  │  │ - prefetchSourceData()  │
│ - resolveCell()   │  │ - processChunk()        │
│ - getBrandSuggest │  │ - finalizeScan()        │
│ - getSummaryStats │  │ - cancelScan()          │
└───────────────────┘  └─────────────────────────┘
    │                      │
    ▼                      ▼
┌─────────────────────────────────────────┐
│ Database Layer                           │
│ - products                              │
│ - product_shop_data                     │
│ - product_erp_data                      │
│ - product_scan_results                  │
│ - product_scan_sessions                 │
│ - smart_sync_brand_rules                │
│ - dismissed_brand_suggestions           │
└─────────────────────────────────────────┘
```

## Appendix B: Referencje

| Dokument | Opis |
|----------|------|
| `PRODUCT_LIST.md` | Dokumentacja panelu listy produktow |
| `IMPORT_PANEL.md` | Dokumentacja panelu importu |
| `_DOCS/SKU_ARCHITECTURE_GUIDE.md` | Architektura SKU jako glownego klucza |
| `Plan_Projektu/Task_plans/encapsulated-percolating-graham.md` | Plan implementacji macierzy |
