# PLAN PRZEBUDOWY MENU PPM v2.0

**Agent:** architect (Planning Manager & Project Plan Keeper)
**Data:** 2025-10-22
**Zadanie:** Zaplanowanie przebudowy menu aplikacji zgodnie z dokumentacją v2.0

---

## EXECUTIVE SUMMARY

### Cel Zadania

Przebudowa struktury menu aplikacji PPM zgodnie z dokumentacją architektury v2.0, która wprowadza kluczowe zmiany organizacyjne:
- Reorganizacja kategorii (usunięcie "ZARZĄDZANIE")
- Role-based Dashboard (7 wersji per rola)
- Unified Import System (CSV + XLSX)
- Dynamic ERP Integrations

### Kluczowe Priorytety

1. **KRYTYCZNE:** Integracja Dashboard z głównym layoutem (user highlight)
2. **WYSOKIE:** Reorganizacja menu zgodnie z v2.0 (12 sekcji)
3. **ŚREDNIE:** Placeholder pages dla nieimplementowanych sekcji
4. **NISKIE:** Weryfikacja wizualna i responsive design

### Timeline Estimate

- **FAZA 1 (Menu Restructuring):** 6-8h
- **FAZA 2 (Dashboard Integration):** 4-6h
- **FAZA 3 (Placeholder Pages):** 3-4h
- **FAZA 4 (Verification):** 2-3h
- **TOTAL:** 15-21h (2-3 dni robocze)

---

## 📊 ANALIZA PORÓWNAWCZA: OBECNE vs DOCELOWE MENU

### Obecna Struktura Menu (admin.blade.php)

**12 głównych sekcji obecnie w sidebar:**

1. **Dashboard** (`/admin`) ✅ ISTNIEJE
2. **SKLEPY** (4 linki) ⚠️ REORGANIZACJA WYMAGANA
   - Lista sklepów ✅
   - Dodaj sklep ✅
   - Synchronizacja ✅
   - Eksport masowy ❌ DO USUNIĘCIA (przeniesione do Produkty)
3. **PRODUKTY** (3 linki) ⚠️ ROZSZERZENIE WYMAGANE
   - Lista produktów ✅
   - Dodaj produkt ✅
   - Kategorie ✅
   - ❌ BRAK: Import z pliku (CSV/XLSX unified)
   - ❌ BRAK: Historie importów
4. **CENNIK** (3 linki) ✅ ZGODNE Z v2.0
   - Grupy cenowe ✅
   - Ceny produktów ✅
   - Aktualizacja masowa ✅
5. **ZARZĄDZANIE** (1 link) ❌ KATEGORIA DO USUNIĘCIA
   - CSV Import/Export → przeniesione do PRODUKTY
6. **SYSTEM** (5 linków) ⚠️ ROZSZERZENIE WYMAGANE
   - Ustawienia ✅
   - Backup ✅
   - Konserwacja ✅
   - Integracje ERP ✅
   - Użytkownicy ✅
   - ❌ BRAK: Logi systemowe
   - ❌ BRAK: Monitoring
   - ❌ BRAK: API Management

**BRAKUJĄCE SEKCJE (v2.0):**
- ❌ **WARIANTY & CECHY** (0/3 podstron)
- ❌ **DOSTAWY & KONTENERY** (0/4 podstron)
- ❌ **ZAMÓWIENIA** (0/3 podstron)
- ❌ **REKLAMACJE** (0/3 podstron)
- ❌ **RAPORTY & STATYSTYKI** (0/4 podstron)
- ❌ **PROFIL UŻYTKOWNIKA** (0/4 podstron)
- ❌ **POMOC** (0/3 podstron)

### Docelowa Struktura Menu (v2.0)

**12 sekcji z 49 route'ami:**

```
┌─────────────────────────────────────────────────┐
│  🏠 DASHBOARD                                   │ [Role-Based Content]
├─────────────────────────────────────────────────┤
│  🏪 SKLEPY PRESTASHOP                           │ [Admin]
│    ├─ Lista sklepów                            │
│    ├─ Dodaj sklep                              │
│    └─ Synchronizacja                           │
├────────────────────────────────────────────────┤
│  📦 PRODUKTY                                   │ [Wszyscy]
│    ├─ Lista produktów                          │
│    ├─ Dodaj produkt                            │
│    ├─ Kategorie                                │
│    ├─ Import z pliku           [NEW]           │
│    ├─ Historie importów        [NEW]           │
│    └─ Szybka Wyszukiwarka                      │
├────────────────────────────────────────────────┤
│  💰 CENNIK                                      │ [Menadżer+]
│    ├─ Grupy cenowe                             │
│    ├─ Ceny produktów                           │
│    └─ Aktualizacja masowa                      │
├─────────────────────────────────────────────────┤
│  🎨 WARIANTY & CECHY           [NEW SECTION]   │ [Menadżer+]
│    ├─ Zarządzanie wariantami                   │
│    ├─ Cechy pojazdów                           │
│    └─ Dopasowania części                       │
├─────────────────────────────────────────────────┤
│  🚚 DOSTAWY & KONTENERY        [NEW SECTION]   │ [Magazynier+]
│    ├─ Lista dostaw                             │
│    ├─ Kontenery                                │
│    ├─ Przyjęcia magazynowe                     │
│    └─ Dokumenty odpraw                         │
├─────────────────────────────────────────────────┤
│  📋 ZAMÓWIENIA                 [NEW SECTION]   │ [Handlowiec+]
│    ├─ Lista zamówień                           │
│    ├─ Rezerwacje z kontenera                   │
│    └─ Historia zamówień                        │
├─────────────────────────────────────────────────┤
│  ⚠️ REKLAMACJE                  [NEW SECTION]   │ [Reklamacje+]
│    ├─ Lista reklamacji                         │
│    ├─ Nowa reklamacja                          │
│    └─ Archiwum                                 │
├─────────────────────────────────────────────────┤
│  📊 RAPORTY & STATYSTYKI       [NEW SECTION]   │ [Menadżer+]
│    ├─ Raporty produktowe                       │
│    ├─ Raporty finansowe                        │
│    ├─ Raporty magazynowe                       │
│    └─ Eksport raportów                         │
├─────────────────────────────────────────────────┤
│  ⚙️ SYSTEM                                      │ [Admin]
│    ├─ Ustawienia systemu                       │
│    ├─ Zarządzanie użytkownikami                │
│    ├─ Integracje ERP           [DYNAMIC LIST]  │
│    ├─ Backup & Restore                         │
│    ├─ Konserwacja bazy                         │
│    ├─ Logi systemowe           [NEW]           │
│    ├─ Monitoring               [NEW]           │
│    └─ API Management           [NEW]           │
├─────────────────────────────────────────────────┤
│  👤 PROFIL UŻYTKOWNIKA         [NEW SECTION]   │ [Wszyscy]
│    ├─ Edycja profilu                           │
│    ├─ Aktywne sesje                            │
│    ├─ Historia aktywności                      │
│    └─ Ustawienia powiadomień                   │
├─────────────────────────────────────────────────┤
│  ❓ POMOC                       [NEW SECTION]   │ [Wszyscy]
│    ├─ Dokumentacja                             │
│    ├─ Skróty klawiszowe                        │
│    └─ Wsparcie techniczne                      │
└─────────────────────────────────────────────────┘
```

