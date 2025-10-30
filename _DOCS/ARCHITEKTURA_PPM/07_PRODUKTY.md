# 07. Produkty

[◀ Powrót do spisu treści](README.md)

---

## 📦 Produkty - Przegląd

Centralny hub zarządzania produktami dla wszystkich sklepów PrestaShop.

**Uprawnienia:**
- **Admin/Menadżer:** Pełny dostęp (CRUD)
- **Redaktor:** Edycja opisów/zdjęć (bez usuwania)
- **Wszyscy:** Odczyt + wyszukiwarka

---

## 7.1 Lista Produktów

**Route:** `/admin/products`
**Controller:** ProductController@index
**Middleware:** auth

### Filtry i Wyszukiwarka (Sticky Header)

```
┌──────────────────────────────────────────────────────────────┐
│ 🔍 [Quick Search: SKU, Nazwa, Producent____________] [🔍]    │
├──────────────────────────────────────────────────────────────┤
│ Kategoria: [dropdown ▼] Typ: [dropdown ▼] Status: [dropdown ▼] │
│ Sklep: [☐ YCF ☐ Pitbike ☐ All] Advanced: [➕ Więcej Filtrów]  │
└──────────────────────────────────────────────────────────────┘
```

**Advanced Filters (Collapsible):**
- Stany magazynowe: [min ___] - [max ___]
- Grupy cenowe: [multi-select]
- Data dodania: [date range]
- Status sync: [✅ Synced / ⏳ Pending / ❌ Error]

### Tabela Produktów

| ☐ | Zdjęcie | SKU | Nazwa | Kategoria | Typ | Status | Cena | Stan | Sklepy | Sync | Akcje |
|---|---------|-----|-------|-----------|-----|--------|------|------|--------|------|-------|
| ☐ | ![](img) | **PROD-001** | Test Product | Części > Silniki | Część | 🟢 Active | 150 PLN | 10 | YCF, PB | ✅ | ⚙️ |

**Kolumny (szczegóły):**
- **SKU:** Klikalne (link do edycji), bold
- **Kategoria:** Breadcrumb (Kategoria > Kat1 > Kat2)
- **Typ:** Badge (Pojazd / Część / Odzież / Inne)
- **Status:** Badge z kolorem (Active: green, Inactive: gray)
- **Sklepy:** Badges sklepów (YCF, PitBike, etc.)
- **Sync:** Ikona (✅ synced / ⏳ pending / ❌ error)
- **Akcje:** Dropdown (Edit, Duplicate, Delete)

### Bulk Operations Bar

**Pokazuje się po zaznaczeniu produktów:**

```
☑️ Zaznaczono 15 produktów

[📤 Eksportuj na Sklepy]  [💰 Edytuj Ceny]  [📦 Edytuj Stany]
[📁 Zmień Kategorię]  [🔄 Zmień Status]  [❌ Usuń]
```

**Eksportuj na Sklepy (Modal):**
```
┌─────────────────────────────────────────┐
│ Wybierz sklepy docelowe:                │
│ ☑️ YCF Official Store                    │
│ ☑️ Pitbike.pl                            │
│ ☐ Cameraman Shop                        │
│                                         │
│ Opcje:                                  │
│ ☑️ Eksportuj zdjęcia                     │
│ ☑️ Eksportuj kategorie                   │
│ ☑️ Eksportuj cechy i dopasowania         │
│                                         │
│ [🚀 Rozpocznij Eksport]  [❌ Anuluj]     │
└─────────────────────────────────────────┘
```

### Header Actions

```
[+ Dodaj Produkt]  [📥 Import z Pliku]  [📤 Eksportuj do CSV]
[📊 / 🃏] Toggle View (Tabela / Karty)
```

**Import z Pliku (NOWOŚĆ v2.0):**
- Unified CSV + XLSX import
- Route: `/admin/products/import`
- Przeniesione z sekcji "ZARZĄDZANIE"

### Pagination

```
Pokazuję 1-25 z 1,245 produktów

[25 ▼] per page  [◀ Poprzednia] [1] [2] [3] ... [50] [Następna ▶]
```

---

## 7.2 Dodaj/Edytuj Produkt

**Route:** `/admin/products/create`, `/admin/products/{id}/edit`
**Controller:** ProductController@create / ProductController@edit
**Middleware:** auth (role:manager+ dla create/delete)

