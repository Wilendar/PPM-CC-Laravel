# Panel Zgłaszania Błędów i Helpdesk - PPM-CC-Laravel

**Status:** 🛠️ Implementacja ukonczona - oczekuje na deployment
**Data:** 2026-01-19
**Priorytet:** HIGH

---

## 1. CEL

Stworzenie systemu zgłoszeń (helpdesk) umożliwiającego użytkownikom raportowanie błędów, propozycji funkcji, pytań i próśb o wsparcie techniczne.

---

## 2. WYMAGANIA (z wyborów użytkownika)

| Aspekt | Wybór |
|--------|-------|
| Dostęp | Tylko zalogowani użytkownicy |
| Typy zgłoszeń | Pełny helpdesk (bug, feature, improvement, question, support) |
| Powiadomienia | Email + Teams (Adaptive Cards) |
| Diagnostyka | Rozszerzona (URL, browser, OS, akcje, console errors) |

---

## 3. ARCHITEKTURA

### 3.1 Model `BugReport`

**Plik:** `app/Models/BugReport.php`
**Migracja:** `database/migrations/YYYY_MM_DD_create_bug_reports_table.php`

```
Kolumny:
├── id (PK)
├── title (varchar 255) *
├── description (text) *
├── steps_to_reproduce (text, nullable)
├── type: bug | feature_request | improvement | question | support *
├── severity: low | medium | high | critical *
├── status: new | in_progress | waiting | resolved | closed | rejected *
├── context_url (varchar 2048)
├── browser_info (varchar 512)
├── os_info (varchar 255)
├── console_errors (JSON, nullable) - ostatnie błędy konsoli
├── user_actions (JSON, nullable) - ostatnie 5 akcji użytkownika
├── screenshot_path (varchar 255, nullable)
├── reporter_id (FK -> users) *
├── assigned_to (FK -> users, nullable)
├── resolution (text, nullable)
├── resolved_at (timestamp, nullable)
├── closed_at (timestamp, nullable)
├── created_at, updated_at
```

**Scopes:** `byStatus()`, `byType()`, `bySeverity()`, `unresolved()`, `assignedTo()`, `reportedBy()`
**Accessors:** `getStatusBadgeAttribute()`, `getTypeBadgeAttribute()`, `getSeverityBadgeAttribute()`

### 3.2 Model `BugReportComment`

**Plik:** `app/Models/BugReportComment.php`
**Migracja:** `database/migrations/YYYY_MM_DD_create_bug_report_comments_table.php`

```
Kolumny:
├── id (PK)
├── bug_report_id (FK) *
├── user_id (FK) *
├── content (text) *
├── is_internal (bool, default: false) - widoczny tylko dla adminów
├── created_at, updated_at
```

### 3.3 Service `BugReportNotificationService`

**Plik:** `app/Services/BugReportNotificationService.php`
**Wzorzec:** SyncNotificationService

```php
notifyNewReport(BugReport $report): void
  ├── AdminNotification::create() - powiadomienie w UI
  ├── Mail::to(admins)->send() - email
  └── TeamsChannel::send() - Teams webhook (Adaptive Cards)

notifyStatusChanged(BugReport $report, string $oldStatus): void
  ├── Mail::to(reporter)->send() - email do zgłaszającego
  └── dispatch('flash-message') - flash w UI
```

---

## 4. KOMPONENTY LIVEWIRE

### 4.1 `BugReportModal` (globalny)

**Plik:** `app/Http/Livewire/BugReports/BugReportModal.php`
**View:** `resources/views/livewire/bug-reports/bug-report-modal.blade.php`

- Modal dostępny z każdej strony (floating button)
- Formularz: title, description, steps, type, severity, screenshot
- Auto-detect: URL, browser, OS
- JavaScript: zbieranie console errors + tracking akcji użytkownika
- WithFileUploads dla screenshota

### 4.2 `UserBugReports` (historia użytkownika)