---

## 🗺️ MAPA ZMIAN: SZCZEGÓŁOWE MAPOWANIE

### 1. Dashboard - KRYTYCZNA ZMIANA

**Obecny Stan:**
- Route: `/admin` → `App\Http\Livewire\Dashboard\AdminDashboard::class`
- Problem: AdminDashboard używa INNEGO layoutu niż reszta aplikacji
- Layout conflict: AdminDashboard nie używa `admin.blade.php` sidebar

**Docelowy Stan:**
- Route: `/dashboard` → Role-based dashboard controller
- Jeden unified layout dla całej aplikacji
- 7 wersji dashboard per rola (Admin, Menadżer, Redaktor, Magazynier, Handlowiec, Reklamacje, Użytkownik)

**Wymagane Zmiany:**
1. Migracja AdminDashboard do głównego layoutu `admin.blade.php`
2. Implementacja role-based content switching
3. Widget system per rola
4. Quick actions per rola

**Status ETAP:** ETAP_04 Panel Admin - COMPLETED (ale wymaga refactoringu layoutu)

---

### 2. Sklepy PrestaShop - USUNIĘCIE JEDNEGO LINKU

**Obecny Stan:**
```
🏪 SKLEPY
  ├─ Lista sklepów       (/admin/shops)
  ├─ Dodaj sklep         (/admin/shops/add)
  ├─ Synchronizacja      (/admin/shops/sync)
  └─ Eksport masowy      (/admin/shops/export)  ❌ DO USUNIĘCIA
```

**Docelowy Stan (v2.0):**
```
🏪 SKLEPY PRESTASHOP
  ├─ Lista sklepów       (/admin/shops)
  ├─ Dodaj sklep         (/admin/shops/create)
  └─ Synchronizacja      (/admin/shops/sync)
```

**Wymagane Zmiany:**
1. ❌ Usuń link "Eksport masowy" z sidebar
2. ✅ Dodaj przycisk "Eksportuj wszystko do CSV" w header Lista Produktów
3. Route pozostaje dla backward compatibility (redirect do /admin/products)

**Status ETAP:** ETAP_04 Panel Admin - COMPLETED

---

### 3. Produkty - ROZSZERZENIE O UNIFIED IMPORT

**Obecny Stan:**
```
📦 PRODUKTY
  ├─ Lista produktów     (/admin/products)
  ├─ Dodaj produkt       (/admin/products/create)
  └─ Kategorie           (/admin/products/categories)
```

**Docelowy Stan (v2.0):**
```
📦 PRODUKTY
  ├─ Lista produktów              (/admin/products)
  ├─ Dodaj produkt                (/admin/products/create)
  ├─ Kategorie                    (/admin/products/categories)
  ├─ Import z pliku      [NEW]    (/admin/products/import)
  ├─ Historie importów   [NEW]    (/admin/products/import-history)
  └─ Szybka Wyszukiwarka [NEW]    (/admin/products/search)
```

**Wymagane Zmiany:**
1. ✅ Dodaj link "Import z pliku" (unified CSV + XLSX)
2. ✅ Dodaj link "Historie importów"
3. ✅ Dodaj link "Szybka Wyszukiwarka"

**Status ETAP:**
- ETAP_05a Produkty - 77% COMPLETE
- ETAP_06 Import/Export - FAZA 6 (CSV) in progress

**Routes Status:**
- `/admin/products/import` → PLACEHOLDER (Livewire component in development)
- `/admin/products/import-history` → PLACEHOLDER
- `/admin/products/search` → PLACEHOLDER

---

### 4. Cennik - BEZ ZMIAN

**Status:** ✅ ZGODNE Z v2.0 (3/3 podstron zaimplementowanych)

```
💰 CENNIK
  ├─ Grupy cenowe            (/admin/price-management/price-groups)
  ├─ Ceny produktów          (/admin/price-management/product-prices)
  └─ Aktualizacja masowa     (/admin/price-management/bulk-updates)
```

**Status ETAP:** ETAP_04 Panel Admin FAZA 4 - COMPLETED

---

### 5. ZARZĄDZANIE - KATEGORIA DO USUNIĘCIA

**Obecny Stan:**
```
📂 ZARZĄDZANIE
  └─ CSV Import/Export   (/admin/csv/import)
```

**Docelowy Stan:** ❌ KATEGORIA USUNIĘTA (przeniesione do PRODUKTY)

**Wymagane Zmiany:**
1. ❌ Usuń całą sekcję "ZARZĄDZANIE" z sidebar
2. ✅ Funkcjonalność przeniesiona do "PRODUKTY > Import z pliku"

---

### 6. Warianty & Cechy - NOWA SEKCJA

**Docelowy Stan (v2.0):**
```
🎨 WARIANTY & CECHY    [NEW SECTION]
  ├─ Zarządzanie wariantami    (/admin/variants)
  ├─ Cechy pojazdów            (/admin/features/vehicles)
  └─ Dopasowania części        (/admin/compatibility)
```

**Status ETAP:** ETAP_05a - 77% COMPLETE (backend gotowy, UI w trakcie)

**Routes Status:**
- `/admin/variants` → PLACEHOLDER → "Ta funkcjonalność będzie dostępna w ETAP_05a, sekcja 4"
- `/admin/features/vehicles` → PLACEHOLDER → "Ta funkcjonalność będzie dostępna w ETAP_05a, sekcja 2"
- `/admin/compatibility` → PLACEHOLDER → "Ta funkcjonalność będzie dostępna w ETAP_05a, sekcja 3"

---

### 7. Dostawy & Kontenery - NOWA SEKCJA

**Docelowy Stan (v2.0):**
```
🚚 DOSTAWY & KONTENERY    [NEW SECTION]
  ├─ Lista dostaw              (/admin/deliveries)
  ├─ Kontenery                 (/admin/deliveries/containers/{id})
  ├─ Przyjęcia magazynowe      (/admin/deliveries/receiving)
  └─ Dokumenty odpraw          (/admin/deliveries/documents)
```

**Status ETAP:** ETAP_10 - ❌ NIE ROZPOCZĘTY (szacowany czas: 50h)

**Routes Status:** Wszystkie 4 routes → PLACEHOLDER
- Message: "Ta funkcjonalność będzie dostępna w ETAP_10: System Dostaw i Kontenerów"

---

### 8. Zamówienia - NOWA SEKCJA

