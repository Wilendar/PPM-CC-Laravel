# RÓŻNICE: Local Development vs Production (Hostido)

**Data:** 2025-12-04
**Projekt:** PPM-CC-Laravel

---

## 📊 PORÓWNANIE ŚRODOWISK

| Aspekt | Local (Windows 11) | Production (Hostido) |
|--------|-------------------|---------------------|
| **OS** | Windows 11 | Linux (Shared Hosting) |
| **PHP** | 8.3.25 (CLI) | 8.3.23 (FPM) |
| **Laravel** | 11.46.1 | 11.46.1 |
| **Composer** | 2.x (local) | 2.8.5 (preinstalowany) |
| **Node.js/npm** | ✅ v22.17.1 / 11.5.1 | ❌ NIE DOSTĘPNE |
| **Vite** | ✅ Lokalnie | ❌ Brak (tylko zbudowane pliki) |
| **MySQL** | Local MySQL 8.0+ | MariaDB 10.11.13 |
| **Redis** | Opcjonalnie | ❌ NIE DOSTĘPNE |
| **Queue Driver** | `database` lub `redis` | `database` (WYMAGANE) |
| **Cache Driver** | `database` lub `redis` | `database` (WYMAGANE) |
| **SSH Access** | N/A | ✅ Port 64321 |
| **Root Path** | `D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel` | `/domains/ppm.mpptrade.pl/public_html/` |

---

## 🔧 KONFIGURACJA .env

### Local (.env)

```env
# ENVIRONMENT
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# LOGGING
LOG_LEVEL=debug

# DATABASE
DB_HOST=127.0.0.1
DB_DATABASE=ppm_cc_laravel_local
DB_USERNAME=root
DB_PASSWORD=

# QUEUE & CACHE
QUEUE_CONNECTION=database
CACHE_STORE=database

# MAIL
MAIL_MAILER=log

# DEVELOPMENT TOOLS
TELESCOPE_ENABLED=true
DEBUGBAR_ENABLED=true

# PRESTASHOP API
PRESTASHOP_RATE_LIMITING_ENABLED=false
PRESTASHOP_DETAILED_LOGGING=true
PRESTASHOP_CATEGORY_PREVIEW_EXPIRATION=24
```

### Production (.env na Hostido)

```env
# ENVIRONMENT
APP_ENV=production
APP_DEBUG=false
APP_URL=https://ppm.mpptrade.pl

# LOGGING
LOG_LEVEL=error

# DATABASE
DB_HOST=localhost
DB_DATABASE=host379076_ppm
DB_USERNAME=host379076_ppm
DB_PASSWORD=[production_password]

# QUEUE & CACHE (MUST be database!)
QUEUE_CONNECTION=database
CACHE_STORE=database

# MAIL
MAIL_MAILER=smtp
MAIL_HOST=[smtp_host]

# DEVELOPMENT TOOLS (DISABLED!)
TELESCOPE_ENABLED=false
DEBUGBAR_ENABLED=false

# PRESTASHOP API
PRESTASHOP_RATE_LIMITING_ENABLED=true
PRESTASHOP_DETAILED_LOGGING=false
PRESTASHOP_CATEGORY_PREVIEW_EXPIRATION=1
```

---

## 🚀 WORKFLOW RÓŻNICE

### Local Development

```powershell
# 1. Code changes w IDE
# 2. Vite hot reload (jeśli npm run dev)
# 3. Lub npm run build
# 4. Testowanie na localhost:8000
# 5. Commit do Git
```

**Cechy:**
- ✅ Hot reload (Vite dev server)
- ✅ Instant feedback
- ✅ Pełne narzędzia debugowania (Telescope, Debugbar)
- ✅ Verbose logging
- ✅ Database driver dla queue/cache (bez Redis)

### Production Deployment