**Plik:** `app/Http/Livewire/BugReports/UserBugReports.php`
**View:** `resources/views/livewire/bug-reports/user-bug-reports.blade.php`
**Route:** `/profile/bug-reports`

- Lista własnych zgłoszeń
- Filtry: status, type
- Podgląd szczegółów + komentarze publiczne

### 4.3 `BugReportList` (panel admina)

**Plik:** `app/Http/Livewire/Admin/BugReports/BugReportList.php`
**View:** `resources/views/livewire/admin/bug-reports/bug-report-list.blade.php`
**Route:** `/admin/bug-reports`

- Lista wszystkich zgłoszeń z paginacją
- Filtry: status, type, severity, assigned_to, search
- Statystyki: nowe, w toku, rozwiązane, per typ
- Bulk actions: zmiana statusu, przypisanie
- Quick preview w modal

### 4.4 `BugReportDetail` (szczegóły + zarządzanie)

**Plik:** `app/Http/Livewire/Admin/BugReports/BugReportDetail.php`
**View:** `resources/views/livewire/admin/bug-reports/bug-report-detail.blade.php`
**Route:** `/admin/bug-reports/{report}`

- Pełne szczegóły zgłoszenia
- Zmiana statusu, przypisanie
- Komentarze (publiczne + wewnętrzne)
- Podgląd screenshota
- Wyświetlenie diagnostyki (console errors, akcje)

---

## 5. ROUTING I UPRAWNIENIA

### Routes

```php
// Użytkownik (zalogowany)
GET /profile/bug-reports         -> UserBugReports
POST /bug-reports (via Livewire) -> BugReportModal::submit()

// Admin (Admin/Manager)
GET /admin/bug-reports           -> BugReportList
GET /admin/bug-reports/{id}      -> BugReportDetail
```

### Policy: `BugReportPolicy`

| Metoda | Rola | Opis |
|--------|------|------|
| `create` | Zalogowany | Każdy zalogowany może zgłaszać |
| `viewOwn` | Zalogowany | Własne zgłoszenia |
| `viewAny` | Admin/Manager | Wszystkie zgłoszenia |
| `manage` | Admin/Manager | Zmiana statusu, przypisanie |
| `createInternalComment` | Admin/Manager | Komentarze wewnętrzne |

---

## 6. UI/UX

### Floating Button

```blade
{{-- W layouts/admin.blade.php - dolny prawy róg --}}
<button @click="$dispatch('open-bug-report-modal')"
        class="fixed bottom-6 right-6 ... layer-panel">
    <svg><!-- Bug icon --></svg>
</button>
```

### JavaScript: Tracking akcji i console errors

```javascript
// app.js - globalny tracking
window.ppmDiagnostics = {
    actions: [],
    consoleErrors: [],

    trackAction(action) {
        this.actions.push({ action, timestamp: Date.now(), url: location.href });
        if (this.actions.length > 5) this.actions.shift();
    },

    init() {
        // Przechwytywanie console.error
        const originalError = console.error;
        console.error = (...args) => {
            this.consoleErrors.push({ message: args.join(' '), timestamp: Date.now() });
            if (this.consoleErrors.length > 10) this.consoleErrors.shift();
            originalError.apply(console, args);
        };

        // Tracking kliknięć i nawigacji
        document.addEventListener('click', (e) => {
            const target = e.target.closest('button, a, [wire\\:click]');
            if (target) this.trackAction(`Click: ${target.textContent?.slice(0, 50) || target.tagName}`);
        });
    }
};
window.ppmDiagnostics.init();
```

---

## 7. INTEGRACJA TEAMS

Wykorzystanie istniejącego `TeamsChannel::send()` z Adaptive Cards:

```php
// BugReportNotificationService.php
TeamsChannel::send($webhookUrl, [
    'title' => "Nowe zgłoszenie: {$report->title}",
    'type' => $report->type,
    'severity' => $report->severity,
    'reporter' => $report->reporter->name,
    'url' => route('admin.bug-reports.show', $report),
]);
```

---