**Docelowy Stan (v2.0):**
```
📋 ZAMÓWIENIA    [NEW SECTION]
  ├─ Lista zamówień            (/admin/orders)
  ├─ Rezerwacje z kontenera    (/admin/orders/reservations)
  └─ Historia zamówień         (/admin/orders/history)
```

**Status ETAP:** Brak dedykowanego ETAP (część przyszłych funkcjonalności)

**Routes Status:** Wszystkie 3 routes → PLACEHOLDER
- Message: "Ta funkcjonalność będzie dostępna w przyszłej wersji aplikacji"

---

### 9. Reklamacje - NOWA SEKCJA

**Docelowy Stan (v2.0):**
```
⚠️ REKLAMACJE    [NEW SECTION]
  ├─ Lista reklamacji    (/admin/claims)
  ├─ Nowa reklamacja     (/admin/claims/create)
  └─ Archiwum            (/admin/claims/archive)
```

**Status ETAP:** Brak dedykowanego ETAP (część przyszłych funkcjonalności)

**Routes Status:** Wszystkie 3 routes → PLACEHOLDER
- Message: "Ta funkcjonalność będzie dostępna w przyszłej wersji aplikacji"

---

### 10. Raporty & Statystyki - NOWA SEKCJA

**Docelowy Stan (v2.0):**
```
📊 RAPORTY & STATYSTYKI    [NEW SECTION]
  ├─ Raporty produktowe      (/admin/reports/products)
  ├─ Raporty finansowe       (/admin/reports/financial)
  ├─ Raporty magazynowe      (/admin/reports/warehouse)
  └─ Eksport raportów        (/admin/reports/export)
```

**Status ETAP:** Brak dedykowanego ETAP (część przyszłych funkcjonalności)

**Routes Status:** Wszystkie 4 routes → PLACEHOLDER
- Message: "Ta funkcjonalność będzie dostępna w przyszłej wersji aplikacji"

---

### 11. System - ROZSZERZENIE

**Obecny Stan:**
```
⚙️ SYSTEM
  ├─ Ustawienia          (/admin/system-settings)
  ├─ Backup              (/admin/backup)
  ├─ Konserwacja         (/admin/maintenance)
  ├─ Integracje ERP      (/admin/integrations)
  └─ Użytkownicy         (/admin/users)
```

**Docelowy Stan (v2.0):**
```
⚙️ SYSTEM
  ├─ Ustawienia systemu            (/admin/system-settings)
  ├─ Zarządzanie użytkownikami     (/admin/users)
  ├─ Integracje ERP    [DYNAMIC]   (/admin/integrations)
  ├─ Backup & Restore              (/admin/backup)
  ├─ Konserwacja bazy              (/admin/maintenance)
  ├─ Logi systemowe     [NEW]      (/admin/logs)
  ├─ Monitoring         [NEW]      (/admin/monitoring)
  └─ API Management     [NEW]      (/admin/api)
```

**Wymagane Zmiany:**
1. ✅ Dodaj link "Logi systemowe" → PLACEHOLDER
2. ✅ Dodaj link "Monitoring" → PLACEHOLDER
3. ✅ Dodaj link "API Management" → PLACEHOLDER

**Status ETAP:** ETAP_04 Panel Admin FAZA C - COMPLETED (3 nowe routes jako placeholders)

---

### 12. Profil Użytkownika - NOWA SEKCJA

**Docelowy Stan (v2.0):**
```
👤 PROFIL UŻYTKOWNIKA    [NEW SECTION]
  ├─ Edycja profilu              (/profile/edit)
  ├─ Aktywne sesje               (/profile/sessions)
  ├─ Historia aktywności         (/profile/activity)
  └─ Ustawienia powiadomień      (/profile/notifications)
```

**Status ETAP:** Częściowo w ETAP_03 Autoryzacja (profil basic)

**Routes Status:**
- `/profile/edit` → ✅ ISTNIEJE (basic implementation)
- `/profile/sessions` → ✅ ISTNIEJE (basic implementation)
- `/profile/activity` → PLACEHOLDER
- `/profile/notifications` → PLACEHOLDER

---

### 13. Pomoc - NOWA SEKCJA

**Docelowy Stan (v2.0):**
```
❓ POMOC    [NEW SECTION]
  ├─ Dokumentacja              (/help)
  ├─ Skróty klawiszowe         (/help/shortcuts)
  └─ Wsparcie techniczne       (/help/support)
```

**Status ETAP:** Brak dedykowanego ETAP (część przyszłych funkcjonalności)

**Routes Status:**
- `/help` → ✅ ISTNIEJE (basic implementation)
- `/help/shortcuts` → ✅ ISTNIEJE (basic implementation)
- `/help/support` → PLACEHOLDER

---

## 📋 MAPOWANIE ETAP → SEKCJE MENU (dla placeholder messages)

### Tabela Mapowania

