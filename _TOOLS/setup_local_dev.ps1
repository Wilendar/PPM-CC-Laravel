# =========================================
# PPM-CC-Laravel - LOCAL DEVELOPMENT SETUP
# =========================================
# Quick setup script dla Windows PowerShell 7
# Uruchom: pwsh _TOOLS/setup_local_dev.ps1
# =========================================

# Kolory i formatowanie
$ESC = [char]27
$Green = "$ESC[92m"
$Red = "$ESC[91m"
$Yellow = "$ESC[93m"
$Blue = "$ESC[94m"
$Cyan = "$ESC[96m"
$Reset = "$ESC[0m"

# Banner
function Show-Banner {
    Write-Host ""
    Write-Host "${Cyan}╔═══════════════════════════════════════════════════════════════╗${Reset}"
    Write-Host "${Cyan}║                                                               ║${Reset}"
    Write-Host "${Cyan}║         ${Green}PPM-CC-Laravel - Local Development Setup${Cyan}          ║${Reset}"
    Write-Host "${Cyan}║                                                               ║${Reset}"
    Write-Host "${Cyan}╚═══════════════════════════════════════════════════════════════╝${Reset}"
    Write-Host ""
}

# Step header
function Show-Step {
    param([string]$Number, [string]$Title)
    Write-Host ""
    Write-Host "${Blue}═══ STEP ${Number}: ${Title} ═══${Reset}"
    Write-Host ""
}

# Success message
function Show-Success {
    param([string]$Message)
    Write-Host "${Green}✓ ${Message}${Reset}"
}

# Error message
function Show-Error {
    param([string]$Message)
    Write-Host "${Red}✗ ${Message}${Reset}"
}

# Warning message
function Show-Warning {
    param([string]$Message)
    Write-Host "${Yellow}⚠ ${Message}${Reset}"
}

# Info message
function Show-Info {
    param([string]$Message)
    Write-Host "${Cyan}ℹ ${Message}${Reset}"
}

# Confirm action
function Confirm-Action {
    param([string]$Message)
    $response = Read-Host "${Yellow}${Message} (y/n)${Reset}"
    return $response -match "^[Yy]"
}

# Check command exists
function Test-Command {
    param([string]$Command)
    $null = Get-Command $Command -ErrorAction SilentlyContinue
    return $?
}

# Main setup
Show-Banner

# Check if in project root
if (-not (Test-Path "composer.json")) {
    Show-Error "Nie jestes w katalogu glownym projektu PPM-CC-Laravel!"
    Show-Info "Przejdz do: D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"
    exit 1
}

Show-Success "Znaleziono projekt PPM-CC-Laravel"

# =========================================
# STEP 1: Sprawdz wymagania systemowe
# =========================================
Show-Step "1" "Sprawdzanie wymagan systemowych"

# PHP
if (Test-Command "php") {
    $phpVersion = (php -v | Select-String -Pattern "PHP (\d+\.\d+\.\d+)").Matches.Groups[1].Value
    Show-Success "PHP: ${phpVersion}"

    if ([version]$phpVersion -lt [version]"8.3.0") {
        Show-Error "PHP 8.3+ wymagane! Aktualna wersja: ${phpVersion}"
        exit 1
    }
} else {
    Show-Error "PHP nie jest zainstalowane!"
    exit 1
}

# Composer
if (Test-Command "composer") {
    $composerVersion = (composer --version | Select-String -Pattern "(\d+\.\d+\.\d+)").Matches.Groups[1].Value
    Show-Success "Composer: ${composerVersion}"
} else {
    Show-Warning "Composer nie znaleziony!"
    Show-Info "Pobierz z: https://getcomposer.org/download/"

    if (-not (Confirm-Action "Kontynuowac bez Composera?")) {
        exit 1
    }
}

# Node.js
if (Test-Command "node") {
    $nodeVersion = (node --version).TrimStart('v')
    Show-Success "Node.js: ${nodeVersion}"

    if ([version]$nodeVersion -lt [version]"18.0.0") {
        Show-Warning "Node.js 18+ zalecany! Aktualna wersja: ${nodeVersion}"
    }
} else {
    Show-Error "Node.js nie jest zainstalowane!"
    exit 1
}

# npm
if (Test-Command "npm") {
    $npmVersion = npm --version
    Show-Success "npm: ${npmVersion}"
} else {
    Show-Error "npm nie jest zainstalowane!"
    exit 1
}

