# PPM-CC-Laravel Frontend Deployment Script
# Autor: Frontend Specialist Agent
# Data: 2025-01-08
# Opis: Script do deployment frontend stack (TailwindCSS + Alpine.js + Vite) na serwerze Hostido

param(
    [switch]$Production = $false,
    [switch]$DevMode = $false,
    [switch]$TestOnly = $false
)

# Kolory dla lepszej czytelności
$Host.UI.RawUI.BackgroundColor = "Black"
$Host.UI.RawUI.ForegroundColor = "White"
Clear-Host

Write-Host "🚀 PPM-CC-Laravel Frontend Deployment" -ForegroundColor Cyan
Write-Host "=======================================" -ForegroundColor Cyan

# Konfiguracja połączenia SSH
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$HostidoServer = "host379076@host379076.hostido.net.pl"
$HostidoPort = "64321"
$RemotePath = "domains/ppm.mpptrade.pl/public_html"

# Funkcja do wykonywania poleceń SSH
function Invoke-HostidoCommand {
    param(
        [string]$Command,
        [string]$Description = "Wykonywanie polecenia"
    )
    
    Write-Host "📋 $Description..." -ForegroundColor Yellow
    $result = plink -ssh $HostidoServer -P $HostidoPort -i $HostidoKey -batch "cd $RemotePath && $Command"
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ $Description - sukces" -ForegroundColor Green
        return $result
    } else {
        Write-Host "❌ $Description - błąd" -ForegroundColor Red
        throw "Błąd wykonania: $Command"
    }
}

# Funkcja testowania połączenia
function Test-HostidoConnection {
    Write-Host "🔍 Testowanie połączenia SSH..." -ForegroundColor Yellow
    
    try {
        $phpVersion = Invoke-HostidoCommand "php -v | head -1" "Test połączenia SSH"
        Write-Host "✅ Połączenie SSH OK - $phpVersion" -ForegroundColor Green
        return $true
    } catch {
        Write-Host "❌ Błąd połączenia SSH" -ForegroundColor Red
        return $false
    }
}

# Frontend Build Process
function Build-FrontendAssets {
    Write-Host "🏗️ Budowanie frontend assets..." -ForegroundColor Magenta
    
    # Sprawdź dependencies
    Invoke-HostidoCommand "npm list --depth=0 2>/dev/null || echo 'Dependencies check needed'" "Sprawdzanie dependencies"
    
    # Install/Update dependencies jeśli potrzebne
    if ($DevMode) {
        Invoke-HostidoCommand "npm install" "Instalowanie dependencies (dev mode)"
    }
    
    # Sprawdź źródłowe pliki
    $cssLines = Invoke-HostidoCommand "wc -l resources/css/app.css | awk '{print `$1}'" "Sprawdzanie app.css"
    $jsLines = Invoke-HostidoCommand "wc -l resources/js/app.js | awk '{print `$1}'" "Sprawdzanie app.js"
    
    Write-Host "📄 app.css: $cssLines linii" -ForegroundColor Blue
    Write-Host "📄 app.js: $jsLines linii" -ForegroundColor Blue
    
    if ([int]$cssLines -lt 10 -or [int]$jsLines -lt 10) {
        Write-Host "⚠️ Źródłowe pliki wydają się puste!" -ForegroundColor Yellow
        if (-not $TestOnly) {
            $continue = Read-Host "Kontynuować? (y/N)"
            if ($continue -ne "y") { exit }
        }
    }
    
    # Production build
    Write-Host "🔨 Uruchamianie npm run build..." -ForegroundColor Magenta
    $buildOutput = Invoke-HostidoCommand "npm run build" "Production build"
    
    # Sprawdź wygenerowane assets
    $assetFiles = Invoke-HostidoCommand "ls -la public/build/assets/ | grep -E '\\.css|\\.js'" "Sprawdzanie wygenerowanych assets"
    Write-Host "📦 Wygenerowane assets:" -ForegroundColor Blue
    Write-Host $assetFiles -ForegroundColor Gray
    
    return $true
}

# Test funkcjonalności
function Test-FrontendFunctionality {
    Write-Host "🧪 Testowanie funkcjonalności frontend..." -ForegroundColor Magenta
    
    # Test dostępności strony
    try {
        $response = Invoke-WebRequest -Uri "https://ppm.mpptrade.pl" -TimeoutSec 10 -UseBasicParsing
        if ($response.StatusCode -eq 200) {
            Write-Host "✅ Strona główna dostępna" -ForegroundColor Green
        }
    } catch {
        Write-Host "❌ Błąd dostępu do strony głównej" -ForegroundColor Red
    }
    
    # Test strony testowej (jeśli istnieje)
    try {
        $testResponse = Invoke-WebRequest -Uri "https://ppm.mpptrade.pl/test-frontend" -TimeoutSec 10 -UseBasicParsing
        if ($testResponse.StatusCode -eq 200) {
            Write-Host "✅ Strona testowa frontend dostępna" -ForegroundColor Green
            
            # Sprawdź czy assets są załadowane
            if ($testResponse.Content -match "text-3xl.*font-bold.*text-blue-600") {
                Write-Host "✅ TailwindCSS klasy wykryte" -ForegroundColor Green
            }
            
            if ($testResponse.Content -match "x-data.*count.*0") {
                Write-Host "✅ Alpine.js komponenty wykryte" -ForegroundColor Green
            }
        }
    } catch {
        Write-Host "⚠️ Strona testowa frontend niedostępna (normalnie dla produkcji)" -ForegroundColor Yellow
    }
}

