# Plan Implementacji: System Administracyjny - Użytkownicy, Role, Sesje i Bezpieczeństwo

**Data:** 2026-01-21
**Status:** PLANOWANIE
**Autor:** Claude Opus 4.5

---

## 📋 STRESZCZENIE

Implementacja kompletnego systemu administracyjnego z panelami:
- Lista użytkowników, szczegóły użytkownika
- Zarządzanie sesjami z real-time monitoring
- System ról i uprawnień
- Security Dashboard
- Logi audytu

**KLUCZOWE ODKRYCIE:** Komponenty Livewire są już **ZAIMPLEMENTOWANE** (4500+ linii kodu). Brakuje:
1. **Model AuditLog** - komponent AuditLogs.php go używa, ale model NIE ISTNIEJE
2. **Widok role-list.blade.php** - komponent RoleList.php renderuje widok który NIE ISTNIEJE
3. **Policies** - brakuje UserSessionPolicy, RolePolicy, PermissionPolicy
4. Routes w `routes/web.php`
5. Links w sidebar navigation

---

## 🔍 ANALIZA OBECNEGO STANU

### A. Komponenty Livewire (GOTOWE ✅)

| Komponent | Plik | Linie | Funkcje |
|-----------|------|-------|---------|
| **UserList** | `app/Http/Livewire/Admin/Users/UserList.php` | 523 | Tabela, filtry, bulk actions, impersonation |
| **UserForm** | `app/Http/Livewire/Admin/Users/UserForm.php` | 654 | Multi-step form (4 kroki), draft auto-save |
| **UserDetail** | `app/Http/Livewire/Admin/Users/UserDetail.php` | 386 | Inline editing, activity timeline, permissions |
| **Sessions** | `app/Http/Livewire/Admin/Sessions.php` | 695 | Real-time, analytics, security detection |
| **RoleList** | `app/Http/Livewire/Admin/Roles/RoleList.php` | 481 | CRUD, hierarchy, comparison, templates |
| **PermissionMatrix** | `app/Http/Livewire/Admin/Permissions/PermissionMatrix.php` | 688 | Matrix view, presets, bulk operations |
| **SecurityDashboard** | `app/Http/Livewire/Admin/Security/SecurityDashboard.php` | 328 | Stats, alerts, user/IP actions |
| **AuditLogs** | `app/Http/Livewire/Admin/AuditLogs.php` | 683 | Filtering, diff viewer, export |

### B. Modele (GOTOWE ✅)

| Model | Funkcje |
|-------|---------|
| **User** | Roles (Spatie), security fields, OAuth, preferences |
| **UserSession** | Device/location tracking, suspicious detection |
| **LoginAttempt** | Brute force detection, geolocation |
| **PasswordPolicy** | Password requirements, lockout |
| **PasswordHistory** | No password reuse |
| **AuditLog** | Polymorphic activity tracking |
| **OAuthAuditLog** | OAuth-specific security logging |

### C. Sidebar Navigation

**Dwa systemy:**
1. `navigation.blade.php` (linie 138-186) - sekcja "Administracja"
2. `admin.blade.php` (linie 607-699) - sekcja "System"

**Istniejące linki w "System":**
- ✅ Ustawienia (`/admin/system-settings`)
- ✅ Backup (`/admin/backup`)
- ✅ Konserwacja (`/admin/maintenance`)
- ✅ Media (`/admin/media`)
- ✅ Zgłoszenia (`/admin/bug-reports`)
- ✅ Integracje ERP (`/admin/integrations`)
- ⚠️ Użytkownicy (`/admin/users`) - disabled
- ⚠️ Logi systemowe (`/admin/logs`) - disabled
- ⚠️ Monitoring (`/admin/monitoring`) - disabled

**BRAKUJĄCE linki:**
- ❌ Sesje (`/admin/sessions`)
- ❌ Role (`/admin/roles`)
- ❌ Uprawnienia (`/admin/permissions`)
- ❌ Bezpieczeństwo (`/admin/security`)
- ❌ Logi audytu (`/admin/activity-log`)