# MySQL/MariaDB
if (Test-Command "mysql") {
    Show-Success "MySQL/MariaDB znalezione"
} else {
    Show-Warning "mysql command nie znaleziony (moze byc zainstalowany ale nie w PATH)"
    Show-Info "Upewnij sie ze MySQL/MariaDB jest zainstalowany (XAMPP/WAMP/Standalone)"
}

# =========================================
# STEP 2: Environment file
# =========================================
Show-Step "2" "Konfiguracja pliku .env"

if (Test-Path ".env") {
    Show-Warning "Plik .env juz istnieje!"

    if (Confirm-Action "Czy chcesz go nadpisac?") {
        Remove-Item ".env" -Force
        Show-Info "Stary .env usuniety"
    } else {
        Show-Info "Pominieto utworzenie .env"
        $skipEnv = $true
    }
}

if (-not $skipEnv) {
    if (Test-Path ".env.local.example") {
        Copy-Item ".env.local.example" ".env"
        Show-Success "Utworzono .env z .env.local.example"
    } elseif (Test-Path ".env.example") {
        Copy-Item ".env.example" ".env"
        Show-Success "Utworzono .env z .env.example"
    } else {
        Show-Error "Nie znaleziono .env.example!"
        exit 1
    }

    Show-Warning "UWAGA: Edytuj .env i ustaw:"
    Show-Info "  - DB_DATABASE (nazwa lokalnej bazy)"
    Show-Info "  - DB_USERNAME (np. root)"
    Show-Info "  - DB_PASSWORD (np. puste dla XAMPP)"

    if (Confirm-Action "Czy chcesz edytowac .env teraz?") {
        notepad .env
    }
}

# =========================================
# STEP 3: Composer dependencies
# =========================================
Show-Step "3" "Instalacja Composer dependencies"

if (Test-Command "composer") {
    if (Confirm-Action "Uruchomic composer install?") {
        Show-Info "Instalowanie pakietow PHP..."
        composer install

        if ($LASTEXITCODE -eq 0) {
            Show-Success "Composer dependencies zainstalowane!"
        } else {
            Show-Error "Blad podczas composer install!"
            exit 1
        }
    }
} else {
    Show-Warning "Pominieto composer install (composer nie znaleziony)"
}

# =========================================
# STEP 4: NPM dependencies
# =========================================
Show-Step "4" "Instalacja NPM dependencies"

if (Confirm-Action "Uruchomic npm install?") {
    Show-Info "Instalowanie pakietow Node.js..."
    npm install

    if ($LASTEXITCODE -eq 0) {
        Show-Success "NPM dependencies zainstalowane!"
    } else {
        Show-Error "Blad podczas npm install!"
        exit 1
    }
}

# =========================================
# STEP 5: Application key
# =========================================
Show-Step "5" "Generowanie APP_KEY"

if (Confirm-Action "Wygenerowac APP_KEY?") {
    php artisan key:generate

    if ($LASTEXITCODE -eq 0) {
        Show-Success "APP_KEY wygenerowany!"
    } else {
        Show-Error "Blad podczas generowania klucza!"
    }
}

# =========================================
# STEP 6: Database setup
# =========================================
Show-Step "6" "Konfiguracja bazy danych"

Show-Warning "UWAGA: Przed uruchomieniem migracji upewnij sie ze:"
Show-Info "  1. MySQL/MariaDB jest uruchomiony"
Show-Info "  2. Baza danych istnieje (np. ppm_cc_laravel_local)"
Show-Info "  3. Credentials w .env sa poprawne"

if (Confirm-Action "Czy baza danych jest gotowa?") {
    # Test connection
    Show-Info "Testowanie polaczenia z baza danych..."
    php artisan tinker --execute="echo DB::connection()->getPdo() ? 'Connected' : 'Failed';"

    if ($LASTEXITCODE -eq 0) {
        Show-Success "Polaczenie z baza danych OK!"

        if (Confirm-Action "Uruchomic migracje + seeders (migrate:fresh --seed)?") {
            Show-Warning "UWAGA: migrate:fresh usunie wszystkie dane!"

            if (Confirm-Action "Kontynuowac?") {
                php artisan migrate:fresh --seed

                if ($LASTEXITCODE -eq 0) {
                    Show-Success "Migracje + seeders ukonczone!"
                } else {
                    Show-Error "Blad podczas migracji!"
                }
            }
        } elseif (Confirm-Action "Uruchomic tylko migracje (migrate)?") {
            php artisan migrate

            if ($LASTEXITCODE -eq 0) {
                Show-Success "Migracje ukonczone!"
            } else {
                Show-Error "Blad podczas migracji!"
            }
        }
    } else {
        Show-Error "Nie mozna polaczyc sie z baza danych!"
        Show-Info "Sprawdz konfiguracje w .env"
    }
} else {
    Show-Info "Pominieto setup bazy danych"
}

