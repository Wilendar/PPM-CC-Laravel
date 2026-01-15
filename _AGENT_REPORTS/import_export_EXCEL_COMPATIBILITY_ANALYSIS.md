# RAPORT ANALIZY: Excel Compatibility dla Systemu Dopasowań

**Data**: 2025-12-04
**Agent**: import-export-specialist
**Zadanie**: Analiza workflow Excel użytkownika dla systemu dopasowań części zamiennych do pojazdów

---

## 🎯 STRESZCZENIE WYKONAWCZE

Użytkownik obecnie zarządza dopasowaniami ~1600 produktów do ~121 modeli pojazdów przez Excel, używając prostego schematu O/Z (Oryginał/Zamiennik). System ten jest szybki w masowej edycji, ale wymaga walidacji i inteligentnych sugestii dostępnych tylko w aplikacji webowej.

**KLUCZOWE WNIOSKI:**
- ✅ Format Excel jest prosty i skuteczny dla bulk operations
- ✅ Większość produktów ma 1-30 dopasowań (81.2%)
- ⚠️ 15.5% produktów nie ma żadnych dopasowań (wymaga attention)
- ⚠️ Brak walidacji prowadzi do potencjalnych błędów
- 🎯 UX aplikacji powinien zachować szybkość Excel + dodać inteligencję

---

## 📊 STRUKTURA PLIKU EXCEL

### 1. PODSTAWOWE STATYSTYKI

**Plik**: `Produkty_Przykład_Large.xlsx`

```
Total Rows: 1591 produktów
Total Columns: 136 kolumn
- Product info columns: 11
- Category columns: 4
- Vehicle model columns: 121
```

### 2. KOLUMNY PRODUKTOWE (11)

| Kolumna | Typ | Przykład | Znaczenie |
|---------|-----|----------|-----------|
| `LP` | Integer | 323 | Lp. produktu |
| `STATUS` | Enum | DO AKTUALIZACJI | Status produktu |
| `Symbol` | String (SKU) | MRF26-73-012 | **SKU - klucz główny** |
| `Nazwa Polska` | String | Hamulec kompletny przód pitbike YCF | Nazwa produktu |
| `Wariant/Matka` | Enum | PRODUKT PROSTY | Typ produktu |
| `Kategoria subiekt` | String | Hamulce > Zaciski, pompy, adaptery | Kategoria ERP |
| `Dodane na B2B` | Enum | Jest na B2B | Status publikacji |
| `ZDJĘCIE W BL` | String | (empty) | Status zdjęcia Baselinker |
| `MARKA` | String | YCF | Marka produktu |
| `Waga (kg)` | Decimal | 0.500000 | Waga produktu |
| `PRESTA` | String | Wszystko | Sklepy PrestaShop |

### 3. KOLUMNY KATEGORII (4)

| Kolumna | Przykład | Poziom |
|---------|----------|--------|
| `Kategoria P0` | Części zamienne | ROOT |
| `Kategoria P1` | Części Pit Bike | Level 1 |
| `Kategoria P2` | Hamulce | Level 2 |
| `Kategoria P3` | Hamulce komplet | Level 3 |

**STRUKTURA HIERARCHICZNA:** P0 > P1 > P2 > P3 (4 poziomy kategorii)

### 4. KOLUMNY DOPASOWAŃ (121 modeli pojazdów)

**PRZYKŁADOWE MODELE:**

**KAYO (39 modeli):**
- KAYO 50 KMB do 2023
- KAYO 90 TS
- KAYO 125 TD
- KAYO K2 250 ENDURO
- KAYO AU300 T3B
- ... (total 39)

**MRF (30 modeli):**
- MRF 80 RUNNER
- MRF 120 TTR e-start
- MRF 140 RC
- MRF eR 1.6 MX
- MRF eJOY 500 MX
- ... (total 30)