```powershell
# 1. Local: npm run build
# 2. Local: Copy manifest do ROOT
# 3. Upload ALL assets + manifest przez pscp
# 4. Upload PHP files przez pscp
# 5. SSH: php artisan cache:clear
# 6. Verification przez Chrome DevTools MCP
```

**Cechy:**
- ❌ Brak hot reload
- ❌ Brak Node.js/npm (static assets only)
- ❌ Brak development tools
- ✅ Minimal logging (production)
- ✅ Database driver WYMAGANY (brak Redis)

---

## 📦 BUILD & ASSETS

### Local Build Process

```powershell
# Build assets lokalnie
npm run build

# Output: public/build/
# ├── assets/
# │   ├── app-[hash].css
# │   ├── app-[hash].js
# │   └── ...
# └── .vite/manifest.json

# ⚠️ KRYTYCZNE: Skopiuj manifest do ROOT
Copy-Item "public/build/.vite/manifest.json" "public/build/manifest.json" -Force
```

### Production Upload

```powershell
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

# ⚠️ KRYTYCZNE: Upload WSZYSTKICH assets (Vite regeneruje hashe!)
pscp -i $HostidoKey -P 64321 -r "public/build/assets/*" `
  host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/public/build/assets/

# Upload manifest do ROOT
pscp -i $HostidoKey -P 64321 "public/build/.vite/manifest.json" `
  host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/public/build/manifest.json

# Clear cache
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php artisan cache:clear && php artisan config:clear && php artisan view:clear"
```

**⚠️ KRYTYCZNE RÓŻNICE:**
- Local: Vite może działać w dev mode (`npm run dev`)
- Production: TYLKO zbudowane static files (no Vite runtime!)
- Local: Manifest w `.vite/manifest.json` jest OK
- Production: Manifest MUSI być w ROOT `manifest.json`

---

## 🗄️ BAZA DANYCH

### Local Database

```sql
-- Nazwa: ppm_cc_laravel_local
-- Host: 127.0.0.1
-- User: root (lub custom)
-- Password: (puste lub custom)

-- Tworzenie:
CREATE DATABASE ppm_cc_laravel_local
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

**Cechy:**
- ✅ Pełny dostęp (root)
- ✅ Możliwość drop/recreate
- ✅ `migrate:fresh` bez ograniczeń
- ✅ Test data (seeders)

### Production Database

```sql
-- Nazwa: host379076_ppm
-- Host: localhost (internal)
-- User: host379076_ppm
-- Password: [production_password]

-- Brak możliwości drop/create database
-- Tylko zarządzanie tabelami
```

**Cechy:**
- ⚠️ Ograniczony dostęp (dedicated user)
- ❌ Brak możliwości drop database
- ⚠️ Ostrożność z `migrate:fresh` (production data!)
- ❌ Brak test data

---

## 🔄 QUEUE SYSTEM

### Local Queue

```env
QUEUE_CONNECTION=database
```

```powershell
# Manual worker (development)
php artisan queue:work --verbose

# Lub Redis (jeśli zainstalowany)
QUEUE_CONNECTION=redis
```

**Cechy:**
- ✅ Database fallback (najprostsze)
- ✅ Verbose output
- ✅ Easy debugging
- ⚠️ Manual start/stop

### Production Queue

```env
QUEUE_CONNECTION=database  # WYMAGANE (brak Redis!)
```

```bash
# Cron job (every minute)
* * * * * cd /domains/ppm.mpptrade.pl/public_html && php artisan schedule:run >> /dev/null 2>&1

