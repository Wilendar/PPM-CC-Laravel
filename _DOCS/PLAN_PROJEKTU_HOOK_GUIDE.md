# Plan Projektu Sync Hook - Automatyczna aktualizacja planu

## Przegląd

Hook `plan-projektu-sync.ts` automatycznie aktualizuje `Plan_Projektu` na podstawie prac subagentów. Hook integruje się z systemem raportowania i wykorzystuje nowe pola `agent_id` i `agent_transcript_path` z Claude Code.

## Architektura

Hook składa się z modularnej architektury:

```
plan-projektu-sync.ts (main hook)
└── plan-projektu-modules/
    ├── plan-parser.ts          - Parsowanie markdown → struktura drzewa
    ├── task-matcher.ts          - Smart dopasowanie pracy do zadań
    ├── status-updater.ts        - Zmiana statusów (❌→🛠️→✅)
    ├── file-linker.ts           - Dodawanie linków do plików
    ├── hierarchy-validator.ts   - Walidacja parent/children
    ├── progress-calculator.ts   - Obliczanie % ukończenia
    ├── markdown-writer.ts       - Zapisywanie zmian do markdown
    └── readme-updater.ts        - Aktualizacja README.md
```

---

## 🎯 Funkcje hooka

### 1. **Smart Task Matching** - Inteligentne dopasowanie

Hook automatycznie dopasowuje pracę subagenta do zadań w planie używając:

**Fuzzy Matching**:
- Analiza słów kluczowych z initial prompt
- Levenshtein distance dla podobieństwa stringów
- Scoring system (0-300+ punktów)

**File Pattern Matching**:
- Dopasowanie zmodyfikowanych plików do kontekstu zadania
- Wykrywanie wzorców (ProductController → Product*, Controller*)

**Status Prioritization**:
- Zadania ❌ (nie rozpoczęte) → +50 punktów
- Zadania 🛠️ (w trakcie) → +75 punktów
- Zadania ✅ (ukończone) → -100 punktów (unikaj)

**Próg dopasowania**: Minimum 50 punktów do uznania za match

#### Przykład dopasowania:

```typescript
// Prompt: "Zaimplementuj SKU field z walidacją w ProductForm"

// Keywords ekstrahowane: ["zaimplementuj", "sku", "field", "walidacja", "productform"]

// Zadanie w planie:
"1.2.1.1.2 SKU field z validation i uniqueness check"

// Scoring:
- "sku" in ID → +100
- "field" in title (exact) → +50
- "validation" ≈ "walidacją" (fuzzy) → +15
- Status ❌ → +50
- Modified: ProductForm.php (matches "productform") → +20
// TOTAL: 235 punktów ✅ MATCH!
```

---

### 2. **Automatyczne dodawanie linków do plików**

Hook dodaje linki `└──📁 PLIK:` pod zadaniami:

```markdown
- ✅ 1.2.1.1.2 SKU field z validation i uniqueness check
  └──📁 PLIK: app/Http/Requests/StoreProductRequest.php
  └──📁 PLIK: app/Http/Livewire/Products/Management/ProductForm.php
  └── 🤖 Ukończone przez: laravel-expert (2025-11-17 14:32)
```

**Funkcje**:
- Wykrywa duplikaty (nie dodaje tego samego pliku dwa razy)
- Normalizuje ścieżki (Windows → Unix style)
- Dodaje timestamp z typem agenta

---

### 3. **Automatyczna zmiana statusów**

Hook zmienia statusy zadań na podstawie rezultatu pracy:

**Logika**:
```
Praca ukończona BEZ błędów → ✅
Praca ukończona Z błędami → ⚠️
Praca rozpoczęta (❌) → 🛠️
```

**Walidacja**:
- Sprawdza czy transition jest poprawny (❌→🛠️→✅)
- Blokuje niepoprawne zmiany (✅ nie powinno zmienić się z powrotem)

---

### 4. **Walidacja hierarchii (parent/children)**

Hook sprawdza czy parent task może być oznaczony jako ✅:

**Zasada**: Parent może być ✅ TYLKO gdy WSZYSTKIE children są ✅

```markdown
# Przykład błędu:
- ✅ 1.2.1 Basic Product Fields  ← Niepoprawne!
  - ✅ 1.2.1.1 Info Tab
  - ❌ 1.2.1.2 Description Tab  ← Nie ukończone!

# Hook raportuje:
⚠️ UWAGA: Task 1.2.1 nie może być oznaczony jako ukończony
   - 1.2.1.2 Description Tab (❌)
```

