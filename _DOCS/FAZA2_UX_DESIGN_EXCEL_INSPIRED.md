# FAZA 2: UX DESIGN - Excel-Inspired Bulk Edit

**Data**: 2025-10-24
**Kontekst**: Analiza pliku `References/Produkty_Przykład.xlsx` (121 vehicle columns, P-EF)
**Cel**: Zaprojektować intuicyjny UX dla bulk edit dopasowań, zachowując workflow Excel

---

## 📊 EXCEL WORKFLOW ANALYSIS

### Current User Workflow (Excel):

**1. Horizontal Drag (Produkt → Wiele Pojazdów)**
```
Excel:
- Zaznacz komórkę produktu (np. Row 7, Column P)
- Wpisz "O" (Oryginał) lub "Z" (Zamiennik)
- Przeciągnij RIGHT → kolumny Q, R, S... (wiele pojazdów)
- Result: 1 produkt przypisany do rodziny pojazdów (np. YCF LITE*, 26 vehicles)
```

**2. Vertical Drag (Wiele Produktów → Pojazd)**
```
Excel:
- Zaznacz komórkę pierwszego produktu (np. Row 2, Column P)
- Wpisz "O" lub "Z"
- Przeciągnij DOWN → rows 3, 4, 5... (wiele produktów)
- Result: Rodzina produktów przypisana do 1 pojazdu
```

**3. Family Pattern (Real-World Example)**
```
SKU 396 (Row 7): Oryginał dla 26 pojazdów YCF rodziny:
- YCF LITE 88S, YCF START 88SE, YCF START 88S PL
- YCF LITE 125, YCF START 125, YCF START 125SE
- YCF PILOT 125, YCF PILOT 150E, YCF PILOT 150
- ... (wszystkie YCF LITE/START/PILOT modele)

SKU 602 (Row 9): Zamiennik dla 19 pojazdów KAYO/MRF rodziny:
- KAYO 110 TSD, KAYO 125 TD, KAYO 125-R TD
- MRF 120 TTR, MRF 140 TTR, MRF 120 RC
- ... (wszystkie KAYO TD* i MRF TTR/RC modele)
```

---

## 🎨 PPM UX DESIGN (Excel-Inspired)

### MODE 1: BULK PART → VEHICLE (Horizontal Drag Equivalent)

**Use Case**: "Mam część która pasuje do całej rodziny pojazdów YCF LITE*"

**Workflow:**
1. **Select Parts** (CompatibilityManagement table)
   ```
   [✓] SKU 396 - Pasuje do YCF LITE rodziny
   [✓] SKU 388 - Pasuje do YCF LITE rodziny
   [ ] SKU 601 - Nie zaznaczony
   ```

2. **Click "Edycja masowa" button** → Opens BulkEditCompatibilityModal

3. **Modal Section 1: Direction Locked**
   ```
   Direction: Part → Vehicle (2 parts selected)

   Selected Parts:
   - SKU 396: [Product Name]
   - SKU 388: [Product Name]
   ```

4. **Modal Section 2: Search Vehicles**
   ```
   Search vehicles (SKU or name):
   [__________________________] 🔍

   Filters:
   [All Brands ▼] [All Series ▼]

   ☑️ Group by family - Show "Apply to family" button
   ```

5. **Modal Section 3: Search Results (Multi-Select)**
   ```
   Found 26 vehicles:

   YCF LITE Family (8 vehicles):
   [ ] YCF LITE 88S
   [ ] YCF LITE 125
   [ ] YCF LITE 150
   ...
   [Select all YCF LITE] ← Family helper button

   YCF START Family (10 vehicles):
   [ ] YCF START 88SE
   [ ] YCF START 88S PL
   [ ] YCF START 125
   ...
   [Select all YCF START] ← Family helper button

   YCF PILOT Family (8 vehicles):
   [ ] YCF PILOT 125
   [ ] YCF PILOT 150E
   ...
   [Select all YCF PILOT] ← Family helper button
   ```

