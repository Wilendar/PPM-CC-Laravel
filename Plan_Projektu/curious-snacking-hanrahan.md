# PLAN: Panel Zarządzania Użytkownikami i Sesjami PPM

**Data utworzenia:** 2026-01-21
**Status:** Do zatwierdzenia
**Priorytet:** Wysoki

---

## 1. PODSUMOWANIE ANALIZY ISTNIEJĄCEGO SYSTEMU
q
### 1.1 Co już istnieje (nie wymaga implementacji):

| Komponent | Status | Lokalizacja |
|-----------|--------|-------------|
| **Spatie Laravel Permission** | ✅ Zainstalowany | pakiet v6.0 |
| **User Model z HasRoles** | ✅ Kompletny | `app/Models/User.php` (704 linie) |
| **7-poziomowa hierarchia ról** | ✅ Skonfigurowana | Admin→Manager→Editor→Warehouseman→Salesperson→Claims→User |
| **49 granularnych uprawnień** | ✅ Zdefiniowanych | products.*, categories.*, media.*, prices.*, users.* |
| **UserList Livewire** | ✅ Kompletny | `app/Http/Livewire/Admin/Users/UserList.php` |
| **UserForm Livewire** | ✅ Kompletny | `app/Http/Livewire/Admin/Users/UserForm.php` (654 linie) |
| **UserDetail Livewire** | ✅ Kompletny | `app/Http/Livewire/Admin/Users/UserDetail.php` |
| **RoleList Livewire** | ✅ Kompletny | `app/Http/Livewire/Admin/Roles/RoleList.php` |
| **PermissionMatrix Livewire** | ✅ Kompletny | `app/Http/Livewire/Admin/Permissions/PermissionMatrix.php` |
| **Sessions Livewire** | ✅ UI gotowe | `app/Http/Livewire/Admin/Sessions.php` (688 linii) |
| **AuditLogs Livewire** | ✅ Kompletny | `app/Http/Livewire/Admin/AuditLogs.php` |
| **BasePolicy z hierarchią** | ✅ Kompletny | `app/Policies/BasePolicy.php` |
| **OAuth Security Service** | ✅ Kompletny | `app/Services/OAuthSecurityService.php` |

### 1.2 BRAKI KRYTYCZNE (do implementacji):

| Element | Problem | Priorytet |
|---------|---------|-----------|
| **Model UserSession** | ❌ Referencjonowany w Sessions.php ale NIE ISTNIEJE | KRYTYCZNY |
| **Tabela user_sessions** | ❌ Migracja nie istnieje | KRYTYCZNY |
| **config/session.php** | ❌ Brak bezpiecznej konfiguracji sesji | KRYTYCZNY |
| **Routes dla User Management** | ❌ Tylko placeholdery | WYSOKI |
| **Blade views** | ❌ Niektóre brakują | WYSOKI |
| **Model LoginHistory** | ❌ Brak | ŚREDNI |
| **Model PasswordPolicy** | ❌ Brak | ŚREDNI |
| **2FA (Two-Factor Auth)** | ❌ Brak | OPCJONALNY |
| **GDPR Compliance** | ❌ Brak | OPCJONALNY |

---

## 2. ARCHITEKTURA ROZWIĄZANIA

### 2.1 Diagram relacji bazy danych