### Formularz Produktu (12 Tabs)

```
┌─────────────────────────────────────────────────────────────┐
│ [📝 Podstawowe] [📁 Kategorie] [📄 Opisy] [💰 Ceny]          │
│ [📦 Stany] [🎨 Warianty] [🖼️ Zdjęcia] [🚗 Cechy Pojazdów]    │
│ [🔧 Dopasowania] [🔍 META & SEO] [📝 Notatki] [🏪 Dane ze Sklepów] │
└─────────────────────────────────────────────────────────────┘
```

#### Tab 1: DANE PODSTAWOWE

```
┌──────────────────────────────────────────┐
│ SKU *                                    │
│ [PROD-001____________________]           │
│   ⚠️ Unique identifier (cannot change)   │
├──────────────────────────────────────────┤
│ Nazwa produktu *                         │
│ [Test Product_________________]          │
├──────────────────────────────────────────┤
│ Typ produktu *                           │
│ ○ Pojazd  ● Część Zamienna              │
│ ○ Odzież  ○ Inne                        │
├──────────────────────────────────────────┤
│ Producent                                │
│ [YCF ▼] [+ Dodaj Nowego]                │
├──────────────────────────────────────────┤
│ Status                                   │
│ [●] Aktywny  Widoczny: [☑️]              │
├──────────────────────────────────────────┤
│ EAN                                      │
│ [5901234567890__________]                │
├──────────────────────────────────────────┤
│ Symbol dostawcy (multi-value, sep: ;)   │
│ [SUPPLIER-001; SUPPLIER-002____]         │
├──────────────────────────────────────────┤
│ Stawka VAT                               │
│ ○ 23%  ○ 8%  ○ 5%  ● 0%  ○ zw.         │
└──────────────────────────────────────────┘
```

#### Tab 2: KATEGORIE

**Dane Domyślne (Global):**

```
Category Tree Picker (5 poziomów):

📂 Pojazdy
  ├─ 🏍️ Motocykle
  │   ├─ ● Elektryczne       ← Kategoria2
  │   │   └─ ● YCF            ← Kategoria3
  │   │       └─ ● Pilot      ← Kategoria4 (najgłębsza)
  │   └─ ○ Spalinowe
  └─ 🚲 Quady

☑️ Oznacz najgłębszą jako domyślną (Pilot)
```

**Per-Shop Categories (Tabs):**

```
[🏪 Global] [YCF Store] [Pitbike Store]

YCF Store:
  Wybrana kategoria: Pojazdy > Motocykle > Elektryczne > YCF

  [📋 Użyj Kategorii Domyślnych]
```

#### Tab 3: OPISY

**Dane Domyślne (Global):**

```
Opis Krótki (max 800 znaków):
┌─────────────────────────────────────────┐
│ [B] [I] [U] [Link] [🖼️]                  │
├─────────────────────────────────────────┤
│ Krótki opis produktu...                 │
│                                         │
│ 720/800 znaków                          │
└─────────────────────────────────────────┘

Opis Długi (max 21,844 znaków):
┌─────────────────────────────────────────┐
│ [B] [I] [U] [List] [Table] [HTML] [🖼️]   │
├─────────────────────────────────────────┤
│ Szczegółowy opis produktu...            │
│                                         │
│ 1,250 / 21,844 znaków                   │
└─────────────────────────────────────────┘
```

**Per-Shop Descriptions:**

```
[🏪 Global] [YCF Store] [Pitbike Store]

YCF Store:
  Opis Krótki: [WYSIWYG editor]
  Opis Długi: [WYSIWYG editor]

  [📋 Użyj Opisów Domyślnych]
```

#### Tab 4: CENY (Grid Layout)

**Grupy Cenowe (Editable Table):**

| Grupa Cenowa | Cena Netto | Cena Brutto | Marża % |
|--------------|------------|-------------|---------|
| Detaliczna | [150.00] | 150.00 | [60%] |
| Dealer Standard | [120.00] | 120.00 | [40%] |
| Dealer Premium | [110.00] | 110.00 | [35%] |
| Warsztat | [130.00] | 130.00 | [45%] |
| Warsztat Premium | [120.00] | 120.00 | [40%] |
| Szkółka-Komis-Drop | [115.00] | 115.00 | [38%] |
| Pracownik | [100.00] | 100.00 | [30%] |

