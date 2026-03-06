# PPM - Admin Dashboard Documentation

> **Wersja:** 1.0.0
> **Data:** 2026-03-06
> **Status:** Production Ready
> **Changelog:** Inicjalna dokumentacja modulu Dashboard po kompletnym refaktorze z monolitu na modularna architekture widgetow

---

## Spis tresci

1. [Overview](#1-overview)
2. [Architektura Plikow](#2-architektura-plikow)
3. [Schema Bazy Danych](#3-schema-bazy-danych)
4. [Widgety - Szczegolowa Dokumentacja](#4-widgety---szczegolowa-dokumentacja)
5. [System Uprawnien i Widocznosci](#5-system-uprawnien-i-widocznosci)
6. [CSS Architecture](#6-css-architecture)
7. [Alpine.js i Real-time Features](#7-alpinejs-i-real-time-features)
8. [Caching Strategy](#8-caching-strategy)
9. [Troubleshooting](#9-troubleshooting)
10. [Changelog](#10-changelog)

---

## 1. Overview

### 1.1 Opis modulu

**AdminDashboard** to glowny panel informacyjny systemu PPM, wyswietlajacy kluczowe metryki, alerty i skroty dla zalogowanego uzytkownika. Modul zostal przebudowany z monolitycznej architektury (**DashboardWidgets.php** + **StatsWidgets.php**) na **13 niezaleznych komponentow Livewire** w modularnym gridzie 4-kolumnowym.

Dashboard dynamicznie dostosowuje zestaw widgetow do roli uzytkownika - widgety administracyjne (SyncJobsStats, SystemHealth, UserStats, SecurityAlerts) sa widoczne tylko dla rol Admin i Manager, oddzielone wizualnym dividerem "Administracja".

**URL Panelu:** `/admin`

### 1.2 Statystyki

| Metryka | Wartosc |
|---------|---------|
| Livewire Components | 13 |
| Blade Views | 13 |
| Pliki CSS | 1 (dedykowany) |
| Config permissions | 1 |
| Linie kodu (backend) | ~1355 |
| Linie kodu (frontend) | ~1084 |
| Linie kodu (CSS) | ~269 |
| Modele uzywane | 9 |
| Widgety z polling | 3 |
| Widgety z cache | 4 |
| **Lacznie LOC** | **~2708** |

### 1.3 Kluczowe funkcjonalnosci

- **Modularny Grid** - 4-kolumnowy responsywny grid z dynamicznym span per widget (1-4 kolumny)
- **Role-based Visibility** - widgety administracyjne widoczne tylko dla Admin/Manager
- **Real-time Clock** - zegar z aktualizacja co sekunde w WelcomeCard (Alpine.js)
- **Wire:poll Monitoring** - SyncJobsStats (30s), SystemHealth (60s), SecurityAlerts (60s), SystemMessages (60s)
- **Business KPI** - metryki produktow (dzis/tydzien/miesiac/rok), kategorii, aktywnych uzytkownikow
- **Products Attention** - alerty o produktach bez zdjec, bez cen, pustych kategoriach, bledach sync
- **System Health** - monitorowanie bazy danych, cache, dysku, kolejki z kolorowymi wskaznikami
- **Security Alerts** - wykrywanie multi-sesji, brute-force, aktywnosci poza godzinami pracy
- **Login History** - historia logowan z geolokalizacja, przegladarka, status (z fallback na UserSession)
- **System Messages** - komunikaty systemowe z priorytetami, CRUD dla adminow, markdown rendering
- **Quick Links** - dynamiczne skroty bazujace na uprawnieniach uzytkownika (14 linkow)
- **Bug Reports** - podsumowanie zgloszen uzytkownika ze statusami i licznikami
- **User Stats** - statystyki uzytkownikow z rozkladem per rola (Spatie)

### 1.4 Ewolucja architektury

```
PRZED (monolityczny):
+-------------------------+    +-------------------+
| DashboardWidgets.php    |    | StatsWidgets.php  |
| (~500 LOC, wszystko     |    | (~300 LOC)        |
|  w jednym pliku)        |    |                   |
+-------------------------+    +-------------------+

PO (modularny):
+-------------------+
| AdminDashboard.php| <- Orchestrator (73 LOC)
+-------------------+
        |
        +-- WelcomeCard (96)     +-- SyncJobsStats (106)   [Admin]
        +-- Logo (inline blade)  +-- SystemHealth (220)    [Admin]
        +-- QuickLinks (123)     +-- UserStats (103)       [Admin]
        +-- BusinessKpi (51)     +-- SecurityAlerts (111)  [Admin]
        +-- SystemMessages (161)
        +-- MyBugReports (56)
        +-- ProductsAttention (83)
        +-- UserActivity (103)
        +-- LoginHistory (69)
```

---

## 2. Architektura Plikow

### 2.1 Livewire Components

| Plik | Linie | Opis |
|------|-------|------|
| `app/Http/Livewire/Dashboard/AdminDashboard.php` | ~73 | Orchestrator - resolveWidgets(), detectUserRole(), layout admin |
| `app/Http/Livewire/Dashboard/Widgets/WelcomeCard.php` | ~96 | Powitanie, rola, ostatnie logowanie, czas sesji |
| `app/Http/Livewire/Dashboard/Widgets/QuickLinks.php` | ~123 | 14 linkow dynamicznych bazujacych na permissions |
| `app/Http/Livewire/Dashboard/Widgets/BusinessKpi.php` | ~51 | Metryki produktow/kategorii/uzytkownikow, cache 300s |
| `app/Http/Livewire/Dashboard/Widgets/SystemMessages.php` | ~161 | CRUD komunikatow, priorytety, markdown, polling 60s |
| `app/Http/Livewire/Dashboard/Widgets/UserActivity.php` | ~103 | 10 ostatnich akcji z AuditLog (bez login/logout) |
| `app/Http/Livewire/Dashboard/Widgets/LoginHistory.php` | ~69 | Historia logowan, geolokalizacja, fallback na UserSession |
| `app/Http/Livewire/Dashboard/Widgets/MyBugReports.php` | ~56 | Bug reports uzytkownika, statusy, liczniki |
| `app/Http/Livewire/Dashboard/Widgets/ProductsAttention.php` | ~83 | Alerty: brak zdjec/cen, puste kategorie, sync failed |
| `app/Http/Livewire/Dashboard/Widgets/SyncJobsStats.php` | ~106 | Running/pending/failed jobs, success rate, avg duration |
| `app/Http/Livewire/Dashboard/Widgets/SystemHealth.php` | ~220 | DB/cache/storage/queue health checks, top 5 tabel |
| `app/Http/Livewire/Dashboard/Widgets/UserStats.php` | ~103 | Total/active/online users, role breakdown (Spatie) |
| `app/Http/Livewire/Dashboard/Widgets/SecurityAlerts.php` | ~111 | Multi-session, brute-force, off-hours detection |

### 2.2 Blade Views

```
resources/views/livewire/dashboard/
+-- admin-dashboard.blade.php         # Grid orchestrator (41 LOC)
+-- widgets/
    +-- welcome-card.blade.php        # Powitanie + zegar (80 LOC)
    +-- quick-links.blade.php         # Siatka linkow (30 LOC)
    +-- business-kpi.blade.php        # Metryki KPI (81 LOC)
    +-- system-messages.blade.php     # Komunikaty + editor (175 LOC)
    +-- user-activity.blade.php       # Feed aktywnosci (43 LOC)
    +-- login-history.blade.php       # Tabela logowan (130 LOC)
    +-- my-bug-reports.blade.php      # Lista zgloszen (91 LOC)
    +-- products-attention.blade.php  # Alerty produktow (96 LOC)
    +-- sync-jobs-stats.blade.php     # Statystyki sync (75 LOC)
    +-- system-health.blade.php       # Health checks (88 LOC)
    +-- user-stats.blade.php          # Statystyki userow (76 LOC)
    +-- security-alerts.blade.php     # Alerty security (78 LOC)
```

### 2.3 CSS

| Plik | Linie | Opis |
|------|-------|------|
| `resources/css/dashboard/dashboard.css` | ~269 | Grid, widget cards, KPI, health indicators, activity feed, bug status badges, divider, logo, clock, markdown |

### 2.4 Config

| Plik | Linie | Opis |
|------|-------|------|
| `config/permissions/dashboard.php` | ~36 | Modul `dashboard` z permission `dashboard.read`, defaults dla 7 rol |

---

## 3. Schema Bazy Danych

Dashboard nie posiada wlasnych tabel - korzysta z modeli innych modulow.

### 3.1 Modele uzywane przez widgety

| Model | Tabela | Uzywany przez |
|-------|--------|---------------|
| `Product` | `products` | BusinessKpi, ProductsAttention |
| `Category` | `categories` | BusinessKpi, ProductsAttention |
| `User` | `users` | BusinessKpi, UserStats, SecurityAlerts |
| `UserSession` | `user_sessions` | WelcomeCard, LoginHistory, UserStats, SecurityAlerts |
| `LoginAttempt` | `login_attempts` | WelcomeCard, LoginHistory, SecurityAlerts |
| `AdminNotification` | `admin_notifications` | SystemMessages |
| `AuditLog` | `audit_logs` | UserActivity, SecurityAlerts |
| `BugReport` | `bug_reports` | MyBugReports |
| `SyncJob` | `sync_jobs` | ProductsAttention, SyncJobsStats |

### 3.2 Dodatkowe tabele (queries bezposrednie)

| Tabela | Uzywany przez | Query |
|--------|---------------|-------|
| `failed_jobs` | SystemHealth | `DB::table('failed_jobs')->count()` |
| `jobs` | SystemHealth | `DB::table('jobs')->count()` |
| `information_schema.tables` | SystemHealth | Top 5 tabel wg rozmiaru |
| `model_has_roles` | UserStats | Spatie role pivot |
| `roles` | UserStats | Spatie role names |

### 3.3 Relacje miedzy tabelami

```
users
    |--- 1:N ---> user_sessions
    |--- 1:N ---> login_attempts
    |--- 1:N ---> audit_logs
    |--- 1:N ---> bug_reports
    |--- N:M ---> roles (via model_has_roles)

products
    |--- N:M ---> categories
    |--- 1:N ---> product_media (media)
    |--- 1:N ---> product_prices (validPrices)

sync_jobs (standalone)
admin_notifications (standalone)
```

---

## 4. Widgety - Szczegolowa Dokumentacja

### 4.1 WelcomeCard

**Span:** 2 | **Widocznosc:** Wszystkie role | **Poll:** Brak | **Cache:** Brak

Wyswietla powitanie (dynamiczne wg godziny), imie i role uzytkownika, czas od ostatniego logowania, adres IP, czas sesji dzisiejszej. Zawiera zegar real-time (Alpine.js).

**Properties:**
```php
public string $userName = '';
public string $userEmail = '';
public string $userRole = '';
public ?string $lastLoginAt = null;    // "2 godziny temu" (diffForHumans)
public ?string $lastLoginIp = null;
public int $sessionDuration = 0;       // minuty dzis
```

**Metody:**
| Metoda | Opis |
|--------|------|
| `loadLastLogin(int $userId)` | Ostatnie udane logowanie z `LoginAttempt` |
| `loadSessionDuration(int $userId)` | Suma minut z `UserSession` za dzis |
| `getGreeting()` | Powitanie wg godziny (6-12/12-18/18-24/0-6) |
| `getFormattedDuration()` | Format "Xh Ymin" lub "X min" |

### 4.2 Logo MPP TRADE

**Span:** 2 | **Widocznosc:** Wszystkie role | **Poll:** Brak | **Cache:** Brak

Widget inline (nie jest komponentem Livewire) - renderowany bezposrednio w `admin-dashboard.blade.php`. Wyswietla logo z zewnetrznego CDN (`mm.mpptrade.pl`) z efektem drop-shadow i dopasowaniem wysokosci do WelcomeCard via Alpine `$nextTick`.

**Fallback:** Jesli obraz nie zaladuje sie (`onerror`), wyswietla tekst "MPP TRADE" w kolorze brand.

### 4.3 QuickLinks

**Span:** 4 | **Widocznosc:** Wszystkie role | **Poll:** Brak | **Cache:** Brak

Siatka 14 linkow szybkiego dostepu filtrowanych na podstawie `$user->can()`. Kazdy link zawiera SVG icon, label i URL.

**Dostepne linki:**

| Link | Permission | URL |
|------|-----------|-----|
| Dashboard | zawsze | `/admin` |
| Produkty | `products.read` | `/admin/products` |
| Nowy produkt | `products.create` | `/admin/products/create` |
| Import | `products.import` | `/admin/products/import` |
| Sklepy | `shops.read` | `/admin/shops` |
| Integracje | `integrations.read` | `/admin/integrations` |
| Uzytkownicy | `users.manage` | `/admin/users` |
| Ustawienia | `system.settings` | `/admin/system-settings` |
| Kategorie | `categories.read` | `/admin/categories` |
| Zamowienia | `orders.read` | `/admin/orders` |
| Dostawy | `deliveries.read` | `/admin/deliveries` |
| Reklamacje | `claims.read` | `/admin/claims` |
| Raporty | `reports.read` | `/admin/reports` |
| Media | `media.read` | `/admin/media` |
| Zgloszenia | `bugs.read` | `/admin/bug-reports` |

### 4.4 BusinessKpi

**Span:** 4 | **Widocznosc:** Wszystkie role | **Poll:** Brak | **Cache:** 300s

Metryki biznesowe w formie kart KPI:

| Metryka | Query |
|---------|-------|
| `products_today` | `Product::whereDate('created_at', today())` |
| `products_week` | `Product::where('created_at', '>=', startOfWeek)` |
| `products_month` | `Product::where('created_at', '>=', startOfMonth)` |
| `products_year` | `Product::where('created_at', '>=', startOfYear)` |
| `total_products` | `Product::count()` |
| `total_categories` | `Category::count()` |
| `active_users` | `User::where('is_active', true)->count()` |

**Cache key:** `dashboard_business_kpi_{userId}` (per user, 300s TTL)

**Metody:** `loadMetrics()`, `refreshMetrics()` (cache bust + reload)

### 4.5 SystemMessages

**Span:** 2 | **Widocznosc:** Wszystkie role (CRUD tylko Admin) | **Poll:** 60s | **Cache:** Brak

Komunikaty systemowe z priorytetami (low/normal/high/critical). Admin moze tworzyc, edytowac i usuwac komunikaty. Wiadomosci renderowane jako Markdown (`Str::markdown()`).

**Properties:**
```php
public $notifications;          // Collection ostatnich 5
public int $unreadCount = 0;
public bool $isAdmin = false;
public bool $showEditor = false;
public ?int $editingId = null;
public string $editTitle = '';
public string $editMessage = '';
public string $editPriority = 'normal';
```

**Metody CRUD:**
| Metoda | Opis |
|--------|------|
| `loadMessages()` | 5 najnowszych (channel != email), count unread |
| `markAsRead(int $id)` | Oznacz jako przeczytany |
| `createMessage()` | Otworz editor (nowy) |
| `startEdit(int $id)` | Otworz editor (edycja) |
| `saveMessage()` | Validate + create/update |
| `deleteMessage(int $id)` | Usun komunikat |

**Priority config (kolory):**

| Priority | Dot | Text | Icon |
|----------|-----|------|------|
| critical | `bg-red-500` | `text-red-400` | `fa-exclamation-triangle` |
| high | `bg-orange-500` | `text-orange-400` | `fa-exclamation-circle` |
| normal | `bg-blue-500` | `text-blue-400` | `fa-info-circle` |
| low | `bg-gray-500` | `text-gray-400` | `fa-bell` |

### 4.6 MyBugReports

**Span:** 2 | **Widocznosc:** Wszystkie role | **Poll:** Brak | **Cache:** Brak

Podsumowanie zgloszen bledow uzytkownika. 5 ostatnich + liczniki per status.

**Properties:**
```php
public $reports;                // 5 najnowszych (by updated_at)
public int $totalCount = 0;
public int $openCount = 0;     // total - (closed + rejected)
public array $statusCounts = [
    'new' => 0,
    'in_progress' => 0,
    'resolved' => 0,
    'closed' => 0,
];
```

### 4.7 ProductsAttention

**Span:** 2 | **Widocznosc:** Wszystkie role | **Poll:** Brak | **Cache:** 300s

Alerty o problemach z produktami:

| Metryka | Query | Severity |
|---------|-------|----------|
| `no_images` | `Product::doesntHave('media')` | >10 red, >0 yellow, 0 green |
| `no_prices` | `Product::doesntHave('validPrices')` | j.w. |
| `empty_categories` | `Category::doesntHave('products')` | j.w. |
| `sync_failed` | `SyncJob::failed()->where(7 days)` | j.w. |

**Severity system:**
- `>10` -> `text-red-400` + `health-dot--error`
- `>0` -> `text-amber-400` + `health-dot--warning`
- `0` -> `text-emerald-400` + `health-dot--healthy`

**Cache key:** `dashboard_products_attention_{userId}` (300s TTL)

### 4.8 UserActivity

**Span:** 2 | **Widocznosc:** Wszystkie role | **Poll:** Brak | **Cache:** Brak

10 ostatnich akcji uzytkownika z `AuditLog` (z wylaczeniem login/logout/login_failed). Kazda akcja ma kolorowy icon, opis po polsku i timestamp.

**Event -> Icon/Color mapping:**

| Event | Color | Opis |
|-------|-------|------|
| `created` | green | Utworzono |
| `updated` | blue | Zaktualizowano |
| `deleted`, `bulk_delete` | red | Usunieto |
| `bulk_update`, `bulk_export` | amber | Operacja masowa |
| `imported` | teal | Import |
| `exported` | indigo | Eksport |
| `synced` | cyan | Synchronizacja |
| `matched` | emerald | Dopasowanie |

**Model display names (PL):** Product->produkt, Category->kategorie, ProductMedia->media, ImportBatch->import, SyncJob->sync job, User->uzytkownika, AdminNotification->komunikat, PrestaShopShop->sklep

### 4.9 LoginHistory

**Span:** 4 | **Widocznosc:** Wszystkie role | **Poll:** Brak | **Cache:** Brak

Tabela 10 ostatnich logowan z `LoginAttempt`. Kolumny: data, IP, przegladarka, typ urzadzenia (icon), lokalizacja (city + country), status (sukces/blad).

**Fallback:** Jesli brak danych w `LoginAttempt`, automatycznie przelacza na `UserSession` (`$usingSessionFallback = true`).

**Device icons:** mobile, tablet, desktop (rozne SVG paths)

### 4.10 SyncJobsStats (Admin-only)

**Span:** 2 | **Widocznosc:** Admin, Manager | **Poll:** 30s | **Cache:** 60s

Real-time statystyki sync jobow:

| Metryka | Query |
|---------|-------|
| `running_jobs` | `SyncJob::running()->count()` |
| `pending_jobs` | `SyncJob::pending()->count()` |
| `failed_jobs` | `SyncJob::failed()->where(7 days)->count()` |
| `completed_today` | `SyncJob::completed()->whereDate(today)` |
| `completed_week` | `SyncJob::completed()->where(startOfWeek)` |
| `completed_month` | `SyncJob::completed()->where(startOfMonth)` |
| `avg_duration` | `SyncJob::completed()->whereDate(today)->avg('duration_seconds')` |
| `success_rate` | `completed / (completed + failed) * 100` (7 dni) |

**Cache key:** `dashboard_sync_jobs_stats` (globalny, 60s TTL)

**Metody:** `formatDuration(int $seconds)` -> "Xs" / "Xm Ys" / "Xh Ym"

### 4.11 SystemHealth (Admin-only)

**Span:** 2 | **Widocznosc:** Admin, Manager | **Poll:** 60s | **Cache:** Brak

Monitorowanie 4 komponentow infrastruktury:

| Check | Metoda | Healthy | Warning | Error |
|-------|--------|---------|---------|-------|
| Database | `checkDatabase()` | response <500ms | 500-1000ms | >1000ms lub exception |
| Cache | `checkCache()` | put/get/forget OK | odczyt != 'ok' | exception |
| Storage | `checkStorage()` | disk used <75% | 75-90% | >90% |
| Queue | `checkQueue()` | 0 failed, <50 pending | failed >0 lub pending >50 | failed >10 |

**DB Details (dodatkowe):**
- Top 5 tabel wg rozmiaru (information_schema query)
- Aktywne polaczenia (`SHOW STATUS LIKE 'Threads_connected'`)
- Slow queries (`SHOW STATUS LIKE 'Slow_queries'`)

**Metody:** `formatBytes(float $bytes)` -> "X.Y GB/MB/KB"

### 4.12 UserStats (Admin-only)

**Span:** 2 | **Widocznosc:** Admin, Manager | **Poll:** Brak | **Cache:** 300s

Statystyki uzytkownikow z rozkladem per rola:

| Metryka | Query |
|---------|-------|
| `total_users` | `User::count()` |
| `active_users` | `User::where('is_active', true)` |
| `online_now` | `UserSession::active()->count()` |
| `new_today` | `User::whereDate('created_at', today)` |
| `new_this_week` | `User::where('created_at', '>=', startOfWeek)` |

**Role breakdown:** Join `model_has_roles` + `roles` (Spatie), grupowane po `roles.name`, z kolorami:

| Rola | Kolor |
|------|-------|
| Admin | `#EF4444` (red) |
| Manager | `#F97316` (orange) |
| Edytor | `#10B981` (emerald) |
| Magazyn | `#3B82F6` (blue) |
| Handlowy | `#8B5CF6` (violet) |
| Reklamacje | `#06B6D4` (cyan) |
| User | `#6B7280` (gray) |

**Cache key:** `dashboard_user_stats` (globalny, 300s TTL)

### 4.13 SecurityAlerts (Admin-only)

**Span:** 2 | **Widocznosc:** Admin, Manager | **Poll:** 60s | **Cache:** Brak

Wykrywanie 3 typow zagrozzen bezpieczenstwa:

| Typ | Severity | Warunek |
|-----|----------|---------|
| `multi_session` | medium (>3 sesji), high (>5) | `UserSession::active()` grupowane per user_id, having >3 |
| `brute_force` | high | `LoginAttempt::failed()` w 24h, grupowane per IP, having >5 |
| `off_hours` | medium | `AuditLog` z HOUR <6 lub >22, w 7 dni, jesli >10 akcji |

**Limity:** Max 8 alertow, sortowane wg severity (high first).

**Properties:**
```php
public array $alerts = [];     // [{type, severity, message, timestamp}]
public int $highCount = 0;
public int $mediumCount = 0;
```

---

## 5. System Uprawnien i Widocznosci

### 5.1 Gate Authorization

```php
// AdminDashboard::mount()
$this->authorize('dashboard.read');
```

Dostep do dashboardu wymaga permission `dashboard.read`, przydzielonej domyslnie WSZYSTKIM 7 rolom.

### 5.2 Widget Visibility Matrix

| Widget | User | Edytor | Magazyn | Handlowy | Reklamacje | Manager | Admin |
|--------|------|--------|---------|----------|------------|---------|-------|
| WelcomeCard | tak | tak | tak | tak | tak | tak | tak |
| Logo | tak | tak | tak | tak | tak | tak | tak |
| QuickLinks | tak | tak | tak | tak | tak | tak | tak |
| BusinessKpi | tak | tak | tak | tak | tak | tak | tak |
| SystemMessages | tak | tak | tak | tak | tak | tak | tak |
| MyBugReports | tak | tak | tak | tak | tak | tak | tak |
| ProductsAttention | tak | tak | tak | tak | tak | tak | tak |
| UserActivity | tak | tak | tak | tak | tak | tak | tak |
| LoginHistory | tak | tak | tak | tak | tak | tak | tak |
| **DIVIDER** | - | - | - | - | - | tak | tak |
| SyncJobsStats | - | - | - | - | - | tak | tak |
| SystemHealth | - | - | - | - | - | tak | tak |
| UserStats | - | - | - | - | - | tak | tak |
| SecurityAlerts | - | - | - | - | - | tak | tak |

### 5.3 Logika widocznosci

```php
// AdminDashboard::resolveWidgets()
$isAdmin = in_array($role, ['Admin', 'Manager']);

// User-level widgets -> zawsze dodawane
// Admin-only widgets -> dodawane tylko gdy $isAdmin === true
```

### 5.4 QuickLinks - permissions per link

Kazdy link w QuickLinks jest filtrowany indywidualnie przez `$user->can('permission.name')`. Uzytkownik widzi tylko linki do paneli, do ktorych ma dostep. Dashboard link jest zawsze widoczny (`'allowed' => true`).

### 5.5 SystemMessages - role-based CRUD

Odczyt komunikatow: wszystkie role. Tworzenie/edycja/usuwanie: tylko rola `Admin` (sprawdzane przez `$this->isAdmin` w blade via `@if($isAdmin)`).

---

## 6. CSS Architecture

### 6.1 Plik CSS

**Plik:** `resources/css/dashboard/dashboard.css` (269 linii)

### 6.2 Grupy klas CSS

| Grupa | Prefix/Klasa | Opis |
|-------|-------------|------|
| Grid Layout | `.dashboard-grid`, `.dashboard-span-{1-4}` | 4-kolumnowy grid z responsive breakpoints |
| Widget Card | `.dashboard-widget`, `.dashboard-widget__header`, `__title`, `__icon` | Bazowa karta widgetu z gradient bg i hover |
| KPI | `.kpi-metric`, `.kpi-metric__value`, `__label` | Karty metryk z duzym fontem |
| Health | `.health-indicator`, `.health-dot`, `.health-dot--{healthy,warning,error,unknown}` | Kolorowe wskazniki statusu |
| Activity | `.activity-item`, `.activity-item__icon`, `__time` | Feed aktywnosci |
| Bug Status | `.bug-status--{new,in_progress,waiting,resolved,closed,rejected}` | Kolorowe badge statusow |
| Divider | `.dashboard-section-divider`, `__line`, `__label`, `__line--reverse` | Separator sekcji Admin |
| Logo | `.dashboard-logo-widget`, `.dashboard-logo-img` | Logo z drop-shadow i hover |
| Clock | `.welcome-clock`, `__time`, `__daydate`, `__day`, `__date` | Zegar w WelcomeCard |
| Role Dots | `.role-dot--{admin,manager,edytor,...}` | Kolorowe kropki rol |
| Markdown | `.dashboard-markdown p/a/strong/em/ul/ol` | Stylowanie markdown w SystemMessages |

### 6.3 Responsive breakpoints

| Breakpoint | Grid | Span behavior |
|------------|------|---------------|
| >1280px | 4 kolumny | span-1..4 dziala normalnie |
| 1024-1280px | 2 kolumny | span-3, span-4 -> span 2 |
| 640-1024px | 2 kolumny | span-3, span-4 -> span 2 |
| <640px | 1 kolumna | wszystkie span -> span 1, clock ukryty |

### 6.4 Design tokens

| Token | Wartosc | Uzycie |
|-------|---------|--------|
| `--mpp-primary` | `#e0ac7e` | Brand color, hover, divider, clock, icon bg |
| `--mpp-primary-rgb` | `224, 172, 126` | Icon background rgba |
| `--text-primary` | `#ffffff` | KPI values |
| `--text-secondary` | `#e5e7eb` | Widget titles, day label |
| `--text-muted` | `#9ca3af` | KPI labels, timestamps, date |

---

## 7. Alpine.js i Real-time Features

### 7.1 Real-time Clock (WelcomeCard)

```javascript
// welcome-card.blade.php
x-data="{
    time: '', day: '', date: '',
    init() {
        this.updateTime();
        setInterval(() => this.updateTime(), 1000);
    },
    updateTime() {
        const now = new Date();
        this.time = now.toLocaleTimeString('pl-PL', {hour:'2-digit', minute:'2-digit', second:'2-digit'});
        this.day = now.toLocaleDateString('pl-PL', {weekday: 'long'});
        this.date = now.toLocaleDateString('pl-PL', {day:'numeric', month:'long', year:'numeric'});
    }
}"
```

Zegar aktualizuje sie co 1 sekunde wylacznie po stronie klienta (brak pollingu do serwera).

### 7.2 Logo Height Matching

```javascript
// admin-dashboard.blade.php (inline)
x-init="$nextTick(() => {
    const sibling = $el.closest('.dashboard-grid').querySelector('.dashboard-widget:not(.dashboard-logo-widget)');
    if (sibling) $el.style.height = sibling.offsetHeight + 'px';
})"
```

Dopasowuje wysokosc logo widgetu do WelcomeCard po renderowaniu.

### 7.3 Wire:poll (Server-side polling)

| Widget | Interwal | Metoda |
|--------|----------|--------|
| SystemMessages | 60s | `loadMessages()` |
| SyncJobsStats | 30s | `loadMetrics()` |
| SystemHealth | 60s | `runChecks()` |
| SecurityAlerts | 60s | `loadAlerts()` |

---

## 8. Caching Strategy

### 8.1 Cached Widgets

| Widget | Cache Key | TTL | Scope |
|--------|-----------|-----|-------|
| BusinessKpi | `dashboard_business_kpi_{userId}` | 300s (5min) | Per user |
| ProductsAttention | `dashboard_products_attention_{userId}` | 300s (5min) | Per user |
| SyncJobsStats | `dashboard_sync_jobs_stats` | 60s (1min) | Global |
| UserStats | `dashboard_user_stats` | 300s (5min) | Global |

### 8.2 Cache Invalidation

Kazdy cached widget ma metode `refreshMetrics()` / `refreshStats()` ktora:
1. `Cache::forget($cacheKey)` - invaliduje cache
2. Ponownie wywoluje `loadMetrics()` / `loadStats()` - laduje swiezy dane

Przyciski "Odswiez" w UI wywoluja te metody.

### 8.3 Widgety bez cache

WelcomeCard, QuickLinks, SystemMessages, UserActivity, LoginHistory, MyBugReports, SystemHealth, SecurityAlerts - dane ladowane bezposrednio z DB przy kazdym renderze/pollingu.

---

## 9. Troubleshooting

### 9.1 Widgety admin nie wyswietlaja sie

**Przyczyna:** Uzytkownik nie ma roli Admin ani Manager.

**Rozwiazanie:**
1. Sprawdz role uzytkownika: `User::find($id)->getRoleNames()`
2. Widget visibility: logika w `AdminDashboard::resolveWidgets()` sprawdza `in_array($role, ['Admin', 'Manager'])`
3. Nadaj role: `$user->assignRole('Admin')`

### 9.2 KPI pokazuje stare dane

**Przyczyna:** Cache 300s nie wygasl.

**Rozwiazanie:**
1. Kliknij przycisk "Odswiez" w widgecie (wywoluje `refreshMetrics()`)
2. Lub reczne czyszczenie: `Cache::forget('dashboard_business_kpi_' . $userId)`
3. Lub globalne: `php artisan cache:clear`

### 9.3 SystemHealth pokazuje "Blad polaczenia" dla Database

**Przyczyna:** Timeout lub blad polaczenia do MySQL.

**Rozwiazanie:**
1. Sprawdz `.env`: `DB_HOST`, `DB_PORT`, `DB_DATABASE`
2. Test polaczenia: `php artisan db:show`
3. Sprawdz logi: `storage/logs/laravel.log`

### 9.4 SecurityAlerts - brak alertow mimo podejrzanej aktywnosci

**Przyczyna:** Progi detekcji sa zbyt wysokie.

**Rozwiazanie:**
- Multi-session: dopiero >3 aktywne sesje (sprawdz `user_sessions.is_active`)
- Brute-force: dopiero >5 failed logins z tego samego IP w 24h
- Off-hours: dopiero >10 akcji poza 6:00-22:00 w 7 dni

### 9.5 QuickLinks - nie wyswietla linkow

**Przyczyna:** Uzytkownik nie ma odpowiednich permissions.

**Rozwiazanie:**
1. Sprawdz permissions: `$user->getAllPermissions()->pluck('name')`
2. Dashboard link jest zawsze widoczny (hardcoded `'allowed' => true`)
3. Pozostale wymagaja odpowiednich `can()` checks

### 9.6 Logo nie laduje sie

**Przyczyna:** Zewnetrzny CDN (`mm.mpptrade.pl`) niedostepny.

**Rozwiazanie:** Widget ma wbudowany fallback `onerror` - wyswietla tekst "MPP TRADE" w kolorze brand. Jezeli problem sie powtarza, rozwazyc lokalna kopie logo.

### 9.7 Wire:poll powoduje wysokie zuzycie zasobow

**Przyczyna:** 4 widgety z pollingiem (30s, 60s, 60s, 60s) generuja ciagle requesty.

**Rozwiazanie:**
- SyncJobsStats: cache 60s ogranicza faktyczne DB queries nawet przy pollingu 30s
- SystemHealth/SecurityAlerts: polling 60s to umiarkowane obciazenie
- Jezeli problem: mozna zwiekszyc interwaly pollingu w blade (`wire:poll.120s`)

---

## 10. Changelog

### v1.0.0 (2026-03-06)

- **Inicjalna wersja** dokumentacji modulu Dashboard
- **Architektura:** Przebudowa z monolitu (DashboardWidgets.php + StatsWidgets.php) na 13 modularnych widgetow Livewire
- **Widgety User-level:** WelcomeCard, Logo, QuickLinks, BusinessKpi, SystemMessages, MyBugReports, ProductsAttention, UserActivity, LoginHistory
- **Widgety Admin-only:** SyncJobsStats, SystemHealth, UserStats, SecurityAlerts
- **Real-time:** Wire:poll na 4 widgetach, Alpine.js clock w WelcomeCard
- **Caching:** 4 widgety z Cache::remember (60-300s TTL)
- **CSS:** Dedykowany `dashboard.css` (269 LOC) z dark theme, responsive grid, brand colors
- **Permissions:** Modul `dashboard.read` z defaults dla wszystkich 7 rol
- **Security monitoring:** Multi-session detection, brute-force alerts, off-hours activity