# Lub manual trigger przez SSH
php artisan queue:work --daemon --tries=3 --timeout=300
```

**Cechy:**
- ✅ Database driver (WYMAGANE)
- ❌ Brak Redis
- ⚠️ Requires cron job lub manual trigger
- ⚠️ Monitoring przez SSH

---

## 💾 CACHE SYSTEM

### Local Cache

```env
CACHE_STORE=database
CACHE_PREFIX=ppm_local_
```

**Cechy:**
- ✅ Database fallback
- ✅ Easy clear (`php artisan cache:clear`)
- ⚠️ Slower than Redis
- ✅ Zero configuration

### Production Cache

```env
CACHE_STORE=database  # WYMAGANE (brak Redis!)
CACHE_PREFIX=
```

**Cechy:**
- ✅ Database driver (WYMAGANE)
- ❌ Brak Redis
- ⚠️ Requires manual clear po deployment
- ⚠️ Monitoring przez SSH

---

## 📝 LOGGING

### Local Logging

```env
LOG_LEVEL=debug
PRESTASHOP_DETAILED_LOGGING=true
PRESTASHOP_LOG_API_REQUESTS=true
```

**Output:**
- `storage/logs/laravel.log` (verbose)
- Telescope (request tracking)
- Debugbar (on-page debugging)

**Cechy:**
- ✅ Full visibility
- ✅ Debug wszystkich requestów
- ✅ N+1 query detection
- ✅ Exception stack traces

### Production Logging

```env
LOG_LEVEL=error
PRESTASHOP_DETAILED_LOGGING=false
PRESTASHOP_LOG_API_REQUESTS=false
```

**Output:**
- `storage/logs/laravel.log` (errors only)
- No Telescope
- No Debugbar

**Cechy:**
- ✅ Minimal footprint
- ⚠️ Only errors logged
- ⚠️ Requires SSH access dla log review
- ⚠️ Manual monitoring

**Monitoring production logs:**

```powershell
# Tail recent logs
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "tail -n 50 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log"

# Search errors
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "grep 'ERROR' domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log | tail -n 20"
```

---

## 🔒 SECURITY

### Local (Relaxed)

```env
APP_DEBUG=true
TELESCOPE_ENABLED=true
DEBUGBAR_ENABLED=true
PRESTASHOP_RATE_LIMITING_ENABLED=false
```

**Cechy:**
- ✅ Full error details
- ✅ Debug tools enabled
- ✅ No rate limiting
- ⚠️ TYLKO dla localhost!

### Production (Strict)

```env
APP_DEBUG=false
TELESCOPE_ENABLED=false
DEBUGBAR_ENABLED=false
PRESTASHOP_RATE_LIMITING_ENABLED=true
```

**Cechy:**
- ✅ No debug info exposure
- ✅ No debug tools
- ✅ Rate limiting enabled
- ✅ Error pages generic

---

## 🧪 TESTING

### Local Testing

```powershell
# PHPUnit
php artisan test

# Specific test
php artisan test --filter=ProductTest

# PHPStan
composer run phpstan

# PHP CS Fixer
composer run cs-fix

# Quality (all)
composer run quality
```

### Production Testing

```bash
# ❌ NIGDY nie uruchamiaj testów na production!
# Testy mogą modyfikować dane!

# Verification:
# - Chrome DevTools MCP (UI/CSS)
# - Manual testing
# - Monitoring logs
```

---

## 📂 FILE STRUCTURE

### Local Structure

```
D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\
├── app/
├── config/
├── database/
├── node_modules/         ✅ Local only
├── public/
│   └── build/
│       ├── assets/
│       └── .vite/        ✅ Vite output
├── resources/
│   ├── css/
│   └── js/
├── storage/
├── vendor/
├── .env                  ⚠️ Local config
├── composer.json
├── package.json          ✅ Local only
├── vite.config.js        ✅ Local only
└── ...
```

### Production Structure

```
/domains/ppm.mpptrade.pl/public_html/
├── app/
├── config/
├── database/
├── public/               ⚠️ Web root!
│   └── build/
│       ├── assets/       ✅ Static files
│       └── manifest.json ⚠️ ROOT location!
├── resources/
│   ├── css/
│   └── js/
├── storage/
├── vendor/
├── .env                  ⚠️ Production config
└── ...