```
+-------------------+       +-------------------+       +-------------------+
|      users        |<-+    |   user_sessions   |       |    audit_logs     |
+-------------------+  |    +-------------------+       +-------------------+
| id (PK)           |  |    | id (PK)           |       | id (PK)           |
| email             |  +----| user_id (FK)      |       | user_id (FK)------+
| password          |       | session_id        |       | auditable_type    |
| is_active         |       | ip_address        |       | auditable_id      |
| force_password_ch |       | user_agent        |       | event             |
| locked_until      |       | device_type       |       | old_values (JSON) |
| password_policy_id|       | browser           |       | new_values (JSON) |
+-------------------+       | is_active         |       | ip_address        |
        |                   | is_suspicious     |       +-------------------+
        v                   | last_activity     |
+-------------------+       | ended_at          |       +-------------------+
|  password_history |       | end_reason        |       |   login_attempts  |
+-------------------+       +-------------------+       +-------------------+
| id                |                                   | id                |
| user_id (FK)      |       +-------------------+       | email             |
| password_hash     |       | password_policies |       | ip_address        |
| created_at        |       +-------------------+       | success           |
+-------------------+       | id                |       | failure_reason    |
                            | name              |       | attempted_at      |
                            | min_length        |       +-------------------+
                            | require_uppercase |
                            | expire_days       |       +-------------------+
                            | lockout_attempts  |       |  security_alerts  |
                            +-------------------+       +-------------------+
                                                        | id                |
                                                        | alert_type        |
                                                        | severity          |
                                                        | related_user_id   |
                                                        | acknowledged      |
                                                        +-------------------+
```

### 2.2 Nowe pliki do utworzenia

| Typ | Ścieżka | Opis |
|-----|---------|------|
| Migration | `database/migrations/XXXX_create_user_sessions_table.php` | Tabela sesji użytkowników |
| Migration | `database/migrations/XXXX_create_login_attempts_table.php` | Historia prób logowania |
| Migration | `database/migrations/XXXX_create_password_policies_table.php` | Polityki haseł |
| Migration | `database/migrations/XXXX_create_password_history_table.php` | Historia haseł |
| Migration | `database/migrations/XXXX_create_security_alerts_table.php` | Alerty bezpieczeństwa |
| Migration | `database/migrations/XXXX_add_security_fields_to_users_table.php` | Nowe pola w users |
| Model | `app/Models/UserSession.php` | Model sesji |
| Model | `app/Models/LoginHistory.php` | Model historii logowań |
| Model | `app/Models/PasswordPolicy.php` | Model polityki haseł |
| Model | `app/Models/SecurityAlert.php` | Model alertów |
| Service | `app/Services/User/SessionManagementService.php` | Zarządzanie sesjami |
| Service | `app/Services/User/PasswordPolicyService.php` | Zarządzanie hasłami |
| Service | `app/Services/Security/SecurityMonitoringService.php` | Monitoring bezpieczeństwa |
| Middleware | `app/Http/Middleware/TrackUserSessionMiddleware.php` | Śledzenie sesji |
| Middleware | `app/Http/Middleware/ForcePasswordChangeMiddleware.php` | Wymuszenie zmiany hasła |
| Config | `config/session.php` | Bezpieczna konfiguracja sesji |
| Config | `config/security.php` | Konfiguracja bezpieczeństwa |
| View | `resources/views/livewire/admin/sessions.blade.php` | Widok monitora sesji |
| Livewire | `app/Http/Livewire/Admin/Security/SecurityDashboard.php` | Dashboard bezpieczeństwa |
| Livewire | `app/Http/Livewire/Admin/Users/OnlineUsersWidget.php` | Widget online users |

---

## 3. SZCZEGÓŁOWY PLAN IMPLEMENTACJI

### FAZA 1: Infrastruktura Danych [KRYTYCZNA]
**Szacowany czas: 2-3 dni**

#### 1.1 Migracja: create_user_sessions_table
```php
Schema::create('user_sessions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('session_id', 255)->unique();
    $table->string('ip_address', 45);
    $table->text('user_agent')->nullable();
    $table->enum('device_type', ['desktop', 'mobile', 'tablet', 'unknown'])->default('unknown');
    $table->string('browser', 100)->nullable();
    $table->string('browser_version', 50)->nullable();
    $table->string('os', 100)->nullable();
    $table->string('country', 100)->nullable();
    $table->string('city', 100)->nullable();
    $table->boolean('is_active')->default(true);
    $table->boolean('is_suspicious')->default(false);
    $table->timestamp('last_activity')->useCurrent();
    $table->timestamp('ended_at')->nullable();
    $table->enum('end_reason', [
        'logout', 'timeout', 'force_logout_admin',
        'concurrent_limit', 'security_block', 'password_change'
    ])->nullable();
    $table->timestamps();

    $table->index(['user_id', 'is_active']);
    $table->index('last_activity');
    $table->index('ip_address');
});
```

