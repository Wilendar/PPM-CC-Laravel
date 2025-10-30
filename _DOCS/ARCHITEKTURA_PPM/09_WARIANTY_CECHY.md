# 09. Warianty & Cechy

[◀ Powrót do spisu treści](README.md)

---

## 🎨 Warianty & Cechy - Przegląd

System zarządzania wariantami produktów, cechami pojazdów i dopasowaniami części.

**Uprawnienia:**
- **Admin/Menadżer:** Pełny dostęp (CRUD)
- **Redaktor:** Edycja wariantów/cech, read-only dopasowania
- **Wszyscy:** Brak dostępu

---

## 9.1 Zarządzanie Grupami Atrybutów Wariantów

**Route:** `/admin/variants`
**Component:** AttributeTypeManager (Livewire)
**Middleware:** auth, role:manager+

**ℹ️ UWAGA:** Ten panel NIE pokazuje listy produktów ani ich wariantów. To panel do zarządzania DEFINICJAMI grup atrybutów (np. Kolor, Rozmiar) i ich wartościami (np. Czerwony, Niebieski).

**Produkty z wariantami** zarządzane są w:
- **Lista produktów** (`/admin/products`) - bulk edit wariantów wielu produktów
- **Formularz produktu** (`/admin/products/{id}/edit`) - edycja wariantów pojedynczego produktu

### Zakładki

```
[Grupy Atrybutow]  [Wartosci Atrybutow]  [Produkty]
```

### Widok: Grupy Atrybutów

**Header:**
```
Grupy Atrybutow
Zarzadzaj typami atrybutow wariantow

[➕ Dodaj Grupe Atrybutow]  [🔄 Synchronizuj]
```

**Cards Grid (przykład z danymi):**

```
┌─────────────────────────────────────┐  ┌─────────────────────────────────────┐
│ 🎨 Kolor                            │  │ 📐 Rozmiar                          │
│ Code: color                         │  │ Code: size                          │
│ Type: color_picker                  │  │ Type: dropdown                      │
│                                     │  │                                     │
│ Wartosci: 15                        │  │ Wartosci: 8                         │
│ Produktow: 234                      │  │ Produktow: 89                       │
│                                     │  │                                     │
│ PrestaShop Sync:                    │  │ PrestaShop Sync:                    │
│ ✅ Shop1 (synced)                   │  │ ✅ Shop1 (synced)                   │
│ ✅ Shop2 (synced)                   │  │ ⚠️ Shop2 (pending)                  │
│ ❌ Shop3 (missing)                  │  │                                     │
│                                     │  │                                     │
│ Position: 1                         │  │ Position: 2                         │
│ Status: ● Active                    │  │ Status: ● Active                    │
│                                     │  │                                     │
│ [⚙️ Edit]  [📝 Wartosci]  [🗑️ Delete]│  │ [⚙️ Edit]  [📝 Wartosci]  [🗑️ Delete]│
└─────────────────────────────────────┘  └─────────────────────────────────────┘

┌─────────────────────────────────────┐
│ 🧵 Material                         │
│ Code: material                      │
│ Type: dropdown                      │
│                                     │
│ Wartosci: 6                         │
│ Produktow: 45                       │
│                                     │
│ PrestaShop Sync:                    │
│ ✅ Shop1 (synced)                   │
│                                     │
│ Position: 3                         │
│ Status: ● Active                    │
│                                     │
│ [⚙️ Edit]  [📝 Wartosci]  [🗑️ Delete]│
└─────────────────────────────────────┘
```

**Empty State:**
```
┌──────────────────────────────────────────┐
│         📦                               │
│   Brak wariantow produktow               │
│                                          │
│   Utworz pierwsza grupe atrybutow        │
│   aby moc zarzadzac wariantami           │
│                                          │
│ [📝 Dodaj Grupe]  [🔄 Import]            │
└──────────────────────────────────────────┘
```

### Modal: Dodaj/Edytuj Grupe Atrybutow

```
┌──────────────────────────────────────────┐
│ Dodaj Grupe Atrybutow                    │
├──────────────────────────────────────────┤
│ Nazwa:                                   │
│ [Kolor_____________________________]     │
│                                          │
│ Kod (slug):                              │
│ [color_____________________________]     │
│                                          │
│ Typ wyswietlania:                        │
│ [Color Picker ▼]                         │
│   Color Picker                           │
│   Dropdown                               │
│   Radio Buttons                          │
│   Image Swatches                         │
│                                          │
│ Pozycja:                                 │
│ [1__]                                    │
│                                          │
│ ☑️ Aktywna                                │
│                                          │
│ [💾 Zapisz]  [❌ Anuluj]                  │
└──────────────────────────────────────────┘
```

### Widok: Wartości Atrybutów (zakładka 2)

**Header:**
```
Wartosci Atrybutow
Zarzadzaj wartosciami dla grup atrybutow

Grupa: [Wszystkie ▼]
       Kolor
       Rozmiar
       Material
```

**Table:**