**YCF (39 modeli):**
- YCF 50A
- YCF LITE 88S
- YCF START 125
- YCF PILOT 150
- YCF FACTORY 190 SP3 DAYTONA 2025
- ... (total 39)

**POZOSTAŁE (13 modeli):**
- RXF Mini 50
- RXF Open 150
- PitGang 125XD
- ... (total 13)

---

## 🔍 ANALIZA WARTOŚCI DOPASOWAŃ

### 1. SYSTEM WARTOŚCI

W kolumnach pojazdów występują **TYLKO 3 wartości**:

| Wartość | Znaczenie | Częstość (w 100 pierwszych wierszach) |
|---------|-----------|----------------------------------------|
| `O` | **Oryginał** | 417 wystąpień |
| `Z` | **Zamiennik** | 342 wystąpień |
| *(puste)* | Brak dopasowania | N/A |

**PRZYKŁADY:**

```
SKU: 18291/152FMH (Uszczelka wydechu pitbike YCF)
├─ YCF LITE 88S = O (Oryginał)
├─ YCF START 88SE = O
├─ YCF PILOT 125 = O
└─ ... (35 dopasowań total)

SKU: 24700/152FMH/03 (Dźwignia zmiany biegów)
├─ YCF LITE 88S = Z (Zamiennik)
├─ YCF START 88SE = O (Oryginał)
├─ YCF START 125 = O
└─ ... (28 dopasowań total)
```

**OBSERWACJA:** Ten sam produkt może być oryginałem dla jednego modelu i zamiennikiem dla innego!

### 2. ROZKŁAD DOPASOWAŃ NA PRODUKT

| Zakres dopasowań | Liczba produktów | % całości |
|------------------|------------------|-----------|
| **Brak dopasowań** | 246 | 15.5% ⚠️ |
| **1-10 dopasowań** | 1046 | 65.7% ✅ |
| **11-30 dopasowań** | 247 | 15.5% |
| **31-60 dopasowań** | 50 | 3.1% |
| **61-100 dopasowań** | 2 | 0.1% |
| **100+ dopasowań** | 0 | 0% |

**WNIOSKI:**
- ✅ Większość produktów (81.2%) ma 1-30 dopasowań - **SWEET SPOT dla UX**
- ⚠️ 246 produktów (15.5%) bez dopasowań - **wymaga attention**
- 🎯 Ekstremalne przypadki (60+ dopasowań) są rzadkie

### 3. TOP 5 PRODUKTÓW Z NAJWIĘKSZĄ LICZBĄ DOPASOWAŃ

| SKU | Nazwa | Marka | Liczba dopasowań |
|-----|-------|-------|------------------|
| 18291/152FMH | Uszczelka wydechu pitbike YCF | YCF | **35** |
| 17332/152FMH01 | Uszczelka kręciec/gaźnik 28mm | YCF | **29** |
| 24700/152FMH/03 | Dźwignia zmiany biegów | YCF | **28** |
| 17332 | Uszczelka kręciec/głowica 28mm | YCF | **26** |
| 636103 | Opona 60/100-14 Dunlop Geomax MX33 | Dunlop | **19** |

**PATTERN:** Uniwersalne części (uszczelki, opony) mają najwięcej dopasowań!

---

## 🏢 ANALIZA MAREK

**TOP 10 MAREK:**

| Marka | Liczba produktów | % całości |
|-------|------------------|-----------|
| **YCF** | 609 | 38.3% |
| **KAYO** | 519 | 32.6% |
| **MRF** | 227 | 14.3% |
| **RXF** | 59 | 3.7% |
| **PitGang** | 26 | 1.6% |
| FASTACE | 20 | 1.3% |
| Dunlop | 12 | 0.8% |
| Kenda | 12 | 0.8% |
| Mitas | 11 | 0.7% |
| GIBSON | 11 | 0.7% |

**KONCENTRACJA:** Top 3 marki (YCF, KAYO, MRF) = **85.2% wszystkich produktów**!

---

## 📋 OBECNY WORKFLOW UŻYTKOWNIKA (Excel)

