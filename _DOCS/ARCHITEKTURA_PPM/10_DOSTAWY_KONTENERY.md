# 10. Dostawy & Kontenery

[◀ Powrót do spisu treści](README.md)

---

## 🚚 Dostawy & Kontenery - Przegląd

System zarządzania dostawami, kontenerami, przyjęciami magazynowymi i dokumentami odpraw.

**Uprawnienia:**
- **Admin/Menadżer:** Pełny dostęp
- **Magazynier:** Edycja ilości, przyjęcia, upload dokumentów
- **Handlowiec:** Read-only (dostępność do rezerwacji)

---

## 10.1 Lista Dostaw

**Route:** `/admin/deliveries`
**Controller:** DeliveryController@index
**Middleware:** auth, role:magazynier+

### Filtry

```
Status dostawy: [Wszystkie ▼]
                Zamówione
                W kontenerze
                Opóźnienie
                W trakcie przyjęcia
                Zakończone

Data dostawy: [od: ___] - [do: ___]
Dostawca: [Wszystkie ▼]
ID Kontenera: [search: _______________]
```

### Tabela Dostaw

| ID Kontenera | Dostawca | Data Zamówienia | Data Dostawy | Status | ORDER | Produkty | Wartość | Akcje |
|--------------|----------|-----------------|--------------|--------|-------|----------|---------|-------|
| CNT-2025-001 | Supplier A | 2025-08-01 | 2025-09-15 | 🟠 W kontenerze | 5 | 450 | 125,000 PLN | [⚙️] |
| CNT-2025-002 | Supplier B | 2025-09-01 | 2025-10-20 | ⏳ W trakcie | 3 | 280 | 85,000 PLN | [⚙️] |
| CNT-2025-003 | Supplier A | 2025-09-15 | 2025-11-01 | 🔴 Opóźnienie | 4 | 320 | 95,000 PLN | [⚙️] |

**Status Badges (Color-Coded):**
- 🔵 **Zamówione** (blue) - Zamówione u dostawcy
- 🟠 **W kontenerze** (orange) - W transporcie
- 🔴 **Opóźnienie** (red + days counter) - Przekroczona ETA (+5 dni)
- ⏳ **W trakcie przyjęcia** (yellow) - Przyjęcie magazynowe trwa
- ✅ **Zakończone** (green) - Zamknięte, produkty w magazynie

### Header Actions

```
[+ Nowa Dostawa]  [📥 Import z XLSX]
```

---

## 10.2 Szczegóły Kontenera

**Route:** `/admin/deliveries/containers/{id}`
**Controller:** ContainerController@show
**Middleware:** auth, role:magazynier+ (edycja)

### Header Info

```
┌────────────────────────────────────────────────────────┐
│ 📦 KONTENER: CNT-2025-001                              │
│ Status: 🟠 W kontenerze                                │
│                                                        │
│ Data zamówienia: 2025-08-01                            │
│ Data dostawy (ETA): 2025-09-15                         │
│ Dostawca: Supplier A (China)                          │
│                                                        │
│ ORDER: 5 | Produkty: 450 | Wartość: 125,000 PLN       │
└────────────────────────────────────────────────────────┘
```

### Tabs

#### Tab 1: ORDERS

**Lista ORDER (Collapsible Cards):**

```
▼ ORDER #1 (85 produktów) - Status: ✅ Kompletny
┌────────────────────────────────────────────────────┐
│ | SKU | Nazwa | Qty Zamówiona | Qty Rzeczywista | Status | Uwagi |
│ | PROD-001 | Test | 50 | [50] | ✅ OK | - |
│ | PROD-002 | ... | 35 | [30] | ⚠️ -5 | [Brakuje 5 szt.] |
└────────────────────────────────────────────────────┘

▶ ORDER #2 (120 produktów) - Status: ⏳ W trakcie
▶ ORDER #3 (90 produktów) - Status: ❌ Pending
```

**Editable Columns (Magazynier):**
- **Qty Rzeczywista:** [input number]
- **Status:** [dropdown: OK / Brak / Uszkodzone / Niezgodne]
- **Uwagi:** [textarea]

#### Tab 2: DOKUMENTY ODPRAW

**Upload Zone (Multiple Files):**

```
┌─────────────────────────────────────────┐
│   📁                                     │
│   Przeciągnij i upuść dokumenty          │
│   ZIP, XLSX, PDF, XML                   │
│                                         │
│   Max size per file: 20MB               │
│   Możesz uploadować wiele jednocześnie  │
└─────────────────────────────────────────┘
```

