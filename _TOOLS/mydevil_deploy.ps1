# 🚀 Hostido Deployment Script dla PPM-CC-Laravel
# Upload plików na serwer Hostido + wykonywanie komend

param(
    [string]$SourcePath = ".",
    [string]$TargetPath = "/domains/ppm.mpptrade.pl/public_html/",
    [string]$Command = "",
    [switch]$UploadOnly,
    [switch]$CommandOnly,
    [switch]$InstallWinSCP,
    [switch]$SetupDirectories
)

# Konfiguracja Hostido
$HostidoHost = "host379076.hostido.net.pl"
$HostidoUser = "host379076"  
$HostidoPort = 64321
$HostidoKeyPath = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

# Ścieżki narzędzi  
$WinSCPPath = "C:\Program Files (x86)\WinSCP\WinSCP.com"
$WinSCPNetPath = "C:\Program Files (x86)\WinSCP\WinSCPnet.dll"

# Import modułu automatyzacji SSH
$AutomationScript = "$PSScriptRoot\hostido_automation.ps1"

# Kolory PowerShell 7
$Colors = @{
    Success = "`e[32m"; Error = "`e[31m"; Info = "`e[36m"; Warning = "`e[33m"; Reset = "`e[0m"
}

function Write-ColorText {
    param([string]$Text, [string]$Color = "Info")
    Write-Host "$($Colors[$Color])$Text$($Colors.Reset)"
}

function Test-Requirements {
    Write-ColorText "🔍 Sprawdzanie wymagań deployment..." "Info"
    
    # Sprawdzenie WinSCP
    if (!(Test-Path $WinSCPPath)) {
        Write-ColorText "❌ WinSCP nie jest zainstalowane" "Error"
        Write-ColorText "💡 Użyj parametru -InstallWinSCP aby zainstalować" "Warning"
        return $false
    }
    
    # Sprawdzenie klucza SSH
    if (!(Test-Path $HostidoKeyPath)) {
        Write-ColorText "❌ Klucz SSH nie znaleziony: $HostidoKeyPath" "Error"
        return $false
    }
    
    # Sprawdzenie skryptu automatyzacji
    if (!(Test-Path $AutomationScript)) {
        Write-ColorText "❌ Skrypt automatyzacji nie znaleziony: $AutomationScript" "Error"
        return $false
    }
    
    Write-ColorText "✅ Wszystkie wymagania spełnione" "Success"
    return $true
}

function Install-WinSCP {
    Write-ColorText "📦 Instalowanie WinSCP..." "Info"
    
    try {
        $winscpUrl = "https://winscp.net/download/WinSCP-6.3.5-Setup.exe"
        $installer = "$env:TEMP\winscp-installer.exe"
        
        Write-ColorText "⬇️ Pobieranie WinSCP..." "Info"
        Invoke-WebRequest -Uri $winscpUrl -OutFile $installer
        
        Write-ColorText "🔧 Instalowanie..." "Info"
        Start-Process $installer -ArgumentList "/VERYSILENT" -Wait
        
        Remove-Item $installer -Force
        Write-ColorText "✅ WinSCP zainstalowane pomyślnie" "Success"
    }
    catch {
        Write-ColorText "❌ Błąd instalacji WinSCP: $_" "Error"
        exit 1
    }
}

function Setup-HostidoDirectories {
    Write-ColorText "📁 Tworzenie struktury katalogów na Hostido..." "Info"
    
    $directories = @(
        "/domains/ppm.mpptrade.pl/public_html/storage/logs",
        "/domains/ppm.mpptrade.pl/public_html/storage/framework/cache",
        "/domains/ppm.mpptrade.pl/public_html/storage/framework/sessions",
        "/domains/ppm.mpptrade.pl/public_html/storage/framework/views",
        "/domains/ppm.mpptrade.pl/public_html/bootstrap/cache"
    )
    
    foreach ($dir in $directories) {
        $createCommand = "mkdir -p $dir && chmod 775 $dir"
        Write-ColorText "📂 Tworzenie: $dir" "Info"
        
        try {
            & "$PSScriptRoot\hostido_automation.ps1" -Command $createCommand
        }
        catch {
            Write-ColorText "⚠️ Błąd tworzenia katalogu $dir" "Warning"
        }
    }
    
    Write-ColorText "✅ Struktura katalogów utworzona" "Success"
}

