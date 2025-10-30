# 08. Cennik

[◀ Powrót do spisu treści](README.md)

---

## 💰 Cennik - Przegląd

Zarządzanie grupami cenowymi i cenami produktów z masową aktualizacją.

**Uprawnienia:**
- **Admin/Menadżer:** Pełny dostęp (edit cen + grupy + bulk)
- **Redaktor:** View only (wszystkie grupy)
- **Wszyscy:** Widoczność cen (bez edycji)

**7 Grup Cenowych:**
1. Detaliczna
2. Dealer Standard
3. Dealer Premium
4. Warsztat
5. Warsztat Premium
6. Szkółka-Komis-Drop
7. Pracownik

---

## 8.1 Grupy Cenowe

**Route:** `/admin/price-management/price-groups`
**Controller:** PriceGroupController@index
**Middleware:** auth, role:manager+

### Lista Grup Cenowych

| Nazwa Grupy | Opis | Domyślna Marża % | Liczba Produktów | Status | Akcje |
|-------------|------|------------------|------------------|--------|-------|
| Detaliczna | Cena dla klientów detalicznych | 60% | 1,245 | ● Active | [⚙️] |
| Dealer Standard | Ceny dla dealerów standardowych | 40% | 890 | ● Active | [⚙️] |
| Dealer Premium | Ceny dla dealerów premium | 35% | 850 | ● Active | [⚙️] |
| Warsztat | Ceny dla warsztatów | 45% | 750 | ● Active | [⚙️] |
| Warsztat Premium | Ceny dla warsztatów premium | 40% | 680 | ● Active | [⚙️] |
| Szkółka-Komis-Drop | Ceny dla szkółek, komisów, drop | 38% | 620 | ● Active | [⚙️] |
| Pracownik | Ceny dla pracowników | 30% | 580 | ● Active | [⚙️] |

### Header Actions

```
[+ Dodaj Grupę Cenową (Custom)]  [📥 Import Grup z PrestaShop]
```

### Formularz Grupy (Modal)

```
┌──────────────────────────────────────────┐
│ Nazwa grupy *                            │
│ [Dealer VIP_________________]            │
├──────────────────────────────────────────┤
│ Opis                                     │
│ [Textarea...____________]                │
├──────────────────────────────────────────┤
│ Domyślna marża %                         │
│ [50%_______]                             │
│   ℹ️ Zastosowana przy tworzeniu produktu │
├──────────────────────────────────────────┤
│ Status                                   │
│ [●] Aktywny                              │
├──────────────────────────────────────────┤
│ Mapowanie PrestaShop ID (per sklep)      │
│ YCF Store: [Group ID: 5 ▼]              │
│ Pitbike: [Group ID: 3 ▼]                │
│                                          │
│ [💾 Zapisz]  [❌ Anuluj]                  │
└──────────────────────────────────────────┘
```

### Bulk Actions

```
[💰 Masowa Zmiana Marży dla Grupy]  [📤 Eksport Cen do CSV]
```

---

## 8.2 Ceny Produktów

**Route:** `/admin/price-management/product-prices`
**Controller:** ProductPriceController@index
**Middleware:** auth, role:manager+

### Filtry

```
Szukaj produktu: [SKU, Nazwa_______________] [🔍]

Kategoria: [Wszystkie ▼]
Grupa cenowa: [☑️ Detaliczna ☑️ Dealer Std ☐ Dealer Premium ...]
Zakres cen: [min: ___] - [max: ___]
```

### Tabela Cen (Editable Inline)

| SKU | Nazwa | Detaliczna | Dealer Std | Dealer Premium | Warsztat | Warsztat Prem | Szkółka | Pracownik | Akcje |
|-----|-------|------------|------------|----------------|----------|---------------|---------|-----------|-------|
| PROD-001 | Test | **[150]** 60% | **[120]** 40% | **[110]** 35% | **[130]** 45% | **[120]** 40% | **[115]** 38% | **[100]** 30% | [💾] |

**Inline Editing:**
- Kliknij cenę aby edytować
- Auto-calculate marża (pokazuje obok jako %)
- Enter = save, Esc = cancel
- Real-time validation (min price, max price)

**Marża Calculation:**
```
Cena Zakup: 100 PLN (netto)
Cena Sprzedaż: 150 PLN (netto)
Marża %: (150 - 100) / 100 * 100 = 50%
```

### Bulk Operations Bar

**Pokazuje się po zaznaczeniu produktów:**

```
☑️ Zaznaczono 25 produktów

[💰 Zastosuj Marżę X%]  [📋 Kopiuj Ceny z Produktu]
[📤 Eksport do CSV]  [📥 Import z CSV]
```

**Zastosuj Marżę (Modal):**