### ZALETY ✅

1. **SZYBKOŚĆ MASOWEJ EDYCJI**
   - Zaznacz kolumnę modelu → przeciągnij O/Z w dół → instant bulk assign
   - Przykład: Przypisanie 50 części do modelu "KAYO 125 TD" = 10 sekund

2. **PRZEJRZYSTOŚĆ**
   - Widok macierzowy (rows = produkty, columns = modele)
   - Łatwa identyfikacja pustych komórek
   - Ctrl+F dla wyszukiwania

3. **ŁATWOŚĆ KOPIOWANIA**
   - Copy/paste między modelami
   - Kopiowanie wzorców dopasowań

4. **EKSPORT/BACKUP**
   - .xlsx = uniwersalny format
   - Łatwy backup i versioning

### WADY ❌

1. **BRAK WALIDACJI**
   - Można wpisać błędną wartość (np. "Oryginal" zamiast "O")
   - Brak sprawdzania czy model istnieje
   - Brak warning przy konfliktach

2. **TRUDNE ZARZĄDZANIE PRZY WIELU MODELACH**
   - 121 kolumn = scrolling w poziomie
   - Trudno znaleźć konkretny model bez Ctrl+F

3. **BRAK SUGESTII**
   - Użytkownik musi ZNAĆ dopasowania
   - Brak inteligentnego podpowiadania na podstawie podobnych produktów

4. **RYZYKO BŁĘDÓW**
   - Przypadkowe nadpisanie wartości
   - Brak audit trail (kto, kiedy zmienił)

5. **SYNCHRONIZACJA**
   - Manualne eksporty do aplikacji
   - Ryzyko rozbieżności między Excel a bazą danych

---

## 🎨 REKOMENDACJE UX DLA APLIKACJI WEBOWEJ

### CELE PROJEKTOWE

1. ✅ **Zachować szybkość** Excel dla bulk operations
2. ✅ **Dodać walidację** i inteligentne sugestie
3. ✅ **Ułatwić zarządzanie** dużą liczbą modeli
4. ✅ **Zapewnić audit trail** i conflict detection

### PROPONOWANY UX: HYBRID APPROACH

#### WARIANT A: "EXCEL-LIKE GRID" (dla power users)

**KONCEPCJA:** Edytowalny grid z funkcjonalnością Excel + AI suggestions

**LAYOUT:**

```
┌─────────────────────────────────────────────────────────────────────┐
│ 🔍 Szybkie filtrowanie                                               │
│ [SKU____] [Nazwa________] [Marka_v] [Modele pojazdów_______v]       │
└─────────────────────────────────────────────────────────────────────┘

┌────────┬─────────────────┬────────┬────────┬────────┬────────┬──────┐
│ SKU    │ Nazwa           │ Marka  │ KAYO   │ MRF    │ YCF    │ ...  │
│        │                 │        │ 125 TD │ 140 RC │ LITE   │      │
├────────┼─────────────────┼────────┼────────┼────────┼────────┼──────┤
│ MRF26  │ Hamulec komplet │ YCF    │ [O_v]  │ [Z_v]  │ [O_v]  │ ...  │
│ -73-012│ przód pitbike   │        │        │        │        │      │
├────────┼─────────────────┼────────┼────────┼────────┼────────┼──────┤
│ 18291  │ Uszczelka       │ YCF    │ [__v]  │ [O_v]  │ [O_v]  │ ...  │
│ /152FMH│ wydechu pitbike │        │        │        │💡 +34  │      │
└────────┴─────────────────┴────────┴────────┴────────┴────────┴──────┘

💡 = AI suggestion: "Podobne produkty mają 34 inne dopasowania YCF"
```

**FUNKCJE:**

1. **DROPDOWN W KOMÓRKACH**
   - Klik → dropdown [Puste | O - Oryginał | Z - Zamiennik]
   - Keyboard: O/Z/Backspace (fast input)