6. **Modal Section 4: Compatibility Type**
   ```
   Compatibility Type:
   ( ) Oryginał  (green badge #10b981)
   ( ) Zamiennik (orange badge #f59e0b)
   ```

7. **Modal Section 5: Preview Table**
   ```
   Preview changes (52 new compatibilities):

   Part           | Vehicle          | Type      | Action
   ----------------------------------------------------------------
   SKU 396        | YCF LITE 88S     | Oryginał  | ➕ ADD
   SKU 396        | YCF LITE 125     | Oryginał  | ➕ ADD
   SKU 388        | YCF LITE 88S     | Oryginał  | ➕ ADD
   SKU 388        | YCF LITE 125     | Oryginał  | ➕ ADD
   ...

   ⚠️ Duplicates detected:
   - SKU 396 ↔ YCF LITE 88S already exists as Zamiennik
     [Replace with Oryginał] [Skip] [Cancel]
   ```

8. **Modal Section 6: Apply**
   ```
   [Cancel] [Zastosuj (52 changes)]
   ```

---

### MODE 2: BULK VEHICLE → PART (Vertical Drag Equivalent)

**Use Case**: "Pojazd KAYO 125 TD potrzebuje wielu części z tej samej rodziny produktów"

**Workflow:**
1. **Click "Edycja masowa" button** (bez zaznaczonych części) → Opens modal w trybie Vehicle→Part

2. **Modal Section 1: Direction Switch**
   ```
   Direction:
   ( ) Part → Vehicle
   (•) Vehicle → Part
   ```

3. **Modal Section 2: Select Vehicles**
   ```
   Search vehicles (SKU or name):
   [KAYO 125                  ] 🔍

   Results:
   [✓] KAYO 125 TD do 2023
   [✓] KAYO 125 TD
   [✓] KAYO 125-R TD
   [✓] KAYO 125 TT
   [ ] KAYO 140 TT (nie zaznaczony)

   [Select all KAYO 125*] ← Family helper

   4 vehicles selected
   ```

4. **Modal Section 3: Search Parts (Multi-Select)**
   ```
   Search parts (SKU or name):
   [__________________________] 🔍

   Filters:
   [All Categories ▼] [All Status ▼]

   Results (50 parts):
   [ ] SKU 601 - [Product Name]
   [ ] SKU 602 - [Product Name]
   [ ] SKU 603 - [Product Name]
   ...

   Bulk actions:
   [Select first 10] [Select all visible]
   ```

5. **Modal Section 4: Compatibility Type**
   ```
   Compatibility Type:
   ( ) Oryginał
   ( ) Zamiennik
   ```

6. **Preview & Apply** (same as MODE 1)

---

### MODE 3: QUICK ACTIONS (No Modal)

**Use Case**: "Szybka zmiana typu dopasowania bez otwierania modalu"

**Workflow:**
1. **In CompatibilityManagement table → Expand row (SKU 396)**
   ```
   Oryginał section:
   [YCF LITE 88S ×] [YCF LITE 125 ×] [YCF START 88SE ×]
   [+ Dodaj Pojazd]

   Zamiennik section:
   [KAYO 125 TD ×]
   [+ Dodaj Pojazd]
   ```

2. **Quick Toggle**: Click badge → Context menu
   ```
   [YCF LITE 88S ×] ← Right-click or long-press

   Context menu:
   • Zmień na Zamiennik
   • Usuń dopasowanie
   • Kopiuj pojazd
   ```

3. **Quick Add**: Click "+ Dodaj Pojazd" → Mini search popup
   ```
   Quick add vehicle:
   [Search...         ] 🔍

   Recent:
   - YCF LITE 150
   - KAYO 125 TD
   - MRF 120 TTR

   [Anuluj] [Dodaj]
   ```

---

### MODE 4: COPY PATTERN (Advanced)

**Use Case**: "Ten produkt ma takie same dopasowania jak inny produkt"

**Workflow:**
1. **In CompatibilityManagement table → Row actions menu**
   ```
   SKU 396 row:
   [▼] Actions menu:
   • Kopiuj dopasowania
   • Wklej dopasowania
   • Edytuj masowo
   ```