| Grupa | Nazwa Wartości | Kod | Kolor | PrestaShop Sync | Produktów | Status | Akcje |
|-------|----------------|-----|-------|-----------------|-----------|--------|-------|
| Kolor | Czerwony | red | 🔴 #FF0000 | ✅ 3/3 sklepy | 45 | ● Active | [⚙️] [🗑️] |
| Kolor | Niebieski | blue | 🔵 #0000FF | ⚠️ 2/3 sklepy | 32 | ● Active | [⚙️] [🗑️] |
| Rozmiar | M | m | - | ✅ 3/3 sklepy | 18 | ● Active | [⚙️] [🗑️] |
| Rozmiar | L | l | - | ✅ 3/3 sklepy | 25 | ● Active | [⚙️] [🗑️] |

**Actions:**
```
[➕ Dodaj Wartosc]  [🔄 Sync All Shops]
```

### Widok: Produkty (zakładka 3)

**Header:**
```
Produkty z Wariantami
Lista produktow wykorzystujacych system wariantow

Grupa: [Wszystkie ▼]
Sklep: [Wszystkie ▼]
```

**Table:**

| SKU | Nazwa Produktu | Grupy Atrybutow | Wariantow | Sklepy | Status | Akcje |
|-----|----------------|-----------------|-----------|--------|--------|-------|
| PROD-001 | Test Product | Kolor, Rozmiar | 6 | 3 | ● Active | [👁️ View] |
| PROD-002 | Another | Rozmiar | 3 | 2 | ● Active | [👁️ View] |

**ℹ️ UWAGA:** Kliknięcie "View" przekierowuje do `/admin/products/{id}/edit` gdzie można edytować warianty tego produktu.

---

## 9.2 Cechy Pojazdów

**Route:** `/admin/features/vehicles`
**Controller:** VehicleFeatureController@index
**Middleware:** auth, role:manager+

### Template Management

**Lista Templateów (Cards):**

```
┌─────────────────────┐ ┌─────────────────────┐
│ ⚡ Pojazdy          │ │ ⛽ Pojazdy          │
│    Elektryczne      │ │    Spalinowe        │
│                     │ │                     │
│ 15 cech             │ │ 20 cech             │
│ Używany: 50 razy    │ │ Używany: 30 razy    │
│                     │ │                     │
│ [⚙️ Edit] [🗑️ Del]   │ │ [⚙️ Edit] [🗑️ Del]   │
└─────────────────────┘ └─────────────────────┘

┌─────────────────────┐
│ ➕ Custom Templates  │
│    (User-Defined)   │
│                     │
│ [+ Dodaj Template]  │
└─────────────────────┘
```

### Template Editor (Modal)

```
┌──────────────────────────────────────────────────────┐
│ Nazwa template *                                     │
│ [Pojazdy Elektryczne________________]                │
├──────────────────────────────────────────────────────┤
│ Lista cech (Sortable, Drag & Drop):                  │
│                                                      │
│ | # | Nazwa Cechy | Typ | Wymagana | Default | [🗑️] │
│ | 1 | VIN | text | ☑️ Yes | - | [🗑️] │
│ | 2 | Rok produkcji | number | ☑️ Yes | 2024 | [🗑️] │
│ | 3 | Engine No. | text | ☐ No | - | [🗑️] │
│ | 4 | Przebieg | number | ☐ No | 0 | [🗑️] │
│ | 5 | Typ silnika | select | ☑️ Yes | Elektryczny | [🗑️] │
│ | 6 | Moc (KM) | number | ☐ No | - | [🗑️] │
│                                                      │
│ [+ Dodaj Cechę]                                      │
│                                                      │
│ [💾 Zapisz]  [❌ Anuluj]                              │
└──────────────────────────────────────────────────────┘
```

### Feature Library (Sidebar)

**Gotowe Cechy do Wyboru (50+ standardowych):**

```
┌────────────────────────────┐
│ 📚 BIBLIOTEKA CECH          │
├────────────────────────────┤
│ 🔍 [Szukaj cechy______]    │
├────────────────────────────┤
│ Podstawowe:                │
│  • VIN                     │
│  • Rok produkcji           │
│  • Engine No.              │
│  • Przebieg                │
│                            │
│ Silnik:                    │
│  • Typ silnika             │
│  • Moc (KM)                │
│  • Pojemność (cm3)         │
│  • Liczba cylindrów        │
│                            │
│ Wymiary:                   │
│  • Długość                 │
│  • Szerokość               │
│  • Wysokość                │
│  • Masa                    │
│                            │
│ ... (scroll dla więcej)    │
└────────────────────────────┘
```

### Bulk Assign

```
┌──────────────────────────────────────────┐
│ Zastosuj template do produktów           │
│                                          │
│ Wybierz produkty:                        │
│ ○ Wszystkie pojazdy (125)                │
│ ● Pojazdy z kategorii:                  │
│   [Pojazdy > Motocykle > Elektryczne ▼] │
│   (50 produktów)                         │
│                                          │
│ Wybierz template:                        │
│ [Pojazdy Elektryczne ▼]                  │
│                                          │
│ Akcja:                                   │
│ ○ Dodaj cechy (zachowaj istniejące)     │
│ ● Zastąp cechy (usuń istniejące)        │
│                                          │
│ [🚀 Zastosuj]  [❌ Anuluj]                │
└──────────────────────────────────────────┘
```