| Sekcja Menu | Route | ETAP | Status | Placeholder Message |
|-------------|-------|------|--------|---------------------|
| Dashboard | `/dashboard` | ETAP_04 | ✅ COMPLETED | (refactoring layoutu wymagany) |
| Sklepy > Lista | `/admin/shops` | ETAP_04 | ✅ COMPLETED | - |
| Sklepy > Dodaj | `/admin/shops/create` | ETAP_04 | ✅ COMPLETED | - |
| Sklepy > Sync | `/admin/shops/sync` | ETAP_04 | ✅ COMPLETED | - |
| Produkty > Lista | `/admin/products` | ETAP_05 | ✅ COMPLETED | - |
| Produkty > Dodaj | `/admin/products/create` | ETAP_05 | ✅ COMPLETED | - |
| Produkty > Kategorie | `/admin/products/categories` | ETAP_05 | ✅ COMPLETED | - |
| Produkty > Import | `/admin/products/import` | ETAP_06 | 🛠️ IN PROGRESS | "Import CSV/XLSX będzie dostępny w ETAP_06 (95% ukończone)" |
| Produkty > Historie | `/admin/products/import-history` | ETAP_06 | 🛠️ IN PROGRESS | "Historia importów będzie dostępna w ETAP_06" |
| Produkty > Wyszukiwarka | `/admin/products/search` | ETAP_09 | ❌ NOT STARTED | "Inteligentna wyszukiwarka będzie dostępna w ETAP_09" |
| Cennik > Grupy | `/admin/price-management/price-groups` | ETAP_04 | ✅ COMPLETED | - |
| Cennik > Ceny | `/admin/price-management/product-prices` | ETAP_04 | ✅ COMPLETED | - |
| Cennik > Bulk | `/admin/price-management/bulk-updates` | ETAP_04 | ✅ COMPLETED | - |
| Warianty > Zarządzanie | `/admin/variants` | ETAP_05a | 🛠️ 77% | "Warianty produktów - ETAP_05a sekcja 4 (77% ukończone)" |
| Warianty > Cechy | `/admin/features/vehicles` | ETAP_05a | 🛠️ 77% | "Cechy pojazdów - ETAP_05a sekcja 2 (77% ukończone)" |
| Warianty > Dopasowania | `/admin/compatibility` | ETAP_05a | 🛠️ 77% | "Dopasowania części - ETAP_05a sekcja 3 (77% ukończone)" |
| Dostawy > Lista | `/admin/deliveries` | ETAP_10 | ❌ NOT STARTED | "System dostaw będzie dostępny w ETAP_10" |
| Dostawy > Kontenery | `/admin/deliveries/containers/{id}` | ETAP_10 | ❌ NOT STARTED | "Zarządzanie kontenerami - ETAP_10" |
| Dostawy > Przyjęcia | `/admin/deliveries/receiving` | ETAP_10 | ❌ NOT STARTED | "Przyjęcia magazynowe - ETAP_10" |
| Dostawy > Dokumenty | `/admin/deliveries/documents` | ETAP_10 | ❌ NOT STARTED | "Dokumenty odpraw - ETAP_10" |
| Zamówienia > Lista | `/admin/orders` | Future | ❌ PLANNED | "Lista zamówień będzie dostępna w przyszłej wersji" |
| Zamówienia > Rezerwacje | `/admin/orders/reservations` | Future | ❌ PLANNED | "Rezerwacje z kontenera - przyszła wersja" |
| Zamówienia > Historia | `/admin/orders/history` | Future | ❌ PLANNED | "Historia zamówień - przyszła wersja" |
| Reklamacje > Lista | `/admin/claims` | Future | ❌ PLANNED | "System reklamacji będzie dostępny w przyszłej wersji" |
| Reklamacje > Nowa | `/admin/claims/create` | Future | ❌ PLANNED | "Nowa reklamacja - przyszła wersja" |
| Reklamacje > Archiwum | `/admin/claims/archive` | Future | ❌ PLANNED | "Archiwum reklamacji - przyszła wersja" |
| Raporty > Produktowe | `/admin/reports/products` | Future | ❌ PLANNED | "Raporty produktowe - przyszła wersja" |
| Raporty > Finansowe | `/admin/reports/financial` | Future | ❌ PLANNED | "Raporty finansowe - przyszła wersja" |
| Raporty > Magazynowe | `/admin/reports/warehouse` | Future | ❌ PLANNED | "Raporty magazynowe - przyszła wersja" |
| Raporty > Eksport | `/admin/reports/export` | Future | ❌ PLANNED | "Eksport raportów - przyszła wersja" |
| System > Logi | `/admin/logs` | Future | ❌ PLANNED | "Logi systemowe - przyszła wersja" |
| System > Monitoring | `/admin/monitoring` | Future | ❌ PLANNED | "Monitoring systemu - przyszła wersja" |
| System > API | `/admin/api` | Future | ❌ PLANNED | "API Management - przyszła wersja" |
| Profil > Aktywność | `/profile/activity` | Future | ❌ PLANNED | "Historia aktywności - przyszła wersja" |
| Profil > Powiadomienia | `/profile/notifications` | Future | ❌ PLANNED | "Ustawienia powiadomień - przyszła wersja" |
| Pomoc > Wsparcie | `/help/support` | Future | ❌ PLANNED | "Wsparcie techniczne - przyszła wersja" |

---

## 🎯 PLAN IMPLEMENTACJI - 4 FAZY

### FAZA 1: Menu Restructuring (6-8h)

**Agent:** frontend-specialist

**Zadania:**

1. **Usunięcie przestarzałych elementów (1-2h)**
   - ❌ Usuń sekcję "ZARZĄDZANIE" (całą)
   - ❌ Usuń link "Sklepy > Eksport masowy"
   - Update: `resources/views/layouts/admin.blade.php` (linie 352-373)

2. **Reorganizacja sekcji PRODUKTY (1-2h)**
   - ✅ Dodaj separator/header "Zarządzanie danymi"
   - ✅ Dodaj link "Import z pliku" (ikona: file-import)
   - ✅ Dodaj link "Historie importów" (ikona: history)
   - ✅ Dodaj link "Szybka Wyszukiwarka" (ikona: search)
   - Routing: placeholder routes (FAZA 3)

3. **Dodanie nowych sekcji menu (2-3h)**
   - ✅ Sekcja "WARIANTY & CECHY" (3 linki)
   - ✅ Sekcja "DOSTAWY & KONTENERY" (4 linki)
   - ✅ Sekcja "ZAMÓWIENIA" (3 linki)
   - ✅ Sekcja "REKLAMACJE" (3 linki)
   - ✅ Sekcja "RAPORTY & STATYSTYKI" (4 linki)
   - Pattern: Clone z istniejących sekcji (Produkty, Cennik)

4. **Rozszerzenie sekcji SYSTEM (1h)**
   - ✅ Dodaj link "Logi systemowe" (ikona: file-text)
   - ✅ Dodaj link "Monitoring" (ikona: activity)
   - ✅ Dodaj link "API Management" (ikona: code)

5. **Dodanie sekcji PROFIL & POMOC (1h)**
   - ✅ Sekcja "PROFIL UŻYTKOWNIKA" (4 linki)
   - ✅ Sekcja "POMOC" (3 linki)
   - Note: Niektóre routes już istnieją (profil.edit, help.index)

**Deliverables:**
- ✅ Plik `resources/views/layouts/admin.blade.php` (updated sidebar)
- ✅ 12 sekcji zgodnych z v2.0
- ✅ 49 linków menu (część placeholder)

**Wymagania techniczne:**
- Zachować istniejący Alpine.js pattern (x-data, x-show, x-transition)
- Użyć spójnych ikon Font Awesome
- Collapsible sections (expand/collapse per sekcja)
- Active state highlighting (request()->is() pattern)
- Sidebar collapse support (istniejąca funkcjonalność)

---

### FAZA 2: Dashboard Integration (4-6h)

**Agent:** livewire-specialist

**Problem:** Dashboard używa INNEGO layoutu niż reszta aplikacji

**Zadania:**

1. **Analiza obecnego AdminDashboard (1h)**
   - Przeczytaj `app/Http/Livewire/Dashboard/AdminDashboard.php`
   - Zidentyfikuj layout dependencies
   - Sprawdź czy AdminDashboard używa custom layout

2. **Migracja do unified layout (2-3h)**
   - Przepisz AdminDashboard aby używało `admin.blade.php`
   - Usunięcie custom layout (jeśli istnieje)
   - Test rendering w sidebar context

3. **Role-based content switching (1-2h)**
   - Dodaj logic do wykrywania roli użytkownika
   - Conditional rendering widgetów per rola
   - Quick actions per rola (Admin, Menadżer, Redaktor, etc.)

**Deliverables:**
- ✅ AdminDashboard zintegrowany z `admin.blade.php`
- ✅ Role-based dashboard content
- ✅ Unified layout dla całej aplikacji

