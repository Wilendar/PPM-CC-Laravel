#Requires -Version 7.0
<#
.SYNOPSIS
    PPM-CC-Laravel Development Environment Autoinstaller

.DESCRIPTION
    Automatyczna instalacja srodowiska developerskiego dla projektu PPM-CC-Laravel.
    Instaluje: Composer, MariaDB, konfiguruje PHP extensions, tworzy baze danych.

.NOTES
    Autor: PPM Team
    Wersja: 1.0.0
    Data: 2025-12-04

.EXAMPLE
    pwsh -ExecutionPolicy Bypass -File autoinstaller_ppm_dev.ps1

.EXAMPLE
    pwsh -ExecutionPolicy Bypass -File autoinstaller_ppm_dev.ps1 -SkipMariaDB
#>

param(
    [switch]$SkipComposer,
    [switch]$SkipMariaDB,
    [switch]$SkipNodeModules,
    [switch]$DryRun,
    [string]$ProjectPath = "D:\Projekty\PPM-CC-Laravel-local",
    [string]$DbName = "ppm_cc_laravel_local",
    [string]$DbUser = "root",
    [string]$DbPassword = ""
)

$ErrorActionPreference = "Stop"
$ProgressPreference = "SilentlyContinue"

# ============================================
# KONFIGURACJA
# ============================================
$Config = @{
    ComposerUrl = "https://getcomposer.org/Composer-Setup.exe"
    MariaDBVersion = "10.11.13"
    MariaDBUrl = "https://downloads.mariadb.org/rest-api/mariadb/10.11.13/winx64-packages/"
    RequiredPHPExtensions = @("pdo_mysql", "mbstring", "openssl", "curl", "fileinfo", "gd", "zip", "redis")
    MinPHPVersion = "8.3.0"
    MinNodeVersion = "18.0.0"
}

# ============================================
# FUNKCJE POMOCNICZE
# ============================================
function Write-Step {
    param([string]$Message, [string]$Status = "INFO")
    $colors = @{
        "INFO" = "Cyan"
        "OK" = "Green"
        "WARN" = "Yellow"
        "ERROR" = "Red"
        "SKIP" = "DarkGray"
    }
    $symbols = @{
        "INFO" = "[*]"
        "OK" = "[+]"
        "WARN" = "[!]"
        "ERROR" = "[X]"
        "SKIP" = "[-]"
    }
    Write-Host "$($symbols[$Status]) " -ForegroundColor $colors[$Status] -NoNewline
    Write-Host $Message
}

function Test-Administrator {
    $currentUser = [Security.Principal.WindowsIdentity]::GetCurrent()
    $principal = New-Object Security.Principal.WindowsPrincipal($currentUser)
    return $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
}

function Get-InstalledSoftware {
    param([string]$Name)
    $paths = @(
        "HKLM:\SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall\*",
        "HKLM:\SOFTWARE\WOW6432Node\Microsoft\Windows\CurrentVersion\Uninstall\*",
        "HKCU:\SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall\*"
    )
    foreach ($path in $paths) {
        $items = Get-ItemProperty $path -ErrorAction SilentlyContinue | Where-Object { $_.DisplayName -like "*$Name*" }
        if ($items) { return $items }
    }
    return $null
}

function Test-Command {
    param([string]$Command)
    try {
        $null = Get-Command $Command -ErrorAction Stop
        return $true
    } catch {
        return $false
    }
}

# ============================================
# BANNER
# ============================================
Clear-Host
Write-Host ""
Write-Host "  =====================================================" -ForegroundColor Cyan
Write-Host "   PPM-CC-Laravel Development Environment Autoinstaller" -ForegroundColor Cyan
Write-Host "  =====================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "  Target: $ProjectPath" -ForegroundColor DarkGray
Write-Host "  Database: $DbName" -ForegroundColor DarkGray
Write-Host ""

if ($DryRun) {
    Write-Host "  [DRY RUN MODE - No changes will be made]" -ForegroundColor Yellow
    Write-Host ""
}