---

## 9.3 Dopasowania Części

**Route:** `/admin/compatibility`
**Controller:** CompatibilityController@index
**Middleware:** auth, role:manager+

### Filtry

```
Część zamienna: [search: SKU/Name_______________]
Sklep PrestaShop: [Wszystkie ▼]
                   YCF Official Store
                   Pitbike.pl
Producent pojazdu: [☑️ YCF ☑️ Pitbike ☐ All]
Status dopasowania: [Wszystkie ▼]
                     Pełne (Oryginał + Zamiennik)
                     Częściowe (tylko Oryginał)
                     Brak
```

### Tabela Części

| SKU Części | Nazwa | Oryginał | Zamiennik | Model (auto) | Status | Akcje |
|------------|-------|----------|-----------|--------------|--------|-------|
| PART-001 | Filtr oleju | 5 | 3 | 8 | ✅ Full | [⚙️] |
| PART-002 | Świeca | 2 | 0 | 2 | 🟡 Partial | [⚙️] |
| PART-003 | Pasek | 0 | 0 | 0 | ❌ None | [⚙️] |

**Kolumny (counts):**
- **Oryginał:** Liczba pojazdów (dedykowane dopasowanie)
- **Zamiennik:** Liczba pojazdów (alternatywne dopasowanie)
- **Model:** Auto-generated (suma Oryginał + Zamiennik)
- **Status:** Badge (Full/Partial/None)

### Bulk Edit Modal

```
┌──────────────────────────────────────────┐
│ Bulk Edit Dopasowań                      │
│                                          │
│ Wybrane części (5):                      │
│ PART-001, PART-002, PART-003, ...        │
│                                          │
│ Akcja:                                   │
│ ● Dodaj do Oryginał                     │
│ ○ Dodaj do Zamiennik                    │
│ ○ Usuń z Dopasowań                      │
│                                          │
│ Pojazdy (searchable multi-select):       │
│ [search: _______________]                │
│                                          │
│ Wybrane (3):                             │
│ ✅ YCF Pilot 50 (PROD-VEH-001) [❌]      │
│ ✅ YCF Pilot 110 (PROD-VEH-002) [❌]     │
│ ✅ Pitbike 125cc (PROD-VEH-010) [❌]     │
│                                          │
│ Preview:                                 │
│ PART-001: Oryginał +3 (5 → 8)           │
│ PART-002: Oryginał +3 (2 → 5)           │
│ ...                                      │
│                                          │
│ [💾 Zastosuj]  [❌ Anuluj]                │
└──────────────────────────────────────────┘
```

### Vehicle List per Part (Expand Row)

**Rozwinięcie wiersza PART-001:**

```
┌────────────────────────────────────────────────────┐
│ ORYGINAŁ (5 pojazdów):                             │
│ ┌────────────────────────────────────────────────┐ │
│ │ [YCF Pilot 50] [YCF Pilot 110] [YCF Pilot 125]│ │
│ │ [Pitbike 110] [Pitbike 125]                    │ │
│ │ [+ Dodaj Pojazd]                               │ │
│ └────────────────────────────────────────────────┘ │
│                                                    │
│ ZAMIENNIK (3 pojazdy):                             │
│ ┌────────────────────────────────────────────────┐ │
│ │ [Generic 110] [Generic 125] [Other Brand]      │ │
│ │ [+ Dodaj Pojazd]                               │ │
│ └────────────────────────────────────────────────┘ │
│                                                    │
│ MODEL (8 pojazdów, auto-generated, read-only):     │
│ ┌────────────────────────────────────────────────┐ │
│ │ ℹ️ Suma Oryginał + Zamiennik                    │ │
│ │ [YCF Pilot 50] [YCF Pilot 110] ... (8 total)   │ │
│ └────────────────────────────────────────────────┘ │
└────────────────────────────────────────────────────┘
```

### Import/Export

```
[📥 Import Dopasowań z CSV]
[📤 Eksport Dopasowań do CSV]
[📄 Generuj Szablon CSV]
```

**Format CSV (Szablon):**

```csv
SKU_Czesci,Typ_Dopasowania,SKU_Pojazdu,Sklep
PART-001,ORYGINAL,PROD-VEH-001,global
PART-001,ORYGINAL,PROD-VEH-002,global
PART-001,ZAMIENNIK,PROD-VEH-010,global
PART-002,ORYGINAL,PROD-VEH-001,ycf-store
```

**Typy Dopasowania:**
- `ORYGINAL` = Oryginał
- `ZAMIENNIK` = Zamiennik
- `MODEL` = Auto-generated (nie można importować)

---

## 📖 Nawigacja

- **Poprzedni moduł:** [08. Cennik](08_CENNIK.md)
- **Następny moduł:** [10. Dostawy & Kontenery](10_DOSTAWY_KONTENERY.md)
- **Powrót:** [Spis treści](README.md)