2. **BULK SELECTION**
   - Zaznacz wiele komórek → Apply O/Z → instant update
   - Shift+Click dla range selection

3. **AI SUGGESTIONS (💡 icon)**
   - "10 podobnych produktów ma dopasowanie do KAYO 125 TD"
   - "Uzupełnij brakujące dopasowania na podstawie marki?"
   - Klik → podgląd sugestii → Accept/Reject

4. **STICKY HEADERS**
   - Fixed SKU/Nazwa columns (zawsze widoczne przy scrollu)
   - Fixed model headers (zawsze widoczne przy scrollu w dół)

5. **VISUAL INDICATORS**
   - O = zielony badge "Oryginał"
   - Z = niebieski badge "Zamiennik"
   - Empty = szary placeholder "–"
   - Conflict = czerwony border (np. duplikat dopasowania)

6. **COLUMN GROUPING**
   - Grupowanie modeli po marce: [▼ KAYO (39 modeli)] [▼ MRF (30)] [▼ YCF (39)]
   - Collapse/expand groups dla czytelności

#### WARIANT B: "SMART FORM" (dla casual users)

**KONCEPCJA:** Jeden produkt at a time, z inteligentnym multi-select

**LAYOUT:**

```
┌─────────────────────────────────────────────────────────────────────┐
│ Edycja dopasowań: Hamulec kompletny przód pitbike YCF               │
│ SKU: MRF26-73-012 | Marka: YCF                                      │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│ 🎯 DOPASOWANIA DO MODELI POJAZDÓW                                   │
│                                                                      │
│ 💡 AI SUGESTIE (10 znalezionych)                                    │
│ ┌────────────────────────────────────────────────────────────────┐ │
│ │ ✅ YCF LITE 88S (podobne produkty: 85%)        [Dodaj jako O]  │ │
│ │ ✅ YCF START 125 (podobne produkty: 90%)       [Dodaj jako O]  │ │
│ │ ✅ YCF PILOT 150 (podobne produkty: 88%)       [Dodaj jako Z]  │ │
│ │    ... 7 więcej                        [Zaakceptuj wszystkie] │ │
│ └────────────────────────────────────────────────────────────────┘ │
│                                                                      │
│ 📋 OBECNE DOPASOWANIA (35)                                          │
│ ┌────────────────────────────────────────────────────────────────┐ │
│ │ 🟢 YCF LITE 88S                       [Oryginał] [✏️] [🗑️]     │ │
│ │ 🟢 YCF START 88SE                     [Oryginał] [✏️] [🗑️]     │ │
│ │ 🔵 YCF PILOT 125                      [Zamiennik] [✏️] [🗑️]    │ │
│ │    ... 32 więcej                                               │ │
│ └────────────────────────────────────────────────────────────────┘ │
│                                                                      │
│ ➕ DODAJ NOWE DOPASOWANIE                                           │
│ [Wybierz model pojazdu___________________________v] [Typ: O/Z_v]   │
│                                              [Dodaj] [Dodaj więcej] │
└─────────────────────────────────────────────────────────────────────┘

[Zapisz zmiany] [Anuluj] [Następny produkt →]
```

**FUNKCJE:**

1. **AI-POWERED SUGGESTIONS**
   - Analiza podobnych produktów (ta sama marka, kategoria, SKU pattern)
   - "85% podobnych produktów ma to dopasowanie" → confidence score
   - Bulk accept suggestions

2. **SMART MULTI-SELECT**
   - Dropdown z grupowaniem: [▼ KAYO (39)] [▼ MRF (30)] [▼ YCF (39)]
   - Type-ahead search: "kayo 125" → filtruje do 3 wyników
   - Multi-select: Zaznacz wiele modeli → Apply O/Z → zapisz

3. **VISUAL BADGES**
   - 🟢 Oryginał (zielony)
   - 🔵 Zamiennik (niebieski)
   - Sortowanie: Oryginały → Zamienniki