# ============================================
# SPRAWDZANIE UPRAWNIEN
# ============================================
Write-Host "=== SPRAWDZANIE UPRAWNIEN ===" -ForegroundColor Magenta
if (-not (Test-Administrator)) {
    Write-Step "Brak uprawnien administratora - niektore instalacje moga wymagac UAC" "WARN"
} else {
    Write-Step "Uruchomiono jako Administrator" "OK"
}

# ============================================
# KROK 1: SPRAWDZANIE WYMAGANYCH KOMPONENTOW
# ============================================
Write-Host ""
Write-Host "=== KROK 1: SPRAWDZANIE SYSTEMU ===" -ForegroundColor Magenta

# PHP
Write-Step "Sprawdzanie PHP..."
if (Test-Command "php") {
    $phpVersion = (php -v 2>&1 | Select-Object -First 1) -replace "PHP (\d+\.\d+\.\d+).*", '$1'
    if ([version]$phpVersion -ge [version]$Config.MinPHPVersion) {
        Write-Step "PHP $phpVersion - OK" "OK"
    } else {
        Write-Step "PHP $phpVersion - zbyt stara wersja (wymagane >= $($Config.MinPHPVersion))" "ERROR"
        exit 1
    }
} else {
    Write-Step "PHP nie znaleziono - zainstaluj PHP 8.3+ przed kontynuacja" "ERROR"
    Write-Host ""
    Write-Host "  Pobierz PHP: https://windows.php.net/download/" -ForegroundColor Yellow
    Write-Host "  Lub zainstaluj Laravel Herd: https://herd.laravel.com/windows" -ForegroundColor Yellow
    exit 1
}

# Node.js
Write-Step "Sprawdzanie Node.js..."
if (Test-Command "node") {
    $nodeVersion = (node -v 2>&1) -replace "v", ""
    if ([version]$nodeVersion -ge [version]$Config.MinNodeVersion) {
        Write-Step "Node.js $nodeVersion - OK" "OK"
    } else {
        Write-Step "Node.js $nodeVersion - zbyt stara wersja (wymagane >= $($Config.MinNodeVersion))" "WARN"
    }
} else {
    Write-Step "Node.js nie znaleziono - zainstaluj Node.js 18+ LTS" "ERROR"
    Write-Host ""
    Write-Host "  Pobierz: https://nodejs.org/" -ForegroundColor Yellow
    exit 1
}

# Git
Write-Step "Sprawdzanie Git..."
if (Test-Command "git") {
    $gitVersion = (git --version 2>&1) -replace "git version ", ""
    Write-Step "Git $gitVersion - OK" "OK"
} else {
    Write-Step "Git nie znaleziono - zainstaluj Git" "ERROR"
    Write-Host ""
    Write-Host "  Pobierz: https://git-scm.com/" -ForegroundColor Yellow
    exit 1
}

# ============================================
# KROK 2: INSTALACJA COMPOSER
# ============================================
Write-Host ""
Write-Host "=== KROK 2: COMPOSER ===" -ForegroundColor Magenta

