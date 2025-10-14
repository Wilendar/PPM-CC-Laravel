# 🏗️ Hostido Build & Assets Script dla PPM-CC-Laravel
# Lokalne buildy assets + upload na serwer + cache management

param(
    [ValidateSet("dev", "production")]
    [string]$Environment = "production",
    [switch]$AssetsOnly,
    [switch]$CacheOnly,
    [switch]$LocalBuild,
    [switch]$DryRun,
    [switch]$Verbose
)

# Konfiguracja projektu
$ProjectRoot = Split-Path $PSScriptRoot -Parent
$BuildOutputPath = "$ProjectRoot\public\build"
$NodeModulesPath = "$ProjectRoot\node_modules"

# Import skryptu automatyzacji
$AutomationScript = "$PSScriptRoot\hostido_automation.ps1"
$DeployScript = "$PSScriptRoot\hostido_deploy.ps1"

# Kolory PowerShell 7
$Colors = @{
    Success = "`e[32m"; Error = "`e[31m"; Info = "`e[36m"; Warning = "`e[33m"; Reset = "`e[0m"
    Build = "`e[35m"; Cache = "`e[94m"; Upload = "`e[93m"
}

function Write-ColorText {
    param([string]$Text, [string]$Color = "Info")
    Write-Host "$($Colors[$Color])$Text$($Colors.Reset)"
}

function Write-VerboseLog {
    param([string]$Message)
    if ($Verbose) {
        Write-ColorText "🔍 $Message" "Info"
    }
}

function Test-BuildRequirements {
    Write-ColorText "🔍 Sprawdzanie wymagań build..." "Info"
    
    # Node.js
    try {
        $nodeVersion = node --version
        Write-ColorText "✅ Node.js: $nodeVersion" "Success"
    }
    catch {
        Write-ColorText "❌ Node.js nie jest zainstalowane" "Error"
        return $false
    }
    
    # NPM
    try {
        $npmVersion = npm --version
        Write-ColorText "✅ NPM: $npmVersion" "Success"
    }
    catch {
        Write-ColorText "❌ NPM nie jest dostępne" "Error"
        return $false
    }
    
    # package.json
    if (!(Test-Path "$ProjectRoot\package.json")) {
        Write-ColorText "❌ package.json nie znaleziony w: $ProjectRoot" "Error"
        return $false
    }
    
    # vite.config.js
    if (!(Test-Path "$ProjectRoot\vite.config.js")) {
        Write-ColorText "❌ vite.config.js nie znaleziony w: $ProjectRoot" "Error"
        return $false
    }
    
    Write-ColorText "✅ Wszystkie wymagania build spełnione" "Success"
    return $true
}

function Install-Dependencies {
    Write-ColorText "📦 Instalowanie dependencies..." "Build"
    
    Set-Location $ProjectRoot
    
    try {
        if ($DryRun) {
            Write-ColorText "🔍 DRY-RUN: npm install" "Warning"
            return $true
        }
        
        Write-VerboseLog "Uruchamianie npm install..."
        $installResult = npm install 2>&1
        
        if ($LASTEXITCODE -eq 0) {
            Write-ColorText "✅ Dependencies zainstalowane pomyślnie" "Success"
            Write-VerboseLog $installResult
        } else {
            Write-ColorText "❌ Błąd instalacji dependencies" "Error"
            Write-Host $installResult
            return $false
        }
    }
    catch {
        Write-ColorText "❌ Wyjątek podczas instalacji: $_" "Error"
        return $false
    }
    
    return $true
}

