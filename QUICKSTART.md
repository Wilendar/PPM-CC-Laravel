# PPM-CC-Laravel - Quick Start Guide

**Szybka instrukcja uruchomienia lokalnego środowiska developerskiego**

---

## ⚡ 5-MINUTE SETUP

### 1. Sprawdź wymagania

```powershell
php -v       # PHP 8.3+
node -v      # Node.js 18+
npm -v       # npm 9+
mysql -V     # MySQL/MariaDB 8.0+
```

### 2. Uruchom automatyczny setup

```powershell
cd "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"
pwsh _TOOLS/setup_local_dev.ps1
```

Script zrobi wszystko automatycznie:
- ✅ Utworzy `.env` z `.env.local.example`
- ✅ Zainstaluje dependencies (composer + npm)
- ✅ Wygeneruje `APP_KEY`
- ✅ Uruchomi migracje + seeders
- ✅ Utworzy storage symlink
- ✅ Zbuduje frontend assets
- ✅ Wyczyści cache

### 3. Edytuj .env (Database credentials)

```env
DB_DATABASE=ppm_cc_laravel_local
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Utwórz bazę danych

```sql
CREATE DATABASE ppm_cc_laravel_local
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

### 5. Start development

```powershell
# Terminal 1: Server
php artisan serve

# Terminal 2: Queue
php artisan queue:work --verbose
```

### 6. Otwórz aplikację

```
URL:      http://localhost:8000
Admin:    http://localhost:8000/admin
Email:    admin@mpptrade.pl
Password: Admin123!MPP
```

---

## 🚀 DAILY COMMANDS (Helper Script)

```powershell
# Start server
pwsh _TOOLS/dev.ps1 serve

# Start queue
pwsh _TOOLS/dev.ps1 queue

# Build assets
pwsh _TOOLS/dev.ps1 build

# Clear cache
pwsh _TOOLS/dev.ps1 clear

# Check status
pwsh _TOOLS/dev.ps1 status

# Show all commands
pwsh _TOOLS/dev.ps1 help
```

---

## 📚 FULL DOCUMENTATION

Szczegółowa dokumentacja w `_DOCS/`:

- **LOCAL_DEVELOPMENT_SETUP.md** - Pełny setup guide (wszystkie szczegóły)
- **LOCAL_VS_PRODUCTION_DIFFERENCES.md** - Różnice local vs production
- **DEPLOYMENT_GUIDE.md** - Deployment na Hostido
- **FRONTEND_VERIFICATION_GUIDE.md** - UI verification
- **CSS_STYLING_GUIDE.md** - CSS guidelines
- **DEBUG_LOGGING_GUIDE.md** - Debug logging practices

---

## 🆘 TROUBLESHOOTING

### Problem: MySQL connection error

```powershell
# Sprawdź czy MySQL działa
Get-Service -Name MySQL*

# Lub XAMPP: Start MySQL w Control Panel
```

### Problem: Vite manifest not found

```powershell
# Build + copy manifest
npm run build
Copy-Item "public/build/.vite/manifest.json" "public/build/manifest.json" -Force
php artisan view:clear
```

### Problem: Port 8000 in use

```powershell
# Użyj innego portu
php artisan serve --port=8001
```

### Problem: Queue not processing

```powershell
# Restart queue worker
php artisan queue:restart
php artisan queue:work --verbose
```

---

**Next Steps:** See `_DOCS/LOCAL_DEVELOPMENT_SETUP.md` for complete guide