**Kalkulator Marży (Sidebar):**

```
┌─────────────────────────────────────┐
│ Cena zakup netto: [100.00___]       │
│ Marża domyślna %: [50%______]       │
│                                     │
│ [⚡ Auto-Calculate Wszystkie]        │
│                                     │
│ [📋 Kopiuj z Produktu...]           │
└─────────────────────────────────────┘
```

#### Tab 5: STANY MAGAZYNOWE

**Magazyny (Editable Table):**

| Magazyn | Stan | Lokalizacja | Status Dostawy | Data Dostawy |
|---------|------|-------------|----------------|--------------|
| MPPTRADE | [10] | [A1-B2] | [Dostępne ▼] | - |
| Pitbike.pl | [5] | [C3] | [W kontenerze ▼] | [2025-11-01 📅] |
| Cameraman | [0] | - | [Zamówione ▼] | [2025-11-15 📅] |

**Status Dostawy (Dropdown):**
- Dostępne
- Zamówione
- Nie zamówione
- Anulowany
- W kontenerze (nr: [CNT-2025-001 ▼])
- Opóźnienie ([+5 dni])
- W trakcie przyjęcia

**Bulk Actions:**

```
[🔄 Ustaw Status Dla Wszystkich]  [📋 Kopiuj Stany z Produktu...]
```

**Alert Niskiego Stanu:**

```
⚠️ Stan Minimalny: [5___] szt.
☑️ Email notification gdy stan < minimum
```

#### Tab 6: WARIANTY

**Lista Wariantów (jeśli produkt ma warianty):**

| SKU Wariantu | Atrybuty | Cena | Stan | Zdjęcia | Status | Akcje |
|--------------|----------|------|------|---------|--------|-------|
| PROD-001-RED | Kolor: Czerwony | 150 | 10 | 3 | Active | [⚙️] |
| PROD-001-BLUE | Kolor: Niebieski | 150 | 5 | 2 | Active | [⚙️] |

**Dodaj Wariant (Modal):**

```
┌─────────────────────────────────────────┐
│ SKU Wariantu                            │
│ [PROD-001-___] [🔄 Auto-generate]       │
├─────────────────────────────────────────┤
│ Wybierz Atrybuty                        │
│ Kolor: [Czerwony ▼]                     │
│ Rozmiar: [M ▼]                          │
├─────────────────────────────────────────┤
│ Opcje                                   │
│ ☑️ Dziedzicz ceny z produktu matki       │
│ ☑️ Dziedzicz stany                       │
│ ☐ Własne zdjęcia                        │
│                                         │
│ [💾 Zapisz]  [❌ Anuluj]                 │
└─────────────────────────────────────────┘
```

#### Tab 7: ZDJĘCIA (max 20)

**Upload Zone (Drag & Drop):**

```
┌─────────────────────────────────────────┐
│   🖼️                                     │
│   Przeciągnij i upuść zdjęcia           │
│   lub kliknij aby wybrać                │
│                                         │
│   Formaty: JPG, JPEG, PNG, WEBP         │
│   Max size: 5MB per file                │
│   Bulk upload: do 10 jednocześnie       │
└─────────────────────────────────────────┘
```

**Gallery Grid (Sortable):**

```
┌───────┬───────┬───────┬───────┐
│ [★]   │       │       │       │
│ [🖼️ 1] │ [🖼️ 2] │ [🖼️ 3] │ [🖼️ 4] │
│ [👁][🗑]│ [👁][🗑]│ [👁][🗑]│ [👁][🗑]│
│ YCF,PB│ YCF   │ YCF   │ All   │
└───────┴───────┴───────┴───────┘

★ = Główne zdjęcie (kliknij aby oznaczyć)
YCF,PB = Label sklepów gdzie jest to zdjęcie
```

**Drag & Drop Reorder:**
- Chwyt: przeciągnij zdjęcie aby zmienić kolejność
- Główne: kliknij gwiazdkę aby oznaczyć jako główne
- Akcje per zdjęcie: View (lightbox), Delete

**Przyciski:**

```
[📋 Kopiuj z Produktu...]
```

#### Tab 8: CECHY POJAZDÓW

**Tylko dla Typ = Pojazd**

**Template Selector:**

```
Szablon: [Pojazdy Elektryczne ▼]
         ○ Pojazdy Spalinowe
         ○ Custom
```