if ($SkipComposer) {
    Write-Step "Pominieto (--SkipComposer)" "SKIP"
} elseif (Test-Command "composer") {
    $composerVersion = (composer -V 2>&1 | Select-Object -First 1) -replace "Composer version (\d+\.\d+\.\d+).*", '$1'
    Write-Step "Composer $composerVersion juz zainstalowany" "OK"
} else {
    Write-Step "Composer nie znaleziony - instalacja..."

    if ($DryRun) {
        Write-Step "[DRY RUN] Pobralbym Composer-Setup.exe i uruchomil" "SKIP"
    } else {
        # Metoda 1: winget (preferowana)
        if (Test-Command "winget") {
            Write-Step "Instalacja przez winget..."
            try {
                $result = winget install --id Composer.Composer --accept-source-agreements --accept-package-agreements 2>&1
                if ($LASTEXITCODE -eq 0) {
                    Write-Step "Composer zainstalowany przez winget" "OK"
                    # Refresh PATH
                    $env:Path = [System.Environment]::GetEnvironmentVariable("Path","Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path","User")
                } else {
                    throw "winget failed"
                }
            } catch {
                Write-Step "winget failed, probuje alternatywna metode..." "WARN"

                # Metoda 2: Bezposrednie pobranie
                $composerSetup = "$env:TEMP\Composer-Setup.exe"
                Write-Step "Pobieranie Composer-Setup.exe..."
                Invoke-WebRequest -Uri $Config.ComposerUrl -OutFile $composerSetup -UseBasicParsing

                Write-Step "Uruchamianie instalatora (wymaga interakcji)..."
                Start-Process -FilePath $composerSetup -Wait
                Remove-Item $composerSetup -Force -ErrorAction SilentlyContinue

                # Refresh PATH
                $env:Path = [System.Environment]::GetEnvironmentVariable("Path","Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path","User")
            }
        } else {
            # Metoda 2: Bezposrednie pobranie
            $composerSetup = "$env:TEMP\Composer-Setup.exe"
            Write-Step "Pobieranie Composer-Setup.exe..."
            Invoke-WebRequest -Uri $Config.ComposerUrl -OutFile $composerSetup -UseBasicParsing

            Write-Step "Uruchamianie instalatora (wymaga interakcji)..."
            Start-Process -FilePath $composerSetup -Wait
            Remove-Item $composerSetup -Force -ErrorAction SilentlyContinue

            # Refresh PATH
            $env:Path = [System.Environment]::GetEnvironmentVariable("Path","Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path","User")
        }

        # Weryfikacja
        if (Test-Command "composer") {
            Write-Step "Composer zainstalowany pomyslnie" "OK"
        } else {
            Write-Step "Composer nie jest dostepny w PATH - moze byc potrzebny restart terminala" "WARN"
        }
    }
}

# ============================================
# KROK 3: INSTALACJA MARIADB
# ============================================
Write-Host ""
Write-Host "=== KROK 3: MARIADB ===" -ForegroundColor Magenta

if ($SkipMariaDB) {
    Write-Step "Pominieto (--SkipMariaDB)" "SKIP"
} elseif (Test-Command "mysql") {
    $mysqlVersion = (mysql --version 2>&1) -replace ".*Distrib (\d+\.\d+\.\d+).*", '$1'
    Write-Step "MySQL/MariaDB $mysqlVersion juz zainstalowany" "OK"
} elseif (Get-InstalledSoftware "MariaDB") {
    Write-Step "MariaDB zainstalowany (nie w PATH)" "WARN"
    Write-Host "  Dodaj MariaDB do PATH lub uruchom ponownie terminal" -ForegroundColor Yellow
} else {
    Write-Step "MariaDB nie znaleziony - instalacja..."

    if ($DryRun) {
        Write-Step "[DRY RUN] Zainstalowalabym MariaDB $($Config.MariaDBVersion)" "SKIP"
    } else {
        # Metoda 1: winget (preferowana)
        if (Test-Command "winget") {
            Write-Step "Instalacja MariaDB przez winget..."
            try {
                $result = winget install --id MariaDB.Server --accept-source-agreements --accept-package-agreements 2>&1
                if ($LASTEXITCODE -eq 0) {
                    Write-Step "MariaDB zainstalowany przez winget" "OK"
                } else {
                    throw "winget failed"
                }
            } catch {
                Write-Step "winget failed - pobierz MariaDB recznie:" "WARN"
                Write-Host ""
                Write-Host "  https://mariadb.org/download/?t=mariadb&p=mariadb&r=10.11.13&os=windows" -ForegroundColor Yellow
                Write-Host ""
            }
        } else {
            Write-Step "winget niedostepny - pobierz MariaDB recznie:" "WARN"
            Write-Host ""
            Write-Host "  https://mariadb.org/download/?t=mariadb&p=mariadb&r=10.11.13&os=windows" -ForegroundColor Yellow
            Write-Host ""
        }
    }
}

# ============================================
# KROK 4: KOPIOWANIE PROJEKTU
# ============================================
Write-Host ""
Write-Host "=== KROK 4: KOPIOWANIE PROJEKTU ===" -ForegroundColor Magenta

$SourcePath = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"

if (Test-Path $ProjectPath) {
    Write-Step "Folder docelowy juz istnieje: $ProjectPath" "WARN"
    $response = Read-Host "  Nadpisac? (t/n)"
    if ($response -ne "t") {
        Write-Step "Pominieto kopiowanie" "SKIP"
    } else {
        if (-not $DryRun) {
            Remove-Item $ProjectPath -Recurse -Force
        }
    }
}

