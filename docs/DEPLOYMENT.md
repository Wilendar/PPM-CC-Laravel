# Deployment PPM-CC-Laravel - Hybrydowy Workflow

## 📋 Spis Treści

1. [Filozofia Hybrydowego Workflow](#filozofia-hybrydowego-workflow)
2. [Przygotowanie Środowiska](#przygotowanie-środowiska)
3. [Skrypty PowerShell](#skrypty-powershell)
4. [Development Workflow](#development-workflow)
5. [Production Deployment](#production-deployment)
6. [Frontend Assets Pipeline](#frontend-assets-pipeline)
7. [Database Operations](#database-operations)
8. [Monitoring i Health Checks](#monitoring-i-health-checks)
9. [Troubleshooting Deployment](#troubleshooting-deployment)
10. [Best Practices](#best-practices)

## 🎯 Filozofia Hybrydowego Workflow

### Dlaczego Hybrydowy?

**Tradycyjny lokalny development** ma wady:
- Konfiguracja PHP 8.3 + extensions na Windows
- MySQL setup i maintenance
- Różnice między lokalnym a produkcyjnym środowiskiem
- Memory limits i inne ograniczenia hostingu współdzielonego

**Nasze rozwiązanie hybrydowe:**
1. 💻 **LOKALNIE**: Pisanie kodu (VS Code), frontend build (Node.js + Vite)
2. 🚀 **DEPLOY**: Automatyczny upload przez SSH/SFTP
3. 🧪 **TEST**: Weryfikacja na https://ppm.mpptrade.pl (środowisko rzeczywiste)
4. 🗄️ **DATABASE**: Bezpośrednia praca na MariaDB produkcyjnej

### Korzyści
- ✅ **Zero konfiguracji** lokalnej bazy/PHP
- ✅ **Identyczne środowisko** development ↔ production
- ✅ **Natychmiastowe testy** na rzeczywistych danych
- ✅ **Automatyzacja** przez PowerShell scripts
- ✅ **Windows-native** workflow

## 🛠️ Przygotowanie Środowiska

### Wymagane Narzędzia Windows

```powershell
# Sprawdź obecne narzędzia
pwsh --version          # PowerShell 7.x
node --version          # v18.17.0+
npm --version           # v9.0.0+
git --version           # Git for Windows
code --version          # VS Code (opcjonalnie)
```

### SSH/SFTP Configuration

**PuTTY + WinSCP Setup:**
```powershell
# Ścieżki narzędzi (example)
$PuTTYPath = "C:\Program Files\PuTTY\plink.exe"
$WinSCPPath = "C:\Program Files (x86)\WinSCP\WinSCP.com"

# Test połączenia SSH
$Key = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
& $PuTTYPath -ssh host379076@host379076.hostido.net.pl -P 64321 -i $Key -batch "pwd"
```

### Klonowanie Repository

```powershell
# Klonowanie projektu
cd "D:\OneDrive - MPP TRADE\Skrypty\"
git clone [repository-url] PPM-CC-Laravel
cd PPM-CC-Laravel

# Setup npm dependencies (lokalnie)
npm install
```

## ⚡ Skrypty PowerShell

### Struktura Skryptów w _TOOLS/

```
_TOOLS/
├── hostido_deploy.ps1           # 🚀 Główny deployment
├── hostido_automation.ps1       # 🤖 SSH automation functions  
├── hostido_build.ps1           # 🔨 Frontend build + upload
├── hostido_frontend_deploy.ps1  # 🎨 Quick assets upload
└── mydevil_*.ps1               # 📦 Backup scripts (MyDevil)
```

### 1. hostido_deploy.ps1 - Główny Deployment

**Podstawowe użycie:**
```powershell
# Full deployment (kod + composer + migracje + cache)
.\_TOOLS\hostido_deploy.ps1

# Tylko upload plików (szybki development)
.\_TOOLS\hostido_deploy.ps1 -UploadOnly

# Tylko wykonanie komend (po manual upload)
.\_TOOLS\hostido_deploy.ps1 -CommandOnly

# Health check aplikacji
.\_TOOLS\hostido_deploy.ps1 -HealthCheck

# Dry run (pokaż co by się stało)
.\_TOOLS\hostido_deploy.ps1 -DryRun
```

**Zaawansowane opcje:**
```powershell
# Custom source path
.\_TOOLS\hostido_deploy.ps1 -SourcePath ".\custom_build"

# Custom target path  
.\_TOOLS\hostido_deploy.ps1 -TargetPath "/domains/ppm.mpptrade.pl/test"

# Execute specific command on server
.\_TOOLS\hostido_deploy.ps1 -Command "php artisan migrate:status"

# Force override (bez backup)
.\_TOOLS\hostido_deploy.ps1 -Force

# Verbose output
.\_TOOLS\hostido_deploy.ps1 -Verbose
```

### 2. Funkcjonalności Deployment Script

**Co robi pełny deployment:**

```powershell
# 1. Pre-deployment checks
Test-Connection ppm.mpptrade.pl  # Sprawdź dostępność serwera
Test-SSHConnection              # Sprawdź SSH connectivity

# 2. File upload (excludes)
# - .git/, node_modules/, tests/
# - .env (używa .env.example jako template)
# - storage/logs/, storage/cache/
# - public/build/ (handled separately)

# 3. Server commands (SSH)
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache  
php artisan view:cache
composer dump-autoload --optimize

# 4. Health check
curl -s https://ppm.mpptrade.pl/health  # Custom endpoint
```

**Backup automatyczny:**
```powershell
# Backup przed deployment
.\_TOOLS\hostido_deploy.ps1 -CreateBackup

# Restore jeśli coś poszło nie tak
.\_TOOLS\hostido_deploy.ps1 -RestoreBackup -BackupName "backup_20240908_143022"

# Lista dostępnych backup
.\_TOOLS\hostido_deploy.ps1 -ListBackups
```

### 3. hostido_build.ps1 - Frontend Assets

**Build process:**
```powershell
# Full frontend build
.\_TOOLS\hostido_build.ps1

# Quick CSS/JS update
.\_TOOLS\hostido_frontend_deploy.ps1

# Build z optymalizacją
.\_TOOLS\hostido_build.ps1 -Optimize

# Build + upload assets
.\_TOOLS\hostido_build.ps1 -UploadAssets
```

**Co się dzieje podczas build:**
```bash
# Lokalnie
npm install                     # Update dependencies
npm run build                  # Vite production build
                              # Output: public/build/

# Upload assets
# public/build/ → server:/domains/.../public_html/build/
# Preserve cache busting hashes
# Update manifest.json
```

## 🔄 Development Workflow

### Typowy Dzień Developera

**1. Poranek - Pull latest changes:**
```powershell
cd "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"
git pull origin main
npm install  # Update frontend dependencies jeśli package.json się zmienił
```

**2. Development cycle:**

```powershell
# A) Praca z kodem (VS Code)
code .  # Otwórz VS Code
# Edytuj pliki PHP, Blade, etc...

# B) Quick deployment (tylko kod)
.\_TOOLS\hostido_deploy.ps1 -UploadOnly

# C) Test na https://ppm.mpptrade.pl
# Otwórz browser i sprawdź changes

# D) Jeśli problem - sprawdź logi
.\_TOOLS\hostido_deploy.ps1 -Command "tail -n 50 storage/logs/laravel.log"
```

**3. Frontend changes:**
```powershell
# Praca z CSS/JS
npm run dev    # Local Vite development server (opcjonalnie)

# Build + deploy assets
.\_TOOLS\hostido_build.ps1 -UploadAssets

# Lub szybki update tylko CSS
.\_TOOLS\hostido_frontend_deploy.ps1
```

**4. Database changes:**
```powershell
# Utwórz migrację (lokalnie)
# Nie masz PHP lokalnie? Użyj SSH!

.\_TOOLS\hostido_deploy.ps1 -Command "php artisan make:migration create_products_table"

# Upload migrations
.\_TOOLS\hostido_deploy.ps1 -UploadOnly

# Run migrations  
.\_TOOLS\hostido_deploy.ps1 -Command "php artisan migrate"
```

### Quick Commands

**Najczęściej używane komendy:**
```powershell
# Deploy tylko aplikacji (bez build)
.\_TOOLS\hostido_deploy.ps1 -UploadOnly

# Deploy full z migracjami
.\_TOOLS\hostido_deploy.ps1

# Check aplikacji
.\_TOOLS\hostido_deploy.ps1 -HealthCheck  

# Zobacz logi
.\_TOOLS\hostido_deploy.ps1 -Command "tail -f storage/logs/laravel.log"

# Clear cache
.\_TOOLS\hostido_deploy.ps1 -Command "php artisan optimize:clear"

# Check status migracji
.\_TOOLS\hostido_deploy.ps1 -Command "php artisan migrate:status"
```

## 🚀 Production Deployment

### Release Deployment Process

**1. Pre-production checklist:**
```powershell
# Sprawdź branch i commit
git status
git log --oneline -n 5

# Run local tests (jeśli są)
npm run test   # Frontend tests
# php artisan test  # Backend tests (przez SSH)
```

**2. Full production deployment:**
```powershell
# Backup bieżącej wersji
.\_TOOLS\hostido_deploy.ps1 -CreateBackup

# Full deployment
.\_TOOLS\hostido_deploy.ps1 -Verbose

# Health check
.\_TOOLS\hostido_deploy.ps1 -HealthCheck
```

**3. Post-deployment verification:**
```powershell
# Sprawdź czy aplikacja działa
curl -I https://ppm.mpptrade.pl
# HTTP/1.1 200 OK

# Sprawdź logi czy brak błędów
.\_TOOLS\hostido_deploy.ps1 -Command "tail -n 100 storage/logs/laravel.log | grep ERROR"

# Check database migrations
.\_TOOLS\hostido_deploy.ps1 -Command "php artisan migrate:status"

# Test kluczowych funkcjonalności
# - Login page loads
# - Dashboard accessible
# - Database connections work
```

### Rollback Procedure

**Jeśli deployment się nie udał:**
```powershell
# 1. Przywróć backup
.\_TOOLS\hostido_deploy.ps1 -RestoreBackup -BackupName "backup_YYYYMMDD_HHMMSS"

# 2. Rollback migracji (jeśli potrzebne)
.\_TOOLS\hostido_deploy.ps1 -Command "php artisan migrate:rollback --step=1"

# 3. Clear cache
.\_TOOLS\hostido_deploy.ps1 -Command "php artisan optimize:clear"

# 4. Health check
.\_TOOLS\hostido_deploy.ps1 -HealthCheck
```

## 🎨 Frontend Assets Pipeline

### Vite Configuration

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
        outDir: 'public/build',          // Output na public/build/
        manifest: true,                  // Generate manifest.json
        rollupOptions: {
            output: {
                manualChunks: {
                    'vendor': ['alpine'],   // Separate vendor chunks
                }
            }
        }
    },
});
```

### Build Process

**Development build (lokalny testing):**
```bash
npm run dev     # Vite dev server na http://localhost:5173
                # Hot reload, source maps
```

**Production build:**
```bash
npm run build   # Minification, optimization, cache busting
                # Output: public/build/assets/app-[hash].css
                #         public/build/assets/app-[hash].js
                #         public/build/manifest.json
```

### Upload Strategy

**Efektywny upload assets:**
```powershell
# hostido_build.ps1 logic:
# 1. npm run build (locally)
# 2. Compare hashes (only upload changed files)
# 3. Upload public/build/ directory
# 4. Update manifest.json
# 5. Clear Laravel view cache (Blade może cache @vite)
```

**Cache busting:**
Laravel Vite automatically handles cache busting through filename hashes:
```blade
{{-- W Blade templates --}}
@vite(['resources/css/app.css', 'resources/js/app.js'])

{{-- Generuje: --}}
<link rel="stylesheet" href="/build/assets/app-abc123.css">
<script type="module" src="/build/assets/app-def456.js"></script>
```

## 🗄️ Database Operations

### Migration Workflow

**Tworzenie migracji:**
```powershell
# Użyj SSH jeśli nie masz PHP lokalnie
.\_TOOLS\hostido_deploy.ps1 -Command "php artisan make:migration create_products_table --create=products"

# Download created migration locally for editing
# (manual SFTP or use WinSCP)
```

**Edycja i deployment migracji:**
```powershell
# 1. Edit migration locally in VS Code
# database/migrations/2024_09_08_123456_create_products_table.php

# 2. Upload migration
.\_TOOLS\hostido_deploy.ps1 -UploadOnly

# 3. Run migration on server
.\_TOOLS\hostido_deploy.ps1 -Command "php artisan migrate"

# 4. Verify migration
.\_TOOLS\hostido_deploy.ps1 -Command "php artisan migrate:status"
```

### Seeder Management

**Database seeding:**
```powershell
# Create seeder
.\_TOOLS\hostido_deploy.ps1 -Command "php artisan make:seeder ProductsSeeder"

# Upload seeder after editing locally
.\_TOOLS\hostido_deploy.ps1 -UploadOnly

# Run seeder
.\_TOOLS\hostido_deploy.ps1 -Command "php artisan db:seed --class=ProductsSeeder"
```

### Database Backup

**Automatic backup during deployment:**
```powershell
# hostido_deploy.ps1 automatically creates DB backup przed migration
# Backup location: /domains/ppm.mpptrade.pl/backups/db/

# Manual backup
.\_TOOLS\hostido_deploy.ps1 -Command "mysqldump -h localhost -u host379076_ppm -p'qkS4FuXMMDDN4DJhatg6' host379076_ppm > backup_$(date +%Y%m%d_%H%M%S).sql"
```

## 📊 Monitoring i Health Checks

### Health Check Endpoint

**Utworzenie health check:**
```php
// routes/web.php
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'database' => DB::connection()->getPdo() ? 'connected' : 'disconnected',
        'laravel' => app()->version(),
        'php' => PHP_VERSION,
        'timestamp' => now()->toISOString(),
    ]);
});
```

**Użycie w deployment:**
```powershell
# health check w skrypcie deployment
$healthCheck = Invoke-RestMethod -Uri "https://ppm.mpptrade.pl/health" -Method Get
if ($healthCheck.status -eq "ok") {
    Write-Host "✅ Application is healthy" -ForegroundColor Green
} else {
    Write-Host "❌ Application health check failed" -ForegroundColor Red
}
```

### Log Monitoring

**Sprawdzanie logów:**
```powershell
# Ostatnie błędy
.\_TOOLS\hostido_deploy.ps1 -Command "grep ERROR storage/logs/laravel.log | tail -n 10"

# Live log watching (Ctrl+C aby wyjść)
.\_TOOLS\hostido_deploy.ps1 -Command "tail -f storage/logs/laravel.log"

# Log rotation check  
.\_TOOLS\hostido_deploy.ps1 -Command "ls -la storage/logs/ | head -10"
```

### Performance Monitoring

**Basic metrics:**
```powershell
# Response time check
Measure-Command { Invoke-WebRequest -Uri "https://ppm.mpptrade.pl" }

# Server resources
.\_TOOLS\hostido_deploy.ps1 -Command "df -h ."  # Disk space
.\_TOOLS\hostido_deploy.ps1 -Command "free -m"  # Memory (jeśli dostępne)
```

## 🔧 Troubleshooting Deployment

### Częste Problemy

**1. SSH Connection Failed**
```powershell
# Debug SSH connection
$Key = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $Key -v

# Check network connectivity
Test-NetConnection host379076.hostido.net.pl -Port 64321
```

**2. File Upload Errors**
```powershell
# Check WinSCP installation
if (!(Test-Path "C:\Program Files (x86)\WinSCP\WinSCP.com")) {
    Write-Error "WinSCP not found - install from winscp.net"
}

# Manual SFTP test
& "C:\Program Files (x86)\WinSCP\WinSCP.com" /command "open sftp://host379076@host379076.hostido.net.pl:64321/ -privatekey=""$Key""" "ls" "exit"
```

**3. Composer Install Failed**
```bash
# SSH manually and debug
ssh -p 64321 host379076@host379076.hostido.net.pl
cd /domains/ppm.mpptrade.pl/public_html

# Check composer issues
composer diagnose
composer install --no-dev --optimize-autoloader -v

# Memory limit issues na hostingu współdzielonym
COMPOSER_MEMORY_LIMIT=512M composer install --no-dev
```

**4. Laravel 500 Error Po Deployment**
```powershell
# Clear all cache
.\_TOOLS\hostido_deploy.ps1 -Command "php artisan optimize:clear"

# Check logs dla specific error
.\_TOOLS\hostido_deploy.ps1 -Command "tail -n 50 storage/logs/laravel.log"

# Check file permissions
.\_TOOLS\hostido_deploy.ps1 -Command "find . -type d -name storage -exec chmod 775 {} \;"
.\_TOOLS\hostido_deploy.ps1 -Command "find . -type d -name bootstrap/cache -exec chmod 775 {} \;"
```

**5. Assets Not Loading**
```powershell
# Check Vite manifest
.\_TOOLS\hostido_deploy.ps1 -Command "cat public/build/manifest.json"

# Rebuild i re-upload assets
.\_TOOLS\hostido_build.ps1 -Force

# Check browser developer tools dla 404 errors
# Common issue: public/build/ folder nie został przesłany
```

### Debug Mode

**Temporary debug enable:**
```powershell
# Enable debug mode (ONLY for debugging!)
.\_TOOLS\hostido_deploy.ps1 -Command "sed -i 's/APP_DEBUG=false/APP_DEBUG=true/' .env"

# Po debugging disable!
.\_TOOLS\hostido_deploy.ps1 -Command "sed -i 's/APP_DEBUG=true/APP_DEBUG=false/' .env"
```

### Recovery Commands

**Emergency recovery:**
```powershell
# Complete application reset (USE WITH CAUTION!)
.\_TOOLS\hostido_deploy.ps1 -Command "php artisan optimize:clear"
.\_TOOLS\hostido_deploy.ps1 -Command "composer dump-autoload"
.\_TOOLS\hostido_deploy.ps1 -Command "php artisan key:generate"
.\_TOOLS\hostido_deploy.ps1 -Command "php artisan config:cache"
```

## 📋 Best Practices

### 1. Git Workflow Integration

```powershell
# Pre-deployment git checks
$currentBranch = git rev-parse --abbrev-ref HEAD
if ($currentBranch -ne "main") {
    Write-Warning "⚠️  You're on branch '$currentBranch', not 'main'"
    Read-Host "Press Enter to continue anyway..."
}

# Commit status
$uncommittedChanges = git status --porcelain
if ($uncommittedChanges) {
    Write-Warning "⚠️  You have uncommitted changes"
    git status
}
```

### 2. Environment Configuration

**Never upload .env directly:**
```powershell
# hostido_deploy.ps1 excludes .env automatycznie
# Użyj .env.example jako template na serwerze
# Manual .env configuration na serwerze przez SSH
```

**Environment variables checklist:**
```bash
# Na serwerze sprawdź .env:
APP_ENV=production          # ✅ Production mode
APP_DEBUG=false            # ✅ Debug disabled  
APP_URL=https://ppm.mpptrade.pl  # ✅ Correct URL
DB_CONNECTION=mysql        # ✅ Database configured
LOG_LEVEL=error           # ✅ Tylko błędy w production
```

### 3. Security Considerations

**File Permissions:**
```bash
# Po każdym deployment sprawdź uprawnienia
find . -type f -exec chmod 644 {} \;    # Pliki: 644
find . -type d -exec chmod 755 {} \;    # Foldery: 755
chmod -R 775 storage bootstrap/cache     # Laravel cache: 775
```

**Sensitive Files:**
```powershell
# Te pliki NIGDY nie są uploadowane (excluded in scripts):
# .env                    # Credentials
# storage/logs/          # Log files
# storage/cache/         # Cache files
# node_modules/          # Frontend dependencies
# .git/                  # Git repository
# tests/                 # Test files
```

### 4. Performance Optimization

**Cache Strategy:**
```powershell
# Po każdym deployment
.\_TOOLS\hostido_deploy.ps1 -Command "php artisan config:cache"     # Config cache
.\_TOOLS\hostido_deploy.ps1 -Command "php artisan route:cache"      # Route cache
.\_TOOLS\hostido_deploy.ps1 -Command "php artisan view:cache"       # Blade cache
.\_TOOLS\hostido_deploy.ps1 -Command "composer dump-autoload --optimize"  # Composer optimize
```

**Database Optimization:**
```bash
# Periodic maintenance (manual)
php artisan optimize
php artisan queue:restart    # Jeśli używasz queue
```

### 5. Backup Strategy

**Automated Backup:**
```powershell
# hostido_deploy.ps1 tworzy backup przed deployment
# Location: /domains/ppm.mpptrade.pl/backups/
# Retention: 7 dni (configurable)

# Manual cleanup starych backup
.\_TOOLS\hostido_deploy.ps1 -Command "find /domains/ppm.mpptrade.pl/backups/ -type f -mtime +7 -delete"
```

**Backup verification:**
```powershell
# List available backups
.\_TOOLS\hostido_deploy.ps1 -Command "ls -la /domains/ppm.mpptrade.pl/backups/ | tail -10"

# Test backup restore (on staging if available)
.\_TOOLS\hostido_deploy.ps1 -RestoreBackup -BackupName "backup_20240908_143022" -DryRun
```

### 6. Development Team Workflow

**Team coordination:**
```powershell
# Before deployment sprawdź team status
git log --oneline --since="1 day ago"    # Recent commits
git branch -r                            # Remote branches

# After deployment notify team
# Slack/Teams notification (można zintegrować w script)
Write-Host "🚀 Deployment completed - https://ppm.mpptrade.pl updated"
```

**Deployment slots (future):**
```powershell
# Możliwość deployment na test subdomain
.\_TOOLS\hostido_deploy.ps1 -TargetPath "/domains/test.ppm.mpptrade.pl/public_html"
# Test na https://test.ppm.mpptrade.pl
# Po weryfikacji deployment na production
```

---

**Next Steps:** [ARCHITECTURE.md](ARCHITECTURE.md) - Szczegółowa architektura systemu