## 8. PLIKI DO UTWORZENIA/MODYFIKACJI

### Nowe pliki:

| Plik | Linie (est.) |
|------|--------------|
| `database/migrations/*_create_bug_reports_table.php` | ~50 |
| `database/migrations/*_create_bug_report_comments_table.php` | ~25 |
| `app/Models/BugReport.php` | ~200 |
| `app/Models/BugReportComment.php` | ~50 |
| `app/Policies/BugReportPolicy.php` | ~60 |
| `app/Services/BugReportNotificationService.php` | ~150 |
| `app/Http/Livewire/BugReports/BugReportModal.php` | ~150 |
| `app/Http/Livewire/BugReports/UserBugReports.php` | ~80 |
| `app/Http/Livewire/Admin/BugReports/BugReportList.php` | ~200 |
| `app/Http/Livewire/Admin/BugReports/BugReportDetail.php` | ~180 |
| `resources/views/livewire/bug-reports/bug-report-modal.blade.php` | ~150 |
| `resources/views/livewire/bug-reports/user-bug-reports.blade.php` | ~100 |
| `resources/views/livewire/admin/bug-reports/bug-report-list.blade.php` | ~200 |
| `resources/views/livewire/admin/bug-reports/bug-report-detail.blade.php` | ~250 |

### Modyfikacje:

| Plik | Zmiana |
|------|--------|
| `routes/web.php` | Dodanie routes dla bug-reports |
| `resources/views/layouts/admin.blade.php` | Floating button + modal include |
| `resources/js/app.js` | ppmDiagnostics tracking |
| `app/Providers/AuthServiceProvider.php` | Rejestracja BugReportPolicy |
| `config/services.php` | Konfiguracja Teams webhook (opcjonalna) |

---

## 9. KOLEJNOŚĆ IMPLEMENTACJI

### FAZA 1: Database & Models ✅
- [x] 1.1: Migration bug_reports
- [x] 1.2: Migration bug_report_comments
- [x] 1.3: Model BugReport
- [x] 1.4: Model BugReportComment
- [x] 1.5: BugReportPolicy

### FAZA 2: User-facing Components ✅
- [x] 2.1: JavaScript diagnostics (app.js)
- [x] 2.2: BugReportModal component + view
- [x] 2.3: Floating button w layout
- [x] 2.4: UserBugReports component + view

### FAZA 3: Admin Components ✅
- [x] 3.1: BugReportList component + view
- [x] 3.2: BugReportDetail component + view
- [x] 3.3: Routing admin

### FAZA 4: Notifications ✅
- [x] 4.1: BugReportNotificationService
- [x] 4.2: Email templates (text-based)
- [x] 4.3: Teams integration (MessageCard format)

### FAZA 5: Testing & Deployment 🛠️
- [ ] 5.1: Migracja na produkcji
- [ ] 5.2: Build assets (npm run build)
- [ ] 5.3: Deploy + Chrome DevTools verification

---

## 10. WERYFIKACJA

### Testy funkcjonalne:
1. Zalogowany użytkownik może otworzyć modal zgłoszenia
2. Formularz zbiera automatycznie: URL, browser, OS, errors, akcje
3. Upload screenshota działa
4. Zgłoszenie pojawia się w panelu admina
5. Admin otrzymuje email + Teams notification
6. Admin może zmienić status, przypisać, komentować
7. Użytkownik widzi zmianę statusu w historii

### Chrome DevTools verification:
- Screenshot głównych widoków
- Console errors check
- Network requests check

---

## 11. REFERENCJE

**Wzorce z projektu:**
- `app/Models/AdminNotification.php` - status workflow, scopes, accessors
- `app/Http/Livewire/Components/ErrorDetailsModal.php` - globalny modal pattern
- `app/Http/Livewire/Admin/Users/UserForm.php` - WithFileUploads, validation
- `app/Services/SyncNotificationService.php` - email + Teams integration
- `app/Services/Channels/TeamsChannel.php` - Adaptive Cards format
