# =========================================
# PPM-CC-Laravel - DAILY DEVELOPMENT HELPER
# =========================================
# Quick commands dla codziennego developmentu
# Użycie: pwsh _TOOLS/dev.ps1 [command]
# =========================================

param(
    [Parameter(Position=0)]
    [string]$Command = "help"
)

# Kolory
$ESC = [char]27
$Green = "$ESC[92m"
$Red = "$ESC[91m"
$Yellow = "$ESC[93m"
$Blue = "$ESC[94m"
$Cyan = "$ESC[96m"
$Reset = "$ESC[0m"

# Helper functions
function Show-Info { param([string]$Message) Write-Host "${Cyan}ℹ ${Message}${Reset}" }
function Show-Success { param([string]$Message) Write-Host "${Green}✓ ${Message}${Reset}" }
function Show-Error { param([string]$Message) Write-Host "${Red}✗ ${Message}${Reset}" }

# Check project root
if (-not (Test-Path "composer.json")) {
    Show-Error "Nie jestes w katalogu glownym projektu!"
    exit 1
}

# Commands
switch ($Command) {
    # =========================================
    # SERVE - Start development server
    # =========================================
    "serve" {
        Show-Info "Uruchamianie development server..."
        php artisan serve
    }

    # =========================================
    # QUEUE - Start queue worker
    # =========================================
    "queue" {
        Show-Info "Uruchamianie queue worker..."
        php artisan queue:work --verbose
    }

    # =========================================
    # DEV - Start Vite dev server
    # =========================================
    "dev" {
        Show-Info "Uruchamianie Vite dev server (hot reload)..."
        npm run dev
    }

    # =========================================
    # BUILD - Build frontend assets
    # =========================================
    "build" {
        Show-Info "Building frontend assets..."
        npm run build

        if ($LASTEXITCODE -eq 0) {
            Show-Success "Build ukończony!"

            # Copy manifest to ROOT
            if (Test-Path "public/build/.vite/manifest.json") {
                Copy-Item "public/build/.vite/manifest.json" "public/build/manifest.json" -Force
                Show-Success "Manifest skopiowany do ROOT"
            }
        }
    }

    # =========================================
    # CLEAR - Clear all caches
    # =========================================
    "clear" {
        Show-Info "Czyszczenie cache..."
        php artisan cache:clear
        php artisan config:clear
        php artisan view:clear
        php artisan route:clear
        Show-Success "Cache wyczyszczony!"
    }

    # =========================================
    # FRESH - Fresh migration + seed
    # =========================================
    "fresh" {
        Show-Info "UWAGA: To usunie wszystkie dane w bazie!"
        $confirm = Read-Host "Kontynuowac? (yes/no)"

        if ($confirm -eq "yes") {
            php artisan migrate:fresh --seed
            Show-Success "Baza danych zresetowana!"
        } else {
            Show-Info "Anulowano"
        }
    }

    # =========================================
    # MIGRATE - Run migrations
    # =========================================
    "migrate" {
        Show-Info "Uruchamianie migracji..."
        php artisan migrate
    }

    # =========================================
    # SEED - Run seeders
    # =========================================
    "seed" {
        Show-Info "Uruchamianie seeders..."
        php artisan db:seed
    }

    # =========================================
    # TEST - Run tests
    # =========================================
    "test" {
        Show-Info "Uruchamianie testow..."
        php artisan test
    }

    # =========================================
    # QUALITY - Run PHPStan + CS Fixer
    # =========================================
    "quality" {
        Show-Info "Uruchamianie quality checks..."
        composer run quality
    }

    # =========================================
    # TINKER - Open Laravel Tinker
    # =========================================
    "tinker" {
        Show-Info "Otwieranie Laravel Tinker..."
        php artisan tinker
    }

    # =========================================
    # LOG - Tail Laravel log
    # =========================================
    "log" {
        if (Test-Path "storage/logs/laravel.log") {
            Show-Info "Wyświetlanie ostatnich 50 linii logu..."
            Get-Content "storage/logs/laravel.log" -Tail 50
        } else {
            Show-Error "Log nie znaleziony: storage/logs/laravel.log"
        }
    }

    # =========================================
    # STATUS - Show current status
    # =========================================
    "status" {
        Write-Host ""
        Write-Host "${Blue}═══ PPM-CC-Laravel Status ═══${Reset}"
        Write-Host ""

        # PHP version
        $phpVersion = (php -v | Select-String -Pattern "PHP (\d+\.\d+\.\d+)").Matches.Groups[1].Value
        Write-Host "  PHP:      ${Green}${phpVersion}${Reset}"

        # Laravel version
        $laravelVersion = (php artisan --version | Select-String -Pattern "(\d+\.\d+\.\d+)").Matches.Groups[1].Value
        Write-Host "  Laravel:  ${Green}${laravelVersion}${Reset}"

        # Node version
        $nodeVersion = (node --version).TrimStart('v')
        Write-Host "  Node.js:  ${Green}${nodeVersion}${Reset}"

        # Database connection
        Write-Host ""
        Write-Host "  Database:"

        $dbTest = php artisan tinker --execute="echo DB::connection()->getPdo() ? 'Connected' : 'Failed';" 2>&1

        if ($dbTest -match "Connected") {
            Write-Host "    Status: ${Green}Connected${Reset}"

            # Count tables
            $productCount = php artisan tinker --execute="echo DB::table('products')->count();" 2>&1
            if ($productCount -match "\d+") {
                Write-Host "    Products: ${Cyan}${productCount}${Reset}"
            }
        } else {
            Write-Host "    Status: ${Red}Disconnected${Reset}"
        }

        # Queue status
        Write-Host ""
        Write-Host "  Queue:"

        $pendingJobs = php artisan tinker --execute="echo DB::table('jobs')->count();" 2>&1
        if ($pendingJobs -match "\d+") {
            Write-Host "    Pending: ${Cyan}${pendingJobs}${Reset}"
        }

        $failedJobs = php artisan tinker --execute="echo DB::table('failed_jobs')->count();" 2>&1
        if ($failedJobs -match "\d+") {
            if ($failedJobs -gt 0) {
                Write-Host "    Failed:  ${Red}${failedJobs}${Reset}"
            } else {
                Write-Host "    Failed:  ${Green}0${Reset}"
            }
        }

        Write-Host ""
    }

    # =========================================
    # OPEN - Open app in browser
    # =========================================
    "open" {
        Show-Info "Otwieranie aplikacji w przegladarce..."
        Start-Process "http://localhost:8000"
    }

    # =========================================
    # ADMIN - Open admin panel
    # =========================================
    "admin" {
        Show-Info "Otwieranie panelu admina w przegladarce..."
        Start-Process "http://localhost:8000/admin"
    }

    # =========================================
    # TELESCOPE - Open Telescope
    # =========================================
    "telescope" {
        Show-Info "Otwieranie Laravel Telescope..."
        Start-Process "http://localhost:8000/telescope"
    }

    # =========================================
    # HELP - Show available commands
    # =========================================
    default {
        Write-Host ""
        Write-Host "${Cyan}╔═══════════════════════════════════════════════════════════════╗${Reset}"
        Write-Host "${Cyan}║                                                               ║${Reset}"
        Write-Host "${Cyan}║      ${Green}PPM-CC-Laravel - Development Helper Commands${Cyan}         ║${Reset}"
        Write-Host "${Cyan}║                                                               ║${Reset}"
        Write-Host "${Cyan}╚═══════════════════════════════════════════════════════════════╝${Reset}"
        Write-Host ""
        Write-Host "${Yellow}Usage:${Reset} pwsh _TOOLS/dev.ps1 [command]"
        Write-Host ""
        Write-Host "${Blue}═══ Server Commands ═══${Reset}"
        Write-Host "  ${Green}serve${Reset}       Start Laravel development server (port 8000)"
        Write-Host "  ${Green}queue${Reset}       Start queue worker (verbose mode)"
        Write-Host "  ${Green}dev${Reset}         Start Vite dev server (hot reload)"
        Write-Host ""
        Write-Host "${Blue}═══ Build Commands ═══${Reset}"
        Write-Host "  ${Green}build${Reset}       Build frontend assets (production)"
        Write-Host ""
        Write-Host "${Blue}═══ Database Commands ═══${Reset}"
        Write-Host "  ${Green}fresh${Reset}       Fresh migration + seed (DELETES DATA!)"
        Write-Host "  ${Green}migrate${Reset}     Run pending migrations"
        Write-Host "  ${Green}seed${Reset}        Run database seeders"
        Write-Host ""
        Write-Host "${Blue}═══ Cache Commands ═══${Reset}"
        Write-Host "  ${Green}clear${Reset}       Clear all caches (config, view, route, cache)"
        Write-Host ""
        Write-Host "${Blue}═══ Testing Commands ═══${Reset}"
        Write-Host "  ${Green}test${Reset}        Run PHPUnit tests"
        Write-Host "  ${Green}quality${Reset}     Run PHPStan + PHP CS Fixer"
        Write-Host ""
        Write-Host "${Blue}═══ Utility Commands ═══${Reset}"
        Write-Host "  ${Green}tinker${Reset}      Open Laravel Tinker (REPL)"
        Write-Host "  ${Green}log${Reset}         Show last 50 lines of Laravel log"
        Write-Host "  ${Green}status${Reset}      Show current system status"
        Write-Host ""
        Write-Host "${Blue}═══ Browser Commands ═══${Reset}"
        Write-Host "  ${Green}open${Reset}        Open app in browser (localhost:8000)"
        Write-Host "  ${Green}admin${Reset}       Open admin panel (localhost:8000/admin)"
        Write-Host "  ${Green}telescope${Reset}   Open Telescope (localhost:8000/telescope)"
        Write-Host ""
        Write-Host "${Blue}═══ Examples ═══${Reset}"
        Write-Host "  ${Cyan}pwsh _TOOLS/dev.ps1 serve${Reset}      # Start server"
        Write-Host "  ${Cyan}pwsh _TOOLS/dev.ps1 queue${Reset}      # Start queue worker"
        Write-Host "  ${Cyan}pwsh _TOOLS/dev.ps1 clear${Reset}      # Clear cache"
        Write-Host "  ${Cyan}pwsh _TOOLS/dev.ps1 status${Reset}     # Check status"
        Write-Host ""
    }
}