**Lista Cech (Dynamic Form):**

| Cecha | Wartość |
|-------|---------|
| VIN | [ABC123456789____] |
| Rok produkcji | [2024] |
| Engine No. | [ENG-001_______] |
| Przebieg | [1500] [km ▼] |

**Przyciski:**

```
[+ Dodaj Cechę (Custom)]
```

#### Tab 9: DOPASOWANIA CZĘŚCI

**Tylko dla Typ = Część Zamienna**

**Filtr Sklepu:**

```
Sklep: [Wszystkie ▼]
       YCF Official Store
       Pitbike.pl
```

**Sekcja ORYGINAŁ:**

```
Multi-select searchable (Produkty Typ=Pojazd):

Wybrane (5):
  ✅ YCF Pilot 50 (PROD-VEH-001)  [❌]
  ✅ YCF Pilot 110 (PROD-VEH-002) [❌]
  ...

[+ Dodaj Pojazd]
```

**Sekcja ZAMIENNIK:**

```
Multi-select searchable (excluding Oryginał):

Wybrane (3):
  ✅ Pitbike 125cc (PROD-VEH-010) [❌]
  ...

[+ Dodaj Pojazd]
```

**Sekcja MODEL (Auto-Generated, Read-Only):**

```
ℹ️ Auto-generowane z Oryginał + Zamiennik

Lista (8 pojazdów):
  Model: YCF Pilot 50
  Model: YCF Pilot 110
  Model: Pitbike 125cc
  ...
```

**Per-Shop Dopasowania (Tabs):**

```
[🏪 Global] [YCF Store] [Pitbike Store]

YCF Store:
  Oryginał: [tylko pojazdy z Producent=YCF]
  Zamiennik: [...]
  Model: [auto-generated]
```

#### Tab 10: META & SEO

```
┌──────────────────────────────────────────┐
│ Meta Title (max 70 znaków)               │
│ [Test Product - YCF Pilot 50___] 45/70  │
├──────────────────────────────────────────┤
│ Meta Description (max 160 znaków)        │
│ [Opis SEO produktu...________] 120/160  │
├──────────────────────────────────────────┤
│ URL Key (slug format)                    │
│ [test-product-ycf-pilot-50___]           │
│   Preview: /products/test-product-...    │
├──────────────────────────────────────────┤
│ Tagi (separator: ;)                      │
│ [ycf; pilot; elektryczny; motocykl]      │
└──────────────────────────────────────────┘
```

#### Tab 11: NOTATKI WEWNĘTRZNE

```
┌──────────────────────────────────────────┐
│ Notatki (unlimited)                      │
│ [Textarea...____________]                │
│                                          │
│ Historia Zmian (Timeline):               │
│ ┌────────────────────────────────────┐   │
│ │ 2025-10-22 10:30 | admin@mpptrade  │   │
│ │ Zmieniono: Cena Detaliczna         │   │
│ │ Przed: 150 PLN → Po: 160 PLN       │   │
│ └────────────────────────────────────┘   │
│ ┌────────────────────────────────────┐   │
│ │ 2025-10-21 14:00 | editor@mpptrade │   │
│ │ Zmieniono: Opis długi              │   │
│ └────────────────────────────────────┘   │
└──────────────────────────────────────────┘
```

#### Tab 12: DANE ZE SKLEPÓW

**Per-Shop Tabs (Read-Only Preview):**

```
[YCF Store] [Pitbike Store]

YCF Store (Read-Only):

  PrestaShop ID: 12345
  URL: https://ycf.pl/products/test-product
  Status Sync: ✅ Synced (2025-10-22 10:30)

  Różnice (Diff View):
  ┌────────────────────────────────────┐
  │ Nazwa:                             │
  │   PPM: Test Product                │
  │   PS:  Test Product                │
  │   ✅ Zgodne                         │
  ├────────────────────────────────────┤
  │ Cena:                              │
  │   PPM: 150 PLN                     │
  │   PS:  145 PLN                     │
  │   ⚠️ Niezgodne (-5 PLN)            │
  ├────────────────────────────────────┤
  │ Stan:                              │
  │   PPM: 10 szt.                     │
  │   PS:  10 szt.                     │
  │   ✅ Zgodne                         │
  └────────────────────────────────────┘

  [🔄 Synchronizuj Teraz]
  [📥 Pobierz Dane ze Sklepu (Overwrite PPM)]
```