**Auto-propagacja statusu**:
```markdown
# Gdy ukończysz ostatnie podzadanie:
- ❌ 1.2.1.2.5 Template insertion  → ✅

# Hook automatycznie aktualizuje:
- ❌ 1.2.1.2 Description Tab → ✅  (wszystkie children ✅)
- 🛠️ 1.2.1 Basic Product Fields → ✅  (wszystkie children ✅)
```

---

### 5. **Przeliczanie % ukończenia ETAP**

Hook automatycznie aktualizuje postęp ETAP-u:

```markdown
**Status ETAPU:** 🛠️ **W TRAKCIE - 87% UKOŃCZONE (133/152 zadań)**
```

**Obliczenia**:
- Zlicza WSZYSTKIE zadania w ETAP
- Zlicza ile jest ✅
- Oblicza procent
- Aktualizuje emoji statusu (❌/🛠️/✅/⚠️)

---

### 6. **Aktualizacja README.md**

Hook aktualizuje `Plan_Projektu/README.md` z ogólnym postępem:

```markdown
## 📊 Postęp ogólny: 68% (1245/1830 zadań)

### Status ETAP-ów:
- ✅ ETAP_01 - Fundament (100%)
- ✅ ETAP_02 - Modele i baza (100%)
- 🛠️ ETAP_05 - Produkty (87% - 133/152)
- ❌ ETAP_06 - Import/Export (0%)
```

---

### 7. **Auto-tworzenie nowych zadań**

Gdy hook nie znajdzie dopasowania (score < 50) a praca była substancjalna:

**Warunki auto-create**:
- Brak dobrego dopasowania (score < 100)
- ≥2 pliki zmodyfikowane LUB ≥3 narzędzia użyte

**Akcja**:
```markdown
⚠️  Nie znaleziono dopasowania w planie
📝 Praca wykonana: 3 pliki zmodyfikowane

💡 Sugestia: Dodaj zadanie do planu ręcznie:
   - Rate Limiting Middleware
   └──📁 PLIK: app/Http/Middleware/RateLimitMiddleware.php
```

---

## 🚀 Jak to działa

### Przykład end-to-end:

#### 1. Uruchamiasz subagenta:
```
User: "Zaimplementuj SKU field z walidacją w ProductForm"

Claude: [Uruchamia Task tool z subagent_type="laravel-expert"]
```

#### 2. Subagent pracuje:
```
- Czyta dokumentację Laravel
- Tworzy app/Http/Requests/StoreProductRequest.php
- Modyfikuje app/Http/Livewire/Products/Management/ProductForm.php
- Dodaje walidację SKU (unique, required, max:50)
- Kończy pracę BEZ błędów
```

#### 3. Hook SubagentStop się uruchamia:
```
[plan-projektu-sync] Starting for agent: a3f8c9b2...
[plan-projektu-sync] Parsing Plan_Projektu...
[plan-projektu-sync] Finding best task match...
[plan-projektu-sync] Match found: 1.2.1.1.2 SKU field... (score: 235)
```

#### 4. Hook wykonuje aktualizacje:
```
✅ Creating backup...
✅ Updating status: ❌ → ✅
✅ Adding file links (2 files)
✅ Adding timestamp
✅ Validating hierarchy
✅ Recalculating progress: 86% → 87%
✅ Writing ETAP file
✅ Updating README.md
```

#### 5. Rezultat w pliku ETAP_05_Produkty.md:
```markdown
# Przed:
- ❌ 1.2.1.1.2 SKU field z validation i uniqueness check

# Po:
- ✅ 1.2.1.1.2 SKU field z validation i uniqueness check
  └──📁 PLIK: app/Http/Requests/StoreProductRequest.php
  └──📁 PLIK: app/Http/Livewire/Products/Management/ProductForm.php
  └── 🤖 Ukończone przez: laravel-expert (2025-11-17 14:32)

**Status ETAPU:** 🛠️ W TRAKCIE - 87% UKOŃCZONE (133/152 zadań)
```

#### 6. Komunikat w transkrypcie Claude:
```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📋 PLAN PROJEKTU - AUTOMATYCZNA AKTUALIZACJA
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

✅ Status zmieniony: ❌ → ✅
📁 Dodano linki do 2 pliku/plików

📊 Zadanie: 1.2.1.1.2 SKU field z validation i uniqueness check
📁 ETAP: ETAP_05 - Moduł Produktów
📈 Postęp ETAP: 87% (133/152)

✅ README.md zaktualizowany

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

---

## 📁 Struktura plików

```
D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\
├── Plan_Projektu/
│   ├── README.md                    ← Aktualizowany przez hook
│   ├── ETAP_01_Fundament.md
│   ├── ETAP_02_Modele_Bazy.md
│   ├── ETAP_05_Produkty.md          ← Przykładowy ETAP
│   └── ETAP_08_ERP_Integracje.md
│
├── .claude/
│   └── hooks/
│       ├── plan-projektu-sync.ts    ← Główny hook
│       └── plan-projektu-modules/   ← Moduły
│           ├── plan-parser.ts
│           ├── task-matcher.ts
│           ├── status-updater.ts
│           ├── file-linker.ts
│           ├── hierarchy-validator.ts
│           ├── progress-calculator.ts
│           ├── markdown-writer.ts
│           └── readme-updater.ts
│
└── _DOCS/
    └── PLAN_PROJEKTU_HOOK_GUIDE.md  ← Ten dokument
