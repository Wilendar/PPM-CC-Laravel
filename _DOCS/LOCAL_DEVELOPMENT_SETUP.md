# INSTRUKCJA KONFIGURACJI LOKALNEGO ŚRODOWISKA DEVELOPERSKIEGO
# PPM-CC-Laravel

**Data utworzenia:** 2025-12-04
**Projekt:** PPM - PrestaShop Product Manager
**Laravel:** 11.46.1
**PHP:** 8.3.25
**Environment:** Windows 11 + PowerShell 7

---

## 📋 SPIS TREŚCI

1. [Wymagania Systemowe](#wymagania-systemowe)
2. [Instalacja Projektu](#instalacja-projektu)
3. [Konfiguracja .env](#konfiguracja-env)
4. [Baza Danych](#baza-danych)
5. [Queue & Cache Drivers](#queue--cache-drivers)
6. [Komendy Startowe](#komendy-startowe)
7. [Vite & Frontend Assets](#vite--frontend-assets)
8. [Storage & Symlinks](#storage--symlinks)
9. [Development Tools](#development-tools)
10. [Troubleshooting](#troubleshooting)

---

## ✅ WYMAGANIA SYSTEMOWE

### Zainstalowane (Zweryfikowane)

```powershell
# PHP
php -v
# PHP 8.3.25 (cli) (built: Aug 26 2025 15:48:14) (ZTS Visual C++ 2019 x64)

# Node.js
node --version
# v22.17.1

# npm
npm --version
# 11.5.1

# Laravel
php artisan --version
# Laravel Framework 11.46.1
```

### Wymagane

- ✅ **PHP:** 8.3+ (zainstalowane: 8.3.25)
- ✅ **Composer:** 2.x (do zainstalowania/weryfikacji)
- ✅ **Node.js:** 18+ (zainstalowane: 22.17.1)
- ✅ **npm:** 9+ (zainstalowane: 11.5.1)
- ⚠️ **MySQL/MariaDB:** 8.0+ lub 10.11+ (do konfiguracji lokalnie)
- 🔧 **Redis:** Opcjonalnie dla queue/cache (fallback: database driver)

### PHP Extensions (Wymagane)

Zweryfikuj instalację:

```powershell
php -m | Select-String -Pattern "pdo|mbstring|openssl|xml|curl|zip|gd|redis"
```

**Wymagane rozszerzenia:**
- `pdo_mysql` - Połączenie z MySQL
- `mbstring` - Multi-byte string support
- `openssl` - Encryption/security
- `xml` - XML parsing
- `curl` - HTTP requests
- `zip` - Archive handling
- `gd` lub `imagick` - Image manipulation
- `redis` - Opcjonalnie dla Redis queue/cache

---

## 📦 INSTALACJA PROJEKTU

### 1. Klonowanie/Pull Repozytorium

Projekt już istnieje lokalnie:

```powershell
cd "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"
git pull origin main  # Lub feature/productform-redesign
```

### 2. Instalacja Dependencies

#### Composer Dependencies

```powershell
# Zainstaluj Composer jeśli nie masz (https://getcomposer.org/download/)
# Sprawdź instalację
composer --version

# Zainstaluj PHP dependencies
composer install

# Opcjonalnie: Zainstaluj tylko production dependencies
composer install --no-dev --optimize-autoloader
```

**Kluczowe Pakiety:**
- `laravel/framework` ^11.0
- `livewire/livewire` ^3.0
- `maatwebsite/excel` ^3.1 (Import/Export XLSX)
- `spatie/laravel-permission` ^6.0 (RBAC)
- `intervention/image` ^3.0 (Image manipulation)
- `laravel/sanctum` ^4.0 (API auth)

#### NPM Dependencies

```powershell
# Zainstaluj Node dependencies
npm install

# Opcjonalnie: Clean install
npm ci
```

**Kluczowe Pakiety:**
- `vite` ^5.0.0
- `laravel-vite-plugin` ^1.0.0
- `tailwindcss` ^3.4.17
- `autoprefixer` ^10.4.21
- `puppeteer` ^24.31.0 (Browser automation)
- `playwright` ^1.55.1 (Testing)

---

## 🔧 KONFIGURACJA .env

### 1. Utwórz plik .env

```powershell
# Skopiuj przykładowy plik
Copy-Item .env.example .env

# Wygeneruj klucz aplikacji
php artisan key:generate
```

### 2. Konfiguracja Środowiska Lokalnego

Otwórz `.env` i dostosuj następujące zmienne:

```env
# =========================================
# APPLICATION ENVIRONMENT
# =========================================
APP_NAME="PPM - Prestashop Product Manager"
APP_ENV=local                          # ⚠️ local (nie production!)
APP_KEY=base64:...                     # Wygenerowane przez key:generate
APP_DEBUG=true                         # ⚠️ true dla local development
APP_TIMEZONE=Europe/Warsaw
APP_URL=http://localhost:8000          # Lokalny URL

APP_LOCALE=pl
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=pl_PL

# =========================================
# LOGGING
# =========================================
LOG_CHANNEL=stack
LOG_STACK=single                       # single dla prostszych logów
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug                        # ⚠️ debug dla local (info/error dla production)

# =========================================
# DATABASE (Local MySQL)
# =========================================
DB_CONNECTION=mysql
DB_HOST=127.0.0.1                      # Lokalny MySQL
DB_PORT=3306
DB_DATABASE=ppm_cc_laravel_local       # ⚠️ Nazwa lokalnej bazy
DB_USERNAME=root                       # ⚠️ Twój lokalny user
DB_PASSWORD=                           # ⚠️ Twoje hasło (lub puste dla XAMPP/WAMP)

# =========================================
# SESSION & CACHE
# =========================================
SESSION_DRIVER=database                # database (bez Redis lokalnie)
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=                        # Puste dla localhost

CACHE_STORE=database                   # database lub redis jeśli masz
CACHE_PREFIX=ppm_local_                # Prefix dla local cache

# =========================================
# QUEUE (Database fallback)
# =========================================
QUEUE_CONNECTION=database              # database (bez Redis lokalnie)

# =========================================
# FILESYSTEM
# =========================================
FILESYSTEM_DISK=local                  # local dla development
# public - dla plików dostępnych publicznie (symlink)
# temp - dla tymczasowych plików

# =========================================
# REDIS (Opcjonalne)
# =========================================
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Jeśli NIE masz Redis, zostaw puste lub użyj database driver

# =========================================
# MAIL (Development)
# =========================================
MAIL_MAILER=log                        # ⚠️ log dla local (nie wysyła maili, tylko loguje)
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="local@ppm.test"
MAIL_FROM_NAME="${APP_NAME}"

# =========================================
# PPM-CC-Laravel SPECIFIC
# =========================================
PPM_COMPANY_NAME="MPP TRADE"
PPM_COMPANY_EMAIL="info@mpptrade.pl"
PPM_MAX_PRODUCTS=100000
PPM_MAX_IMPORT_SIZE=50000
PPM_CONTAINER_STORAGE_PATH="storage/app/containers"
PPM_PRODUCT_IMAGES_PATH="storage/app/products/images"

# =========================================
# PRESTASHOP API (Development Stores)
# =========================================
PRESTASHOP_STORES_CONFIG="config/prestashop_stores.json"

# API Configuration
PRESTASHOP_API_TIMEOUT=30
PRESTASHOP_API_RETRY_ATTEMPTS=3
PRESTASHOP_API_RETRY_DELAY_MS=1000
PRESTASHOP_RATE_LIMITING_ENABLED=false  # ⚠️ false dla local testing

# Category Preview
PRESTASHOP_CATEGORY_PREVIEW_ENABLED=true
PRESTASHOP_CATEGORY_PREVIEW_EXPIRATION=1

# Logging (verbose dla local)
PRESTASHOP_DETAILED_LOGGING=true       # ⚠️ true dla local
PRESTASHOP_LOG_API_REQUESTS=true
PRESTASHOP_LOG_SYNC_OPERATIONS=true

# =========================================
# ERP INTEGRATION (Development)
# =========================================
BASELINKER_API_TOKEN=your_test_token_here
SUBIEKT_GT_SERVER=
DYNAMICS_API_URL=

# =========================================
# DEVELOPMENT TOOLS
# =========================================
TELESCOPE_ENABLED=true                 # ⚠️ true dla local debugging
DEBUGBAR_ENABLED=true                  # ⚠️ true dla local debugging

# Broadcast (opcjonalne)
BROADCAST_CONNECTION=log

# AWS S3 (opcjonalne, na razie local storage)
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

# Vite
VITE_APP_NAME="${APP_NAME}"
```

---

## 🗄️ BAZA DANYCH

### 1. Utwórz Lokalną Bazę Danych

#### Opcja A: MySQL/MariaDB lokalnie

```powershell
# Połącz się z MySQL
mysql -u root -p

# Utwórz bazę danych
CREATE DATABASE ppm_cc_laravel_local CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Utwórz użytkownika (opcjonalnie)
CREATE USER 'ppm_local'@'localhost' IDENTIFIED BY 'ppm_local_password';
GRANT ALL PRIVILEGES ON ppm_cc_laravel_local.* TO 'ppm_local'@'localhost';
FLUSH PRIVILEGES;

# Wyjdź
EXIT;
```

#### Opcja B: XAMPP/WAMP

1. Uruchom XAMPP Control Panel
2. Start Apache + MySQL
3. Otwórz phpMyAdmin: http://localhost/phpmyadmin
4. Utwórz nową bazę: `ppm_cc_laravel_local` (utf8mb4_unicode_ci)

### 2. Uruchom Migracje

```powershell
# Uruchom wszystkie migracje
php artisan migrate

# Jeśli chcesz zresetować bazę i uruchomić na nowo
php artisan migrate:fresh

# Z seeders (dane testowe)
php artisan migrate:fresh --seed
```

**Kluczowe Migracje (96 plików):**
- `create_products_table` - Główna tabela produktów
- `create_product_categories_table` - Kategorie (5 poziomów)
- `create_price_groups_table` - Grupy cenowe
- `create_product_shop_data_table` - Multi-store data
- `create_prestashop_shops_table` - Sklepy PrestaShop
- `create_product_variants_table` - Warianty produktów
- `create_feature_types_table` - System cech (Features)
- `create_media_table` - System mediów
- `create_job_progress_table` - Progress tracking
- `create_jobs_table` - Queue system

### 3. Uruchom Seeders

```powershell
# Wszystkie seeders
php artisan db:seed

# Pojedyncze seeders
php artisan db:seed --class=RolePermissionSeeder
php artisan db:seed --class=UserSeeder
php artisan db:seed --class=PriceGroupSeeder
php artisan db:seed --class=FeatureTypeSeeder
php artisan db:seed --class=AttributeTypeSeeder
php artisan db:seed --class=WarehouseSeeder
```

**Kluczowe Seeders:**
- `RolePermissionSeeder` - Role i uprawnienia (Admin, Manager, etc.)
- `UserSeeder` - Test users (admin@mpptrade.pl / Admin123!MPP)
- `PriceGroupSeeder` - Grupy cenowe (Detaliczna, Dealer, etc.)
- `FeatureTypeSeeder` - Typy cech produktów
- `AttributeTypeSeeder` - Typy atrybutów wariantów
- `WarehouseSeeder` - Magazyny

### 4. Weryfikacja Bazy Danych

```powershell
# Sprawdź połączenie
php artisan tinker
>>> DB::connection()->getPdo();
>>> DB::table('users')->count();
>>> exit
```

---

## ⚙️ QUEUE & CACHE DRIVERS

### Queue Configuration

**Rekomendacja dla Local:**
- **Development bez Redis:** `QUEUE_CONNECTION=database`
- **Development z Redis:** `QUEUE_CONNECTION=redis`

#### Database Queue (Fallback)

```env
QUEUE_CONNECTION=database
```

**Uruchom Queue Worker:**

```powershell
# Start queue worker
php artisan queue:work

# Z verbose output
php artisan queue:work --verbose

# Specific queue
php artisan queue:work --queue=prestashop,media,default

# Z timeout i retries
php artisan queue:work --timeout=300 --tries=3

# Restart workers po zmianie kodu
php artisan queue:restart
```

**Monitorowanie:**

```powershell
# Sprawdź pending jobs
php artisan queue:monitor database:default,database:prestashop

# Failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

#### Redis Queue (Opcjonalnie)

**Instalacja Redis (Windows):**

1. Download Redis for Windows: https://github.com/microsoftarchive/redis/releases
2. Zainstaluj i uruchom jako service
3. Lub użyj Docker:

```powershell
docker run -d -p 6379:6379 --name redis-local redis:alpine
```

**Konfiguracja:**

```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
```

### Cache Configuration

**Rekomendacja dla Local:**
- **Development bez Redis:** `CACHE_STORE=database`
- **Development z Redis:** `CACHE_STORE=redis`

#### Database Cache (Fallback)

```env
CACHE_STORE=database
CACHE_PREFIX=ppm_local_
```

**Komendy Cache:**

```powershell
# Clear all cache
php artisan cache:clear

# Clear config cache
php artisan config:clear

# Clear route cache
php artisan route:clear

# Clear view cache
php artisan view:clear

# Cache config dla performance
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### Redis Cache (Opcjonalnie)

```env
CACHE_STORE=redis
REDIS_CLIENT=phpredis
```

---

## 🚀 KOMENDY STARTOWE

### Setup Complete (Od Zera)

```powershell
# 1. Przejdź do projektu
cd "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"

# 2. Zainstaluj dependencies
composer install
npm install

# 3. Konfiguracja environment
Copy-Item .env.example .env
php artisan key:generate

# 4. Edytuj .env (ustaw DB credentials, etc.)
# [Edycja .env zgodnie z sekcją powyżej]

# 5. Setup bazy danych
php artisan migrate:fresh --seed

# 6. Storage setup
php artisan storage:link
New-Item -ItemType Directory -Force -Path "storage/app/containers"
New-Item -ItemType Directory -Force -Path "storage/app/products/images"
New-Item -ItemType Directory -Force -Path "storage/app/temp"

# 7. Build frontend assets
npm run build

# 8. Start development
php artisan serve
# Otwórz: http://localhost:8000
```

### Daily Development Workflow

```powershell
# Start Laravel development server
php artisan serve
# Aplikacja dostępna: http://localhost:8000

# (W nowym terminalu) Start queue worker
php artisan queue:work --verbose

# (W nowym terminalu) Start Vite dev server (opcjonalnie)
npm run dev
# Hot reload dla CSS/JS

# Czyszczenie cache po zmianach
php artisan cache:clear && php artisan config:clear && php artisan view:clear
```

### Testowanie

```powershell
# Uruchom testy PHPUnit
php artisan test

# Specific test
php artisan test --filter=ProductTest

# Quality checks
composer run quality  # PHPStan + PHP CS Fixer

# PHPStan only
composer run phpstan

# PHP CS Fixer only
composer run cs-fix
```

---

## 🎨 VITE & FRONTEND ASSETS

### Vite Configuration

**KRYTYCZNA ZASADA:** Vite działa TYLKO lokalnie! Produkcja otrzymuje gotowe zbudowane pliki.

#### Development (Hot Reload)

```powershell
# Start Vite dev server z hot reload
npm run dev

# Vite będzie nasłuchiwać zmian w:
# - resources/css/**/*.css
# - resources/js/**/*.js
# - resources/views/**/*.blade.php (Livewire components)
```

**W aplikacji Laravel:**

```blade
{{-- layouts/admin.blade.php --}}
@vite(['resources/css/app.css', 'resources/js/app.js'])
```

Vite automatycznie wstrzyknie hot reload script w trybie development.

#### Production Build (Local)

```powershell
# Build dla produkcji (manifest + hashed assets)
npm run build

# Output: public/build/
# ├── assets/
# │   ├── app-[hash].css
# │   ├── app-[hash].js
# │   ├── components-[hash].css
# │   └── ...
# └── .vite/manifest.json  ⚠️ Musi być skopiowany do public/build/manifest.json!
```

**⚠️ KRYTYCZNE:** Manifest Location

```powershell
# Po build, skopiuj manifest
Copy-Item "public/build/.vite/manifest.json" "public/build/manifest.json"
```

Laravel wymaga manifestu w `public/build/manifest.json` (ROOT), ale Vite tworzy w `.vite/manifest.json`.

### CSS Structure

**Istniejące pliki CSS:**

```
resources/css/
├── app.css                     # Main app styles + imports
├── admin/
│   ├── components.css          # Admin UI components
│   └── layout.css              # Admin layout/grid
├── products/
│   └── category-form.css       # Product category forms
└── components/
    └── category-picker.css     # Category picker component
```

**ZASADA:** Dodawaj style do ISTNIEJĄCYCH plików zamiast tworzyć nowe (Vite manifest cache issue).

### Asset Verification

```powershell
# Sprawdź czy assets są zbudowane
Get-ChildItem -Path "public/build/assets" -Recurse

# Sprawdź manifest
Get-Content "public/build/manifest.json" | ConvertFrom-Json

# Weryfikacja w przeglądarce
# DevTools → Network → Filter CSS/JS → Sprawdź czy ładuje z public/build/assets/
```

---

## 📁 STORAGE & SYMLINKS

### Storage Structure

```
storage/
├── app/
│   ├── public/              # Publicznie dostępne pliki
│   ├── containers/          # Dokumenty kontenerów (XLSX, ZIP, PDF)
│   ├── products/
│   │   └── images/          # Zdjęcia produktów
│   └── temp/                # Pliki tymczasowe
├── framework/
│   ├── cache/
│   ├── sessions/
│   └── views/
└── logs/
    └── laravel.log
```

### Symlink Setup

```powershell
# Utwórz symlink storage/app/public → public/storage
php artisan storage:link

# Weryfikacja
Test-Path "public/storage"  # Should return True
Get-Item "public/storage"   # Should show symlink details

# Utwórz dodatkowe foldery
New-Item -ItemType Directory -Force -Path "storage/app/containers"
New-Item -ItemType Directory -Force -Path "storage/app/products/images"
New-Item -ItemType Directory -Force -Path "storage/app/temp"
```

### Permissions (Windows)

```powershell
# Upewnij się że storage/ i bootstrap/cache/ są writable
# Na Windows zwykle nie ma problemu, ale możesz sprawdzić:

# Test write access
New-Item -Path "storage/app/test.txt" -ItemType File -Value "test"
Remove-Item "storage/app/test.txt"

# Jeśli problem z permissjami:
# Prawy klik → Properties → Security → Edit → Add Full Control dla Twojego usera
```

---

## 🛠️ DEVELOPMENT TOOLS

### Laravel Telescope (Debugging)

**Instalacja (już w projekcie):**

```json
// composer.json
"laravel/telescope": "^5.0"  // require-dev
```

**Konfiguracja:**

```env
TELESCOPE_ENABLED=true
```

**Publish config (jeśli jeszcze nie):**

```powershell
php artisan telescope:install
php artisan migrate
```

**Dostęp:**

```
http://localhost:8000/telescope
```

**Features:**
- Request monitoring
- Query logging (N+1 detection)
- Job tracking
- Mail preview
- Exception tracking
- Cache monitoring
- Redis commands

### Laravel Debugbar (opcjonalnie)

```powershell
# Instalacja (jeśli nie ma)
composer require barryvdh/laravel-debugbar --dev

# Konfiguracja
php artisan vendor:publish --provider="Barryvdh\Debugbar\ServiceProvider"
```

```env
DEBUGBAR_ENABLED=true
```

Debugbar pojawi się na dole każdej strony w trybie development.

### Chrome DevTools MCP

**STATUS:** ✅ MCP zainstalowane i aktywne

**Użycie:** Weryfikacja frontend po deploymencie/zmianach CSS/JS.

```powershell
# Przykład - weryfikacja strony
node _TOOLS/screenshot_page.cjs "http://localhost:8000/admin"
```

**Dokumentacja:** `_DOCS/CHROME_DEVTOOLS_MCP_GUIDE.md`

### Node.js Testing Tools

```powershell
# Puppeteer - Browser automation
# Playwright - E2E testing

# Przykładowe testy
node _TOOLS/full_console_test.cjs "http://localhost:8000/admin/products"
```

---

## 🚑 TROUBLESHOOTING

### Problem 1: Composer not found

```powershell
# Zainstaluj Composer
# Download: https://getcomposer.org/download/

# Weryfikacja
composer --version

# Jeśli nadal nie działa, dodaj do PATH:
# Windows → Environment Variables → System Variables → Path
# Dodaj: C:\ProgramData\ComposerSetup\bin
```

### Problem 2: MySQL Connection Error

```
SQLSTATE[HY000] [2002] No connection could be made
```

**Rozwiązanie:**

```powershell
# Sprawdź czy MySQL działa
Get-Service -Name MySQL*  # Windows Service

# Lub XAMPP
# Start XAMPP Control Panel → Start MySQL

# Sprawdź port
netstat -an | Select-String "3306"

# Weryfikacja połączenia
mysql -u root -p -h 127.0.0.1 -P 3306
```

### Problem 3: Queue Jobs Not Processing

```powershell
# Sprawdź czy queue worker działa
php artisan queue:work --verbose

# Sprawdź pending jobs
DB::table('jobs')->count();  # W tinker

# Restart queue
php artisan queue:restart
php artisan queue:work
```

### Problem 4: Vite Assets Not Loading

```
Vite manifest not found
```

**Rozwiązanie:**

```powershell
# 1. Build assets
npm run build

# 2. Sprawdź manifest
Test-Path "public/build/manifest.json"
Test-Path "public/build/.vite/manifest.json"

# 3. Skopiuj manifest do ROOT
Copy-Item "public/build/.vite/manifest.json" "public/build/manifest.json" -Force

# 4. Clear cache
php artisan config:clear
php artisan view:clear

# 5. Restart server
# Ctrl+C w terminalu z php artisan serve
php artisan serve
```

### Problem 5: Storage Symlink Issue

```
The file "public/storage" does not exist
```

**Rozwiązanie:**

```powershell
# Usuń stary symlink (jeśli istnieje)
Remove-Item -Path "public/storage" -Force -ErrorAction SilentlyContinue

# Utwórz nowy
php artisan storage:link

# Weryfikacja
Get-Item "public/storage"  # Should show Target pointing to storage/app/public
```

### Problem 6: Permission Denied (Storage)

```powershell
# Windows zwykle nie ma problemu, ale jeśli:

# 1. Sprawdź ownership
Get-Acl "storage" | Format-List

# 2. Nadaj Full Control dla Twojego usera
# Prawy klik → Properties → Security → Edit → Add Full Control

# 3. Lub przez PowerShell (Admin)
$acl = Get-Acl "storage"
$rule = New-Object System.Security.AccessControl.FileSystemAccessRule(
    $env:USERNAME, "FullControl", "ContainerInherit,ObjectInherit", "None", "Allow"
)
$acl.SetAccessRule($rule)
Set-Acl "storage" $acl
```

### Problem 7: Port 8000 Already in Use

```
Failed to listen on 127.0.0.1:8000
```

**Rozwiązanie:**

```powershell
# Sprawdź co używa portu
netstat -ano | Select-String ":8000"

# Użyj innego portu
php artisan serve --port=8001
```

### Problem 8: Node/npm Version Issues

```powershell
# Sprawdź wersje
node --version  # Should be 18+
npm --version   # Should be 9+

# Update Node.js
# Download latest LTS: https://nodejs.org/

# Clear npm cache
npm cache clean --force

# Reinstall dependencies
Remove-Item -Recurse -Force node_modules
Remove-Item package-lock.json
npm install
```

---

## 📚 DODATKOWE ZASOBY

### Dokumentacja Projektu

- `CLAUDE.md` - Main project documentation
- `_DOCS/DEPLOYMENT_GUIDE.md` - Deployment procedures
- `_DOCS/FRONTEND_VERIFICATION_GUIDE.md` - UI verification
- `_DOCS/CSS_STYLING_GUIDE.md` - CSS guidelines
- `_DOCS/DEBUG_LOGGING_GUIDE.md` - Debug logging practices
- `_DOCS/SKU_ARCHITECTURE_GUIDE.md` - SKU-first architecture
- `_ISSUES_FIXES/` - Known issues and solutions

### External Documentation

- Laravel 11.x: https://laravel.com/docs/11.x
- Livewire 3.x: https://livewire.laravel.com/docs/3.x
- Alpine.js: https://alpinejs.dev/
- Vite: https://vitejs.dev/
- Tailwind CSS: https://tailwindcss.com/docs

### Komendy Quick Reference

```powershell
# Development
php artisan serve                    # Start dev server
php artisan queue:work               # Start queue worker
npm run dev                          # Vite hot reload
npm run build                        # Production build

# Database
php artisan migrate                  # Run migrations
php artisan migrate:fresh --seed     # Fresh + seed
php artisan db:seed                  # Run seeders

# Cache
php artisan cache:clear              # Clear cache
php artisan config:clear             # Clear config
php artisan view:clear               # Clear views
php artisan route:clear              # Clear routes

# Testing
php artisan test                     # Run tests
composer run quality                 # PHPStan + CS Fixer

# Maintenance
php artisan queue:restart            # Restart queue
php artisan storage:link             # Create storage symlink
php artisan optimize                 # Optimize application
```

---

## 🎯 NASTĘPNE KROKI

Po skonfigurowaniu lokalnego środowiska:

1. ✅ Uruchom `php artisan serve` i otwórz http://localhost:8000
2. ✅ Zaloguj się jako admin: `admin@mpptrade.pl` / `Admin123!MPP`
3. ✅ Sprawdź dashboard: http://localhost:8000/admin
4. ✅ Przetestuj produkty: http://localhost:8000/admin/products
5. ✅ Skonfiguruj sklep PrestaShop (testowy): http://localhost:8000/admin/shops
6. ✅ Start development zgodnie z planem w `Plan_Projektu/`

---

**Data ostatniej aktualizacji:** 2025-12-04
**Wersja dokumentu:** 1.0
**Status:** ✅ Production Ready
