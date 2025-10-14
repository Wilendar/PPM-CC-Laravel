# 🚀 Hostido SSH Automation Script
# Automatyzacja połączeń SSH z Hostido.net.pl shared hosting

param(
    [string]$Command = "hostname && whoami && php -v",
    [switch]$TestConnection,
    [switch]$InstallPuTTY
)

# Konfiguracja Hostido
$HostidoHost = "host379076.hostido.net.pl"
$HostidoUser = "host379076"  
$HostidoPort = 64321
$HostidoKeyPath = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

# Ścieżki narzędzi
$PuTTYPath = "C:\Program Files\PuTTY"
$PlinkPath = "$PuTTYPath\plink.exe"
$WinSCPPath = "C:\Program Files (x86)\WinSCP\WinSCP.exe"

# Kolory dla PowerShell 7
$Colors = @{
    Success = "`e[32m"  # Zielony
    Error   = "`e[31m"  # Czerwony  
    Info    = "`e[36m"  # Cyan
    Warning = "`e[33m"  # Żółty
    Reset   = "`e[0m"   # Reset
}

function Write-ColorText {
    param([string]$Text, [string]$Color = "Info")
    Write-Host "$($Colors[$Color])$Text$($Colors.Reset)"
}

function Test-Requirements {
    Write-ColorText "🔍 Sprawdzanie wymagań..." "Info"
    
    if (!(Test-Path $PlinkPath)) {
        Write-ColorText "❌ PuTTY nie jest zainstalowane w $PuTTYPath" "Error"
        Write-ColorText "💡 Użyj parametru -InstallPuTTY aby zainstalować" "Warning"
        return $false
    }
    
    if (!(Test-Path $HostidoKeyPath)) {
        Write-ColorText "❌ Klucz SSH nie znaleziony: $HostidoKeyPath" "Error"
        return $false
    }
    
    Write-ColorText "✅ Wszystkie wymagania spełnione" "Success"
    return $true
}

function Install-PuTTY {
    Write-ColorText "📦 Instalowanie PuTTY..." "Info"
    
    try {
        $puttyUrl = "https://the.earth.li/~sgtatham/putty/latest/w64/putty-64bit-0.81-installer.msi"
        $installer = "$env:TEMP\putty-installer.msi"
        
        Write-ColorText "⬇️ Pobieranie PuTTY..." "Info"
        Invoke-WebRequest -Uri $puttyUrl -OutFile $installer
        
        Write-ColorText "🔧 Instalowanie..." "Info"
        Start-Process msiexec.exe -ArgumentList "/i", $installer, "/quiet" -Wait
        
        Remove-Item $installer -Force
        Write-ColorText "✅ PuTTY zainstalowane pomyślnie" "Success"
    }
    catch {
        Write-ColorText "❌ Błąd instalacji PuTTY: $_" "Error"
        exit 1
    }
}

function Test-HostidoConnection {
    Write-ColorText "🔗 Testowanie połączenia z Hostido..." "Info"
    Write-ColorText "Host: $HostidoHost:$HostidoPort" "Info"
    Write-ColorText "User: $HostidoUser" "Info"
    
    try {
        $result = & $PlinkPath -ssh -i $HostidoKeyPath -P $HostidoPort -batch "$HostidoUser@$HostidoHost" "echo 'Connection successful: $(date)'"
        
        if ($LASTEXITCODE -eq 0) {
            Write-ColorText "✅ Połączenie z Hostido pomyślne!" "Success"
            Write-ColorText "📋 Odpowiedź: $result" "Info"
        } else {
            Write-ColorText "❌ Błąd połączenia z Hostido" "Error"
            return $false
        }
    }
    catch {
        Write-ColorText "❌ Wyjątek podczas połączenia: $_" "Error"
        return $false
    }
    
    return $true
}

function Invoke-HostidoCommand {
    param([string]$Command)
    
    Write-ColorText "🚀 Wykonywanie komendy na Hostido..." "Info"
    Write-ColorText "Command: $Command" "Info"
    
    try {
        $result = & $PlinkPath -ssh -i $HostidoKeyPath -P $HostidoPort -batch "$HostidoUser@$HostidoHost" $Command
        
        if ($LASTEXITCODE -eq 0) {
            Write-ColorText "✅ Komenda wykonana pomyślnie" "Success"
            Write-ColorText "📋 Wynik:" "Info"
            Write-Host $result
        } else {
            Write-ColorText "❌ Błąd wykonania komendy" "Error"
            Write-Host $result
            return $false
        }
    }
    catch {
        Write-ColorText "❌ Wyjątek podczas wykonywania: $_" "Error"
        return $false
    }
    
    return $true
}

# Główna logika skryptu
Write-ColorText "🌟 Hostido SSH Automation Script" "Info"
Write-ColorText "================================" "Info"

# Instalacja PuTTY jeśli wymagana
if ($InstallPuTTY) {
    Install-PuTTY
    exit 0
}

# Sprawdzenie wymagań
if (!(Test-Requirements)) {
    exit 1
}

# Test połączenia
if ($TestConnection) {
    if (Test-HostidoConnection) {
        Write-ColorText "🎉 Test połączenia zakończony sukcesem!" "Success"
    } else {
        Write-ColorText "💥 Test połączenia nieudany!" "Error"
        exit 1
    }
    exit 0
}

# Wykonanie komendy
if ($Command) {
    if (Invoke-HostidoCommand -Command $Command) {
        Write-ColorText "🎉 Operacja zakończona sukcesem!" "Success"
    } else {
        Write-ColorText "💥 Operacja nieudana!" "Error"
        exit 1
    }
}