#### 1.2 Migracja: create_login_attempts_table
```php
Schema::create('login_attempts', function (Blueprint $table) {
    $table->id();
    $table->string('email', 255);
    $table->string('ip_address', 45);
    $table->text('user_agent')->nullable();
    $table->boolean('success')->default(false);
    $table->string('failure_reason', 100)->nullable();
    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
    $table->timestamp('attempted_at')->useCurrent();

    $table->index(['email', 'attempted_at']);
    $table->index(['ip_address', 'attempted_at']);
});
```

#### 1.3 Migracja: create_password_policies_table
```php
Schema::create('password_policies', function (Blueprint $table) {
    $table->id();
    $table->string('name', 100);
    $table->integer('min_length')->default(8);
    $table->integer('max_length')->default(128);
    $table->boolean('require_uppercase')->default(true);
    $table->boolean('require_lowercase')->default(true);
    $table->boolean('require_numbers')->default(true);
    $table->boolean('require_symbols')->default(false);
    $table->integer('expire_days')->default(0); // 0 = never
    $table->integer('history_count')->default(3);
    $table->integer('lockout_attempts')->default(5);
    $table->integer('lockout_duration_minutes')->default(30);
    $table->boolean('is_default')->default(false);
    $table->timestamps();
});
```

#### 1.4 Migracja: add_security_fields_to_users
```php
Schema::table('users', function (Blueprint $table) {
    $table->boolean('force_password_change')->default(false)->after('password');
    $table->timestamp('password_changed_at')->nullable()->after('force_password_change');
    $table->foreignId('password_policy_id')->nullable()->constrained('password_policies')->nullOnDelete();
    $table->integer('failed_login_attempts')->default(0);
    $table->timestamp('locked_until')->nullable();
    $table->json('password_history')->nullable(); // Encrypted hashes
});
```

#### 1.5 Model UserSession
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSession extends Model
{
    protected $fillable = [
        'user_id', 'session_id', 'ip_address', 'user_agent',
        'device_type', 'browser', 'browser_version', 'os',
        'country', 'city', 'is_active', 'is_suspicious',
        'last_activity', 'ended_at', 'end_reason'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_suspicious' => 'boolean',
        'last_activity' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function terminate(string $reason): void
    {
        $this->update([
            'is_active' => false,
            'ended_at' => now(),
            'end_reason' => $reason,
        ]);
    }
}
```

---

### FAZA 2: Services i Middleware [WYSOKI PRIORYTET]
**Szacowany czas: 2 dni**

#### 2.1 SessionManagementService
```php
namespace App\Services\User;

class SessionManagementService
{
    public function createSession(User $user, Request $request): UserSession;
    public function terminateSession(UserSession $session, string $reason): void;
    public function terminateAllUserSessions(User $user, ?string $exceptSessionId = null): int;
    public function terminateAllSessionsExceptAdmin(): int;
    public function getActiveSessions(): Collection;
    public function getUserSessions(User $user): Collection;
    public function detectSuspiciousSessions(): Collection;
    public function updateSessionActivity(string $sessionId): void;
}
```

#### 2.2 PasswordPolicyService
```php
namespace App\Services\User;

class PasswordPolicyService
{
    public function validatePassword(string $password, ?PasswordPolicy $policy = null): array;
    public function requirePasswordChange(User $user): void;
    public function changePassword(User $user, string $newPassword): void;
    public function isPasswordExpired(User $user): bool;
    public function resetPassword(User $user): string; // returns temp password
    public function enforcePasswordHistory(User $user, string $newPassword): bool;
}
```

#### 2.3 TrackUserSessionMiddleware
```php
namespace App\Http\Middleware;

class TrackUserSessionMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $sessionService = app(SessionManagementService::class);
            $sessionService->updateSessionActivity(session()->getId());
        }
        return $next($request);
    }
}
```

#### 2.4 ForcePasswordChangeMiddleware
```php
namespace App\Http\Middleware;

class ForcePasswordChangeMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && Auth::user()->force_password_change) {
            if (!$request->is('password/change*', 'logout')) {
                return redirect()->route('password.change')
                    ->with('warning', 'Musisz zmienić hasło przed kontynuowaniem.');
            }
        }
        return $next($request);
    }
}
```

---

### FAZA 3: Aktywacja Routes i Widoków [WYSOKI PRIORYTET]
**Szacowany czas: 1-2 dni**

#### 3.1 Routes w routes/web.php
```php
Route::middleware(['auth', 'role:Admin'])->prefix('admin')->name('admin.')->group(function () {
    // User Management
    Route::get('/users', Users\UserList::class)->name('users.index');
    Route::get('/users/create', Users\UserForm::class)->name('users.create');
    Route::get('/users/{user}', Users\UserDetail::class)->name('users.show');
    Route::get('/users/{user}/edit', Users\UserForm::class)->name('users.edit');

    // Role Management
    Route::get('/roles', Roles\RoleList::class)->name('roles.index');

    // Permission Matrix
    Route::get('/permissions', Permissions\PermissionMatrix::class)->name('permissions.index');

    // Session Monitor
    Route::get('/sessions', Sessions::class)->name('sessions.index');

    // Audit Logs
    Route::get('/activity-log', AuditLogs::class)->name('activity-log.index');

    // Security Dashboard
    Route::get('/security', Security\SecurityDashboard::class)->name('security.index');
});
```

#### 3.2 Blade View: sessions.blade.php (weryfikacja/utworzenie)

---

### FAZA 4: Rozszerzenie istniejących komponentów
**Szacowany czas: 2 dni**

#### 4.1 Rozszerzenie UserList.php
- Dodać kolumnę "Aktywne sesje"
- Dodać akcję "Wyloguj wszystkie sesje"
- Dodać filtr "Wymaga zmiany hasła"
- Dodać bulk action "Wyloguj zaznaczonych"

#### 4.2 Rozszerzenie UserForm.php
- Checkbox "Wymuś zmianę hasła przy następnym logowaniu"
- Select "Polityka haseł"
- Przycisk "Resetuj hasło i wyślij email"
- Przycisk "Wyloguj użytkownika"

#### 4.3 Rozszerzenie Sessions.php
- Dodać `wire:poll.10s` dla real-time updates
- Integracja z modelem UserSession
- Przycisk "Wyloguj wszystkich oprócz adminów"

---

### FAZA 5: Nowe komponenty UI
**Szacowany czas: 3-4 dni**

#### 5.1 SecurityDashboard.php
```php
namespace App\Http\Livewire\Admin\Security;

class SecurityDashboard extends Component
{
    public int $activeSessionsCount;
    public int $failedLoginsToday;
    public int $suspiciousActivities;
    public Collection $recentAlerts;
    public Collection $topAttackingIPs;
    public Collection $lockedOutUsers;

    public function mount(): void;
    public function refreshStats(): void;
    public function acknowledgeAlert(int $alertId): void;
    public function render(): View;
}
```

#### 5.2 OnlineUsersWidget.php
```php
namespace App\Http\Livewire\Admin\Users;

class OnlineUsersWidget extends Component
{
    public Collection $onlineUsers;
    public int $refreshInterval = 30;

    public function refreshOnlineUsers(): void;
    public function forceLogoutUser(int $userId): void;
    public function render(): View;
}
```

#### 5.3 PasswordPolicyManager.php
```php
namespace App\Http\Livewire\Admin\Security;