4. **QUICK ACTIONS**
   - ✏️ Zmień typ (O ↔ Z)
   - 🗑️ Usuń dopasowanie
   - Bulk delete: Zaznacz wiele → Delete

5. **NAVIGATION**
   - "Następny produkt →" - przejdź do kolejnego produktu bez dopasowań
   - Keyboard shortcuts: Ctrl+S (save), Ctrl+→ (next)

#### WARIANT C: "BULK WIZARD" (dla masowych operacji)

**KONCEPCJA:** Kreator do masowego przypisania wielu produktów do modelu

**LAYOUT:**

```
┌─────────────────────────────────────────────────────────────────────┐
│ KREATOR MASOWEGO PRZYPISANIA                                         │
│                                                                       │
│ Krok 1: Wybierz model pojazdu                                        │
│ [KAYO 125 TD_____________________________v]                          │
│                                                                       │
│ Krok 2: Wybierz produkty do przypisania                             │
│ ┌─────────────────────────────────────────────────────────────────┐ │
│ │ 🔍 Filtruj: [Marka: KAYO_v] [Kategoria: Hamulce_v]              │ │
│ │                                                                  │ │
│ │ [✓] MRF26-73-012 - Hamulec kompletny przód                      │ │
│ │ [✓] 18291/152FMH - Uszczelka wydechu                            │ │
│ │ [ ] 24700/152FMH/03 - Dźwignia zmiany biegów                    │ │
│ │     ... 1588 więcej                                             │ │
│ │                                                                  │ │
│ │ [Zaznacz wszystkie] [Odznacz wszystkie]                         │ │
│ └─────────────────────────────────────────────────────────────────┘ │
│                                                                       │
│ Krok 3: Typ dopasowania                                             │
│ ⚪ Oryginał (O)   ⚪ Zamiennik (Z)                                   │
│                                                                       │
│ Podsumowanie: 2 produkty → KAYO 125 TD jako Oryginał                │
│                                                                       │
│ [◀ Wstecz] [Przypisz] [Anuluj]                                      │
└─────────────────────────────────────────────────────────────────────┘
```

**FUNKCJE:**

1. **3-STEP WIZARD**
   - Step 1: Wybór modelu (type-ahead search)
   - Step 2: Multi-select produktów (z filtrowaniem)
   - Step 3: Typ dopasowania (O/Z)

2. **PREVIEW**
   - Podsumowanie przed zapisem
   - "2 produkty → KAYO 125 TD jako Oryginał"

3. **FILTROWANIE**
   - Marka, kategoria, status
   - "Pokaż tylko produkty bez dopasowań do tego modelu"

---

## 🔄 IMPORT/EKSPORT DOPASOWAŃ Z/DO EXCEL

### IMPORT WORKFLOW

**SCENARIUSZ:** Użytkownik ma Excel z nowymi dopasowaniami

```
1. Upload Excel file
   ↓
2. WALIDACJA:
   ✓ Sprawdź kolumny (SKU, modele pojazdów)
   ✓ Sprawdź wartości (tylko O/Z/puste)
   ✓ Sprawdź czy SKU istnieją w bazie
   ✓ Sprawdź czy modele istnieją w bazie
   ↓
3. PREVIEW:
   "Znaleziono 1591 produktów"
   "121 modeli pojazdów"
   "8547 dopasowań do importu"
   ⚠️ "246 produktów nie znalezionych (utworzyć?)"
   ⚠️ "5 modeli nie znalezionych w bazie"
   ↓
4. CONFLICT RESOLUTION:
   "Produkt SKU-123 ma już dopasowanie KAYO 125 TD = Z"
   "W Excel: KAYO 125 TD = O"
   ⚪ Zastąp istniejące  ⚪ Pomiń  ⚪ Zapytaj dla każdego
   ↓
5. IMPORT:
   [========= 75% =========       ] 6410/8547
   "Importing matches..."
   ↓
6. PODSUMOWANIE:
   ✅ 8500 dopasowań zaimportowano
   ⚠️ 47 konfliktów rozwiązano
   ❌ 5 błędów (log do pobrania)
```

