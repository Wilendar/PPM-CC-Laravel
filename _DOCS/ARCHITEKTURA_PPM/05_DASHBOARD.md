# 05. Dashboard (Role-Based)

[◀ Powrót do spisu treści](README.md)

---

## 🏠 Dashboard - Przegląd

**Route:** `/dashboard`
**Uprawnienia:** Wszyscy zalogowani użytkownicy
**Typ:** Role-based content (różna zawartość per rola)

---

## ✨ NOWOŚĆ v2.0: Role-Based Dashboards

Każda rola widzi **różny dashboard** optimized dla swoich potrzeb i uprawnień.

---

## 1. Dashboard ADMIN

### KPI Cards (4-column grid)

```
┌───────────────┬───────────────┬───────────────┬───────────────┐
│ 📦 PRODUKTY   │ 🏪 SKLEPY     │ 👥 UŻYTKOWNICY│ 🔗 INTEGRACJE │
│   1,245       │   5 active    │   12 active   │   3 connected │
│   +15 today   │   2 offline   │   2 pending   │   BaseLinker  │
└───────────────┴───────────────┴───────────────┴───────────────┘
```

**Metryki:**
- Liczba produktów w systemie + dzisiejszy przyrost
- Sklepy PrestaShop (active/offline/total)
- Użytkownicy (active/pending invitations)
- Integracje ERP (connected/disconnected)

### Quick Actions Panel

```
[+ Dodaj Sklep]  [+ Dodaj Użytkownika]  [⚙️ Ustawienia Systemu]  [📊 Raporty]
```

### Recent Activity Timeline (real-time)

- Ostatnie zmiany produktów (kto, kiedy, co zmienił)
- Ostatnie synchronizacje (sklep, status, czas trwania)
- Ostatnie importy (user, typ, sukces/błędy)
- Błędy systemowe (critical/warning)

### Wykresy Statystyk

**1. Produkty per Kategoria (Pie Chart)**
- Top 10 kategorii z liczbą produktów
- Reszta jako "Inne"

**2. Trend Synchronizacji (Line Chart - 7 dni)**
- Liczba synchronizacji per dzień
- Sukces vs. błędy

**3. Status Sklepów PrestaShop (Bar Chart)**
- Per sklep: produkty zsynchronizowane / do synchronizacji

### Alerts & Notifications

```
⚠️ KRYTYCZNE (3)
  - Sklep "YCF" offline (2h)
  - Database size 90% (4.5GB/5GB)
  - Backup failed last night

⚠️ OSTRZEŻENIA (5)
  - 15 produktów bez zdjęć
  - BaseLinker sync delayed 30min
  - 3 reklamacje pending >7 days

ℹ️ INFO (2)
  - Monthly report ready
  - System update available
```

---

## 2. Dashboard MENADŻER

### KPI Cards (4-column grid)

```
┌───────────────┬───────────────┬───────────────┬───────────────┐
│ 📦 PRODUKTY   │ 📊 SYNC STATUS│ 📦 MAGAZYNY   │ 💰 CENNIK     │
│   1,245       │   ✅ Synced   │   850 items   │   7 grup      │
│   +15 today   │   Last: 10min │   ⚠️ Low: 12  │   Updated: 2h │
└───────────────┴───────────────┴───────────────┴───────────────┘
```

### Quick Actions

```
[+ Dodaj Produkt]  [📥 Import CSV]  [📤 Eksport]  [🔄 Synchronizuj Wszystkie]
```

### Recent Activity

- Ostatnie edycje produktów (moje + team)
- Ostatnie synchronizacje (status + czas)
- Ostatnie importy (CSV/XLSX + wyniki)

### Wykresy

**1. Produkty Dodane (Line Chart - 30 dni)**
**2. Stany Magazynowe (Stacked Bar)**
- Per magazyn: dostępne / zamówione / w transporcie

---

## 3. Dashboard REDAKTOR

### KPI Cards (2-column grid)

```
┌────────────────────────────┬────────────────────────────┐
│ 📝 MOJE EDYCJE (dziś)      │ ⚠️ PRODUKTY DO UZUPEŁNIENIA│
│   12 produktów             │   bez zdjęć: 15            │
│   25 zmian                 │   bez opisu: 8             │
└────────────────────────────┴────────────────────────────┘
```

### Quick Actions

```
[🔍 Wyszukaj Produkt]  [📝 Ostatnio Edytowane]  [⚠️ Produkty Bez Zdjęć]
```

### Lista Ostatnio Edytowanych (10 items)

| SKU | Nazwa | Co zmienione | Kiedy |
|-----|-------|--------------|-------|
| PROD-001 | Test | Opis + 3 zdjęcia | 5 min temu |

---

## 4. Dashboard MAGAZYNIER

### KPI Cards (3-column grid)