function Build-Assets {
    Write-ColorText "🏗️ Building assets dla $Environment..." "Build"
    
    Set-Location $ProjectRoot
    
    try {
        if ($DryRun) {
            Write-ColorText "🔍 DRY-RUN: npm run build" "Warning"
            return $true
        }
        
        # Usunięcie starych buildów
        if (Test-Path $BuildOutputPath) {
            Write-VerboseLog "Usuwanie starych buildów..."
            Remove-Item $BuildOutputPath -Recurse -Force
        }
        
        # Build assets
        Write-VerboseLog "Uruchamianie Vite build..."
        $buildCommand = if ($Environment -eq "dev") { "npm run dev" } else { "npm run build" }
        
        $buildResult = Invoke-Expression $buildCommand 2>&1
        
        if ($LASTEXITCODE -eq 0) {
            Write-ColorText "✅ Assets zbudowane pomyślnie" "Success"
            Write-VerboseLog $buildResult
            
            # Sprawdzenie rezultatu
            if (Test-Path $BuildOutputPath) {
                $buildFiles = Get-ChildItem $BuildOutputPath -Recurse | Measure-Object
                Write-ColorText "📁 Zbudowano $($buildFiles.Count) plików" "Build"
            }
        } else {
            Write-ColorText "❌ Błąd budowania assets" "Error"
            Write-Host $buildResult
            return $false
        }
    }
    catch {
        Write-ColorText "❌ Wyjątek podczas budowania: $_" "Error"
        return $false
    }
    
    return $true
}

function Upload-Assets {
    Write-ColorText "📤 Przesyłanie assets na Hostido..." "Upload"
    
    if ($DryRun) {
        Write-ColorText "🔍 DRY-RUN: Upload assets" "Warning"
        return $true
    }
    
    try {
        # Upload tylko folderu public/build
        $assetsUploadScript = @"
cd /domains/ppm.mpptrade.pl/public_html && 
find public/build -type f -delete 2>/dev/null || true
"@
        
        Write-VerboseLog "Oczyszczanie starych assets na serwerze..."
        & $AutomationScript -Command $assetsUploadScript
        
        Write-VerboseLog "Upload nowych assets..."
        & $DeployScript -SourcePath "$ProjectRoot\public\build" -TargetPath "/domains/ppm.mpptrade.pl/public_html/public/build" -UploadOnly
        
        if ($LASTEXITCODE -eq 0) {
            Write-ColorText "✅ Assets przesłane na serwer" "Success"
        } else {
            Write-ColorText "❌ Błąd przesyłania assets" "Error"
            return $false
        }
    }
    catch {
        Write-ColorText "❌ Wyjątek podczas upload assets: $_" "Error"
        return $false
    }
    
    return $true
}

function Clear-LaravelCache {
    Write-ColorText "🧹 Czyszczenie cache Laravel..." "Cache"
    
    if ($DryRun) {
        Write-ColorText "🔍 DRY-RUN: Clear Laravel cache" "Warning"
        return $true
    }
    
    $cacheCommands = @(
        "cd /domains/ppm.mpptrade.pl/public_html",
        "php artisan config:clear",
        "php artisan cache:clear", 
        "php artisan route:clear",
        "php artisan view:clear",
        "php artisan optimize:clear"
    )
    
    $fullCommand = $cacheCommands -join " && "
    
    try {
        Write-VerboseLog "Wykonywanie komend czyszczenia cache..."
        & $AutomationScript -Command $fullCommand
        
        if ($LASTEXITCODE -eq 0) {
            Write-ColorText "✅ Cache Laravel wyczyszczony" "Success"
        } else {
            Write-ColorText "❌ Błąd czyszczenia cache" "Error"
            return $false
        }
    }
    catch {
        Write-ColorText "❌ Wyjątek podczas czyszczenia cache: $_" "Error"
        return $false
    }
    
    return $true
}

function Optimize-LaravelCache {
    Write-ColorText "⚡ Optymalizacja cache Laravel dla production..." "Cache"
    
    if ($Environment -ne "production") {
        Write-ColorText "⚠️ Optymalizacja cache tylko dla production environment" "Warning"
        return $true
    }
    
    if ($DryRun) {
        Write-ColorText "🔍 DRY-RUN: Optimize Laravel cache" "Warning"
        return $true
    }
    
    $optimizeCommands = @(
        "cd /domains/ppm.mpptrade.pl/public_html",
        "php artisan config:cache",
        "php artisan route:cache",
        "php artisan view:cache",
        "php artisan optimize"
    )
    
    $fullCommand = $optimizeCommands -join " && "
    
    try {
        Write-VerboseLog "Wykonywanie komend optymalizacji..."
        & $AutomationScript -Command $fullCommand
        
        if ($LASTEXITCODE -eq 0) {
            Write-ColorText "✅ Cache Laravel zoptymalizowany" "Success"
        } else {
            Write-ColorText "❌ Błąd optymalizacji cache" "Error"
            return $false
        }
    }
    catch {
        Write-ColorText "❌ Wyjątek podczas optymalizacji: $_" "Error"
        return $false
    }
    
    return $true
}