**Wymagania techniczne:**
- Zachować istniejące widgety AdminDashboard
- Użyć Livewire properties dla role detection
- Alpine.js dla conditional rendering widgetów
- CSS: Użyć istniejących klas `enterprise-card`, grid layout

---

### FAZA 3: Placeholder Pages (3-4h)

**Agent:** laravel-expert

**Zadania:**

1. **Stworzenie placeholder Blade component (1h)**

   Plik: `resources/views/components/placeholder-page.blade.php`

   ```blade
   <div class="min-h-screen flex items-center justify-center p-8">
       <div class="enterprise-card max-w-2xl w-full text-center">
           <div class="mb-6">
               <svg class="w-24 h-24 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
               </svg>
           </div>
           <h1 class="text-h1 mb-4">{{ $title }}</h1>
           <p class="text-body mb-6">{{ $message }}</p>

           @if($etap)
               <div class="inline-flex items-center px-4 py-2 rounded-lg bg-orange-500/10 border border-orange-500/30">
                   <svg class="w-5 h-5 mr-2 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                   </svg>
                   <span class="text-sm font-medium text-orange-300">{{ $etap }}</span>
               </div>
           @endif

           <div class="mt-8 pt-8 border-t border-gray-700">
               <a href="/admin" class="btn-enterprise-secondary">
                   <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                   </svg>
                   Powrót do Dashboard
               </a>
           </div>
       </div>
   </div>
   ```

2. **Dodanie routes dla placeholder pages (2-3h)**

   Plik: `routes/web.php` (w grupie `admin`)

   **Warianty & Cechy (ETAP_05a):**
   ```php
   Route::get('/variants', function () {
       return view('components.placeholder-page', [
           'title' => 'Zarządzanie Wariantami',
           'message' => 'System zarządzania wariantami produktów jest w trakcie implementacji.',
           'etap' => 'ETAP_05a sekcja 4 - 77% ukończone'
       ]);
   })->name('variants.index');

   Route::get('/features/vehicles', function () {
       return view('components.placeholder-page', [
           'title' => 'Cechy Pojazdów',
           'message' => 'System cech pojazdów i templates jest w trakcie implementacji.',
           'etap' => 'ETAP_05a sekcja 2 - 77% ukończone'
       ]);
   })->name('features.vehicles');

   Route::get('/compatibility', function () {
       return view('components.placeholder-page', [
           'title' => 'Dopasowania Części',
           'message' => 'System dopasowań części zamiennych do pojazdów jest w trakcie implementacji.',
           'etap' => 'ETAP_05a sekcja 3 - 77% ukończone'
       ]);
   })->name('compatibility.index');
   ```

   **Produkty - Import (ETAP_06):**
   ```php
   Route::get('/products/import', function () {
       return view('components.placeholder-page', [
           'title' => 'Import z Pliku',
           'message' => 'Unified import system (CSV + XLSX) jest prawie gotowy.',
           'etap' => 'ETAP_06 - 95% ukończone'
       ]);
   })->name('products.import');

   Route::get('/products/import-history', function () {
       return view('components.placeholder-page', [
           'title' => 'Historie Importów',
           'message' => 'Historia importów CSV/XLSX będzie dostępna wkrótce.',
           'etap' => 'ETAP_06'
       ]);
   })->name('products.import.history');
   ```

   **Produkty - Wyszukiwarka (ETAP_09):**
   ```php
   Route::get('/products/search', function () {
       return view('components.placeholder-page', [
           'title' => 'Szybka Wyszukiwarka',
           'message' => 'Inteligentna wyszukiwarka z autosugestiami będzie dostępna w ETAP_09.',
           'etap' => 'ETAP_09 - System Wyszukiwania'
       ]);
   })->name('products.search');
   ```

   **Dostawy & Kontenery (ETAP_10) - 4 routes:**
   ```php
   Route::prefix('deliveries')->name('deliveries.')->group(function () {
       Route::get('/', function () {
           return view('components.placeholder-page', [
               'title' => 'Lista Dostaw',
               'message' => 'System zarządzania dostawami będzie dostępny w ETAP_10.',
               'etap' => 'ETAP_10 - System Dostaw i Kontenerów'
           ]);
       })->name('index');

       Route::get('/containers/{id}', function () {
           return view('components.placeholder-page', [
               'title' => 'Szczegóły Kontenera',
               'message' => 'Zarządzanie kontenerami będzie dostępne w ETAP_10.',
               'etap' => 'ETAP_10'
           ]);
       })->name('container');

       Route::get('/receiving', function () {
           return view('components.placeholder-page', [
               'title' => 'Przyjęcia Magazynowe',
               'message' => 'System przyjęć magazynowych będzie dostępny w ETAP_10.',
               'etap' => 'ETAP_10'
           ]);
       })->name('receiving');

       Route::get('/documents', function () {
           return view('components.placeholder-page', [
               'title' => 'Dokumenty Odpraw',
               'message' => 'System zarządzania dokumentami odpraw będzie dostępny w ETAP_10.',
               'etap' => 'ETAP_10'
           ]);
       })->name('documents');
   });
   ```

   **Zamówienia (Future) - 3 routes:**
   ```php
   Route::prefix('orders')->name('orders.')->group(function () {
       Route::get('/', function () {
           return view('components.placeholder-page', [
               'title' => 'Lista Zamówień',
               'message' => 'System zamówień będzie dostępny w przyszłej wersji aplikacji.',
               'etap' => null
           ]);
       })->name('index');

       Route::get('/reservations', function () {
           return view('components.placeholder-page', [
               'title' => 'Rezerwacje z Kontenera',
               'message' => 'System rezerwacji towarów z kontenera będzie dostępny wkrótce.',
               'etap' => null
           ]);
       })->name('reservations');

       Route::get('/history', function () {
           return view('components.placeholder-page', [
               'title' => 'Historia Zamówień',
               'message' => 'Historia zamówień będzie dostępna w przyszłej wersji.',
               'etap' => null
           ]);
       })->name('history');
   });
   ```

   **Reklamacje (Future) - 3 routes:**
   ```php
   Route::prefix('claims')->name('claims.')->group(function () {
       Route::get('/', function () {
           return view('components.placeholder-page', [
               'title' => 'Lista Reklamacji',
               'message' => 'System reklamacji będzie dostępny w przyszłej wersji aplikacji.',
               'etap' => null
           ]);
       })->name('index');

       Route::get('/create', function () {
           return view('components.placeholder-page', [
               'title' => 'Nowa Reklamacja',
               'message' => 'Formularz nowej reklamacji będzie dostępny wkrótce.',
               'etap' => null
           ]);
       })->name('create');

       Route::get('/archive', function () {
           return view('components.placeholder-page', [
               'title' => 'Archiwum Reklamacji',
               'message' => 'Archiwum reklamacji będzie dostępne w przyszłej wersji.',
               'etap' => null
           ]);
       })->name('archive');
   });
   ```

   **Raporty & Statystyki (Future) - 4 routes:**
   ```php
   Route::prefix('reports')->name('reports.')->group(function () {
       Route::get('/products', function () {
           return view('components.placeholder-page', [
               'title' => 'Raporty Produktowe',
               'message' => 'Business Intelligence: raporty produktowe będą dostępne wkrótce.',
               'etap' => null
           ]);
       })->name('products');

       Route::get('/financial', function () {
           return view('components.placeholder-page', [
               'title' => 'Raporty Finansowe',
               'message' => 'Raporty finansowe będą dostępne w przyszłej wersji.',
               'etap' => null
           ]);
       })->name('financial');

       Route::get('/warehouse', function () {
           return view('components.placeholder-page', [
               'title' => 'Raporty Magazynowe',
               'message' => 'Raporty magazynowe będą dostępne w przyszłej wersji.',
               'etap' => null
           ]);
       })->name('warehouse');

       Route::get('/export', function () {
           return view('components.placeholder-page', [
               'title' => 'Eksport Raportów',
               'message' => 'System eksportu raportów będzie dostępny wkrótce.',
               'etap' => null
           ]);
       })->name('export');
   });
   ```

   **System - nowe routes (Future) - 3 routes:**
   ```php
   Route::get('/logs', function () {
       return view('components.placeholder-page', [
           'title' => 'Logi Systemowe',
           'message' => 'Przeglądarka logów systemowych będzie dostępna w przyszłej wersji.',
           'etap' => null
       ]);
   })->name('logs.index');

   Route::get('/monitoring', function () {
       return view('components.placeholder-page', [
           'title' => 'Monitoring Systemu',
           'message' => 'Dashboard monitoringu systemu będzie dostępny wkrótce.',
           'etap' => null
       ]);
   })->name('monitoring.index');

   Route::get('/api', function () {
       return view('components.placeholder-page', [
           'title' => 'API Management',
           'message' => 'Panel zarządzania API będzie dostępny w przyszłej wersji.',
           'etap' => null
       ]);
   })->name('api.index');
   ```

   **Profil Użytkownika - brakujące routes (Future) - 2 routes:**
   ```php
   // W grupie middleware(['auth'])
   Route::get('/profile/activity', function () {
       return view('components.placeholder-page', [
           'title' => 'Historia Aktywności',
           'message' => 'Historia aktywności użytkownika będzie dostępna wkrótce.',
           'etap' => null
       ]);
   })->name('profile.activity');

   Route::get('/profile/notifications', function () {
       return view('components.placeholder-page', [
           'title' => 'Ustawienia Powiadomień',
           'message' => 'Panel ustawień powiadomień będzie dostępny w przyszłej wersji.',
           'etap' => null
       ]);
   })->name('profile.notifications');
   ```

   **Pomoc - brakujące route (Future) - 1 route:**
   ```php
   Route::get('/help/support', function () {
       return view('components.placeholder-page', [
           'title' => 'Wsparcie Techniczne',
           'message' => 'System zgłoszeń wsparcia technicznego będzie dostępny wkrótce.',
           'etap' => null
       ]);
   })->name('help.support');
   ```