if (-not (Test-Path $ProjectPath) -or $response -eq "t") {
    Write-Step "Kopiowanie projektu do $ProjectPath..."

    if ($DryRun) {
        Write-Step "[DRY RUN] Skopiowalbym $SourcePath -> $ProjectPath" "SKIP"
    } else {
        # Kopiowanie z wylaczeniem node_modules i vendor (beda zainstalowane lokalnie)
        $excludeDirs = @("node_modules", "vendor", ".git", "storage\logs\*", "storage\framework\cache\*")

        Write-Step "Tworzenie struktury folderow..."
        New-Item -ItemType Directory -Path $ProjectPath -Force | Out-Null

        Write-Step "Kopiowanie plikow (moze to chwile potrwac)..."

        # Uzyj robocopy dla szybszego kopiowania
        $robocopyArgs = @(
            $SourcePath,
            $ProjectPath,
            "/E",           # Kopiuj podfoldery lacznie z pustymi
            "/XD", "node_modules", "vendor", ".git",  # Wyklucz foldery
            "/XF", "*.log",  # Wyklucz pliki log
            "/NFL", "/NDL", "/NJH", "/NJS",  # Minimalizuj output
            "/MT:8"         # 8 watkow
        )

        $robocopyResult = & robocopy @robocopyArgs

        if ($LASTEXITCODE -le 7) {
            Write-Step "Projekt skopiowany pomyslnie" "OK"
        } else {
            Write-Step "Robocopy zakonczyl z kodem: $LASTEXITCODE" "WARN"
        }

        # Utworz puste foldery storage
        $storageDirs = @(
            "$ProjectPath\storage\app\public",
            "$ProjectPath\storage\framework\cache",
            "$ProjectPath\storage\framework\sessions",
            "$ProjectPath\storage\framework\views",
            "$ProjectPath\storage\logs"
        )
        foreach ($dir in $storageDirs) {
            New-Item -ItemType Directory -Path $dir -Force -ErrorAction SilentlyContinue | Out-Null
        }
        Write-Step "Utworzono foldery storage" "OK"
    }
}

# ============================================
# KROK 5: KONFIGURACJA .ENV
# ============================================
Write-Host ""
Write-Host "=== KROK 5: KONFIGURACJA .ENV ===" -ForegroundColor Magenta

$envFile = "$ProjectPath\.env"
$envExample = "$ProjectPath\.env.example"

if ($DryRun) {
    Write-Step "[DRY RUN] Utworzylbym .env z ustawieniami lokalnymi" "SKIP"
} else {
    if (Test-Path $envExample) {
        Write-Step "Tworzenie .env z .env.example..."
        Copy-Item $envExample $envFile -Force

        # Modyfikacja dla lokalnego srodowiska
        $envContent = Get-Content $envFile -Raw

        # Podstawowe zmiany
        $replacements = @{
            'APP_ENV=production' = 'APP_ENV=local'
            'APP_DEBUG=false' = 'APP_DEBUG=true'
            'APP_URL=https://ppm.mpptrade.pl' = 'APP_URL=http://localhost:8000'
            'DB_HOST=.*' = "DB_HOST=127.0.0.1"
            'DB_DATABASE=.*' = "DB_DATABASE=$DbName"
            'DB_USERNAME=.*' = "DB_USERNAME=$DbUser"
            'DB_PASSWORD=.*' = "DB_PASSWORD=$DbPassword"
            'QUEUE_CONNECTION=.*' = 'QUEUE_CONNECTION=database'
            'CACHE_STORE=.*' = 'CACHE_STORE=database'
            'SESSION_DRIVER=.*' = 'SESSION_DRIVER=database'
        }

        foreach ($pattern in $replacements.Keys) {
            $envContent = $envContent -replace $pattern, $replacements[$pattern]
        }

        # Dodaj lokalne ustawienia
        $localSettings = @"

# === LOCAL DEVELOPMENT SETTINGS ===
TELESCOPE_ENABLED=true
DEBUGBAR_ENABLED=true
LOG_LEVEL=debug
"@

        if ($envContent -notmatch "LOCAL DEVELOPMENT SETTINGS") {
            $envContent += $localSettings
        }

        $envContent | Set-Content $envFile -NoNewline
        Write-Step ".env skonfigurowany dla lokalnego srodowiska" "OK"
    } else {
        Write-Step ".env.example nie znaleziony - utworz .env recznie" "WARN"
    }
}