```

---

## ⚙️ Konfiguracja

Hook jest **automatycznie aktywny** - TypeScript hooki w `.claude/hooks/` są wykrywane przez Claude Code.

### Wymagania:
- ✅ Folder `Plan_Projektu/` w root projektu
- ✅ Pliki ETAP w formacie `ETAP_XX_Nazwa.md`
- ✅ Struktura zadań z numeracją (np. "8.1.1.1.1")
- ✅ Statusy emoji (❌, 🛠️, ✅, ⚠️)

### Opcjonalne:
- `Plan_Projektu/README.md` - dla ogólnego postępu

---

## 🔧 Troubleshooting

### Hook nie aktualizuje planu

**Problem**: Brak zmian w plikach ETAP po zakończeniu subagenta

**Rozwiązania**:
1. Sprawdź czy folder `Plan_Projektu/` istnieje
2. Sprawdź logi w konsoli: `[plan-projektu-sync]`
3. Sprawdź czy subagent zmodyfikował pliki (hook wymaga ≥1 pliku)

### Dopasowanie nie działa

**Problem**: Hook raportuje "Nie znaleziono dopasowania"

**Rozwiązania**:
1. Sprawdź czy zadanie istnieje w planie
2. Użyj bardziej opisowego initial prompt (więcej keywords)
3. Sprawdź czy zadanie ma status ❌ lub 🛠️ (nie ✅)

### Błędny status parent task

**Problem**: Parent task oznaczony jako ✅ ale children nie są ukończone

**Rozwiązania**:
1. Hook to wykryje i pokaże warning
2. Ręcznie popraw status parent task na 🛠️
3. Ukończ wszystkie children tasks

---

## 📊 Metryki i statystyki

Hook zbiera informacje o:
- Liczbie dopasowanych zadań
- Scoring dopasowań
- Liczbie zmodyfikowanych plików
- Postępie ETAP-ów
- Ogólnym postępie projektu

---

## 🔮 Przyszłe rozszerzenia

Możliwe rozbudowy hooka:

### 🔹 Blocker Detection
Automatyczne wykrywanie blokerów z błędów:
```markdown
- ⚠️ 1.2.1.1.2 SKU field (ZABLOKOWANE)
  └── BLOKER: Table 'products' missing column 'sku'
  └── WYMAGA: 1.1.3.2 Database Migrations
```

### 🔹 TODO Sync
Synchronizacja TODO z planem:
```typescript
// Auto-generuj TODO z zadań 🛠️ w planie
TodoWrite([
  { content: "1.2.1.2 Description Tab", status: "pending" },
  { content: "1.2.2 Advanced Settings", status: "in_progress" }
]);
```

### 🔹 Dependency Graph
Wykrywanie zależności między zadaniami

### 🔹 Sugestie następnych zadań
AI-powered recommendations co robić dalej

---

## 💡 Best Practices

### Dla subagentów:
1. **Opisowy initial prompt** - użyj słów kluczowych z planu
2. **Modyfikuj pliki** - hook wymaga ≥1 zmodyfikowanego pliku
3. **Finish cleanly** - unikaj błędów dla auto-✅

### Dla planu projektu:
1. **Spójna numeracja** - zachowaj format "X.Y.Z.W"
2. **Opisowe tytuły** - używaj keywords (SKU, validation, ProductForm)
3. **Statusy emoji** - zawsze używaj ❌🛠️✅⚠️
4. **Hierarchia** - grupuj podzadania pod parent tasks

---

## Historia zmian

### v1.0.0 - 2025-11-17
- ✅ Pierwsza wersja z pełną funkcjonalnością
- ✅ FAZA 1: Core (parser, progress, linker, writer)
- ✅ FAZA 2: Smart Matching (task-matcher, status-updater, hierarchy-validator)
- ✅ FAZA 3: Advanced (readme-updater)
- ✅ 1400+ linii kodu TypeScript
- ✅ Architektura modularna (8 modułów)

---

**Utworzono**: 2025-11-17
**Autor**: Claude Code + Kamil Wiliński
**Wersja**: 1.0.0
**Moduły**: 8 + główny hook
**Linii kodu**: ~1400