```
┌───────────────────┬───────────────────┬───────────────────┐
│ 🚚 DOSTAWY        │ 📦 KONTENERY      │ ✅ PRZYJĘCIA      │
│   Oczekujące: 3   │   W transporcie: 2│   Dzisiaj: 1      │
│   Opóźnione: 1    │   Do przyjęcia: 1 │   W trakcie: 0    │
└───────────────────┴───────────────────┴───────────────────┘
```

### Quick Actions

```
[+ Nowa Dostawa]  [📋 Przyjęcie Magazynowe]  [📦 Kontenery]
```

### Aktywne Dostawy (Timeline)

- Kontenery w transporcie (ETA + status tracking)
- Dostawy oczekujące (data zamówienia + dostawca)
- Opóźnione dostawy (czerwone alerty + counter dni)

### Stany Magazynowe (Alerty)

⚠️ **Niski Stan (12 produktów)**
- Lista produktów poniżej minimum
- Link do szczegółów + quick order button

---

## 5. Dashboard HANDLOWIEC

### KPI Cards (3-column grid)

```
┌───────────────────┬───────────────────┬───────────────────┐
│ 📋 ZAMÓWIENIA     │ 🔖 REZERWACJE     │ 📦 DOSTĘPNE       │
│   Pending: 5      │   Aktywne: 8      │   W kontenerze: 3 │
│   Shipped: 12     │   Wygasłe: 2      │   Dostępne: 450   │
└───────────────────┴───────────────────┴───────────────────┘
```

### Quick Actions

```
[+ Nowe Zamówienie]  [🔖 Rezerwuj z Kontenera]  [📋 Moje Zamówienia]
```

### Zamówienia Pending (Lista)

| Nr Zamówienia | Klient | Data | Wartość | Status | Akcje |
|---------------|--------|------|---------|--------|-------|
| ORD-001 | Jan Kowalski | Dziś | 1,250 PLN | Pending | [Ship] |

---

## 6. Dashboard REKLAMACJE

### KPI Cards (3-column grid)

```
┌───────────────────┬───────────────────┬───────────────────┐
│ ⚠️ NOWE           │ 🔧 W TRAKCIE      │ ✅ ZAMKNIĘTE      │
│   3 reklamacje    │   5 reklamacje    │   12 (ten tydzień)│
│   High: 1         │   >7 dni: 2       │   Avg: 3.5 dni    │
└───────────────────┴───────────────────┴───────────────────┘
```

### Quick Actions

```
[+ Nowa Reklamacja]  [⚠️ Priorytety High]  [📋 Wszystkie Reklamacje]
```

### Reklamacje Pending (Timeline)

**High Priority (1):**
- RMA-001 | Jan Kowalski | Produkt wadliwy | 2 dni temu

**Normal Priority (2):**
- RMA-002 | ... | Niezgodność | 1 dzień temu

---

## 7. Dashboard UŻYTKOWNIK (Basic)

### Welcome Panel

```
👋 Witaj, Jan Kowalski!
Ostatnie logowanie: 2025-10-21 10:30
```

### Quick Search

```
┌─────────────────────────────────────────────────────────┐
│  🔍 Wyszukaj produkt po SKU, nazwie, kategorii...      │
└─────────────────────────────────────────────────────────┘
```

### Ostatnio Przeglądane (5 items)

| SKU | Nazwa | Kategoria | Cena | Akcja |
|-----|-------|-----------|------|-------|
| PROD-001 | Test | Części | 150 PLN | [Zobacz] |

### Podstawowe Statystyki (Read-Only)

- Liczba produktów: 1,245
- Liczba kategorii: 85
- Liczba sklepów: 5

---

## 🎨 UI/UX Patterns

### Responsive Layout

**Desktop (>1024px):**
- 4-column grid dla KPI cards
- Sidebar + main content
- Charts pełnej szerokości

**Tablet (768-1024px):**
- 2-column grid dla KPI cards
- Collapsible sidebar
- Charts scrollable horizontal

**Mobile (<768px):**
- 1-column stack
- Simplified KPI cards (tylko liczby + ikony)
- Charts jako swipeable carousel

### Auto-Refresh

```javascript
// Livewire wire:poll
<div wire:poll.30s>
    <!-- KPI cards, recent activity -->
</div>
```

**Częstotliwość:**
- KPI cards: co 30s
- Recent activity: co 1min
- Charts: co 5min (lub manual refresh button)

### Loading States

```html
<div wire:loading.delay>
    <div class="spinner">Ładowanie...</div>
</div>
```

---

## 📖 Nawigacja

- **Poprzedni moduł:** [04. Macierz Uprawnień](04_MACIERZ_UPRAWNIEN.md)
- **Następny moduł:** [06. Sklepy PrestaShop](06_SKLEPY_PRESTASHOP.md)
- **Powrót:** [Spis treści](README.md)