# ============================================
# KROK 6: INSTALACJA DEPENDENCIES
# ============================================
Write-Host ""
Write-Host "=== KROK 6: INSTALACJA DEPENDENCIES ===" -ForegroundColor Magenta

if ($DryRun) {
    Write-Step "[DRY RUN] Uruchomilbym composer install i npm install" "SKIP"
} else {
    Push-Location $ProjectPath

    # Composer
    if (Test-Command "composer") {
        Write-Step "Uruchamianie composer install..."
        try {
            $composerResult = composer install --no-interaction 2>&1
            Write-Step "Composer dependencies zainstalowane" "OK"
        } catch {
            Write-Step "Blad composer install: $_" "ERROR"
        }
    } else {
        Write-Step "Composer niedostepny - uruchom 'composer install' pozniej" "WARN"
    }

    # NPM
    if (-not $SkipNodeModules) {
        Write-Step "Uruchamianie npm install..."
        try {
            $npmResult = npm install 2>&1
            Write-Step "NPM dependencies zainstalowane" "OK"
        } catch {
            Write-Step "Blad npm install: $_" "ERROR"
        }
    } else {
        Write-Step "Pominieto npm install (--SkipNodeModules)" "SKIP"
    }

    Pop-Location
}

# ============================================
# KROK 7: GENEROWANIE APP_KEY
# ============================================
Write-Host ""
Write-Host "=== KROK 7: APP_KEY ===" -ForegroundColor Magenta

if ($DryRun) {
    Write-Step "[DRY RUN] Wygenerowalbym APP_KEY" "SKIP"
} else {
    Push-Location $ProjectPath

    if (Test-Command "php") {
        Write-Step "Generowanie APP_KEY..."
        try {
            php artisan key:generate --force 2>&1 | Out-Null
            Write-Step "APP_KEY wygenerowany" "OK"
        } catch {
            Write-Step "Blad generowania APP_KEY: $_" "WARN"
        }
    }

    Pop-Location
}

# ============================================
# PODSUMOWANIE
# ============================================
Write-Host ""
Write-Host "=====================================================" -ForegroundColor Green
Write-Host " INSTALACJA ZAKONCZONA" -ForegroundColor Green
Write-Host "=====================================================" -ForegroundColor Green
Write-Host ""
Write-Host "  Projekt: $ProjectPath" -ForegroundColor Cyan
Write-Host "  Baza danych: $DbName" -ForegroundColor Cyan
Write-Host ""
Write-Host "  NASTEPNE KROKI:" -ForegroundColor Yellow
Write-Host ""
Write-Host "  1. Utworz baze danych (jesli MariaDB zainstalowany):" -ForegroundColor White
Write-Host "     mysql -u root -p -e `"CREATE DATABASE $DbName CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`"" -ForegroundColor DarkGray
Write-Host ""
Write-Host "  2. Uruchom migracje:" -ForegroundColor White
Write-Host "     cd `"$ProjectPath`"" -ForegroundColor DarkGray
Write-Host "     php artisan migrate --seed" -ForegroundColor DarkGray
Write-Host ""
Write-Host "  3. Uruchom serwer developerski:" -ForegroundColor White
Write-Host "     php artisan serve" -ForegroundColor DarkGray
Write-Host ""
Write-Host "  4. W osobnym terminalu uruchom Vite:" -ForegroundColor White
Write-Host "     npm run dev" -ForegroundColor DarkGray
Write-Host ""
Write-Host "  5. Otworz przegladarke:" -ForegroundColor White
Write-Host "     http://localhost:8000" -ForegroundColor DarkGray
Write-Host ""
Write-Host "  Login: admin@mpptrade.pl / Admin123!MPP" -ForegroundColor Green
Write-Host ""