### Footer Actions (Sticky)

```
[💾 Zapisz]
[💾 Zapisz i Eksportuj na Sklepy ▼]
  └─ [YCF Store]
     [Pitbike Store]
     [Wszystkie Sklepy]
[📋 Duplikuj Produkt]
[❌ Anuluj]
```

---

## 7.3 Kategorie

**Route:** `/admin/products/categories`
**Controller:** CategoryController@index
**Middleware:** auth, role:manager+

### Category Tree View (5 poziomów)

```
📂 WSZYSTKIE KATEGORIE (85)

📂 Pojazdy (125 produktów) [▼]
  ├─ 🏍️ Motocykle (80) [▼]
  │   ├─ ● Elektryczne (50) [▼]
  │   │   ├─ ● YCF (30)
  │   │   │   └─ ● Pilot (15)
  │   │   └─ ● Pitbike (20)
  │   └─ ○ Spalinowe (30)
  └─ 🚲 Quady (45)

📂 Części (1,100 produktów) [▶]
  ├─ 🔧 Silniki (250)
  └─ ⚙️ Przekładnie (180)

[+ Dodaj Kategorię Główną]
[📥 Import z PrestaShop]
[📤 Eksport do CSV]
```

**Akcje per Kategoria:**
- **Edit:** Edytuj nazwę/opis/zdjęcie
- **Add Child:** Dodaj podkategorię
- **Delete:** Usuń (walidacja czy są produkty)

### Category Form (Sidebar lub Modal)

```
┌──────────────────────────────────────────┐
│ Nazwa kategorii *                        │
│ [Motocykle Elektryczne_______]           │
├──────────────────────────────────────────┤
│ Kategoria nadrzędna                      │
│ [Pojazdy > Motocykle ▼] (4 poziomy)      │
├──────────────────────────────────────────┤
│ Opis kategorii                           │
│ [Textarea...____________]                │
├──────────────────────────────────────────┤
│ Status                                   │
│ [●] Aktywny                              │
├──────────────────────────────────────────┤
│ Zdjęcie kategorii                        │
│ [📁 Wybierz plik...]                      │
├──────────────────────────────────────────┤
│ Sortowanie (position)                    │
│ [1__]                                    │
│                                          │
│ [💾 Zapisz]  [❌ Anuluj]                  │
└──────────────────────────────────────────┘
```

---

## 7.4 Import z Pliku (NOWOŚĆ v2.0)

**Route:** `/admin/products/import`
**Controller:** ImportController@index
**Middleware:** auth, role:manager+

**⚠️ PRZENIESIONO z sekcji "ZARZĄDZANIE" → PRODUKTY**

### Upload Section

```
┌─────────────────────────────────────────┐
│   📁                                     │
│   Przeciągnij i upuść plik               │
│   CSV, XLSX, TXT                        │
│                                         │
│   Max size: 10MB (CSV/TXT)              │
│   Max size: 50MB (XLSX)                 │
└─────────────────────────────────────────┘

Typ importu: [Produkty (complete) ▼]
             Warianty
             Cechy
             Dopasowania
```

### Template Download

```
Pobierz szablon:
  [📥 Produkty (CSV)]  [📥 Produkty (XLSX)]
  [📥 Warianty (CSV)]  [📥 Warianty (XLSX)]
  [📥 Cechy (CSV)]     [📥 Cechy (XLSX)]
  [📥 Dopasowania (CSV)] [📥 Dopasowania (XLSX)]
```

### Import Wizard (Steps)

**Step 1: Upload pliku**

```
✅ Plik uploaded: products_2025.xlsx (2.5 MB)
   Format: XLSX
   Rows: 450

[➡️ Dalej: Podgląd i Walidacja]
```

**Step 2: Podgląd i Walidacja**

```
Podgląd pierwszych 10 wierszy:

| SKU | Nazwa | Cena | Stan | Status |
|-----|-------|------|------|--------|
| PROD-001 | Test | 150 | 10 | ✅ Valid |
| PROD-002 | ... | - | 5 | ⚠️ Missing Price |

Column Mapping (Auto-Detect + Manual):
  Excel Column A → PPM Field: [SKU ▼]
  Excel Column B → PPM Field: [Nazwa ▼]
  Excel Column C → PPM Field: [Cena Netto ▼]

Validation Errors (2):
  Row 2: ⚠️ Missing required field: Cena
  Row 5: ❌ Invalid SKU format: "ABC-###"

Conflict Resolution:
  ○ Skip duplicates (default)
  ● Overwrite existing
  ○ Update existing (merge)

[➡️ Dalej: Import] [◀️ Wstecz]
```