**Deliverables:**
- ✅ Placeholder Blade component (`placeholder-page.blade.php`)
- ✅ 26 placeholder routes dodanych do `routes/web.php`
- ✅ Każdy placeholder z odpowiednim komunikatem i odnośnikiem do ETAP

**Wymagania techniczne:**
- Użyć Blade component pattern (clean, reusable)
- Spójny design z istniejącymi stronami (`enterprise-card`)
- Responsive design (mobile-first)
- Przycisk "Powrót do Dashboard" na każdej placeholder page

---

### FAZA 4: Verification & Testing (2-3h)

**Agent:** frontend-specialist (+ deployment-specialist)

**Zadania:**

1. **Local testing (1h)**
   - Test wszystkich 49 linków menu
   - Verify active state highlighting
   - Test sidebar collapse/expand
   - Test responsive menu (mobile/tablet/desktop)

2. **Deployment (1h)**
   - Build assets: `npm run build`
   - Upload `admin.blade.php` via SSH
   - Upload `placeholder-page.blade.php` via SSH
   - Upload `routes/web.php` via SSH
   - Clear cache: `php artisan view:clear && php artisan cache:clear`

3. **Production verification (30-60min)**
   - Screenshot verification (użyj `/analizuj_strone` lub `screenshot_page.cjs`)
   - Test każdej placeholder page na ppm.mpptrade.pl
   - Verify menu działa poprawnie w sidebar
   - Test Dashboard integration (czy używa unified layout)

**Deliverables:**
- ✅ Screenshot raport (`_TOOLS/screenshots/`)
- ✅ Production verification checklist (wszystkie routes działają)
- ✅ Menu v2.0 fully deployed

**Wymagania techniczne:**
- Użyć `frontend-verification` skill (mandatory dla UI changes)
- Screenshot full page + viewport dla każdej nowej sekcji
- Weryfikacja CSS loading (szczególnie sidebar styles)
- Test na różnych rozdzielczościach (1920x1080, 1366x768, 768x1024)

---

## 📝 DELEGACJE AGENTÓW - KONKRETNE PROMPTY

### Delegacja 1: frontend-specialist (FAZA 1)

```
# ZADANIE: Menu Restructuring zgodnie z v2.0

**Kontekst:** Przebudowa menu sidebar w `admin.blade.php` zgodnie z dokumentacją architektury v2.0.

**Zadania:**

1. **Usuń przestarzałe elementy:**
   - Sekcję "ZARZĄDZANIE" (linie ~352-373 w `admin.blade.php`)
   - Link "Eksport masowy" w sekcji SKLEPY

2. **Rozszerz sekcję PRODUKTY:**
   - Dodaj link "Import z pliku" (route: admin.products.import)
   - Dodaj link "Historie importów" (route: admin.products.import.history)
   - Dodaj link "Szybka Wyszukiwarka" (route: admin.products.search)
   - Ikony: file-import, history, search

3. **Dodaj 5 nowych sekcji menu:**
   - WARIANTY & CECHY (3 linki): /admin/variants, /admin/features/vehicles, /admin/compatibility
   - DOSTAWY & KONTENERY (4 linki): /admin/deliveries, /admin/deliveries/containers/{id}, /admin/deliveries/receiving, /admin/deliveries/documents
   - ZAMÓWIENIA (3 linki): /admin/orders, /admin/orders/reservations, /admin/orders/history
   - REKLAMACJE (3 linki): /admin/claims, /admin/claims/create, /admin/claims/archive
   - RAPORTY & STATYSTYKI (4 linki): /admin/reports/products, /admin/reports/financial, /admin/reports/warehouse, /admin/reports/export

4. **Rozszerz sekcję SYSTEM:**
   - Dodaj link "Logi systemowe" (route: admin.logs.index)
   - Dodaj link "Monitoring" (route: admin.monitoring.index)
   - Dodaj link "API Management" (route: admin.api.index)

5. **Dodaj 2 nowe sekcje:**
   - PROFIL UŻYTKOWNIKA (4 linki): /profile/edit, /profile/sessions, /profile/activity, /profile/notifications
   - POMOC (3 linki): /help, /help/shortcuts, /help/support

**Plik do edycji:**
- `resources/views/layouts/admin.blade.php`

**Pattern do użycia:** Clone istniejących sekcji (Produkty, Cennik) - zachowaj Alpine.js pattern (x-data, x-show, x-collapse).

**Wymagania:**
- Zachować spójny design (Font Awesome icons, spacing, colors)
- Active state highlighting: `{{ request()->is('admin/path*') ? 'bg-gray-700 text-white' : '' }}`
- Collapsible sections support
- Sidebar collapse compatibility

**Referencja:** `_AGENT_REPORTS/architect_menu_v2_plan_2025-10-22.md` - FAZA 1

**Timeline:** 6-8h
```

