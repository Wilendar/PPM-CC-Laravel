# TODO: Audyt struktury bazy danych PPM (2025-11-19)

## 1. Dokumentacja vs. rzeczywista baza
- `_DOCS/Struktura_Bazy_Danych.md` (np. linie 188-200 oraz 344-410) opisuje schemat sprzed refaktoryzacji: `product_variants` ma kolumny `variant_attributes`, `price_modifier`, `stock_modifier`, a `product_shop_data` zawiera `external_id` i `sync_errors`. Tymczasem baza zawiera zestaw tabel `product_variants`, `attribute_types`, `attribute_values`, `variant_attributes`, `variant_prices`, `variant_stock`, `variant_images` oraz rozbudowane kolumny synchronizacji w `product_shop_data`.
- W dokumencie nadal widnieje tabela `product_sync_status` (m.in. sekcja 823-888), która została scalona z `product_shop_data` przez migracje `2025_10_13_000001...` i `000002...`.
- Sekcja `products` nie zawiera kolumn `available_from`, `available_to`, `is_featured`, `default_variant_id`, mimo że zostały dodane (`database/migrations/2025_09_22_000001_add_publishing_schedule_to_products_table.php`, `2025_10_17_100015_add_variant_columns_to_products_table.php`).
- Tabele wdrożone w październiku 2025 r. (`category_preview`, `job_progress`, `prestashop_attribute_group_mapping`, `prestashop_attribute_value_mapping`, `prestashop_shop_price_mappings`, `vehicle_compatibility_cache`, `vehicle_models`, `vehicle_compatibility`, `compatibility_attributes`, `compatibility_sources`) w ogóle nie są opisane w dokumentacji głównej.
- ETAP_11 w docs nadal oznacza `vehicle_models` i `product_vehicle_compatibility` jako “PLANNED”, mimo że migracje są wdrożone i `vehicle_models` ma dane (10 rekordów).

### Zadania
1. Zaktualizować `_DOCS/Struktura_Bazy_Danych.md`, `_DOCS/Struktura_Plikow_Projektu.md` oraz odpowiednie pliki w `_DOCS/ARCHITEKTURA_PPM/` tak, aby odzwierciedlały realne kolumny i wszystkie istniejące tabele (w tym workflow kategorii i mapowania PrestaShop).
2. Dodać sekcję „Historia” dla `product_sync_status` i jasno wskazać, że tabelę zastąpiły pola w `product_shop_data`.
3. Przenieść stare plany/raporty odnoszące się do `product_sync_status` do archiwum albo uzupełnić notatkami o nowej architekturze.

## 2. Duplikacja danych wariantowych
- W `product_prices` i `product_stock` istnieją kolumny `product_variant_id`, ale formularze (np. `app/Http/Livewire/Products/Management/ProductForm.php:941`, `ProductFormSaver.php:529-590`) wymuszają `whereNull('product_variant_id')`. Jednocześnie w bazie brak rekordów w `product_variants`, `variant_prices`, `variant_stock` (zapytania liczące zwracają 0).
- Utrzymujemy równolegle tabele `variant_prices` i `variant_stock`, ale nie są w ogóle używane – dane z formularza nigdy do nich nie trafiają, a realne ceny/stany zapisują się tylko dla produktu głównego.

### Zadania
1. Zdecydować, czy ceny/stan wariantów przechowujemy w `product_prices`/`product_stock` (z `product_variant_id`) czy w dedykowanych tabelach `variant_*`. Rekomendacja: jeden model danych (najlepiej `product_prices` + `product_stock` dla wszystkich przypadków).
2. Jeśli wybieramy `product_prices`/`product_stock`, dostosować `VariantManager` (`app/Services/Product/VariantManager.php:255-335`) i formularze tak, aby zapisy do wariantów miały ustawione `product_variant_id`.
3. Migracja czyszcząca: przenieść ewentualne dane (na dziś 0 rekordów, więc operacja formalna), usunąć nieużywane tabele `variant_prices`/`variant_stock`, uprościć modele/dokumentację.

## 3. Indeksy i konsolidacja `product_shop_data`
- Po migracji konsolidacyjnej `product_shop_data` zawiera dwa identyczne indeksy `idx_shop_products` i `idx_shop_products_products_id_shop_id_index` oraz stary `idx_external_lookup (shop_id, external_id)`, który stał się bezsensowny po usunięciu kolumny `external_id`.
- Dokumentacja (sekcja 344-410) nadal wspomina o `external_id` i `sync_errors`, zamiast o nowych kolumnach (`prestashop_product_id`, `pending_fields`, `validation_warnings`, `retry_count`, `priority`, `conflict_log` itd.).
- W planach i raportach (np. `_AGENT_REPORTS/2025-10-13_SYNC_ARCHITECTURE_DIAGNOSIS_REPORT.md`, `Plan_Projektu/ETAP_07_Prestashop_API.md`) wciąż pojawia się stary model `ProductSyncStatus`.

