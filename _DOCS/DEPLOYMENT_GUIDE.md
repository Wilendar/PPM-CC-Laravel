# Deployment Guide - PPM-CC-Laravel

**Dokument:** Kompletny przewodnik deployment na Hostido.net.pl
**Ostatnia aktualizacja:** 2025-10-14
**Powiązane:** CLAUDE.md → Komendy i Workflow

---

## 🚀 ŚRODOWISKO DEPLOYMENT

### Konfiguracja Hostido

- **Domena:** ppm.mpptrade.pl
- **Hosting:** Hostido.net.pl
- **SSH Host:** host379076@host379076.hostido.net.pl
- **SSH Port:** 64321
- **SSH Key:** `D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk`
- **Laravel Root:** `domains/ppm.mpptrade.pl/public_html/` (bezpośrednio w public_html)
- **Database:** host379076_ppm@localhost (MariaDB 10.11.13)
- **PHP:** 8.3.23 (natywnie dostępny)
- **Composer:** 2.8.5 (preinstalowany)

---

## 📦 DEVELOPMENT WORKFLOW

### Lokalne środowisko development

```bash
# Start local dev server
php artisan serve

# Run migrations
php artisan migrate
php artisan migrate:rollback
php artisan migrate:status

# Seeders
php artisan db:seed
php artisan db:seed --class=ProductSeeder

# Build assets
npm install
npm run dev       # Development mode
npm run build     # Production build

# Tests
php artisan test
./vendor/bin/phpunit
```

---

## 🔑 SSH CONNECTION SETUP

### PowerShell Setup

```powershell
# Set SSH key path (raz na sesję)
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

# Test connection
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "php -v"
```

### Ręczne połączenie SSH

```bash
# Wymaga klucza SSH (HostidoSSHNoPass.ppk)
# Użyj PuTTY lub plink dla Windows
ssh -p 64321 host379076@host379076.hostido.net.pl
```

---

## 📤 DEPLOYMENT COMMANDS

### 1. Upload pojedynczego pliku (pscp)

```powershell
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

# Syntax
pscp -i $HostidoKey -P 64321 "LOCAL_PATH" host379076@host379076.hostido.net.pl:REMOTE_PATH

# Example: Upload PHP file
pscp -i $HostidoKey -P 64321 `
  "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\app\Models\Product.php" `
  host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/app/Models/Product.php

# Example: Upload Blade view
pscp -i $HostidoKey -P 64321 `
  "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\resources\views\livewire\products\product-list.blade.php" `
  host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/resources/views/livewire/products/product-list.blade.php
```

### 2. Execute remote commands (plink)

```powershell
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

# Syntax
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "COMMAND"

# Example: Run migrations
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php artisan migrate --force"

# Example: Clear cache
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear"
```

### 3. Composer operations

```powershell
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

# Install dependencies (production)
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && composer install --no-dev --optimize-autoloader"

# Update dependencies
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && composer update"

# Dump autoload
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && composer dump-autoload"
```

---

## ⚡ QUICK DEPLOYMENT PATTERNS

### Pattern 1: Single File Upload + Cache Clear

```powershell
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

# Upload file
pscp -i $HostidoKey -P 64321 `
  "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\path\to\file.php" `
  host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/path/to/file.php

# Clear cache
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear"
```

### Pattern 2: Multiple Files Upload

```powershell
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

# Upload multiple files
pscp -i $HostidoKey -P 64321 `
  "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\app\Models\Product.php" `
  host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/app/Models/Product.php

pscp -i $HostidoKey -P 64321 `
  "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\app\Models\Category.php" `
  host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/app/Models/Category.php

# Clear cache once
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php artisan cache:clear"
```

### Pattern 3: Migration Deployment

```powershell
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

# Upload migration file
pscp -i $HostidoKey -P 64321 `
  "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\database\migrations\2025_10_14_create_table.php" `
  host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/database/migrations/2025_10_14_create_table.php

# Run migrations + clear cache
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php artisan migrate --force && php artisan cache:clear"
```

