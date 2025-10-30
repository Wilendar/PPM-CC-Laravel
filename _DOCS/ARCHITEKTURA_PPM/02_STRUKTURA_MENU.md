# 02. Struktura Menu

[◀ Powrót do spisu treści](README.md)

---

## 🏠 Główna Struktura Menu

### Sidebar Navigation (Hierarchiczna)

```
┌─────────────────────────────────────────────────┐
│  🏠 DASHBOARD                                   │ [Wszyscy - Role-Based Content]
├─────────────────────────────────────────────────┤
│  🏪 SKLEPY PRESTASHOP                           │ [Admin]
│    ├─ Lista sklepów                            │
│    ├─ Dodaj sklep                              │
│    └─ Synchronizacja                           │
├────────────────────────────────────────────────┤
│  📦 PRODUKTY                                   │ [Wszyscy]
│    ├─ Lista produktów                          │ [Wszyscy podgląd, edycja Menadżer+]
│    ├─ Dodaj produkt                            │ [Menadżer+]
│    ├─ Kategorie                                │ [Menadżer+]
│    ├─ Import z pliku                           │ [NEW - Menadżer+]
│    ├─ Historie importów                        │ [NEW - Menadżer+]
│    └─ Szybka Wyszukiwarka                      │ [Wszyscy]
├────────────────────────────────────────────────┤
│  💰 CENNIK                                      │ [Menadżer+]
│    ├─ Grupy cenowe                             │
│    ├─ Ceny produktów                           │
│    └─ Aktualizacja masowa                      │
├─────────────────────────────────────────────────┤
│  🎨 WARIANTY & CECHY                            │ [Menadżer+]
│    ├─ Zarządzanie wariantami                   │
│    ├─ Cechy pojazdów                           │
│    └─ Dopasowania części                       │
├─────────────────────────────────────────────────┤
│  🚚 DOSTAWY & KONTENERY                        │ [Magazynier+]
│    ├─ Lista dostaw                             │
│    ├─ Kontenery                                │
│    ├─ Przyjęcia magazynowe                     │
│    └─ Dokumenty odpraw                         │
├─────────────────────────────────────────────────┤
│  📋 ZAMÓWIENIA                                  │ [Handlowiec+]
│    ├─ Lista zamówień                           │
│    ├─ Rezerwacje z kontenera                   │
│    └─ Historia zamówień                        │
├─────────────────────────────────────────────────┤
│  ⚠️ REKLAMACJE                                  │ [Reklamacje+]
│    ├─ Lista reklamacji                         │
│    ├─ Nowa reklamacja                          │
│    └─ Archiwum                                 │
├─────────────────────────────────────────────────┤
│  📊 RAPORTY & STATYSTYKI                        │ [Menadżer+]
│    ├─ Raporty produktowe                       │
│    ├─ Raporty finansowe                        │
│    ├─ Raporty magazynowe                       │
│    └─ Eksport raportów                         │
├─────────────────────────────────────────────────┤
│  ⚙️ SYSTEM                                      │ [Admin]
│    ├─ Ustawienia systemu                       │
│    ├─ Zarządzanie użytkownikami                │
│    ├─ Integracje ERP                           │ [NEW - Dynamiczna lista]
│    ├─ Backup & Restore                         │
│    ├─ Konserwacja bazy                         │
│    ├─ Logi systemowe                           │
│    ├─ Monitoring                               │
│    └─ API Management                           │
├─────────────────────────────────────────────────┤
│  👤 PROFIL UŻYTKOWNIKA                          │ [Wszyscy]
│    ├─ Edycja profilu                           │
│    ├─ Aktywne sesje                            │
│    ├─ Historia aktywności                      │
│    └─ Ustawienia powiadomień                   │
├─────────────────────────────────────────────────┤
│  ❓ POMOC                                       │ [Wszyscy]
│    ├─ Dokumentacja                             │
│    ├─ Skróty klawiszowe                        │
│    └─ Wsparcie techniczne                      │
└─────────────────────────────────────────────────┘
```

---