### Zadania
1. Przygotować migrację, która usuwa zbędne indeksy i dodaje `idx_external_lookup` na (`shop_id`, `prestashop_product_id`) lub inną potrzebną kombinację.
2. Ujednolicić nazewnictwo pól synchronizacji i wprowadzić je do dokumentacji + checklist deployowych.
3. Zaktualizować komentarze w kodzie oraz checklisty agentów, aby nie sugerowały pracy na `ProductSyncStatus`.

## 4. Workflow importu kategorii (Category Preview)
- Tabela `category_preview` (`database/migrations/2025_10_08_120000...`) i `job_progress` (`2025_10_07_000000...`) są aktywnie używane (`app/Jobs/PrestaShop/BulkCreateCategories.php:229`, `BulkImportProducts.php:769`), ale nie ma o nich wzmianki w dokumentacji ogólnej.
- `job_progress` zawiera już 260 rekordów – warto upewnić się, że retencja i cleanup są opisane i monitorowane (cron dla `expires_at`).

### Zadania
1. Dodać opis workflow „Category Preview + Job Progress” do `_DOCS/Struktura_Bazy_Danych.md` i przewodników integracji.
2. Sprawdzić, czy cleanup job usuwa stare rekordy, i udokumentować harmonogram (np. w `_DOCS/DEPLOYMENT_GUIDE.md`).

## 5. Moduł dopasowań pojazdów
- Migracje `2025_10_17_100010_create_vehicle_models_table.php` i `2025_10_17_100013_create_vehicle_compatibility_table.php` są wdrożone, `vehicle_models` ma 10 wpisów, ale w dokumentacji sekcja ETAP_11 jest oznaczona jako “PLANNED”.
- Brak informacji o `vehicle_compatibility_cache`, `compatibility_attributes`, `compatibility_sources`.

### Zadania
1. Uzupełnić dokumentację o rzeczywiste tabele oraz dodać status „IN PROGRESS” / „COMPLETED” z datą.
2. Przygotować plan uzupełnienia `vehicle_compatibility` (obecnie 0 rekordów) i odnotować go w planach ETAP_11.

## 6. Stan bieżący – metryki
- Liczba tabel w bazie (28 zgłoszonych jako brakujących w dokumentacji) – wszystkie wymagają wpisu w docs.
- `product_variants`, `variant_prices`, `variant_stock` – 0 rekordów (zapytania przez PDO).
- `category_preview` – 0 rekordów (po czyszczeniu), `job_progress` – 260 rekordów, `vehicle_models` – 10 rekordów, `vehicle_compatibility` – 0 rekordów.

## 7. Kolejne kroki
1. Uzgodnić z zespołem docelowy model wariantów – decyzja blokuje dalsze migracje.
2. Zaplanować i wykonać aktualizację dokumentacji (najpierw `_DOCS/Struktura_Bazy_Danych.md`, potem pliki zależne).
3. Przygotować migrację optymalizującą `product_shop_data` (indeksy, cleanup) oraz wykonać techniczne zadania wynikające z wybranego modelu danych wariantowych.
4. Dodać wpis do `_REPORTS` po zakończeniu, aby zachować ślad audytowy.