---

### Delegacja 2: livewire-specialist (FAZA 2)

```
# ZADANIE: Dashboard Integration - Unified Layout

**Kontekst:** Dashboard (`/admin`) obecnie używa INNEGO layoutu niż reszta aplikacji. User highlight: "Dashboard powinien być w tym samym układzie co reszta aplikacji"

**Problem:**
- AdminDashboard component używa custom layout lub nie używa `admin.blade.php` sidebar
- Brak unified experience (menu/layout różni się na Dashboard vs inne strony)

**Zadania:**

1. **Analiza obecnego stanu (1h):**
   - Przeczytaj `app/Http/Livewire/Dashboard/AdminDashboard.php`
   - Zidentyfikuj layout dependencies (czy używa custom layout?)
   - Sprawdź jak renderuje się Dashboard obecnie

2. **Migracja do unified layout (2-3h):**
   - Przepisz AdminDashboard aby używało `admin.blade.php` (główny layout aplikacji)
   - Usunięcie custom layout (jeśli istnieje)
   - Test: Dashboard powinien mieć sidebar menu z admin.blade.php

3. **Role-based content (1-2h):**
   - Dodaj logikę wykrywania roli użytkownika (auth()->user()->role)
   - Conditional rendering widgetów per rola (Admin, Menadżer, Redaktor, Magazynier, etc.)
   - Quick actions buttons per rola (np. Admin: "Dodaj sklep", Menadżer: "Dodaj produkt")

**Pliki do edycji:**
- `app/Http/Livewire/Dashboard/AdminDashboard.php`
- `resources/views/livewire/dashboard/admin-dashboard.blade.php`

**Wymagania:**
- Dashboard MUSI używać `admin.blade.php` layout (sidebar visible)
- Zachować istniejące widgety (KPI, charts, etc.)
- Alpine.js dla conditional rendering widgetów
- CSS: użyć `enterprise-card`, grid layout

**Referencja:**
- `_AGENT_REPORTS/architect_menu_v2_plan_2025-10-22.md` - FAZA 2
- `_DOCS/ARCHITEKTURA_PPM/05_DASHBOARD.md` - role-based dashboard design

**Timeline:** 4-6h
```

---

### Delegacja 3: laravel-expert (FAZA 3)

```
# ZADANIE: Placeholder Pages dla nieimplementowanych sekcji

**Kontekst:** Nowe menu v2.0 zawiera 26 routes które nie mają jeszcze implementacji. Musimy stworzyć placeholder pages z informacją o ETAP-ie i statusie.

**Zadania:**

1. **Stwórz Blade component (1h):**
   - Plik: `resources/views/components/placeholder-page.blade.php`
   - Props: `title`, `message`, `etap` (nullable)
   - Design: Centered card, ikona construction, przycisk "Powrót do Dashboard"

2. **Dodaj 26 placeholder routes (2-3h):**
   - Warianty & Cechy (3): /admin/variants, /admin/features/vehicles, /admin/compatibility
   - Produkty (3): /admin/products/import, /admin/products/import-history, /admin/products/search
   - Dostawy (4): /admin/deliveries, /admin/deliveries/containers/{id}, /admin/deliveries/receiving, /admin/deliveries/documents
   - Zamówienia (3): /admin/orders, /admin/orders/reservations, /admin/orders/history
   - Reklamacje (3): /admin/claims, /admin/claims/create, /admin/claims/archive
   - Raporty (4): /admin/reports/products, /admin/reports/financial, /admin/reports/warehouse, /admin/reports/export
   - System (3): /admin/logs, /admin/monitoring, /admin/api
   - Profil (2): /profile/activity, /profile/notifications
   - Pomoc (1): /help/support

**Placeholder Messages - Mapowanie do ETAP:**
- ETAP_05a (77% complete): "System wariantów jest w trakcie implementacji - ETAP_05a sekcja X (77% ukończone)"
- ETAP_06 (95% complete): "Import CSV/XLSX jest prawie gotowy - ETAP_06 (95% ukończone)"
- ETAP_09 (not started): "Inteligentna wyszukiwarka będzie dostępna w ETAP_09"
- ETAP_10 (not started): "System dostaw będzie dostępny w ETAP_10"
- Future (planned): "Ta funkcjonalność będzie dostępna w przyszłej wersji aplikacji"

**Pliki do edycji:**
- `resources/views/components/placeholder-page.blade.php` (CREATE)
- `routes/web.php` (UPDATE - dodaj 26 routes w grupie `admin`)

**Pattern routes:**
```php
Route::get('/path', function () {
    return view('components.placeholder-page', [
        'title' => 'Tytuł Strony',
        'message' => 'Opis funkcjonalności',
        'etap' => 'ETAP_XX sekcja Y' // lub null dla Future
    ]);
})->name('route.name');
```

**Wymagania:**
- Blade component spójny z design system (enterprise-card, btn-enterprise-secondary)
- Każdy placeholder z odpowiednim komunikatem
- Responsive design (mobile-first)
- Przycisk "Powrót do Dashboard" na każdej stronie

**Referencja:**
- `_AGENT_REPORTS/architect_menu_v2_plan_2025-10-22.md` - FAZA 3 (szczegółowy listing routes)
- `_DOCS/ARCHITEKTURA_PPM/03_ROUTING_TABLE.md` - routing patterns

**Timeline:** 3-4h
```

---

## ⚠️ RISK ANALYSIS & MITIGATION

### Ryzyka

1. **Dashboard Layout Conflict (WYSOKIE RYZYKO)**
   - **Problem:** AdminDashboard może mieć głębokie dependencies na custom layout
   - **Mitigation:** livewire-specialist powinien NAJPIERW przeanalizować (1h) przed przepisywaniem
   - **Fallback:** Jeśli refactoring >6h, pozostaw Dashboard z custom layout i zaktualizuj tylko menu

