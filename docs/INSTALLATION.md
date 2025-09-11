# Instalacja PPM-CC-Laravel - Przewodnik Krok po Kroku

## 📋 Spis Treści

1. [Wymagania Systemowe](#wymagania-systemowe)
2. [Środowisko Hostido.net.pl](#środowisko-hostido)
3. [SSH i Klucze](#ssh-i-klucze)
4. [Instalacja Laravel](#instalacja-laravel)
5. [Konfiguracja Bazy Danych](#konfiguracja-bazy-danych)
6. [Pakiety Composer](#pakiety-composer)
7. [Konfiguracja Pakietów](#konfiguracja-pakietów)
8. [Środowisko Lokalne](#środowisko-lokalne)
9. [Troubleshooting](#troubleshooting)

## 🔧 Wymagania Systemowe

### Serwer Produkcyjny (Hostido.net.pl)
- **PHP**: 8.3.23 ✅ (preinstalowane)
- **Composer**: 2.8.5 ✅ (preinstalowane)
- **MariaDB**: 10.11.13 ✅ (dostępne)
- **SSH**: Port 64321 ✅ (skonfigurowane)
- **SSL**: Automatyczne certyfikaty ✅
- **Memory Limit**: 512MB (współdzielony hosting)

### Środowisko Lokalne (Development)
- **Node.js**: 18.17.0+ (dla Vite + npm)
- **PowerShell**: 7.x (Windows)
- **VS Code**: Zalecane IDE
- **WinSCP/PuTTY**: SSH/SFTP tools
- **Git**: System kontroli wersji

## 🌐 Środowisko Hostido

### Dane Połączenia

```bash
# SSH
Host: host379076.hostido.net.pl
Port: 64321
User: host379076
Key: HostidoSSHNoPass.ppk

# FTP (alternatywa)
Host: host379076.hostido.net.pl  
User: ai@ppm.mpptrade.pl
Pass: aprsArbnRGUC7FbJxDL4

# Baza danych
Host: localhost
Database: host379076_ppm
User: host379076_ppm
Pass: qkS4FuXMMDDN4DJhatg6
```

### Struktura Katalogów

```
/domains/ppm.mpptrade.pl/
├── public_html/                 # Laravel ROOT (nie public!)
│   ├── app/                     # Aplikacja Laravel
│   ├── vendor/                  # Pakiety Composer
│   ├── .env                     # Konfiguracja środowiska
│   ├── artisan                  # CLI Laravel
│   └── index.php               # Entry point (zmodyfikowany)
├── logs/                        # Logi serwera
└── tmp/                         # Pliki tymczasowe
```

## 🔐 SSH i Klucze

### 1. Konfiguracja Kluczy SSH

**Lokalizacja klucza:**
```
D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk
```

**Klucz publiczny:**
```
ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQDopTaHzGMFLjRdMAZPCSngSqtfhoAQNzYUObQEG/q9IkFsOgodNk9SvxZ3/OYifih96aYN+w6Vchv5TchmiFBqHzDiVd77iI2kQ3m1nNX6K1rjDIJs5EuBlwOHceN+Eih2p7UBO12BQYfFwMNAFHolrIOSfczrTQdke/yWRpBrAOX0IaX599X4gq3CuhfZ+C0QyOVweEmaVUrOG6I5U7WOYUb9/C9iwJeG1Vxa5E1zTIasNjvcc2VpXnLmcBNgx5w3jBVvRhF1Smvc9igqPEelGo3CTccT1VSFcz6sZRwrqx94e4NNiKiyWroRn3UUQpEDfNFzdCNvSm/I1e19IjZd rsa-key-20240718
```

### 2. Test Połączenia SSH

```powershell
# Test podstawowego połączenia
ssh -p 64321 -i "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" host379076@host379076.hostido.net.pl

# Alternatywnie z PuTTY
$key = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $key -batch "php -v"
```

### 3. Weryfikacja Uprawnień

```bash
# Po połączeniu SSH sprawdź:
pwd                               # /home/host379076
cd /domains/ppm.mpptrade.pl/public_html
ls -la                           # Struktura Laravel
php artisan --version            # Laravel 12.x
composer --version               # Composer 2.8.5
```

## 🚀 Instalacja Laravel

### 1. Utworzenie Projektu (✅ WYKONANE)

Laravel został zainstalowany bezpośrednio na serwerze:

```bash
# SSH na serwer
ssh -p 64321 host379076@host379076.hostido.net.pl

# Nawigacja do public_html
cd /domains/ppm.mpptrade.pl/public_html

# Instalacja Laravel 12.x (wykonane)
composer create-project laravel/laravel . "^12.0"
```

### 2. Adaptacja do Hostingu (✅ WYKONANE)

Standardowo Laravel ma folder `public/`, ale na hostingu współdzielonym potrzebujemy adaptacji:

```bash
# Przeniesienie zawartości public/ do public_html/ (wykonane)
mv public/* .
mv public/.[^.]* .
rmdir public

# Aktualizacja ścieżek w index.php (wykonane)
# $maintenance = __DIR__.'/../storage/framework/maintenance.php';
# require __DIR__.'/../vendor/autoload.php';
# $app = require_once __DIR__.'/../bootstrap/app.php';

# Staje się:
# $maintenance = __DIR__.'/storage/framework/maintenance.php';
# require __DIR__.'/vendor/autoload.php';  
# $app = require_once __DIR__.'/bootstrap/app.php';
```

### 3. Weryfikacja Instalacji

```bash
# Test działania Laravel
curl -I https://ppm.mpptrade.pl
# Powinno zwrócić: HTTP/1.1 200 OK

# Sprawdzenie wersji
php artisan --version
# Laravel Framework 12.28.1
```

## 🗄️ Konfiguracja Bazy Danych

### 1. Dane Połączenia MariaDB

```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=host379076_ppm
DB_USERNAME=host379076_ppm
DB_PASSWORD=qkS4FuXMMDDN4DJhatg6
```

### 2. Test Połączenia

```bash
# SSH na serwer
php artisan tinker

# W Tinker:
DB::select('SELECT VERSION()');
# Powinno zwrócić MariaDB 10.11.13

# Test podstawowy
DB::select('SHOW TABLES');
```

### 3. Uruchomienie Migracji (✅ WYKONANE)

```bash
# Podstawowe migracje Laravel (wykonane)
php artisan migrate

# Sprawdzenie tabel
php artisan migrate:status

# Wynik powinien pokazać:
# users, password_resets, failed_jobs, personal_access_tokens
```

### 4. Konfiguracja MySQL dla UTF-8

```php
// config/database.php - weryfikacja (wykonane)
'mysql' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'forge'),
    'username' => env('DB_USERNAME', 'forge'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => 'utf8mb4',           // ✅ Polskie znaki
    'collation' => 'utf8mb4_unicode_ci', // ✅ Sortowanie Unicode
    'prefix' => '',
    'strict' => true,
    'engine' => null,
],
```

## 📦 Pakiety Composer

### 1. Pakiety Główne (✅ ZDEFINIOWANE)

```json
{
    "require": {
        "php": "^8.1",
        "laravel/framework": "^11.0",
        "laravel/tinker": "^2.10",
        "livewire/livewire": "^3.0",           // ✅ UI Components
        "maatwebsite/excel": "^3.1",           // ✅ XLSX Import/Export
        "spatie/laravel-permission": "^6.0"    // ✅ System uprawnień
    }
}
```

### 2. Pakiety Deweloperskie

```json
{
    "require-dev": {
        "fakerphp/faker": "^1.23",
        "laravel/pint": "^1.13",              // ✅ Code style
        "phpunit/phpunit": "^11.0.1",         // ✅ Testy
        "phpstan/phpstan": "^1.10",           // ✅ Analiza statyczna
        "friendsofphp/php-cs-fixer": "^3.48", // ✅ Formatowanie
        "laravel/telescope": "^5.0"           // ✅ Debugging (dev only)
    }
}
```

### 3. Instalacja na Serwerze

```bash
# SSH na serwer
cd /domains/ppm.mpptrade.pl/public_html

# Instalacja bez pakietów deweloperskich (production)
composer install --no-dev --optimize-autoloader

# Weryfikacja
composer show | grep -E "(livewire|excel|permission)"
```

## ⚙️ Konfiguracja Pakietów

### 1. Livewire 3.x Configuration

```bash
# Publikacja konfiguracji Livewire
php artisan vendor:publish --tag=livewire:config

# Konfiguracja w config/livewire.php
php artisan livewire:discover
```

**Test komponentu:**
```bash
# Utwórz testowy komponent
php artisan make:livewire Welcome

# Sprawdź czy działa na https://ppm.mpptrade.pl
```

### 2. Laravel Excel Configuration

```bash
# Publikacja konfiguracji Excel
php artisan vendor:publish --provider="Maatwebsite\Excel\ExcelServiceProvider" --tag=config

# Test basic functionality
php artisan tinker
```

```php
// W Tinker - test Excel
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\UsersExport; // będzie utworzone później

// Podstawowy test
Excel::download(new \Maatwebsite\Excel\Concerns\FromArray([
    [1, 2, 3],
    [4, 5, 6]
]), 'test.xlsx');
```

### 3. Spatie Permissions Configuration

```bash
# Publikacja migracji permissions
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"

# Uruchom migracje
php artisan migrate

# Sprawdź utworzone tabele
php artisan db:show --table=roles
php artisan db:show --table=permissions
```

**Konfiguracja modelu User:**
```php
// app/Models/User.php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;
    
    // ... reszta modelu
}
```

### 4. Cache i Optymalizacja

```bash
# Po konfiguracji wszystkich pakietów
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optymalizacja autoloader
composer dump-autoload --optimize
```

## 💻 Środowisko Lokalne

### 1. Setup Tools Windows

**Node.js + npm:**
```powershell
# Sprawdź wersję
node --version   # v18.17.0+
npm --version    # v9.0.0+

# Jeśli brak, pobierz z nodejs.org
# Instalacja w projekcie (local)
cd "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"
npm install
```

**VS Code Extensions:**
```
- PHP IntelliSense (bmewburn.vscode-intelephense-client)
- Laravel Extension Pack (onecentlin.laravel-extension-pack)
- Livewire Language Support (cierra.livewire-vscode)
- Tailwind CSS IntelliSense (bradlc.vscode-tailwindcss)
- PowerShell (ms-vscode.powershell)
```

### 2. Package.json Configuration

```json
{
    "type": "module",
    "devDependencies": {
        "@tailwindcss/forms": "^0.5.2",
        "alpinejs": "^3.4.2",
        "autoprefixer": "^10.4.2",
        "axios": "^1.6.4",
        "laravel-vite-plugin": "^1.0",
        "postcss": "^8.4.31",
        "tailwindcss": "^3.2.1",
        "vite": "^5.0"
    },
    "scripts": {
        "dev": "vite",
        "build": "vite build",
        "preview": "vite preview"
    }
}
```

### 3. Vite Configuration

```js
// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
    build: {
        outDir: 'public/build',
        manifest: true,
    },
});
```

### 4. Frontend Setup

**TailwindCSS:**
```bash
# Instalacja lokalnie
npm install -D tailwindcss postcss autoprefixer
npx tailwindcss init -p
```

**Alpine.js integration:**
```js
// resources/js/app.js
import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();
```

## 🔍 Troubleshooting

### Problem 1: SSH Connection Refused

**Przyczyna:** Nieprawidłowy port lub klucz SSH

**Rozwiązanie:**
```powershell
# Sprawdź połączenie z verbose
ssh -p 64321 -i "path\to\key.ppk" -v host379076@host379076.hostido.net.pl

# Alternatywnie przez PuTTY
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i "path\to\key.ppk" -v
```

### Problem 2: Laravel 500 Error

**Przyczyna:** Brak .env lub nieprawidłowa konfiguracja

**Rozwiązanie:**
```bash
# SSH na serwer i sprawdź
cd /domains/ppm.mpptrade.pl/public_html
ls -la .env                    # Czy plik istnieje?
php artisan config:clear       # Wyczyść cache config
tail -f storage/logs/laravel.log  # Sprawdź logi
```

### Problem 3: Database Connection Error

**Przyczyna:** Nieprawidłowe dane połączenia MySQL

**Rozwiązanie:**
```bash
# Test połączenia bezpośrednio
mysql -h localhost -u host379076_ppm -p host379076_ppm
# Wprowadź hasło: qkS4FuXMMDDN4DJhatg6

# Sprawdź konfigurację Laravel
php artisan config:show database.connections.mysql
```

### Problem 4: Composer Memory Limit

**Przyczyna:** Hosting współdzielony ma limity pamięci

**Rozwiązanie:**
```bash
# Zwiększ memory limit dla composer
COMPOSER_MEMORY_LIMIT=512M composer install --no-dev

# Lub w .htaccess (jeśli działa)
echo "php_value memory_limit 512M" >> .htaccess
```

### Problem 5: File Permissions

**Przyczyna:** Nieprawidłowe uprawnienia po SFTP upload

**Rozwiązanie:**
```bash
# SSH na serwer i ustaw uprawnienia
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod -R 775 storage bootstrap/cache
```

### Problem 6: Assets Not Loading

**Przyczyna:** Vite build lub routing issue

**Rozwiązanie:**
```bash
# Lokalnie
npm run build

# Upload przez SFTP
# _TOOLS/hostido_frontend_deploy.ps1

# Sprawdź manifest
cat public/build/manifest.json
```

### Problem 7: Livewire Not Working

**Przyczyna:** Brak publikacji konfiguracji lub Alpine.js

**Rozwiązanie:**
```bash
# SSH na serwer
php artisan vendor:publish --tag=livewire:assets --force
php artisan view:clear

# Sprawdź czy Alpine.js jest załadowane w blade
@livewireStyles
@livewireScripts
```

### Problem 8: Excel Import Memory Error

**Przyczyna:** Duże pliki XLSX na hostingu współdzielonym

**Rozwiązanie:**
```php
// config/excel.php
'imports' => [
    'read_only' => true,
    'chunk_size' => 1000,          // Zmniejsz chunk size
],

// W kodzie importu
Excel::import(new Import, $file, null, \Maatwebsite\Excel\Excel::XLSX, [
    'chunk_size' => 500
]);
```

## ✅ Weryfikacja Instalacji

### Checklist Kompletnej Instalacji

**Serwer:**
- [ ] SSH działa (port 64321)
- [ ] Laravel 12.x zainstalowany w public_html/
- [ ] MariaDB połączenie działa
- [ ] .env skonfigurowany poprawnie
- [ ] Migracje uruchomione

**Pakiety:**
- [ ] composer install wykonany
- [ ] Livewire config opublikowany
- [ ] Excel config opublikowany  
- [ ] Spatie Permissions migracje uruchomione

**Frontend:**
- [ ] Node.js zainstalowany lokalnie
- [ ] npm install wykonany
- [ ] Vite build działa
- [ ] Assets loading na https://ppm.mpptrade.pl

**Deployment:**
- [ ] _TOOLS/ scripts działają
- [ ] SFTP upload działa
- [ ] Health check pozytywny

### Final Test

```bash
# Test kompletnej aplikacji
curl -s https://ppm.mpptrade.pl | grep "Laravel"

# Powinno zwrócić pozytywny rezultat
# Status: 200 OK
# Laravel application running
```

---

**Next Steps:** [DEPLOYMENT.md](DEPLOYMENT.md) - Konfiguracja deployment pipeline