---

## 📊 MAPOWANIE FUNKCJONALNOŚCI DO PLANU UŻYTKOWNIKA

### 4.1 Lista Użytkowników (/admin/users)
| Funkcja | Status | Komponent | Metoda |
|---------|--------|-----------|--------|
| Tabela z sortowaniem, filtrowaniem, paginacją | ✅ | UserList | `getUsersProperty()`, `sortBy()` |
| Avatar, nazwa, email, rola, status | ✅ | UserList | View: `user-list.blade.php` |
| Kolumna "Aktywne sesje" | ⚠️ | UserList | Dodać: `$user->activeSessions->count()` |
| Bulk actions (aktywuj, dezaktywuj) | ✅ | UserList | `executeBulkAction()` |
| Bulk action "Wyloguj zaznaczonych" | ❌ | UserList | Dodać: `bulkForceLogout()` |
| Impersonation | ✅ | UserList | `impersonateUser()` |

### 4.2 Szczegóły Użytkownika (/admin/users/{id})
| Funkcja | Status | Komponent | Metoda |
|---------|--------|-----------|--------|
| Edit-in-place | ✅ | UserDetail | `startEdit()`, `saveEdit()` |
| Activity timeline | ✅ | UserDetail | `getUserActivityProperty()` (SIMULATED) |
| Reset hasła przez admina | ✅ | UserDetail | `resetPassword()` |
| Widok uprawnień | ✅ | UserDetail | `getAllUserPermissionsProperty()` |
| Przycisk "Wyloguj użytkownika" | ❌ | UserDetail | Dodać: `forceLogoutUser()` |
| Przycisk "Wymuś zmianę hasła" | ✅ | UserDetail | Już w resetPassword modal |

### 4.3 Zarządzanie Rolami (/admin/roles)
| Funkcja | Status | Komponent | Metoda |
|---------|--------|-----------|--------|
| CRUD dla ról | ✅ | RoleList | `saveRole()`, `deleteRole()` |
| Hierarchia ról | ✅ | RoleList | `getRoleUsageStatsProperty()` |
| Templates ról | ✅ | RoleList | `createFromTemplate()` |
| Permission matrix per rola | ✅ | RoleList | `getPermissionMatrixProperty()` |
| Blokada edycji/usuwania ról systemowych | ✅ | RoleList | Check w `deleteRole()` |

### 4.4 Macierz Uprawnień (/admin/permissions)
| Funkcja | Status | Komponent | Metoda |
|---------|--------|-----------|--------|
| Grid: uprawnienia × role | ✅ | PermissionMatrix | Computed properties |
| Module grouping | ✅ | PermissionMatrix | `getPermissionsByModuleProperty()` |
| Bulk operations | ✅ | PermissionMatrix | `executeBulkAction()` |
| Templates | ✅ | PermissionMatrix | `applyTemplate()` |
| Diff preview przed zapisem | ✅ | PermissionMatrix | `checkForConflictingUsers()` |

### 4.5 Monitor Sesji (/admin/sessions)
| Funkcja | Status | Komponent | Metoda |
|---------|--------|-----------|--------|
| Lista aktywnych sesji | ✅ | Sessions | `getSessionsProperty()` |
| Device/location info | ✅ | Sessions | `getDeviceIcon()`, location fields |
| Force logout pojedynczej sesji | ✅ | Sessions | `forceLogout()` |
| Force logout wszystkich oprócz admina | ❌ | Sessions | Dodać: `forceLogoutAllExceptAdmin()` |
| Security alerts | ✅ | Sessions | `detectSecurityIssues()` |
| wire:poll real-time | ⚠️ | Sessions | Sprawdzić implementację |