2. **Route Naming Conflicts (ŚREDNIE RYZYKO)**
   - **Problem:** Niektóre placeholder routes mogą kolidować z istniejącymi
   - **Mitigation:** laravel-expert MUSI sprawdzić `routes/web.php` przed dodaniem (grep dla route names)
   - **Fallback:** Użyj prefiksów `placeholder.` jeśli konflikt

3. **CSS Not Loading for New Sections (NISKIE RYZYKO)**
   - **Problem:** Nowe sekcje menu mogą mieć złe style (spacing, icons)
   - **Mitigation:** frontend-specialist MUSI użyć frontend-verification skill po deployment
   - **Fallback:** Hotfix CSS jeśli potrzeba (inline styles jako temporary fix)

4. **Menu Overflow on Small Screens (NISKIE RYZYKO)**
   - **Problem:** 12 sekcji menu może nie zmieścić się na małych ekranach
   - **Mitigation:** Test responsive w FAZA 4 (tablet 768x1024, mobile 375x667)
   - **Fallback:** Sidebar scroll (overflow-y-auto) - już zaimplementowane w admin.blade.php

### Zależności Blokujące

1. **FAZA 2 depends on FAZA 1:** Dashboard integration wymaga nowego menu (sidebar musi istnieć)
2. **FAZA 3 independent:** Placeholder routes mogą być dodane równolegle z FAZA 1-2
3. **FAZA 4 depends on ALL:** Verification wymaga wszystkich zmian deployed

### Punkty Kontrolne (Checkpoints)

1. **Po FAZA 1:** Zrzut ekranu nowego menu (wszystkie 12 sekcji widoczne)
2. **Po FAZA 2:** Test Dashboard z sidebar menu (unified layout)
3. **Po FAZA 3:** Test 3 losowych placeholder pages (message + ETAP visible)
4. **Po FAZA 4:** Full production verification (49 routes, wszystkie działają)

---

## 📊 EXPECTED OUTCOMES

### Immediate Results

1. **Menu zgodne z v2.0:**
   - 12 głównych sekcji
   - 49 podstron (linków menu)
   - Usunięte przestarzałe kategorie (ZARZĄDZANIE)

2. **Dashboard Integration:**
   - Unified layout dla całej aplikacji
   - Role-based content (7 wersji per rola)
   - Consistent user experience

3. **Placeholder Pages:**
   - 26 placeholder routes z informacją o statusie ETAP
   - User wie kiedy funkcjonalność będzie dostępna
   - Professional UX (nie "404 Not Found")

### Long-term Benefits

1. **Improved Information Architecture:**
   - Logiczna organizacja funkcjonalności
   - Import/Export w kontekście Produktów (nie jako osobna kategoria)
   - Integracje ERP w kontekście System (dynamiczna lista)

2. **Scalability:**
   - Łatwe dodawanie nowych sekcji menu
   - Plugin-based ERP integrations (future)
   - Role-based dashboard rozszerzalny

3. **Better User Experience:**
   - Consistent menu w całej aplikacji
   - Przejrzysty roadmap (placeholder messages z ETAP)
   - Mobile-friendly menu (collapsible sections)

---

## 📋 POST-IMPLEMENTATION CHECKLIST

### Architect (After Delegation)

- [ ] Zaktualizuj `Plan_Projektu/ETAP_05a_Produkty.md` z info o menu v2.0
- [ ] Zaktualizuj `Plan_Projektu/README.md` z postępem menu restructuring
- [ ] Stwórz task tracking dla każdego agenta (TodoWrite)
- [ ] Monitor progress reports w `_AGENT_REPORTS/`

### Frontend-specialist (After FAZA 1)

- [ ] Screenshot nowego menu (wszystkie sekcje)
- [ ] Test responsive menu (desktop, tablet, mobile)
- [ ] Raport w `_AGENT_REPORTS/frontend_specialist_menu_v2_implementation_*.md`

### Livewire-specialist (After FAZA 2)

- [ ] Screenshot Dashboard z unified layout
- [ ] Test role-based content switching (minimum 3 role)
- [ ] Raport w `_AGENT_REPORTS/livewire_specialist_dashboard_integration_*.md`

### Laravel-expert (After FAZA 3)

- [ ] Test 26 placeholder routes (wszystkie działają)
- [ ] Verify placeholder messages (ETAP info correct)
- [ ] Raport w `_AGENT_REPORTS/laravel_expert_placeholder_pages_*.md`

### Deployment-specialist (After FAZA 4)

- [ ] Production deployment checklist (admin.blade.php, routes.web.php, placeholder-page.blade.php)
- [ ] Cache cleared (view + config + route)
- [ ] Screenshot verification (production)
- [ ] Raport w `_AGENT_REPORTS/deployment_specialist_menu_v2_deployment_*.md`

---

## 🎯 SUCCESS CRITERIA

**Menu v2.0 uznawane jest za UKOŃCZONE gdy:**

1. ✅ Wszystkie 12 sekcji menu istnieją w sidebar
2. ✅ Wszystkie 49 linków menu działają (28 implemented + 21 placeholder)
3. ✅ Dashboard używa unified layout (admin.blade.php sidebar visible)
4. ✅ Placeholder pages mają spójny design i odpowiednie komunikaty o ETAP
5. ✅ Menu działa responsive (desktop, tablet, mobile)
6. ✅ Production verification passed (wszystkie routes 200 OK)
7. ✅ User może nawigować po całej aplikacji z consistent menu

---

## 📝 NOTES & REFERENCES

### Dokumentacja v2.0

- **Primary:** `_DOCS/ARCHITEKTURA_PPM/02_STRUKTURA_MENU.md`
- **Secondary:** `_DOCS/ARCHITEKTURA_PPM/03_ROUTING_TABLE.md`
- **Design:** `_DOCS/ARCHITEKTURA_PPM/17_UI_UX_GUIDELINES.md`

### Plan Projektu Status

- **ETAP_04:** ✅ COMPLETED (Panel Admin - basis dla menu)
- **ETAP_05a:** 🛠️ 77% COMPLETE (Warianty & Cechy - 3 placeholder routes)
- **ETAP_06:** 🛠️ IN PROGRESS (Import/Export - 2 placeholder routes)
- **ETAP_10:** ❌ NOT STARTED (Dostawy - 4 placeholder routes)
- **Future:** ❌ PLANNED (Zamówienia, Reklamacje, Raporty - 12 placeholder routes)

### Contact & Questions

**Agent:** architect (Planning Manager & Project Plan Keeper)
**Date:** 2025-10-22
**Report Location:** `_AGENT_REPORTS/architect_menu_v2_plan_2025-10-22.md`

---

**KONIEC RAPORTU**