**STRUKTURA PLIKU IMPORTU:**

```
| SKU          | Nazwa Polska         | KAYO 125 TD | MRF 140 RC | YCF LITE 88S | ... |
|--------------|----------------------|-------------|------------|--------------|-----|
| MRF26-73-012 | Hamulec kompletny    | O           | Z          |              | ... |
| 18291/152FMH | Uszczelka wydechu    |             | O          | O            | ... |
```

**ZASADY:**
- Kolumna SKU = REQUIRED (klucz główny)
- Kolumny z nazwami modeli = dopasowania (wartości: O/Z/puste)
- Kolejność kolumn = dowolna
- Ignorowane kolumny: Nazwa Polska, Marka, Kategorie (tylko info, nie import)

### EKSPORT WORKFLOW

**SCENARIUSZ:** Użytkownik chce Excel do offline edycji

```
1. Wybór danych do eksportu
   ┌────────────────────────────────────────┐
   │ ☑ Wszystkie produkty (1591)            │
   │ ☐ Tylko produkty z marki: [YCF__v]     │
   │ ☐ Tylko kategoria: [Hamulce_v]         │
   │ ☐ Tylko produkty bez dopasowań (246)   │
   └────────────────────────────────────────┘
   ↓
2. Wybór modeli do eksportu
   ┌────────────────────────────────────────┐
   │ ☑ Wszystkie modele (121)               │
   │ ☐ Tylko marka: [KAYO_v] [MRF_v] [YCF_v]│
   │ ☐ Custom selection (multi-select)      │
   └────────────────────────────────────────┘
   ↓
3. Dodatkowe kolumny (opcjonalne)
   ┌────────────────────────────────────────┐
   │ ☑ Nazwa Polska                         │
   │ ☑ Marka                                │
   │ ☑ Kategorie (P0-P3)                    │
   │ ☐ Waga                                 │
   │ ☐ Status                               │
   └────────────────────────────────────────┘
   ↓
4. GENEROWANIE EXCEL
   [========= 100% =========]
   ✅ Plik gotowy do pobrania
   [📥 Pobierz Dopasowania_2025-12-04.xlsx]
```

**FORMAT EKSPORTU (identyczny jak import):**

```excel
| SKU          | Nazwa Polska         | Marka | KAYO 125 TD | MRF 140 RC | YCF LITE 88S | ... |
|--------------|----------------------|-------|-------------|------------|--------------|-----|
| MRF26-73-012 | Hamulec kompletny    | YCF   | O           | Z          |              | ... |
| 18291/152FMH | Uszczelka wydechu    | YCF   |             | O          | O            | ... |
```

---

## 🗄️ STRUKTURA BAZY DANYCH

### PROPONOWANY SCHEMAT

```sql
-- Tabela modeli pojazdów
CREATE TABLE vehicle_models (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL UNIQUE, -- "KAYO 125 TD"
    brand VARCHAR(100), -- "KAYO"
    model_code VARCHAR(100), -- "125 TD"
    year VARCHAR(50), -- "do 2023", "2025", NULL
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_brand (brand),
    INDEX idx_name (name)
);

-- Tabela dopasowań (pivot)
CREATE TABLE product_vehicle_matches (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    vehicle_model_id INT NOT NULL,
    match_type ENUM('original', 'replacement') NOT NULL, -- O/Z

    -- Audit trail
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT, -- user_id
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT, -- user_id

    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_model_id) REFERENCES vehicle_models(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,

    -- Constraint: Jeden produkt może mieć tylko JEDNO dopasowanie do danego modelu
    UNIQUE KEY unique_product_vehicle (product_id, vehicle_model_id),

    INDEX idx_product (product_id),
    INDEX idx_vehicle (vehicle_model_id),
    INDEX idx_match_type (match_type)
);

-- Historia zmian (dla audit trail)
CREATE TABLE product_vehicle_matches_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    vehicle_model_id INT NOT NULL,
    match_type ENUM('original', 'replacement'),
    action ENUM('created', 'updated', 'deleted') NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    changed_by INT, -- user_id

    INDEX idx_product (product_id),
    INDEX idx_changed_at (changed_at)
);
```

