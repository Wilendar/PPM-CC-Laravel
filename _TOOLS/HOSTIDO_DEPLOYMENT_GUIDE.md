# 🚀 HOSTIDO DEPLOYMENT GUIDE - PPM-CC-Laravel

Kompletny przewodnik po skryptach PowerShell do automatycznego deployment aplikacji Laravel na serwer Hostido.net.pl.

## 📋 Spis Treści

1. [Przygotowanie Środowiska](#przygotowanie-środowiska)
2. [Skrypty Deployment](#skrypty-deployment)
3. [Przykłady Użycia](#przykłady-użycia)
4. [Rozwiązywanie Problemów](#rozwiązywanie-problemów)
5. [Best Practices](#best-practices)

## 🔧 Przygotowanie Środowiska

### Wymagania Systemowe
- **OS**: Windows 10/11
- **PowerShell**: 7.0+ (`pwsh`)
- **SSH Key**: `D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk`
- **Narzędzia**: PuTTY, WinSCP (auto-install dostępny)

### Dane Serwera Hostido
```plaintext
Host: host379076.hostido.net.pl:64321
User: host379076
SSH Key: HostidoSSHNoPass.ppk
Laravel Path: /domains/ppm.mpptrade.pl/public_html/
Database: host379076_ppm@localhost (MariaDB)
URL: https://ppm.mpptrade.pl
```

### Pierwsza Instalacja Narzędzi
```powershell
# Instalacja PuTTY
.\hostido_automation.ps1 -InstallPuTTY

# Instalacja WinSCP
.\hostido_deploy.ps1 -InstallWinSCP

# Test połączenia SSH
.\hostido_automation.ps1 -TestConnection

# Setup katalogów na serwerze
.\hostido_deploy.ps1 -SetupDirectories
```

## 🔄 Skrypty Deployment

### 1. `hostido_automation.ps1` - SSH Automation & Monitoring

**Funkcje:**
- Wykonywanie komend SSH na serwerze
- Health check aplikacji Laravel
- Pobieranie i monitoring logów
- Real-time monitoring aplikacji

**Parametry:**
```powershell
-Command "string"          # Komenda do wykonania
-TestConnection           # Test połączenia SSH
-HealthCheck             # Sprawdzenie stanu Laravel
-GetLogs                 # Pobranie logów aplikacji
-MonitorApp             # Real-time monitoring
-LogLevel "error|info|debug"  # Poziom logów (default: error)
-LogLines 50            # Liczba linii logów (default: 50)
-InstallPuTTY          # Instalacja PuTTY
```

**Przykłady:**
```powershell
# Health check aplikacji
.\hostido_automation.ps1 -HealthCheck

# Pobranie ostatnich 100 linii logów
.\hostido_automation.ps1 -GetLogs -LogLines 100 -LogLevel "info"

# Monitoring aplikacji w czasie rzeczywistym
.\hostido_automation.ps1 -MonitorApp

# Wykonanie custom komendy
.\hostido_automation.ps1 -Command "cd /domains/ppm.mpptrade.pl/public_html && php artisan --version"
```

### 2. `hostido_deploy.ps1` - File Deployment & Management

**Funkcje:**
- Upload plików aplikacji na serwer
- Automatyczne backup przed deployment
- Rollback do poprzednich wersji
- Health check po deployment
- Post-deployment commands (cache, permissions)

**Parametry:**
```powershell
-SourcePath "path"       # Ścieżka źródłowa (default: ".")
-TargetPath "path"       # Ścieżka docelowa na serwerze
-Command "string"        # Custom komenda po deployment
-UploadOnly             # Tylko upload plików
-CommandOnly            # Tylko wykonanie komendy
-CreateBackup           # Tylko tworzenie backup
-RestoreBackup          # Przywracanie z backup
-BackupName "name"      # Nazwa backup (auto-gen if empty)
-HealthCheck            # Tylko health check
-DryRun                 # Symulacja bez wykonania
-Force                  # Wymuszenie działania
-Verbose                # Szczegółowe logi
-SetupDirectories       # Setup struktury katalogów
-InstallWinSCP         # Instalacja WinSCP
```

**Przykłady:**
```powershell
# Pełny deployment z backup i health check
.\hostido_deploy.ps1 -Verbose

# Tylko upload plików (bez komend Laravel)
.\hostido_deploy.ps1 -UploadOnly -Verbose

# Tworzenie named backup
.\hostido_deploy.ps1 -CreateBackup -BackupName "before_v2.0"

# Rollback do konkretnego backup
.\hostido_deploy.ps1 -RestoreBackup -BackupName "backup_20250908_143022"

# Dry-run deployment (test bez wykonania)
.\hostido_deploy.ps1 -DryRun -Verbose

# Health check aplikacji
.\hostido_deploy.ps1 -HealthCheck
```

### 3. `hostido_build.ps1` - Asset Building & Cache Management

**Funkcje:**
- Budowanie assets lokalnie (Vite)
- Upload zbudowanych assets na serwer
- Zarządzanie cache Laravel
- Optymalizacja dla production

**Parametry:**
```powershell
-Environment "dev|production"  # Środowisko build (default: production)
-AssetsOnly                   # Tylko operacje assets
-CacheOnly                    # Tylko operacje cache
-LocalBuild                   # Tylko lokalny build (bez upload)
-DryRun                       # Symulacja bez wykonania
-Verbose                      # Szczegółowe logi
```

**Przykłady:**
```powershell
# Pełny build i deploy assets
.\hostido_build.ps1 -Environment production -Verbose

# Tylko lokalny build assets
.\hostido_build.ps1 -LocalBuild -Verbose

# Tylko operacje cache na serwerze
.\hostido_build.ps1 -CacheOnly -Verbose

# Build development (bez optymalizacji)
.\hostido_build.ps1 -Environment dev -Verbose

# Test build pipeline
.\hostido_build.ps1 -DryRun -Verbose
```

## 📚 Przykłady Użycia

### Standardowy Workflow Development

#### 1. Rozwój Lokalny → Deployment Production
```powershell
# 1. Build assets production
.\hostido_build.ps1 -Environment production -Verbose

# 2. Pełny deployment z backup
.\hostido_deploy.ps1 -Verbose

# 3. Sprawdzenie stanu aplikacji
.\hostido_automation.ps1 -HealthCheck
```

#### 2. Szybki Upload Zmian (bez pełnego build)
```powershell
# Tylko upload plików aplikacji
.\hostido_deploy.ps1 -UploadOnly -Verbose

# Clear cache bez rebuild
.\hostido_build.ps1 -CacheOnly -Verbose

# Health check
.\hostido_automation.ps1 -HealthCheck
```

#### 3. Deployment z Custom Komendami
```powershell
# Deployment + migracje bazy danych
.\hostido_deploy.ps1 -Command "php artisan migrate --force" -Verbose

# Deployment + custom seeder
.\hostido_deploy.ps1 -Command "php artisan db:seed --class=ProductSeeder" -Verbose
```

### Emergency Procedures

#### 1. Rollback do Poprzedniej Wersji
```powershell
# Lista dostępnych backup
.\hostido_automation.ps1 -Command "ls -la /domains/ppm.mpptrade.pl/backups/"

# Rollback do konkretnego backup
.\hostido_deploy.ps1 -RestoreBackup -BackupName "backup_20250908_143022" -Force
```

#### 2. Debugging Problemów
```powershell
# Szczegółowy health check
.\hostido_automation.ps1 -HealthCheck

# Pobranie szczegółowych logów
.\hostido_automation.ps1 -GetLogs -LogLevel "debug" -LogLines 200

# Real-time monitoring
.\hostido_automation.ps1 -MonitorApp
```

#### 3. Naprawa Uprawnień i Cache
```powershell
# Fix permissions + clear cache
.\hostido_automation.ps1 -Command "cd /domains/ppm.mpptrade.pl/public_html && chmod -R 775 storage/ bootstrap/cache/ && php artisan optimize:clear"

# Rebuild cache
.\hostido_build.ps1 -CacheOnly
```

### Maintenance Procedures

#### 1. Regularne Backup
```powershell
# Daily backup
.\hostido_deploy.ps1 -CreateBackup -BackupName "daily_$(Get-Date -Format 'yyyyMMdd')"

# Pre-maintenance backup
.\hostido_deploy.ps1 -CreateBackup -BackupName "pre_maintenance_$(Get-Date -Format 'yyyyMMdd_HHmm')"
```

#### 2. Performance Monitoring
```powershell
# Monitoring aplikacji przez 5 minut
timeout 300 .\hostido_automation.ps1 -MonitorApp

# Health check z szczegółowymi info
.\hostido_automation.ps1 -HealthCheck
```

## ⚠️ Rozwiązywanie Problemów

### Problem: SSH Connection Failed
```powershell
# Sprawdzenie klucza SSH
Test-Path "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

# Test połączenia manual
.\hostido_automation.ps1 -TestConnection

# Reinstalacja PuTTY
.\hostido_automation.ps1 -InstallPuTTY
```

### Problem: WinSCP Upload Failed
```powershell
# Reinstalacja WinSCP
.\hostido_deploy.ps1 -InstallWinSCP

# Test upload z verbose logging
.\hostido_deploy.ps1 -UploadOnly -Verbose

# Manual SFTP test
.\hostido_automation.ps1 -Command "echo 'SFTP test successful'"
```

### Problem: Laravel Errors po Deployment
```powershell
# Clear wszystkie cache
.\hostido_build.ps1 -CacheOnly

# Sprawdzenie uprawnień
.\hostido_automation.ps1 -Command "cd /domains/ppm.mpptrade.pl/public_html && find storage/ -type f -exec chmod 664 {} \; && find storage/ -type d -exec chmod 775 {} \;"

# Health check z detalami
.\hostido_automation.ps1 -HealthCheck

# Sprawdzenie logów błędów
.\hostido_automation.ps1 -GetLogs -LogLevel "error" -LogLines 100
```

### Problem: Assets Nie Ładują Się
```powershell
# Rebuild assets
.\hostido_build.ps1 -Environment production -Verbose

# Sprawdzenie czy assets są na serwerze
.\hostido_automation.ps1 -Command "ls -la /domains/ppm.mpptrade.pl/public_html/public/build/"

# Clear browser cache & Laravel cache
.\hostido_build.ps1 -CacheOnly
```

## 🎯 Best Practices

### 1. Przed Każdym Deployment
```powershell
# 1. Test połączenia
.\hostido_automation.ps1 -TestConnection

# 2. Health check obecnej wersji
.\hostido_automation.ps1 -HealthCheck

# 3. Dry-run deployment
.\hostido_deploy.ps1 -DryRun -Verbose

# 4. Pełny deployment z backup
.\hostido_deploy.ps1 -Verbose
```

### 2. Po Deployment
```powershell
# 1. Health check nowej wersji
.\hostido_automation.ps1 -HealthCheck

# 2. Sprawdzenie logów
.\hostido_automation.ps1 -GetLogs -LogLevel "error" -LogLines 50

# 3. Test funkcjonalności przez browser
# https://ppm.mpptrade.pl
```

### 3. Regularna Maintenance
```powershell
# Co tydzień: backup + cleanup
.\hostido_deploy.ps1 -CreateBackup -BackupName "weekly_$(Get-Date -Format 'yyyyMMdd')"

# Co miesiąc: cleanup starych backup (manual)
.\hostido_automation.ps1 -Command "cd /domains/ppm.mpptrade.pl/backups && ls -la"

# Monitor health
.\hostido_automation.ps1 -HealthCheck
```

### 4. Security Guidelines
- **NIGDY** nie commituj SSH keys do git
- **ZAWSZE** używaj backup przed deployment
- **SPRAWDZAJ** health po każdym deployment
- **MONITORUJ** logi błędów regularnie

### 5. Performance Guidelines
- Używaj `-Environment production` dla production builds
- Zawsze cache Laravel config/routes/views dla production
- Sprawdzaj disk space regularnie
- Monitoruj response times aplikacji

## 🔗 Przydatne Komendy

### Laravel Commands na Serwerze
```powershell
# Migracje
.\hostido_automation.ps1 -Command "cd /domains/ppm.mpptrade.pl/public_html && php artisan migrate --force"

# Seeder
.\hostido_automation.ps1 -Command "cd /domains/ppm.mpptrade.pl/public_html && php artisan db:seed"

# Queue worker status
.\hostido_automation.ps1 -Command "cd /domains/ppm.mpptrade.pl/public_html && php artisan queue:work --once"

# Clear wszystko
.\hostido_automation.ps1 -Command "cd /domains/ppm.mpptrade.pl/public_html && php artisan optimize:clear"

# Reoptimize dla production
.\hostido_automation.ps1 -Command "cd /domains/ppm.mpptrade.pl/public_html && php artisan optimize"
```

### System Commands na Serwerze
```powershell
# Disk usage
.\hostido_automation.ps1 -Command "df -h | grep domains"

# Memory usage
.\hostido_automation.ps1 -Command "free -h"

# PHP processes
.\hostido_automation.ps1 -Command "ps aux | grep php"

# Check PHP version
.\hostido_automation.ps1 -Command "php -v"
```

---

## 📞 Kontakt i Support

W przypadku problemów:

1. **Sprawdź logi**: `.\hostido_automation.ps1 -GetLogs -LogLevel "error"`
2. **Health check**: `.\hostido_automation.ps1 -HealthCheck`
3. **Dry-run test**: `.\hostido_deploy.ps1 -DryRun -Verbose`
4. **Dokumentacja Laravel**: https://laravel.com/docs
5. **Hostido Support**: https://hostido.net.pl

**Utworzono**: 2025-09-08  
**Wersja**: 1.0  
**Ostatnia aktualizacja**: 2025-09-08