### Pattern 4: CSS/JS Assets Deployment

```powershell
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

# 1. Build assets locally
npm run build

# 2. Upload compiled CSS
pscp -i $HostidoKey -P 64321 `
  "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\public\build\assets\app-*.css" `
  host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/public/build/assets/

# 3. Upload compiled JS
pscp -i $HostidoKey -P 64321 `
  "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\public\build\assets\app-*.js" `
  host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/public/build/assets/

# 4. Upload manifest
pscp -i $HostidoKey -P 64321 `
  "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\public\build\manifest.json" `
  host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/public/build/manifest.json

# 5. Clear cache
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear"
```

### Pattern 5: Full Application Update

```powershell
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

# 1. Upload changed files (use pscp for each)
# ... multiple pscp commands ...

# 2. Update dependencies
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && composer install --no-dev --optimize-autoloader"

# 3. Run migrations
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php artisan migrate --force"

# 4. Clear all caches
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php artisan optimize:clear"
```

---

## 🔧 MAINTENANCE COMMANDS

### Cache Management

```powershell
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

# Clear all caches
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php artisan optimize:clear"

# Clear specific caches
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear"

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php artisan cache:clear"

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php artisan config:clear"

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php artisan route:clear"

# Optimize for production
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php artisan optimize"
```

### Queue Management

```powershell
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

# Start queue worker (background)
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && nohup php artisan queue:work --daemon > /dev/null 2>&1 &"

# Restart queue workers
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php artisan queue:restart"
```

### Database Operations

```powershell
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

# Run migrations
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php artisan migrate --force"

# Rollback migrations
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php artisan migrate:rollback"

# Migration status
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php artisan migrate:status"

# Run seeders
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php artisan db:seed --class=ProductSeeder"
```

---

## ⚠️ DEPLOYMENT CHECKLIST

Przed każdym deploymentem sprawdź:

- [ ] Zbudowano assets lokalnie (`npm run build`)
- [ ] Przetestowano zmiany lokalnie
- [ ] Utworzono backup przed krytycznymi zmianami
- [ ] Sprawdzono brak hardcoded values
- [ ] Zweryfikowano zgodność z enterprise patterns
- [ ] Przygotowano rollback plan (jeśli potrzebny)

Po deploymencie:

- [ ] Zweryfikowano poprawność na https://ppm.mpptrade.pl
- [ ] Sprawdzono logi błędów (`storage/logs/laravel.log`)
- [ ] Przetestowano kluczowe funkcjonalności
- [ ] Uruchomiono screenshot verification (dla zmian UI)
- [ ] Zaktualizowano dokumentację (jeśli potrzebne)

---

## 🚨 TROUBLESHOOTING

### Problem: "Permission denied"

```powershell
# Check SSH key path
echo $HostidoKey
# Should output: D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk

# Verify key exists
Test-Path $HostidoKey
# Should output: True
```

### Problem: "Connection timed out"

```powershell
# Test basic connectivity
Test-NetConnection -ComputerName host379076.hostido.net.pl -Port 64321
```

### Problem: "Class not found" po deploymencie

```powershell
# Regenerate autoload
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && composer dump-autoload"

# Clear all caches
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php artisan optimize:clear"
```

### Problem: Zmiany CSS nie widoczne

```powershell
# 1. Rebuild assets locally
npm run build

# 2. Upload compiled assets (sprawdź manifest.json dla hash)
# 3. Clear view cache
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear"

# 4. Hard refresh browser (Ctrl+Shift+R)
```

---

## 📖 POWIĄZANA DOKUMENTACJA

- **CLAUDE.md** - Główne zasady projektu
- **_DOCS/FRONTEND_VERIFICATION_GUIDE.md** - Weryfikacja zmian UI po deploymencie
- **dane_hostingu.md** - Szczegółowe dane dostępowe

---

**UWAGA:** Zawsze testuj krytyczne zmiany na staging environment przed deploymentem na produkcję!