## 🔑 Kluczowe Zmiany v2.0

### 1. Usunięto kategorię "ZARZĄDZANIE"
**Przed (v1.0):**
```
📂 ZARZĄDZANIE [Menadżer+]
  ├─ CSV Import/Export
  ├─ Import XLSX
  └─ Historie importów
```

**Po (v2.0):**
```
📦 PRODUKTY [Wszyscy]
  ├─ ... (existing items)
  ├─ Import z pliku      [NEW - unified CSV + XLSX]
  └─ Historie importów   [NEW]
```

**Uzasadnienie:**
- ✅ Import/Export logicznie związane z produktami
- ✅ Uproszczenie struktury menu
- ✅ Unified interface dla CSV + XLSX (jeden punkt wejścia)

---

### 2. Przeniesiono "Integracje ERP" do SYSTEM

**Przed (v1.0):**
```
🔗 INTEGRACJE ERP [Admin]
  ├─ BaseLinker
  ├─ Subiekt GT
  └─ Microsoft Dynamics
```

**Po (v2.0):**
```
⚙️ SYSTEM [Admin]
  ├─ ... (existing items)
  └─ Integracje ERP [Dynamiczna lista]
```

**Uzasadnienie:**
- ✅ Integracje = konfiguracja systemowa (Admin panel)
- ✅ Dynamiczna lista zamiast hardcoded (plugin-based)
- ✅ Możliwość dodawania custom integrations
- ✅ Spójność z innymi ustawieniami systemowymi

**Implementacja Dynamicznej Listy:**
```php
// Route: /admin/integrations
// Dynamicznie ładuje listę dostępnych integracji

Route::get('/admin/integrations', [IntegrationController::class, 'index'])
    ->name('admin.integrations.index'); // Lista wszystkich

Route::get('/admin/integrations/{slug}', [IntegrationController::class, 'show'])
    ->name('admin.integrations.show'); // Szczegóły (baselinker, subiekt, dynamics, custom)

Route::get('/admin/integrations/{slug}/configure', [IntegrationController::class, 'configure'])
    ->name('admin.integrations.configure'); // Konfiguracja
```

---

### 3. Usunięto "Sklepy PrestaShop > Eksport masowy"

**Przed (v1.0):**
```
🏪 SKLEPY PRESTASHOP
  ├─ Lista sklepów
  ├─ Dodaj sklep
  ├─ Synchronizacja
  └─ Eksport masowy    [USUNIĘTO]
```

**Po (v2.0):**
```
📦 PRODUKTY > Lista produktów
  └─ Przycisk "Eksportuj wszystko do CSV" [NEW - w header actions]
```

**Uzasadnienie:**
- ✅ Eksport = akcja na produktach (logicznie w Lista Produktów)
- ✅ Przycisk zamiast osobnej strony (szybszy dostęp)
- ✅ Kontekst: jestem na liście produktów → mogę exportować
- ✅ Spójność z bulk operations (zaznacz produkty → export selected)

---

### 4. Role-Based Dashboard (NOWOŚĆ)

**v2.0:** Dashboard pokazuje różną zawartość w zależności od roli użytkownika.

**Dashboard per Rola:**

| Rola | Główne Widgety | Quick Actions | Statystyki |
|------|----------------|---------------|------------|
| **Admin** | KPI wszystkich obszarów, błędy sync, alerty systemowe | Dodaj sklep, Import CSV, Ustawienia systemu | Produkty, Sklepy, Użytkownicy, Integracje |
| **Menadżer** | KPI produktów, sync status, magazyny | Dodaj produkt, Import CSV, Eksport | Produkty, Synchronizacje, Cennik |
| **Redaktor** | Ostatnie edycje, produkty bez zdjęć | Edytuj produkt, Wyszukaj | Edycje produktów, Brakujące opisy |
| **Magazynier** | Dostawy, kontenery, przyjęcia | Nowa dostawa, Przyjęcie magazynowe | Stany magazynowe, Kontenery w transporcie |
| **Handlowiec** | Zamówienia, rezerwacje | Nowe zamówienie, Rezerwuj z kontenera | Zamówienia pending, Rezerwacje aktywne |
| **Reklamacje** | Reklamacje pending, timeline | Nowa reklamacja, Zamknij reklamację | Reklamacje otwarte/zamknięte, Priorytety |
| **Użytkownik** | Wyszukiwarka, ostatnie produkty | Wyszukaj produkt | Podstawowe statystyki (read-only) |