### 4.6 Logi Audytu (/admin/activity-log)
| Funkcja | Status | Komponent | Metoda |
|---------|--------|-----------|--------|
| Filtry: user, action, date range | ✅ | AuditLogs | Multiple filters |
| Diff viewer | ✅ | AuditLogs | `showLogDiff()` |
| Charts | ⚠️ | AuditLogs | `setViewMode('chart')` - weryfikacja |
| Suspicious detection | ✅ | AuditLogs | `detectSuspiciousActivity()` |
| Export do Excel/PDF | ✅ | AuditLogs | `exportLogs()` |

### 4.7 Dashboard Bezpieczeństwa (/admin/security) - NOWY
| Funkcja | Status | Komponent | Metoda |
|---------|--------|-----------|--------|
| Active sessions count | ✅ | SecurityDashboard | Stats metrics |
| Failed logins chart | ✅ | SecurityDashboard | `getFailedLoginsChartData()` |
| Security alerts list | ✅ | SecurityDashboard | `getAlertsProperty()` |
| Top attacking IPs | ✅ | SecurityDashboard | `topAttackingIps` |
| Locked out users | ✅ | SecurityDashboard | `lockedUsers` |
| Password expiration warnings | ✅ | SecurityDashboard | `expiringPasswords` |

---

## 🚨 BRAKUJĄCE ELEMENTY (KRYTYCZNE)

### A. Brakujący Model: AuditLog

**Problem:** Komponent `AuditLogs.php` importuje `App\Models\AuditLog` który NIE ISTNIEJE.

**Rozwiązanie:** Utworzyć model `app/Models/AuditLog.php`:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id', 'auditable_type', 'auditable_id', 'event',
        'old_values', 'new_values', 'ip_address', 'user_agent', 'source', 'comment'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable()
    {
        return $this->morphTo();
    }
}
```

### B. Brakujący Widok: role-list.blade.php

**Problem:** Komponent `RoleList.php` renderuje widok `livewire.admin.roles.role-list` który NIE ISTNIEJE.

**Rozwiązanie:** Utworzyć widok `resources/views/livewire/admin/roles/role-list.blade.php`

### C. Brakujące Policies

**Istniejące:** BasePolicy, UserPolicy, ProductPolicy, CategoryPolicy, BugReportPolicy

**Brakujące (wymagane do authorize()):**
1. `app/Policies/UserSessionPolicy.php` - dla Sessions.php
2. `app/Policies/RolePolicy.php` - dla RoleList.php (jeśli używa authorize)
3. `app/Policies/PermissionPolicy.php` - dla PermissionMatrix.php (jeśli używa authorize)

---

## 📝 PLAN IMPLEMENTACJI

### ETAP 0: Utworzenie Brakujących Elementów (PRZED ROUTES!)

**0.1 Model AuditLog:**
- Plik: `app/Models/AuditLog.php`
- Relacje: user(), auditable() (polymorphic)
- Casts: old_values, new_values → array

**0.2 Widok role-list.blade.php:**
- Plik: `resources/views/livewire/admin/roles/role-list.blade.php`
- Zawartość: tabela ról, modaly CRUD, comparison view, templates

**0.3 Policies (jeśli komponenty używają authorize):**
- `app/Policies/UserSessionPolicy.php`
- Zarejestrować w `AuthServiceProvider.php`

---

### ETAP 1: Routes (KRYTYCZNE)

**Plik:** `routes/web.php`

```php
// W grupie Route::prefix('admin')->name('admin.')->middleware(...)->group(function () {

// Users Management
Route::get('/users', \App\Http\Livewire\Admin\Users\UserList::class)
    ->name('users.index');
Route::get('/users/create', \App\Http\Livewire\Admin\Users\UserForm::class)
    ->name('users.create');
Route::get('/users/{user}', \App\Http\Livewire\Admin\Users\UserDetail::class)
    ->name('users.show');
Route::get('/users/{user}/edit', \App\Http\Livewire\Admin\Users\UserForm::class)
    ->name('users.edit');