# ❌ NIE ISTNIEJĄ:
# - node_modules/
# - package.json
# - vite.config.js
# - public/build/.vite/
```

---

## 🚨 COMMON PITFALLS

### 1. Manifest Location

**❌ BŁĄD:**
```
Local build → Deploy public/build/.vite/manifest.json → Production 404
```

**✅ ROZWIĄZANIE:**
```powershell
# Local: Copy manifest to ROOT
Copy-Item "public/build/.vite/manifest.json" "public/build/manifest.json"

# Deploy: Upload ROOT manifest
pscp [...] "public/build/manifest.json" [...]:public/build/manifest.json
```

### 2. Incomplete Asset Upload

**❌ BŁĄD:**
```
Local build → Deploy only changed files → Production CSS missing
```

**✅ ROZWIĄZANIE:**
```powershell
# ZAWSZE upload WSZYSTKICH assets (Vite regeneruje hashe!)
pscp -r "public/build/assets/*" [...]:public/build/assets/
```

### 3. Cache Not Cleared

**❌ BŁĄD:**
```
Local build → Deploy → Production shows old styles
```

**✅ ROZWIĄZANIE:**
```bash
# ZAWSZE clear cache po deployment
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### 4. Redis Configuration

**❌ BŁĄD:**
```env
# Production .env
QUEUE_CONNECTION=redis  # ❌ Redis nie działa na Hostido!
CACHE_STORE=redis      # ❌ Redis nie działa na Hostido!
```

**✅ ROZWIĄZANIE:**
```env
# Production .env (WYMAGANE!)
QUEUE_CONNECTION=database
CACHE_STORE=database
```

### 5. Development Tools on Production

**❌ BŁĄD:**
```env
# Production .env
APP_DEBUG=true           # ❌ Security risk!
TELESCOPE_ENABLED=true   # ❌ Performance hit!
```

**✅ ROZWIĄZANIE:**
```env
# Production .env
APP_DEBUG=false
TELESCOPE_ENABLED=false
DEBUGBAR_ENABLED=false
```

---

## 📋 DEPLOYMENT CHECKLIST

### Pre-Deployment (Local)

- [ ] `npm run build` (fresh build)
- [ ] Copy manifest to ROOT: `public/build/manifest.json`
- [ ] Verify manifest hashes: `Get-Content public/build/manifest.json`
- [ ] Test locally: `php artisan serve`
- [ ] Clear local cache: `php artisan cache:clear`
- [ ] Commit changes to Git

### Deployment (Upload)

- [ ] Upload ALL assets: `pscp -r public/build/assets/*`
- [ ] Upload ROOT manifest: `pscp public/build/manifest.json`
- [ ] Upload PHP files: `pscp app/...` `pscp config/...`
- [ ] Upload views: `pscp resources/views/...`
- [ ] Verify upload (HTTP 200): `curl -I https://ppm.mpptrade.pl/public/build/assets/app-X.css`

### Post-Deployment (Production)

- [ ] SSH clear cache: `php artisan cache:clear`
- [ ] SSH clear config: `php artisan config:clear`
- [ ] SSH clear views: `php artisan view:clear`
- [ ] Verify manifest: `cat public/build/manifest.json`
- [ ] Chrome DevTools verification (UI/CSS)
- [ ] Check console errors (DevTools)
- [ ] Test functionality manually
- [ ] Monitor logs: `tail storage/logs/laravel.log`

---

## 🔗 RELATED DOCS

- `LOCAL_DEVELOPMENT_SETUP.md` - Local setup guide
- `DEPLOYMENT_GUIDE.md` - Full deployment procedures
- `FRONTEND_VERIFICATION_GUIDE.md` - UI verification
- `CHROME_DEVTOOLS_MCP_GUIDE.md` - DevTools MCP usage
- `_ISSUES_FIXES/CSS_INCOMPLETE_DEPLOYMENT_ISSUE.md` - Deployment troubleshooting

---

**Data ostatniej aktualizacji:** 2025-12-04
**Wersja dokumentu:** 1.0