**Implementacja:**
```php
// DashboardController@index
public function index()
{
    $user = auth()->user();
    $role = $user->role; // 'admin', 'manager', 'editor', etc.

    // Różne widoki per rola
    return view("dashboard.{$role}", [
        'widgets' => $this->getWidgetsForRole($role),
        'quickActions' => $this->getQuickActionsForRole($role),
        'statistics' => $this->getStatisticsForRole($role),
    ]);
}
```

---

### 5. Unified Import System

**v2.0:** Jeden interfejs dla CSV + XLSX importu.

**Route:** `/admin/products/import`

**Workflow:**
1. Upload file (CSV lub XLSX auto-detected)
2. Wybór typu importu (Produkty / Warianty / Cechy / Dopasowania)
3. Column mapping (auto-detect + manual adjust)
4. Validation & Preview
5. Import execution
6. Error report (downloadable CSV)

**Przyciski "Pobierz szablon":**
- Szablon: Produkty (CSV + XLSX)
- Szablon: Warianty (CSV + XLSX)
- Szablon: Cechy (CSV + XLSX)
- Szablon: Dopasowania (CSV + XLSX)

---

## 📊 Statystyki Menu

### Liczba Sekcji per Poziom

| Poziom Menu | Liczba Sekcji | Przykłady |
|-------------|---------------|-----------|
| **Top Level** (główne kategorie) | 12 | Dashboard, Sklepy, Produkty, Cennik, etc. |
| **Second Level** (podstrony) | ~45 | Lista produktów, Dodaj produkt, Grupy cenowe, etc. |
| **Third Level** (tabs/modals) | ~80+ | Tabs w edycji produktu, modals, wizardy |

### Uprawnienia per Sekcja

| Uprawnienie | Liczba Sekcji |
|-------------|---------------|
| **Admin only** | 3 (Sklepy, System, części Raportów) |
| **Menadżer+** | 6 (Produkty edycja, Cennik, Warianty, Raporty) |
| **Magazynier+** | 1 (Dostawy) |
| **Handlowiec+** | 1 (Zamówienia) |
| **Reklamacje+** | 1 (Reklamacje) |
| **Wszyscy** | 3 (Dashboard, Produkty odczyt, Pomoc, Profil) |

---

## 🎨 UI/UX Patterns

### Sidebar Behavior

**Desktop (>1024px):**
- Sidebar stały (zawsze widoczny)
- Width: 280px
- Collapsible sections (expand/collapse)
- Active state highlighting

**Tablet (768-1024px):**
- Sidebar collapsible (hamburger menu)
- Overlay mode (gdy otwarty, overlay na content)
- Auto-close po kliknięciu linku

**Mobile (<768px):**
- Sidebar jako full-screen drawer
- Slide-in animation
- Close button + overlay backdrop

### Active State

```css
.sidebar-item.active {
    background: var(--color-primary);
    color: white;
    border-left: 4px solid var(--color-accent);
}
```

### Expandable Sections

```html
<div class="sidebar-section" x-data="{ open: true }">
    <div class="sidebar-section-header" @click="open = !open">
        <span>📦 PRODUKTY</span>
        <svg x-show="!open">▼</svg>
        <svg x-show="open">▲</svg>
    </div>
    <div class="sidebar-section-content" x-show="open" x-collapse>
        <!-- submenu items -->
    </div>
</div>
```

---

## 📖 Nawigacja

- **Poprzedni moduł:** [01. Cel Dokumentu](01_CEL_DOKUMENTU.md)
- **Następny moduł:** [03. Routing Table](03_ROUTING_TABLE.md)
- **Powrót:** [Spis treści](README.md)