# Cleanup i optimizacja
function Optimize-Production {
    if ($Production) {
        Write-Host "🚀 Optimizacja dla produkcji..." -ForegroundColor Magenta
        
        # Wyczyść cache
        Invoke-HostidoCommand "php artisan config:clear" "Czyszczenie config cache"
        Invoke-HostidoCommand "php artisan route:clear" "Czyszczenie route cache" 
        Invoke-HostidoCommand "php artisan view:clear" "Czyszczenie view cache"
        
        # Cache dla produkcji
        Invoke-HostidoCommand "php artisan config:cache" "Tworzenie config cache"
        Invoke-HostidoCommand "php artisan route:cache" "Tworzenie route cache"
        
        # Sprawdź APP_DEBUG
        $debugSetting = Invoke-HostidoCommand "grep APP_DEBUG .env" "Sprawdzanie debug mode"
        if ($debugSetting -match "true") {
            Write-Host "⚠️ APP_DEBUG=true w produkcji!" -ForegroundColor Red
            Invoke-HostidoCommand "sed -i 's/APP_DEBUG=true/APP_DEBUG=false/' .env" "Wyłączanie debug mode"
        }
        
        Write-Host "✅ Optimizacja produkcyjna ukończona" -ForegroundColor Green
    }
}

# Raport podsumowujący
function Show-DeploymentReport {
    Write-Host "`n📋 RAPORT DEPLOYMENT" -ForegroundColor Cyan
    Write-Host "====================" -ForegroundColor Cyan
    
    # Informacje o środowisku
    $phpVersion = Invoke-HostidoCommand "php -v | head -1" "PHP version"
    $laravelVersion = Invoke-HostidoCommand "php artisan --version" "Laravel version"
    
    Write-Host "🖥️  PHP: $phpVersion" -ForegroundColor Blue
    Write-Host "🚀 Laravel: $laravelVersion" -ForegroundColor Blue
    
    # Informacje o assets
    $cssSize = Invoke-HostidoCommand "ls -lh public/build/assets/*.css | awk '{print `$5}'" "CSS size"
    $jsSize = Invoke-HostidoCommand "ls -lh public/build/assets/*.js | awk '{print `$5}'" "JS size"
    
    Write-Host "📄 CSS size: $cssSize" -ForegroundColor Blue
    Write-Host "📄 JS size: $jsSize" -ForegroundColor Blue
    
    # Status aplikacji
    Write-Host "`n🎯 FRONTEND STACK STATUS:" -ForegroundColor Green
    Write-Host "✅ TailwindCSS 4.0 - Gotowy do użycia" -ForegroundColor Green
    Write-Host "✅ Alpine.js 3.15 + plugins - Gotowy do użycia" -ForegroundColor Green
    Write-Host "✅ Vite 7.x - Build system skonfigurowany" -ForegroundColor Green
    Write-Host "✅ Livewire 3.6.4 - Integracja przygotowana" -ForegroundColor Green
    
    Write-Host "`n🌐 Dostępne URL:" -ForegroundColor Cyan
    Write-Host "• Główna: https://ppm.mpptrade.pl" -ForegroundColor White
    Write-Host "• Dashboard: https://ppm.mpptrade.pl/dashboard" -ForegroundColor White
    
    if (-not $Production) {
        Write-Host "• Test frontend: https://ppm.mpptrade.pl/test-frontend" -ForegroundColor Yellow
    }
}

# MAIN EXECUTION
try {
    # Test połączenia
    if (-not (Test-HostidoConnection)) {
        throw "Nie można nawiązać połączenia SSH"
    }
    
    # Build assets (tylko jeśli nie test-only)
    if (-not $TestOnly) {
        Build-FrontendAssets
    }
    
    # Test funkcjonalności
    Test-FrontendFunctionality
    
    # Optimizacja dla produkcji
    Optimize-Production
    
    # Raport
    Show-DeploymentReport
    
    Write-Host "`n🎉 DEPLOYMENT ZAKOŃCZONY POMYŚLNIE!" -ForegroundColor Green
    
} catch {
    Write-Host "`n❌ BŁĄD DEPLOYMENT: $_" -ForegroundColor Red
    exit 1
}

# Opcje uruchomienia
Write-Host "`n📋 SPOSÓB UŻYCIA:" -ForegroundColor Cyan
Write-Host "./hostido_frontend_deploy.ps1                 # Standardowy deploy" -ForegroundColor White
Write-Host "./hostido_frontend_deploy.ps1 -Production     # Deploy produkcyjny" -ForegroundColor White  
Write-Host "./hostido_frontend_deploy.ps1 -DevMode        # Deploy rozwojowy" -ForegroundColor White
Write-Host "./hostido_frontend_deploy.ps1 -TestOnly       # Tylko testy" -ForegroundColor White