```
┌─────────────────────────────────────────┐
│ Dla zaznaczonych produktów (25):        │
│                                         │
│ Grupa cenowa: [Detaliczna ▼]           │
│                                         │
│ Marża %: [60%_______]                   │
│                                         │
│ Preview:                                │
│ PROD-001: 100 PLN → 160 PLN (+60 PLN)  │
│ PROD-002: 80 PLN → 128 PLN (+48 PLN)   │
│ ...                                     │
│                                         │
│ [💾 Zastosuj]  [❌ Anuluj]               │
└─────────────────────────────────────────┘
```

---

## 8.3 Aktualizacja Masowa

**Route:** `/admin/price-management/bulk-updates`
**Controller:** BulkPriceController@index
**Middleware:** auth, role:manager+

### Wizard Aktualizacji Cen (5 Steps)

#### Step 1: Wybór Produktów

```
○ Wszystkie produkty (1,245)
● Produkty z kategorii:
  📂 [Pojazdy > Motocykle > Elektryczne ▼]
  Produkty: 50

○ Produkty według filtrów:
  Producent: [YCF ▼]
  Typ: [Pojazd ▼]

○ Import listy SKU:
  [Textarea: PROD-001; PROD-002; ... ______]
  lub [📁 Upload CSV]

[➡️ Dalej: Wybór Grup]
```

#### Step 2: Wybór Grup Cenowych

```
Które grupy cenowe zaktualizować?

☑️ Detaliczna
☑️ Dealer Standard
☐ Dealer Premium
☑️ Warsztat
☐ Warsztat Premium
☐ Szkółka-Komis-Drop
☐ Pracownik

[➡️ Dalej: Akcja] [◀️ Wstecz]
```

#### Step 3: Akcja

```
Wybierz akcję:

● Ustaw marżę %: [60%_______]
○ Zwiększ o %: [10%_______]
○ Zmniejsz o %: [5%________]
○ Ustaw cenę stałą: [150 PLN__]

[➡️ Dalej: Preview] [◀️ Wstecz]
```

#### Step 4: Preview Zmian

```
Preview zmian (50 produktów, 3 grupy cenowe):

| SKU | Grupa | Stara Cena | Nowa Cena | Różnica % |
|-----|-------|------------|-----------|-----------|
| PROD-001 | Detaliczna | 150 PLN | 160 PLN | +6.7% |
| PROD-001 | Dealer Std | 120 PLN | 128 PLN | +6.7% |
| PROD-001 | Warsztat | 130 PLN | 139 PLN | +6.9% |
| PROD-002 | ... | ... | ... | ... |

Podsumowanie:
- Produkty: 50
- Grupy cenowe: 3 (Detaliczna, Dealer Std, Warsztat)
- Total zmian: 150 cen
- Średnia zmiana: +6.8%

[➡️ Wykonaj] [◀️ Wstecz]
```

#### Step 5: Wykonanie

```
Progress: ████████████ 100% (150/150)

Statistics:
  ✅ Updated: 150 cen
  ⚠️ Skipped: 0
  ❌ Failed: 0

Log:
  ✅ PROD-001 | Detaliczna | 150 → 160 PLN
  ✅ PROD-001 | Dealer Std | 120 → 128 PLN
  ...

[📥 Download Report (CSV)]
[✅ Zakończ]
```

---

## 🎨 UI/UX Patterns

### Inline Price Editing

```html
<td class="price-cell" x-data="{ editing: false, price: 150 }">
    <!-- Display Mode -->
    <div x-show="!editing" @click="editing = true" class="price-display">
        <strong>150 PLN</strong>
        <span class="margin-badge">60%</span>
    </div>

    <!-- Edit Mode -->
    <input
        x-show="editing"
        x-model="price"
        @blur="editing = false; $wire.updatePrice($event.target.value)"
        @keydown.enter="editing = false"
        @keydown.escape="editing = false; price = 150"
        type="number"
        step="0.01"
        class="price-input"
    />
</td>
```

### Margin Badge (Color-Coded)

```css
.margin-badge {
    font-size: 0.75rem;
    padding: 2px 6px;
    border-radius: 4px;
}

/* High margin (>50%) = green */
.margin-badge.high { background: #10b981; color: white; }

/* Medium margin (30-50%) = yellow */
.margin-badge.medium { background: #f59e0b; color: white; }

/* Low margin (<30%) = red */
.margin-badge.low { background: #ef4444; color: white; }
```

---

## 📖 Nawigacja

- **Poprzedni moduł:** [07. Produkty](07_PRODUKTY.md)
- **Następny moduł:** [09. Warianty & Cechy](09_WARIANTY_CECHY.md)
- **Powrót:** [Spis treści](README.md)