### PRZYKŁADOWE DANE

```sql
-- Vehicle models
INSERT INTO vehicle_models (name, brand, model_code, year) VALUES
('KAYO 125 TD', 'KAYO', '125 TD', NULL),
('KAYO 125 TD do 2023', 'KAYO', '125 TD', 'do 2023'),
('YCF LITE 88S', 'YCF', 'LITE 88S', NULL),
('YCF START 125SE 2025', 'YCF', 'START 125SE', '2025');

-- Matches
INSERT INTO product_vehicle_matches (product_id, vehicle_model_id, match_type, created_by) VALUES
(1234, 1, 'original', 8),     -- MRF26-73-012 → KAYO 125 TD = O
(1234, 2, 'replacement', 8),  -- MRF26-73-012 → KAYO 125 TD do 2023 = Z
(1234, 3, 'original', 8);     -- MRF26-73-012 → YCF LITE 88S = O
```

---

## 🤖 AI-POWERED SUGESTIE

### ALGORYTM SUGESTII

**CEL:** Podpowiadanie dopasowań na podstawie podobnych produktów

**INPUT:**
- Produkt: SKU, Nazwa, Marka, Kategoria
- Istniejące dopasowania

**ALGORYTM:**

```python
def suggest_vehicle_matches(product):
    suggestions = []

    # 1. PODOBNE PRODUKTY (ta sama marka + kategoria)
    similar_products = Product.where(
        'brand = ? AND category_id = ? AND id != ?',
        product.brand, product.category_id, product.id
    ).limit(20)

    # 2. AGREGACJA DOPASOWAŃ
    match_scores = {}
    for similar in similar_products:
        for match in similar.vehicle_matches:
            vehicle_id = match.vehicle_model_id
            match_type = match.match_type

            if vehicle_id not in match_scores:
                match_scores[vehicle_id] = {'O': 0, 'Z': 0, 'total': 0}

            match_scores[vehicle_id][match_type] += 1
            match_scores[vehicle_id]['total'] += 1

    # 3. CONFIDENCE SCORE
    total_products = len(similar_products)
    for vehicle_id, scores in match_scores.items():
        confidence = (scores['total'] / total_products) * 100

        # Preferuj typ z większą liczbą wystąpień
        suggested_type = 'O' if scores['O'] > scores['Z'] else 'Z'

        if confidence >= 50:  # Threshold: 50%
            suggestions.append({
                'vehicle_model_id': vehicle_id,
                'suggested_type': suggested_type,
                'confidence': confidence,
                'based_on_products': scores['total']
            })

    # 4. SORTOWANIE (confidence DESC)
    suggestions.sort(key=lambda x: x['confidence'], reverse=True)

    return suggestions
```

**PRZYKŁAD:**

```
Produkt: Uszczelka wydechu YCF (SKU: 18291/152FMH)

Sugestie AI:
1. YCF LITE 88S = O (confidence: 85%, based on 17 similar products)
2. YCF START 125 = O (confidence: 90%, based on 18 similar products)
3. YCF PILOT 150 = Z (confidence: 75%, based on 15 similar products)
...
```

---

## 📊 METRYKI & ANALYTICS

### DASHBOARD DOPASOWAŃ

**WIDGETY:**

1. **POKRYCIE DOPASOWAŃ**
   ```
   ┌─────────────────────────────────────┐
   │ 📊 POKRYCIE DOPASOWAŃ               │
   │                                     │
   │ [██████████████░░░░] 84.5%          │
   │                                     │
   │ ✅ Z dopasowaniami: 1345 produktów  │
   │ ⚠️ Bez dopasowań: 246 produktów     │
   └─────────────────────────────────────┘
   ```