**Lista Dokumentów:**

| Nazwa Pliku | Typ | Rozmiar | Data Uploadu | Uploaded By | Akcje |
|-------------|-----|---------|--------------|-------------|-------|
| odprawa_CNT001.zip | ZIP | 15MB | 2025-09-10 10:30 | admin@mpptrade | [📥] [🗑️] |
| faktury.pdf | PDF | 2.5MB | 2025-09-10 11:00 | magazyn@mpptrade | [📥] [🗑️] |
| manifest.xlsx | XLSX | 850KB | 2025-09-10 11:15 | magazyn@mpptrade | [📥] [🗑️] |

**Akcje:**
- **Download:** Pobierz plik
- **Delete:** Usuń (tylko admin/manager)

#### Tab 3: HISTORIA PRZYJĘĆ

**Timeline Zmian:**

```
┌────────────────────────────────────────────────────────┐
│ 2025-09-10 14:30 | magazyn@mpptrade                    │
│ ✅ Zakończono przyjęcie ORDER #1                       │
│ Qty: 85/85 produktów (100%)                           │
│ Różnice: -5 szt. PROD-002 (Brak)                      │
├────────────────────────────────────────────────────────┤
│ 2025-09-10 12:00 | magazyn@mpptrade                    │
│ 📦 Rozpoczęto przyjęcie ORDER #1                       │
├────────────────────────────────────────────────────────┤
│ 2025-09-10 10:00 | admin@mpptrade                      │
│ 📄 Uploaded: odprawa_CNT001.zip                        │
├────────────────────────────────────────────────────────┤
│ 2025-09-01 08:00 | system                              │
│ 🚢 Status zmieniony: Zamówione → W kontenerze          │
└────────────────────────────────────────────────────────┘
```

**Uwagi Magazynu (Timeline Notes):**
- Kto przyjmował
- Różnice ilościowe (szczegółowo per produkt)
- Uwagi magazyniera (komentarze)
- Zdjęcia (jeśli dodane przez app Android - future)

### Actions Footer

```
[✅ Zamknij Dostawę]  [📤 Eksport do CSV]  [🖨️ Drukuj Raport]
```

**Zamknij Dostawę (Modal Confirmation):**

```
┌──────────────────────────────────────────┐
│ ⚠️ Zamknięcie dostawy                    │
│                                          │
│ Zamykasz dostawę CNT-2025-001            │
│                                          │
│ Po zamknięciu:                           │
│ ☑️ Stany magazynowe zostaną zaktualizowane│
│ ☑️ Dokument zostanie utworzony w ERP     │
│ ☑️ Status zmieni się na "Zakończone"     │
│ ❌ Nie będzie możliwości edycji          │
│                                          │
│ Czy na pewno chcesz zamknąć?             │
│                                          │
│ [✅ Tak, Zamknij]  [❌ Anuluj]            │
└──────────────────────────────────────────┘
```

---

## 10.3 Przyjęcia Magazynowe

**Route:** `/admin/deliveries/receiving`
**Controller:** ReceivingController@index
**Middleware:** auth, role:magazynier+

### Active Receipts (Cards)

```
┌─────────────────────────────────────────┐
│ 📦 CNT-2025-002                         │
│ Data rozpoczęcia: 2025-10-22 09:00      │
│                                         │
│ Progress: ████████░░ 75% (210/280)      │
│                                         │
│ Użytkownik: magazyn@mpptrade            │
│                                         │
│ [▶️ Kontynuuj] [✅ Zakończ] [❌ Anuluj]  │
└─────────────────────────────────────────┘
```

### Receiving Interface (Per Kontener)

**Scanner Integration:**

```
┌──────────────────────────────────────────┐
│ 🔍 Barcode Scanner (Auto-Focus)          │
│ [___________________________] [Scan]     │
│                                          │
│ Lub wprowadź SKU ręcznie:                │
│ [PROD-001______________] [🔍 Szukaj]     │
└──────────────────────────────────────────┘

Match Status: ✅ Znaleziono PROD-001
```

**Product Verification (Current Product):**