// Sessions Management (już dodany)
Route::get('/sessions', \App\Http\Livewire\Admin\Sessions::class)
    ->name('sessions.index');

// Roles Management
Route::get('/roles', \App\Http\Livewire\Admin\Roles\RoleList::class)
    ->name('roles.index');

// Permissions Management
Route::get('/permissions', \App\Http\Livewire\Admin\Permissions\PermissionMatrix::class)
    ->name('permissions.index');

// Security Dashboard
Route::get('/security', \App\Http\Livewire\Admin\Security\SecurityDashboard::class)
    ->name('security.index');

// Audit Logs
Route::get('/activity-log', \App\Http\Livewire\Admin\AuditLogs::class)
    ->name('activity-log.index');
```

### ETAP 2: Sidebar Links

**Plik:** `resources/views/layouts/navigation.blade.php` (sekcja "Administracja")

Dodać linki:
1. Użytkownicy → `route('admin.users.index')`
2. Role → `route('admin.roles.index')`
3. Uprawnienia → `route('admin.permissions.index')`
4. Sesje → `route('admin.sessions.index')`
5. Bezpieczeństwo → `route('admin.security.index')`
6. Logi audytu → `route('admin.activity-log.index')`

**Plik:** `resources/views/layouts/admin.blade.php` (sekcja "System")

Zaktualizować/aktywować linki.

### ETAP 3: Uzupełnienia Funkcjonalności

#### 3.1 UserList - Dodać kolumnę "Aktywne sesje"
```php
// W komponencie
public function getUsersProperty()
{
    return User::query()
        ->withCount(['sessions' => fn($q) => $q->active()])
        // ...existing code
}
```

#### 3.2 UserList - Dodać bulk "Wyloguj zaznaczonych"
```php
public function bulkForceLogoutUsers()
{
    $users = User::whereIn('id', $this->selectedUsers)->get();
    foreach ($users as $user) {
        $user->sessions()->active()->each(fn($s) => $s->terminate('bulk_force_logout'));
    }
    $this->dispatch('notify', type: 'success', message: 'Wylogowano zaznaczonych użytkowników');
}
```

#### 3.3 UserDetail - Dodać "Wyloguj użytkownika"
```php
public function forceLogoutUser()
{
    $this->user->sessions()->active()->each(fn($s) => $s->terminate('force_admin'));
    $this->dispatch('notify', type: 'success', message: 'Użytkownik został wylogowany');
}
```

#### 3.4 Sessions - Dodać "Wyloguj wszystkich oprócz admina"
```php
public function forceLogoutAllExceptAdmin()
{
    $adminUserIds = User::role('Admin')->pluck('id');
    UserSession::active()
        ->whereNotIn('user_id', $adminUserIds)
        ->each(fn($s) => $s->terminate('bulk_force_logout'));
    $this->refreshStats();
}
```

#### 3.5 Sessions - Weryfikacja wire:poll
Sprawdzić czy `wire:poll.5s` jest w widoku blade.

### ETAP 4: Weryfikacja Widoków Blade

Sprawdzić istnienie:
- `resources/views/livewire/admin/users/user-list.blade.php`
- `resources/views/livewire/admin/users/user-form.blade.php`
- `resources/views/livewire/admin/users/user-detail.blade.php`
- `resources/views/livewire/admin/sessions.blade.php`
- `resources/views/livewire/admin/roles/role-list.blade.php`
- `resources/views/livewire/admin/permissions/permission-matrix.blade.php`
- `resources/views/livewire/admin/security/security-dashboard.blade.php`
- `resources/views/livewire/admin/audit-logs.blade.php`

### ETAP 5: Chrome DevTools Verification

Po każdym etapie:
1. Build: `npm run build`
2. Deploy: `pscp` + cache clear
3. Verify: Claude in Chrome screenshot
4. Report status

---

## 🎯 KOLEJNOŚĆ WDROŻENIA

| # | Zadanie | Priorytet | Zależności |
|---|---------|-----------|------------|
| **0.1** | Model AuditLog.php | 🔴 CRITICAL | - |
| **0.2** | Widok role-list.blade.php | 🔴 CRITICAL | - |
| **0.3** | Policy UserSessionPolicy (jeśli wymagane) | 🟡 HIGH | - |
| 1 | Routes w web.php | 🔴 CRITICAL | 0.1, 0.2 |
| 2 | Sidebar links (navigation.blade.php) | 🔴 CRITICAL | Routes |
| 3 | Sidebar links (admin.blade.php) | 🔴 CRITICAL | Routes |
| 4 | UserList: kolumna "Aktywne sesje" | 🟡 HIGH | - |
| 5 | UserList: bulk "Wyloguj zaznaczonych" | 🟡 HIGH | - |
| 6 | UserDetail: "Wyloguj użytkownika" | 🟡 HIGH | - |
| 7 | Sessions: "Wyloguj wszystkich oprócz admina" | 🟡 HIGH | - |
| 8 | Sessions: wire:poll verification | 🟡 HIGH | - |
| 9 | Chrome DevTools full verification | 🔴 CRITICAL | Wszystkie |

---

## 📁 PLIKI DO UTWORZENIA (NOWE)

1. **app/Models/AuditLog.php** - Model dla logów audytu
2. **resources/views/livewire/admin/roles/role-list.blade.php** - Widok zarządzania rolami
3. **app/Policies/UserSessionPolicy.php** - Policy dla sesji (opcjonalnie)

## 📁 PLIKI DO MODYFIKACJI

1. **routes/web.php** - Dodanie routes
2. **resources/views/layouts/navigation.blade.php** - Sidebar links
3. **resources/views/layouts/admin.blade.php** - Sidebar links (aktywacja disabled linków)
4. **app/Http/Livewire/Admin/Users/UserList.php** - Bulk logout, sessions count
5. **app/Http/Livewire/Admin/Users/UserDetail.php** - Force logout button
6. **app/Http/Livewire/Admin/Sessions.php** - Force logout all except admin
7. **resources/views/livewire/admin/sessions.blade.php** - wire:poll check
8. **app/Providers/AuthServiceProvider.php** - Rejestracja policies (opcjonalnie)

---

## ✅ WERYFIKACJA

Po implementacji sprawdzić:

1. **Routing:**
   - [ ] `/admin/users` → Lista użytkowników
   - [ ] `/admin/users/create` → Formularz nowego użytkownika
   - [ ] `/admin/users/{id}` → Szczegóły użytkownika
   - [ ] `/admin/sessions` → Monitor sesji
   - [ ] `/admin/roles` → Zarządzanie rolami
   - [ ] `/admin/permissions` → Macierz uprawnień
   - [ ] `/admin/security` → Dashboard bezpieczeństwa
   - [ ] `/admin/activity-log` → Logi audytu

2. **Sidebar:**
   - [ ] Wszystkie linki widoczne dla Admin
   - [ ] Hover states działają
   - [ ] Active state podświetla aktualną stronę

3. **Funkcjonalność:**
   - [ ] Filtry działają
   - [ ] Bulk operations działają
   - [ ] Real-time updates (wire:poll)
   - [ ] Export do Excel/CSV

4. **Chrome DevTools:**
   - [ ] Brak błędów w konsoli
   - [ ] HTTP 200 dla wszystkich zasobów
   - [ ] Screenshot wizualnie poprawny

---

## ⚠️ UWAGI

1. **DEV_AUTH_BYPASS:** Logowanie jest wyłączone w trybie dev - dostęp bezpośredni do URL
2. **Spatie Permission:** System ról/uprawnień jest gotowy i działający
3. **Security:** Mechanizmy bezpieczeństwa (brute force, lockout) są zaimplementowane
4. **Export PDF:** Skeleton implementacji - może wymagać uzupełnienia