• Najważniejsze wnioski

  - Sekcja o wariantach w _DOCS/Struktura_Bazy_Danych.md:188-200 opisuje monolityczną tabelę product_variants z kolumnami
    variant_attributes, price_modifier, stock_modifier, których w bazie nie ma – rzeczywisty schemat został rozbity na product_variants,
    attribute_types, attribute_values, variant_attributes, variant_prices, variant_stock i variant_images (np. database/
    migrations/2025_10_17_100002_create_attribute_types_table.php:31-51, 2025_10_17_100004_create_variant_prices_table.php:39-67,
    2025_10_17_100005_create_variant_stock_table.php:40-77). Dokumentacja nie wymienia tych tabel, więc nowy zespół nie ma jak odtworzyć
    faktycznej architektury wariantów.
  - W dokumentacji nadal figuruje tabela product_sync_status (np. _DOCS/Struktura_Bazy_Danych.md:344-410 i 823-
    888, Plan_Projektu/ETAP_07_Prestashop_API.md:493), mimo że została scalona z product_shop_data i fizycznie
    usunięta (database/migrations/2025_10_13_000001_consolidate_sync_tracking_to_product_shop_data.php:52-181 +
    2025_10_13_000002_drop_product_sync_status_table.php). Kod biznesowy pracuje już tylko na ProductShopData, ale komentarze/plan nadal
    kierują do nieistniejącej tabeli – to źródło błędów przy onboardingu.
  - Opis tabeli products ( _DOCS/Struktura_Bazy_Danych.md:78-99,96) jest niespójny z realną bazą: w DB
    dodano available_from, available_to, is_featured, default_variant_id i całkowicie zrezygnowano z JSON-
    a publishing_schedule (database/migrations/2025_09_22_000001_add_publishing_schedule_to_products_table.php:15-25 oraz
    2025_10_17_100015_add_variant_columns_to_products_table.php:18-58). Brak tego w dokumencie utrudnia przygotowywanie importów i testów.
  - product_shop_data zawiera dziś komplet pól synchronizacji (m.in. prestashop_product_id, pending_fields, validation_warnings,
    retry_count, priority, conflict_log), a także odziedziczyła stare indeksy – w szczególności idx_shop_products (shop_id) i
    automatycznie obcięty po migracji idx_external_lookup (shop_id) są identyczne i nie obejmują już kolumny, do której pierwotnie służyły
    (database/migrations/2025_09_18_000003_create_product_shop_data_table.php:98-116 + konsolidacja 2025_10_13_000001...). Dokument (linie
    2025_10_24_160000_create_prestashop_shop_price_mappings_table.php) oraz cache dopasowań pojazdów
    (2025_10_17_100014_create_vehicle_compatibility_cache_table.php). Brak ich opisu uniemożliwia audyt struktury „na papierze”.
        _AGENT_REPORTS/2025-10-13_SYNC_ARCHITECTURE_DIAGNOSIS_REPORT.md:75-294) zastąpić odniesienia do ProductSyncStatus opisem nowej
        architektury ProductShopData.
      - W repo pozostawić tylko migracje tworzące/dropping product_sync_status w archiwum (np. przenieść do _ARCHIVE) i dopisać sekcję
        „Historyczne” w dokumentacji, by było jasne dlaczego tabeli w bazie już nie ma.
  3. Ujednolicenie przechowywania cen i stanów wariantów
      - Etap 1: dopisać enkapsulację w VariantManager (app/Services/Product/VariantManager.php:255-335) tak, by ceny i stany wariantów
        trafiały do product_prices i product_stock z ustawionym product_variant_id.
      - Etap 2: migracją przenieść ewentualne dane z variant_prices/variant_stock (na dziś 0 rekordów, więc operacja bezpieczna) i
        zaktualizować formularze (app/Http/Livewire/Products/Management/ProductForm.php oraz .../Services/ProductFormSaver.php:529-590),
        żeby przestały filtrować whereNull('product_variant_id').
      - Etap 3: po wdrożeniu usunąć modele i migracje variant_prices/variant_stock, upraszczając schemat i dokumentację.
  4. Porządek w indeksach product_shop_data
      - Przygotować migrację usuwającą zbędny indeks idx_shop_products i nadającą idx_external_lookup sensowne kolumny (np. shop_id +
        prestashop_product_id albo shop_id + external_reference). Obecny stan powstał po dropie kolumny external_id i pozostawia dwa
        identyczne indeksy na shop_id (marnotrawstwo zasobów).
      - Po migracji zaktualizować dokumentację sekcji product_shop_data i checklisty deploy (_ISSUES_FIXES/
        CSS_INCOMPLETE_DEPLOYMENT_ISSUE.md ma odniesienia do starych pól sync).
  5. Opis i monitoring workflow importu kategorii
      - Dodać do dokumentacji opis tabel category_preview i job_progress oraz przepływu pracy (Plan_Projektu/
        ETAP_07a_FAZA_3D_CATEGORY_PREVIEW.md:167-445 już to opisuje, ale brak synchronizacji z główną dokumentacją).
      - Zweryfikować, czy cleanup (expires_at) działa – obecnie job_progress ma 260 rekordów, a category_preview 0; warto dodać zadanie
        cron w docs.
  6. Aktualizacja modułu dopasowania pojazdów
      - Opisać w dokumentacji nowe tabele vehicle_models, vehicle_compatibility, compatibility_attributes, compatibility_sources i
        vehicle_compatibility_cache (zob. migracje 2025_10_17_100010... oraz 2025_10_17_100013...).
      - Ustalić plan wygenerowania danych vehicle_compatibility (dziś 0 rekordów) i dopisać go do roadmapy ETAP_11, zamiast trzymać status
        „PLANNED”.
  7. Ciągłe utrzymanie Planów i raportów
      - Przejrzeć Plan_Projektu/* oraz najważniejsze raporty w _AGENT_REPORTS pod kątem odwołań do nieistniejących tabel/procesów i
        zaktualizować je po wprowadzeniu powyższych zmian, aby workflow agentów znów był spójny z realnym stanem bazy.

  Po wdrożeniu powyższych kroków baza i dokumentacja znów będą zsynchronizowane, a zespół uniknie utrzymywania dwóch równoległych modeli
  (warianty, sync). Rekomenduję po każdej zmianie uruchomić php artisan migrate --pretend na środowisku testowym i spisać diff w _REPORTS,
  aby zachować ślad audytowy.