function Test-BuildOutput {
    Write-ColorText "🔍 Weryfikacja wyników build..." "Info"
    
    # Sprawdzenie lokalnych assets
    if (Test-Path $BuildOutputPath) {
        $buildFiles = Get-ChildItem $BuildOutputPath -Recurse -File
        Write-ColorText "📁 Lokalne assets: $($buildFiles.Count) plików" "Info"
        
        # Sprawdzenie głównych plików
        $criticalFiles = @("manifest.json")
        foreach ($file in $criticalFiles) {
            $filePath = Join-Path $BuildOutputPath $file
            if (Test-Path $filePath) {
                Write-ColorText "✅ Znaleziono: $file" "Success"
            } else {
                Write-ColorText "⚠️ Brak krytycznego pliku: $file" "Warning"
            }
        }
    } else {
        Write-ColorText "❌ Brak folderu build output" "Error"
        return $false
    }
    
    return $true
}

function Show-BuildSummary {
    param([hashtable]$Results)
    
    Write-ColorText "" "Info"
    Write-ColorText "📊 PODSUMOWANIE BUILD & DEPLOY" "Info"
    Write-ColorText "==============================" "Info"
    
    foreach ($key in $Results.Keys) {
        $status = if ($Results[$key]) { "✅ SUCCESS" } else { "❌ FAILED" }
        $color = if ($Results[$key]) { "Success" } else { "Error" }
        Write-ColorText "$key : $status" $color
    }
    
    Write-ColorText "" "Info"
    Write-ColorText "Environment: $Environment" "Info"
    Write-ColorText "Timestamp: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" "Info"
    
    if ($Results.Values -contains $false) {
        Write-ColorText "💥 Build process zawiera błędy!" "Error"
        return $false
    } else {
        Write-ColorText "🎉 Build process zakończony sukcesem!" "Success"
        return $true
    }
}

# Główna logika skryptu
Write-ColorText "🏗️ Hostido Build & Assets Script dla PPM-CC-Laravel" "Build"
Write-ColorText "====================================================" "Build"
Write-ColorText "Environment: $Environment" "Info"
Write-ColorText "Mode: $(if($DryRun){'DRY-RUN'}else{'EXECUTION'})" $(if($DryRun){'Warning'}else{'Info'})

# Sprawdzenie wymagań
if (!(Test-BuildRequirements)) {
    Write-ColorText "💥 Wymagania build nie są spełnione!" "Error"
    exit 1
}

# Tracking rezultatów
$Results = @{}

# Tylko cache operations
if ($CacheOnly) {
    Write-ColorText "🧹 Tryb: Tylko operacje cache" "Cache"
    $Results["Clear Cache"] = Clear-LaravelCache
    $Results["Optimize Cache"] = Optimize-LaravelCache
    Show-BuildSummary -Results $Results
    exit $(if ($Results.Values -contains $false) { 1 } else { 0 })
}

# Instalacja dependencies (jeśli nie LocalBuild)
if (!$LocalBuild) {
    $Results["Install Dependencies"] = Install-Dependencies
    if (!$Results["Install Dependencies"]) {
        Write-ColorText "💥 Nie można kontynuować bez dependencies!" "Error"
        exit 1
    }
}

# Build assets
$Results["Build Assets"] = Build-Assets
if (!$Results["Build Assets"]) {
    Write-ColorText "💥 Nie można kontynuować bez zbudowanych assets!" "Error"
    exit 1
}

# Weryfikacja build output
$Results["Verify Build"] = Test-BuildOutput

# Upload assets (jeśli nie LocalBuild)
if (!$LocalBuild -and !$AssetsOnly) {
    $Results["Upload Assets"] = Upload-Assets
    $Results["Clear Cache"] = Clear-LaravelCache
    $Results["Optimize Cache"] = Optimize-LaravelCache
}

# Podsumowanie
$success = Show-BuildSummary -Results $Results

if ($success) {
    Write-ColorText "🌐 Sprawdź aplikację: https://ppm.mpptrade.pl" "Info"
    exit 0
} else {
    exit 1
}