class PasswordPolicyManager extends Component
{
    public Collection $policies;
    public ?PasswordPolicy $editingPolicy = null;
    public bool $showModal = false;

    public function createPolicy(): void;
    public function editPolicy(int $id): void;
    public function savePolicy(): void;
    public function deletePolicy(int $id): void;
    public function setAsDefault(int $id): void;
    public function render(): View;
}
```

---

### FAZA 6: Security Enhancements [OPCJONALNA]
**Szacowany czas: 3-4 dni**

#### 6.1 IP Restrictions
- Tabela `ip_restrictions` (whitelist/blacklist)
- UI do zarządzania listą IP
- Middleware `IpRestrictionMiddleware`

#### 6.2 Security Alerts System
- Tabela `security_alerts`
- Automatyczne wykrywanie:
  - Brute force attacks
  - Credential stuffing
  - Unusual login patterns
- Email notifications do adminów

#### 6.3 config/session.php
```php
return [
    'driver' => 'database',
    'lifetime' => 120,
    'expire_on_close' => false,
    'encrypt' => true,
    'secure' => true,
    'http_only' => true,
    'same_site' => 'lax',

    // PPM Custom
    'concurrent_limit' => 3,
    'idle_timeout' => 30,
];
```

---

### FAZA 7: GDPR Compliance [OPCJONALNA]
**Szacowany czas: 2-3 dni**

- GDPRService: exportUserData(), anonymizeUser(), deleteUserData()
- Tabela `user_consents`
- UI do eksportu danych użytkownika
- Mechanizm anonimizacji

---

## 4. FUNKCJONALNOŚCI PANELU ADMINA

### 4.1 Lista Użytkowników (/admin/users)
- [x] Tabela z sortowaniem, filtrowaniem, paginacją
- [x] Avatar, nazwa, email, rola, status
- [ ] Kolumna "Aktywne sesje" - **DO DODANIA**
- [x] Bulk actions (aktywuj, dezaktywuj)
- [ ] Bulk action "Wyloguj zaznaczonych" - **DO DODANIA**
- [x] Impersonation (zaloguj jako)

### 4.2 Szczegóły Użytkownika (/admin/users/{id})
- [x] Edit-in-place dla podstawowych pól
- [x] Activity timeline
- [ ] Reset hasła przez admina - **DO WERYFIKACJI**
- [x] Widok uprawnień
- [ ] Przycisk "Wyloguj użytkownika" - **DO DODANIA**
- [ ] Przycisk "Wymuś zmianę hasła" - **DO DODANIA**

### 4.3 Zarządzanie Rolami (/admin/roles)
- [x] CRUD dla ról
- [x] Hierarchia ról
- [x] Templates ról
- [x] Permission matrix per rola
- [ ] System ról - blokada edycji/usuwania - **DO WERYFIKACJI**

### 4.4 Macierz Uprawnień (/admin/permissions)
- [x] Grid: uprawnienia × role
- [x] Module grouping
- [x] Bulk operations
- [x] Templates
- [ ] Diff preview przed zapisem - **DO WERYFIKACJI**

### 4.5 Monitor Sesji (/admin/sessions)
- [x] Lista aktywnych sesji
- [x] Device/location info
- [x] Force logout pojedynczej sesji
- [ ] Force logout wszystkich oprócz admina - **DO DODANIA**
- [x] Security alerts
- [ ] wire:poll real-time - **DO DODANIA**

### 4.6 Logi Audytu (/admin/activity-log)
- [x] Filtry: user, action, date range
- [x] Diff viewer
- [x] Charts
- [x] Suspicious detection
- [ ] Export do Excel/PDF - **DO IMPLEMENTACJI**

### 4.7 Dashboard Bezpieczeństwa (/admin/security) - **NOWY**
- [ ] Active sessions count
- [ ] Failed logins chart
- [ ] Security alerts list
- [ ] Top attacking IPs
- [ ] Locked out users
- [ ] Password expiration warnings

---

## 5. SEEDER: PasswordPolicySeeder

```php
public function run(): void
{
    PasswordPolicy::create([
        'name' => 'Standard',
        'min_length' => 8,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_symbols' => false,
        'expire_days' => 0,
        'history_count' => 3,
        'lockout_attempts' => 5,
        'lockout_duration_minutes' => 30,
        'is_default' => true,
    ]);

    PasswordPolicy::create([
        'name' => 'Wysokie bezpieczeństwo (Admini)',
        'min_length' => 12,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_symbols' => true,
        'expire_days' => 90,
        'history_count' => 5,
        'lockout_attempts' => 3,
        'lockout_duration_minutes' => 60,
        'is_default' => false,
    ]);
}
```

---

## 6. WERYFIKACJA

### 6.1 Testy manualne
- [ ] Utworzenie użytkownika z przypisaniem roli
- [ ] Zmiana roli użytkownika
- [ ] Reset hasła przez admina
- [ ] Wymuszenie zmiany hasła
- [ ] Force logout pojedynczej sesji
- [ ] Force logout wszystkich oprócz admina
- [ ] Tworzenie nowej roli z uprawnieniami
- [ ] Macierz uprawnień - zmiana i zapis

### 6.2 Chrome DevTools Verification
- [ ] Navigate do /admin/users
- [ ] Screenshot UI
- [ ] Console errors check
- [ ] Network requests check
- [ ] Navigate do /admin/sessions
- [ ] Real-time updates verification

---

## 7. PLIKI KRYTYCZNE DO MODYFIKACJI

| Plik | Akcja | Priorytet |
|------|-------|-----------|
| `routes/web.php` | Aktywacja routes | KRYTYCZNY |
| `app/Http/Livewire/Admin/Sessions.php` | Integracja UserSession | KRYTYCZNY |
| `app/Models/User.php` | Nowe pola security | WYSOKI |
| `app/Http/Livewire/Admin/Users/UserList.php` | Rozszerzenie | WYSOKI |
| `app/Http/Livewire/Admin/Users/UserForm.php` | Rozszerzenie | WYSOKI |
| `app/Http/Kernel.php` | Rejestracja middleware | WYSOKI |

---

## 8. PODSUMOWANIE HARMONOGRAMU

| Faza | Opis | Czas | Status |
|------|------|------|--------|
| 1 | Infrastruktura danych (migracje, modele) | 2-3 dni | ❌ |
| 2 | Services i Middleware | 2 dni | ❌ |
| 3 | Routes i widoki | 1-2 dni | ❌ |
| 4 | Rozszerzenie istniejących komponentów | 2 dni | ❌ |
| 5 | Nowe komponenty UI | 3-4 dni | ❌ |
| 6 | Security enhancements (opcjonalne) | 3-4 dni | ❌ |
| 7 | GDPR compliance (opcjonalne) | 2-3 dni | ❌ |

**CAŁKOWITY SZACOWANY CZAS:** 10-14 dni (fazy 1-5 obowiązkowe)

---

## 9. RYZYKA I MITYGACJA

| Ryzyko | Prawdopodobieństwo | Wpływ | Mitygacja |
|--------|-------------------|-------|-----------|
| Brak modelu UserSession blokuje Sessions.php | Wysokie | Krytyczny | Priorytet w Fazie 1 |
| Konflikt z istniejącym audit_logs | Średnie | Średni | Migracja ALTER zamiast DROP |
| Performance przy dużej liczbie sesji | Niskie | Średni | Indeksy + cache Redis |
| OAuth compatibility | Niskie | Średni | Zachowanie OAuthSecurityService |

---

*Plan przygotowany na podstawie analizy 3 agentów planowania z perspektywami: Architektura Laravel, Komponenty Livewire UI, Security Best Practices*