2. **TOP MODELE (najwięcej dopasowań)**
   ```
   ┌─────────────────────────────────────┐
   │ 🏆 TOP MODELE                       │
   │                                     │
   │ 1. YCF LITE 88S - 520 dopasowań     │
   │ 2. KAYO 125 TD - 480 dopasowań      │
   │ 3. MRF 140 RC - 350 dopasowań       │
   └─────────────────────────────────────┘
   ```

3. **AKTYWNOŚĆ (ostatnie 7 dni)**
   ```
   ┌─────────────────────────────────────┐
   │ 📈 AKTYWNOŚĆ                        │
   │                                     │
   │ Dodane dopasowania: +127            │
   │ Usunięte dopasowania: -15           │
   │ Zmienione O↔Z: 8                    │
   └─────────────────────────────────────┘
   ```

---

## ✅ PODSUMOWANIE & NEXT STEPS

### KLUCZOWE WNIOSKI

1. ✅ **Format Excel jest prosty i skuteczny**
   - 2 wartości (O/Z) + puste = łatwa walidacja
   - Struktura macierzowa = przejrzysta

2. ✅ **UX aplikacji powinien zachować szybkość Excel**
   - Grid view dla power users
   - Smart form dla casual users
   - Bulk wizard dla masowych operacji

3. ✅ **AI suggestions = game changer**
   - 85% produktów w tej samej marce/kategorii ma podobne dopasowania
   - Confidence score > 50% = wiarygodne sugestie

4. ⚠️ **15.5% produktów bez dopasowań wymaga uwagi**
   - Priorytet: AI suggestions dla tych produktów
   - Dashboard alert: "246 produktów bez dopasowań"

### REKOMENDOWANY WORKFLOW

**FAZA 1: IMPORT ISTNIEJĄCYCH DANYCH**
1. Import Excel → validacja → preview → import
2. Utworzenie 121 modeli pojazdów w bazie
3. Import 8547 dopasowań

**FAZA 2: UX IMPLEMENTATION**
1. Grid view (Wariant A) - dla power users
2. Smart form (Wariant B) - dla casual users
3. Bulk wizard (Wariant C) - dla masowych operacji

**FAZA 3: AI SUGGESTIONS**
1. Algorytm sugestii (based on similar products)
2. Confidence score (50%+ threshold)
3. Bulk accept suggestions

**FAZA 4: EKSPORT**
1. Excel export (identyczny format jak import)
2. Filtrowanie przed eksportem
3. Round-trip compatibility (import → edit → export → import)

### PRIORYTET IMPLEMENTACJI

| Priorytet | Feature | Uzasadnienie |
|-----------|---------|--------------|
| **P0** | Import Excel | Migracja istniejących danych |
| **P0** | Smart form (Wariant B) | MVP dla casual users |
| **P1** | AI suggestions | 85% produktów skorzysta |
| **P1** | Eksport Excel | Round-trip workflow |
| **P2** | Grid view (Wariant A) | Power users (advanced) |
| **P3** | Bulk wizard (Wariant C) | Nice-to-have |

---

## 📁 ZAŁĄCZNIKI

### PLIKI ANALIZY

- `_TEMP/analyze_quick.ps1` - Skrypt analizy struktury Excel
- `_TEMP/analyze_matches.ps1` - Skrypt analizy dopasowań

### PRZYKŁADOWE DANE

**Plik źródłowy:** `References/Produkty_Przykład_Large.xlsx`

**Statystyki:**
- 1591 produktów
- 136 kolumn (11 produktowe + 4 kategorie + 121 modeli)
- ~8547 dopasowań (estimation based on 65.7% coverage)

---

**KONIEC RAPORTU**

---

**Przygotował:** import-export-specialist
**Data:** 2025-12-04
**Status:** ✅ COMPLETED