2. **Click "Kopiuj dopasowania"** → Shows notification
   ```
   ✓ Copied 26 compatibilities from SKU 396
   ```

3. **Select target product (SKU 388) → Click "Wklej dopasowania"**
   ```
   Paste compatibilities from SKU 396:

   [✓] Oryginał (13 vehicles)
   [ ] Zamiennik (0 vehicles)

   Options:
   [✓] Skip duplicates
   [ ] Replace existing

   [Anuluj] [Wklej (13 changes)]
   ```

---

## 🔑 KEY UX PRINCIPLES

### 1. **Excel Parity** (Familiar Workflow)
- ✅ Horizontal drag → Part→Vehicle bulk edit
- ✅ Vertical drag → Vehicle→Part bulk edit
- ✅ Family patterns → "Select all [Family]" buttons
- ✅ Quick toggle → Right-click context menu

### 2. **Performance** (Bulk Operations)
- ✅ Multi-select (checkboxes)
- ✅ Family helpers (select all YCF LITE*)
- ✅ Preview table (before apply)
- ✅ Transaction-safe (DB::transaction with attempts: 5)

### 3. **Safety** (No Data Loss)
- ✅ Preview changes before apply
- ✅ Duplicate detection (warn user)
- ✅ Undo support (optional - future)
- ✅ Validation (prevent invalid combinations)

### 4. **Discoverability** (Intuitive)
- ✅ "Edycja masowa" button (prominent in table header)
- ✅ Context menus (right-click badges)
- ✅ Recent vehicles (quick add)
- ✅ Tooltips (explain each action)

---

## 📋 IMPLEMENTATION CHECKLIST (FAZA 2)

### Backend (laravel-expert):
- [ ] CompatibilityManager::bulkAddCompatibilities($partIds, $vehicleIds, $attributeCode)
- [ ] CompatibilityManager::detectDuplicates($data)
- [ ] CompatibilityManager::copyCompatibilities($sourcePartId, $targetPartId, $options)
- [ ] DB::transaction(..., attempts: 5) - deadlock resilience
- [ ] Validation rules (prevent invalid combinations)

### Frontend (livewire-specialist):
- [ ] BulkEditCompatibilityModal component (~300 linii)
  - [ ] Direction switch (Part→Vehicle / Vehicle→Part)
  - [ ] Search (SKU + name, dual mode)
  - [ ] Multi-select (checkboxes)
  - [ ] Family helpers ("Select all [Family]" buttons)
  - [ ] Compatibility type radio (Oryginał / Zamiennik)
  - [ ] Preview table (changes before apply)
  - [ ] Duplicate detection UI
  - [ ] Apply button (transaction dispatch)
- [ ] Quick toggle context menu (expandable row badges)
- [ ] Copy/paste pattern (row actions)

### UI/UX (frontend-specialist):
- [ ] Modal styling (enterprise card design)
- [ ] Family grouping visual (indented lists)
- [ ] Preview table styling (diff colors: green ADD, yellow UPDATE, red CONFLICT)
- [ ] Context menu styling (right-click menu)
- [ ] Loading states (during bulk operations)
- [ ] Success/error notifications

---

## 🎯 SUCCESS METRICS

**User can:**
- ✅ Assign 1 part to 26 vehicles in <1 minute (vs. 26 minutes in Excel)
- ✅ Assign 50 parts to 1 vehicle in <1 minute (vs. 50 minutes in Excel)
- ✅ Toggle Oryginał ↔ Zamiennik without deleting
- ✅ Copy pattern from one part to another
- ✅ See preview before committing changes
- ✅ Use family helpers to bulk-select vehicle groups

**Performance:**
- ✅ Bulk operations complete in <5 seconds (100 compatibilities)
- ✅ Search results load in <500ms
- ✅ Preview table renders in <300ms

**Safety:**
- ✅ Zero data loss (transaction rollback on error)
- ✅ Duplicate detection (100% accuracy)
- ✅ Validation prevents invalid combinations

---

**Next Step**: Delegate FAZA 2 implementation to livewire-specialist + laravel-expert with this UX spec.