# =========================================
# STEP 7: Storage setup
# =========================================
Show-Step "7" "Konfiguracja storage"

if (Confirm-Action "Utworzyc storage symlink?") {
    php artisan storage:link

    if ($LASTEXITCODE -eq 0) {
        Show-Success "Storage symlink utworzony!"
    } else {
        Show-Warning "Blad podczas tworzenia symlink (moze juz istniec)"
    }
}

# Utwórz dodatkowe foldery
Show-Info "Tworzenie dodatkowych folderow storage..."

$folders = @(
    "storage/app/containers",
    "storage/app/products/images",
    "storage/app/temp"
)

foreach ($folder in $folders) {
    if (-not (Test-Path $folder)) {
        New-Item -ItemType Directory -Force -Path $folder | Out-Null
        Show-Success "Utworzono: ${folder}"
    } else {
        Show-Info "Istnieje: ${folder}"
    }
}

# =========================================
# STEP 8: Build frontend assets
# =========================================
Show-Step "8" "Build frontend assets"

if (Confirm-Action "Uruchomic npm run build?") {
    Show-Info "Building assets (moze potrwac chwile)..."
    npm run build

    if ($LASTEXITCODE -eq 0) {
        Show-Success "Assets zbudowane!"

        # Sprawdź manifest
        if (Test-Path "public/build/.vite/manifest.json") {
            if (-not (Test-Path "public/build/manifest.json")) {
                Copy-Item "public/build/.vite/manifest.json" "public/build/manifest.json" -Force
                Show-Success "Manifest skopiowany do ROOT (public/build/manifest.json)"
            }
        }
    } else {
        Show-Error "Blad podczas build!"
    }
}

# =========================================
# STEP 9: Cache clear
# =========================================
Show-Step "9" "Czyszczenie cache"

if (Confirm-Action "Wyczyscic cache?") {
    php artisan cache:clear
    php artisan config:clear
    php artisan view:clear
    php artisan route:clear

    Show-Success "Cache wyczyszczony!"
}

# =========================================
# SUMMARY
# =========================================
Write-Host ""
Write-Host "${Cyan}╔═══════════════════════════════════════════════════════════════╗${Reset}"
Write-Host "${Cyan}║                                                               ║${Reset}"
Write-Host "${Cyan}║                   ${Green}SETUP ZAKONCZONY!${Cyan}                          ║${Reset}"
Write-Host "${Cyan}║                                                               ║${Reset}"
Write-Host "${Cyan}╚═══════════════════════════════════════════════════════════════╝${Reset}"
Write-Host ""

Show-Info "Nastepne kroki:"
Write-Host ""
Write-Host "  ${Yellow}1.${Reset} Uruchom development server:"
Write-Host "     ${Cyan}php artisan serve${Reset}"
Write-Host ""
Write-Host "  ${Yellow}2.${Reset} (W nowym terminalu) Uruchom queue worker:"
Write-Host "     ${Cyan}php artisan queue:work --verbose${Reset}"
Write-Host ""
Write-Host "  ${Yellow}3.${Reset} (Opcjonalnie) Uruchom Vite dev server:"
Write-Host "     ${Cyan}npm run dev${Reset}"
Write-Host ""
Write-Host "  ${Yellow}4.${Reset} Otwórz aplikacje w przegladarce:"
Write-Host "     ${Cyan}http://localhost:8000${Reset}"
Write-Host ""
Write-Host "  ${Yellow}5.${Reset} Zaloguj sie jako admin:"
Write-Host "     Email:    ${Green}admin@mpptrade.pl${Reset}"
Write-Host "     Password: ${Green}Admin123!MPP${Reset}"
Write-Host ""
Write-Host "  ${Yellow}6.${Reset} Sprawdz dokumentacje:"
Write-Host "     ${Cyan}_DOCS/LOCAL_DEVELOPMENT_SETUP.md${Reset}"
Write-Host ""

if (Confirm-Action "Czy chcesz uruchomic development server teraz?") {
    Show-Info "Uruchamianie php artisan serve..."
    Write-Host ""
    php artisan serve
}