**Step 3: Import Execution**

```
Progress: ████████░░ 80% (360/450)

Statistics:
  ✅ Success: 360
  ⚠️ Skipped: 12 (duplicates)
  ❌ Failed: 78 (validation errors)

[📥 Download Error Report (CSV)]

[✅ Zakończ] [🔄 Retry Failed]
```

---

## 7.5 Historie Importów

**Route:** `/admin/products/import-history`
**Controller:** ImportHistoryController@index
**Middleware:** auth, role:manager+

### Tabela Importów

| Data | Użytkownik | Typ | Plik | Status | Sukces | Błędy | Akcje |
|------|------------|-----|------|--------|--------|-------|-------|
| 2025-10-22 | admin@ | XLSX Produkty | products.xlsx | ✅ Completed | 450 | 12 | [👁] [📥] |
| 2025-10-21 | manager@ | CSV Warianty | variants.csv | ⚠️ Partial | 320 | 30 | [👁] [🔄] |

**Filtry:**
- Data (date range)
- Użytkownik (dropdown)
- Status (Success / Failed / Partial)
- Typ (XLSX / CSV)

**Akcje:**
- **View:** Modal z full log
- **Download:** Error report (CSV)
- **Re-import:** Retry failed rows
- **Delete:** Usuń historię

---

## 7.6 Wyszukiwarka

**Route:** `/admin/products/search`
**Controller:** ProductSearchController@index
**Middleware:** auth

### Inteligentna Wyszukiwarka (Fullscreen)

```
┌──────────────────────────────────────────────────────────┐
│  🔍 Wyszukaj po SKU, nazwie, kategorii, producencie...   │
│      [________________________________] [🔍]              │
│                                                          │
│  Live Autocomplete:                                      │
│  ┌────────────────────────────────────────────────────┐  │
│  │ Produkty:                                          │  │
│  │   📦 PROD-001 | Test Product                       │  │
│  │   📦 PROD-002 | Another Product                    │  │
│  │ Kategorie:                                         │  │
│  │   📁 Motocykle > Elektryczne                       │  │
│  │ Producenci:                                        │  │
│  │   🏭 YCF                                            │  │
│  └────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────┘

Search Mode:
  ○ Wyszukaj dokładnie (exact match)
  ● Fuzzy search (tolerancja błędów, literówki)

Advanced Filters [▼]:
  - Typ produktu: [multi-select]
  - Kategoria: [tree picker]
  - Producent: [multi-select]
  - Zakres cen: [min-max slider]
  - Zakres stanów: [min-max slider]
  - Status: [active / inactive / all]
  - Sklepy: [multi-select]
  - Data dodania: [date range]
```

### Search Results

```
Znaleziono 15 produktów

Sortowanie: [Relevance ▼]
            Nazwa
            SKU
            Cena
            Stan

View: [📊 List] [🃏 Grid]

[Tabela/Grid produktów - jak w Lista Produktów]

[1] [2] [3] ... [5]
```

### Default View (Przed Wyszukaniem)

```
┌─────────────────────────────────────────┐
│ 📊 STATYSTYKI PPM                       │
├─────────────────────────────────────────┤
│ Total Products: 1,245                   │
│ Total Categories: 85                    │
│                                         │
│ Products by Type (Pie Chart):           │
│   [🔵 Części: 70%]                      │
│   [🟢 Pojazdy: 20%]                     │
│   [🟡 Odzież: 8%]                       │
│   [🟠 Inne: 2%]                         │
│                                         │
│ Low Stock Alerts: 12                    │
└─────────────────────────────────────────┘

ℹ️ Wyszukaj towar, aby zobaczyć szczegóły
```

---

## 📖 Nawigacja

- **Poprzedni moduł:** [06. Sklepy PrestaShop](06_SKLEPY_PRESTASHOP.md)
- **Następny moduł:** [08. Cennik](08_CENNIK.md)
- **Powrót:** [Spis treści](README.md)