function Deploy-ToHostido {
    param([string]$Source, [string]$Target)
    
    Write-ColorText "🚀 Rozpoczynanie deployment na Hostido..." "Info"
    Write-ColorText "Source: $Source" "Info"
    Write-ColorText "Target: $Target" "Info"
    
    # Wykluczenia dla upload
    $ExcludePatterns = @(
        "node_modules\*",
        ".git\*",
        "tests\*",
        "storage\logs\*",
        "storage\app\public\*",
        ".env*",
        "*.log",
        "_TOOLS\*",
        "_REPORTS\*",
        "_OTHER\*"
    )
    
    try {
        # Tworzenie skryptu WinSCP
        $ScriptPath = "$env:TEMP\hostido_deploy_script.txt"
        $ScriptContent = @"
open sftp://$HostidoUser@$HostidoHost:$HostidoPort -privatekey="$HostidoKeyPath"
cd $Target
lcd $Source
synchronize remote -delete -filemask="|$($ExcludePatterns -join ';')"
close
exit
"@
        
        $ScriptContent | Out-File -FilePath $ScriptPath -Encoding UTF8
        
        Write-ColorText "📤 Przesyłanie plików na Hostido..." "Info"
        
        # Wykonanie WinSCP script
        $result = & $WinSCPPath /console /script=$ScriptPath
        
        if ($LASTEXITCODE -eq 0) {
            Write-ColorText "✅ Pliki przesłane pomyślnie!" "Success"
        } else {
            Write-ColorText "❌ Błąd przesyłania plików" "Error"
            Write-Host $result
            return $false
        }
        
        # Cleanup
        Remove-Item $ScriptPath -Force
        
        return $true
    }
    catch {
        Write-ColorText "❌ Wyjątek podczas deployment: $_" "Error"
        return $false
    }
}

function Invoke-PostDeployCommands {
    Write-ColorText "⚙️ Wykonywanie komend post-deployment..." "Info"
    
    $commands = @(
        "cd $TargetPath",
        "chmod -R 775 storage/ bootstrap/cache/",
        "php artisan config:clear",
        "php artisan cache:clear", 
        "php artisan route:clear",
        "php artisan view:clear",
        "php artisan config:cache",
        "php artisan route:cache",
        "php artisan view:cache"
    )
    
    $fullCommand = $commands -join " && "
    
    try {
        Write-ColorText "🔧 Konfigurowanie aplikacji..." "Info"
        & "$PSScriptRoot\hostido_automation.ps1" -Command $fullCommand
        Write-ColorText "✅ Konfiguracja zakończona" "Success"
        return $true
    }
    catch {
        Write-ColorText "❌ Błąd konfiguracji: $_" "Error"
        return $false
    }
}

# Główna logika skryptu
Write-ColorText "🌟 Hostido Deployment Script dla PPM-CC-Laravel" "Info"
Write-ColorText "================================================" "Info"

# Instalacja WinSCP jeśli wymagana
if ($InstallWinSCP) {
    Install-WinSCP
    exit 0
}

# Setup katalogów jeśli wymagane
if ($SetupDirectories) {
    if (!(Test-Requirements)) { exit 1 }
    Setup-HostidoDirectories
    exit 0
}

# Sprawdzenie wymagań
if (!(Test-Requirements)) {
    exit 1
}

# Wykonanie tylko komendy
if ($CommandOnly -and $Command) {
    Write-ColorText "🎯 Wykonywanie tylko komendy..." "Info"
    & $AutomationScript -Command $Command
    exit $LASTEXITCODE
}

# Upload tylko plików
if ($UploadOnly) {
    Write-ColorText "📤 Przesyłanie tylko plików..." "Info"
    if (Deploy-ToHostido -Source $SourcePath -Target $TargetPath) {
        Write-ColorText "🎉 Upload zakończony sukcesem!" "Success"
    } else {
        Write-ColorText "💥 Upload nieudany!" "Error"
        exit 1
    }
    exit 0
}

# Pełny deployment (domyślny)
Write-ColorText "🚀 Rozpoczynanie pełnego deployment..." "Info"

# 1. Upload plików
if (!(Deploy-ToHostido -Source $SourcePath -Target $TargetPath)) {
    Write-ColorText "💥 Deployment nieudany - błąd upload!" "Error"
    exit 1
}

# 2. Wykonanie komend post-deployment
if (!(Invoke-PostDeployCommands)) {
    Write-ColorText "⚠️ Deployment częściowo udany - błąd konfiguracji" "Warning"
    exit 1
}

# 3. Wykonanie custom komendy jeśli podana
if ($Command) {
    Write-ColorText "🎯 Wykonywanie custom komendy..." "Info"
    & $AutomationScript -Command $Command
}

Write-ColorText "🎉 Deployment zakończony sukcesem!" "Success"
Write-ColorText "🌐 Sprawdź aplikację: https://ppm.mpptrade.pl" "Info"