```
┌────────────────────────────────────────────────────┐
│ SKU: PROD-001 (duży font)                          │
│ Nazwa: Test Product                                │
│ 🖼️ [Zdjęcie produktu]                              │
├────────────────────────────────────────────────────┤
│ Qty Zamówiona: 50 szt.                             │
│                                                    │
│ Qty Rzeczywista:                                   │
│ ○ Zgodne (50 szt.)                                 │
│ ● Niezgodne                                        │
│   Manual input: [48___] szt.                       │
│   [-] [+] (buttons)                                │
├────────────────────────────────────────────────────┤
│ Uwagi (opcjonalne):                                │
│ [Brakuje 2 szt. - uszkodzone w transporcie___]    │
├────────────────────────────────────────────────────┤
│ Upload zdjęcia (opcjonalne):                       │
│ [📷 Camera] [📁 File]                              │
│   [thumbnail if uploaded]                          │
│                                                    │
│ [✅ Potwierdź] [⏩ Pomiń]                           │
└────────────────────────────────────────────────────┘
```

**Progress Tracking (Sidebar):**

```
┌────────────────────────────┐
│ PRODUKTY (210/280)          │
├────────────────────────────┤
│ ✅ Zweryfikowane (210):     │
│   PROD-001 ✅               │
│   PROD-002 ✅               │
│   PROD-003 ✅               │
│   ...                      │
│                            │
│ ⚠️ Niezgodne (15):         │
│   PROD-050 ⚠️ (-2 szt.)    │
│   PROD-075 ⚠️ (+5 szt.)    │
│   ...                      │
│                            │
│ ⏸️ Pozostałe (70):         │
│   PROD-210                 │
│   PROD-211                 │
│   ...                      │
└────────────────────────────┘
```

### Actions

```
[✅ Zakończ Przyjęcie]
[⏸️ Wstrzymaj Przyjęcie (Save Progress)]
```

**Zakończ Przyjęcie:**
- Walidacja: wszystkie produkty zweryfikowane?
- Summary report (PDF/CSV)
- Update stanów magazynowych
- Zmiana statusu kontenera → "Zakończone"

---

## 10.4 Dokumenty Odpraw

**Route:** `/admin/deliveries/documents`
**Controller:** DeliveryDocumentController@index
**Middleware:** auth, role:magazynier+

### Filtry

```
Kontener: [Wszystkie ▼]
Typ dokumentu: [Wszystkie ▼]
               ZIP
               PDF
               XLSX
               XML
Data: [od: ___] - [do: ___]
```

### Grid Dokumentów (Cards with Preview)

```
┌─────────────────┐ ┌─────────────────┐
│ 📁 odprawa.zip  │ │ 📄 faktura.pdf  │
│ 15MB            │ │ 2.5MB           │
│                 │ │ [PDF thumbnail] │
│ CNT-2025-001    │ │ CNT-2025-001    │
│ 2025-09-10      │ │ 2025-09-10      │
│                 │ │                 │
│ [📥] [👁] [🗑️]   │ │ [📥] [👁] [🗑️]   │
└─────────────────┘ └─────────────────┘
```

**Akcje per Dokument:**
- **Download:** Pobierz plik
- **View:** Lightbox preview (PDF/images)
- **Delete:** Usuń (tylko admin/manager)

### Bulk Actions

```
☑️ Zaznaczono 5 dokumentów

[📥 Download Selected (as ZIP)]  [🗑️ Delete Selected]
```

---

## 🎨 UI/UX Patterns

### Status Badge (Kontenery)

```css
.status-badge.zamowione {
    background: #3b82f6; /* blue */
    color: white;
}

.status-badge.w-kontenerze {
    background: #f97316; /* orange */
    animation: pulse 2s infinite;
}

.status-badge.opoznienie {
    background: #ef4444; /* red */
    color: white;
}

.status-badge.opoznienie::after {
    content: ' (+' attr(data-days) ' dni)';
    font-weight: bold;
}
```

### Receiving Progress Bar

```html
<div class="receiving-progress" wire:poll.2s>
    <div class="progress-bar" style="width: {{ $progress }}%">
        <span>{{ $verified }} / {{ $total }} ({{ $progress }}%)</span>
    </div>

    <div class="progress-stats">
        <span class="verified">✅ {{ $verified }}</span>
        <span class="mismatched">⚠️ {{ $mismatched }}</span>
        <span class="remaining">⏸️ {{ $remaining }}</span>
    </div>
</div>
```

---

## 📖 Nawigacja

- **Poprzedni moduł:** [09. Warianty & Cechy](09_WARIANTY_CECHY.md)
- **Następny moduł:** [11. Zamówienia](11_ZAMOWIENIA.md)
- **Powrót:** [Spis treści](README.md)
