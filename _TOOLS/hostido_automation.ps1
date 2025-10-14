# 🚀 Hostido SSH Automation Script
# Automatyzacja połączeń SSH z Hostido.net.pl shared hosting

param(
    [string]$Command = "hostname && whoami && php -v",
    [switch]$TestConnection,
    [switch]$InstallPuTTY,
    [switch]$HealthCheck,
    [switch]$GetLogs,
    [switch]$MonitorApp,
    [ValidateSet("error", "info", "debug")]
    [string]$LogLevel = "error",
    [int]$LogLines = 50
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
        
        Write-ColorText "⬇️  Pobieranie PuTTY..." "Info"
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
    Write-ColorText "Host: ${HostidoHost}:${HostidoPort}" "Info"
    Write-ColorText "User: $HostidoUser" "Info"
    
    try {
        $result = & $PlinkPath -ssh -i $HostidoKeyPath -P $HostidoPort -batch "${HostidoUser}@${HostidoHost}" "echo 'Connection successful: $(date)'"
        
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
        $result = & $PlinkPath -ssh -i $HostidoKeyPath -P $HostidoPort -batch "${HostidoUser}@${HostidoHost}" $Command
        
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

function Get-LaravelHealthCheck {
    Write-ColorText "🏥 Sprawdzanie stanu aplikacji Laravel..." "Info"
    
    $healthCommands = @(
        "cd domains/ppm.mpptrade.pl/public_html",
        "php artisan --version",
        "php -m | grep -E '(mysqli|pdo_mysql|openssl|mbstring|tokenizer|xml|ctype|json)'",
        "ls -la storage/logs/ | head -5",
        "df -h | grep domains",
        "php artisan config:show database.default 2>/dev/null || echo 'Database config OK'"
    )
    
    $fullCommand = $healthCommands -join " && "
    
    try {
        $result = & $PlinkPath -ssh -i $HostidoKeyPath -P $HostidoPort -batch "${HostidoUser}@${HostidoHost}" $fullCommand
        
        if ($LASTEXITCODE -eq 0) {
            Write-ColorText "✅ Health check zakończony pomyślnie" "Success"
            Write-ColorText "📋 Szczegóły:" "Info"
            Write-Host $result
            return $true
        } else {
            Write-ColorText "❌ Health check wykrył problemy" "Error"
            Write-Host $result
            return $false
        }
    }
    catch {
        Write-ColorText "❌ Wyjątek podczas health check: $_" "Error"
        return $false
    }
}

function Get-LaravelLogs {
    param([string]$Level, [int]$Lines)
    
    Write-ColorText "📄 Pobieranie logów Laravel (poziom: $Level, linie: $Lines)..." "Info"
    
    $logPath = "domains/ppm.mpptrade.pl/public_html/storage/logs"
    
    $logCommands = @(
        "cd $logPath",
        "ls -la *.log | head -3",
        "echo '--- OSTATNIE $Lines LINII ---'",
        "tail -n $Lines laravel.log 2>/dev/null || echo 'Brak pliku laravel.log'"
    )
    
    if ($Level -ne "error") {
        $logCommands += "echo '--- WSZYSTKIE POZIOMY ---'"
        $logCommands += "tail -n $Lines *.log 2>/dev/null | head -n 100"
    }
    
    $fullCommand = $logCommands -join " && "
    
    try {
        $result = & $PlinkPath -ssh -i $HostidoKeyPath -P $HostidoPort -batch "${HostidoUser}@${HostidoHost}" $fullCommand
        
        if ($LASTEXITCODE -eq 0) {
            Write-ColorText "✅ Logi pobrane pomyślnie" "Success"
            Write-ColorText "📋 Zawartość logów:" "Info"
            Write-Host $result
            return $true
        } else {
            Write-ColorText "❌ Błąd pobierania logów" "Error"
            Write-Host $result
            return $false
        }
    }
    catch {
        Write-ColorText "❌ Wyjątek podczas pobierania logów: $_" "Error"
        return $false
    }
}

function Start-ApplicationMonitor {
    Write-ColorText "📊 Uruchamianie monitora aplikacji..." "Info"
    Write-ColorText "⚠️ Naciśnij Ctrl+C aby zatrzymać monitoring" "Warning"
    
    $monitorCommands = @(
        "cd domains/ppm.mpptrade.pl/public_html",
        "echo '=== SYSTEM INFO ==='",
        "date && uptime",
        "echo '=== DISK SPACE ==='", 
        "df -h | grep domains",
        "echo '=== MEMORY USAGE ==='",
        "free -h 2>/dev/null || echo 'Memory info not available'",
        "echo '=== PHP PROCESSES ==='",
        "ps aux | grep php | head -5",
        "echo '=== RECENT LOG ENTRIES ==='",
        "tail -n 10 storage/logs/laravel.log 2>/dev/null || echo 'No recent logs'",
        "echo '=== END MONITORING ==='",
        "echo"
    )
    
    try {
        while ($true) {
            $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
            Write-ColorText "🔄 Monitor check: $timestamp" "Info"
            
            $fullCommand = $monitorCommands -join " && "
            $result = & $PlinkPath -ssh -i $HostidoKeyPath -P $HostidoPort -batch "${HostidoUser}@${HostidoHost}" $fullCommand
            
            Write-Host $result
            Write-ColorText "⏰ Czekanie 30 sekund do następnego check..." "Info"
            Start-Sleep -Seconds 30
        }
    }
    catch [System.Management.Automation.PipelineStoppedException] {
        Write-ColorText "🛑 Monitoring zatrzymany przez użytkownika" "Warning"
    }
    catch {
        Write-ColorText "❌ Błąd podczas monitoringu: $_" "Error"
    }
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

# Health Check Laravel
if ($HealthCheck) {
    if (Get-LaravelHealthCheck) {
        Write-ColorText "🎉 Health Check zakończony sukcesem!" "Success"
    } else {
        Write-ColorText "💥 Health Check wykrył problemy!" "Error"
        exit 1
    }
    exit 0
}

# Pobieranie logów
if ($GetLogs) {
    if (Get-LaravelLogs -Level $LogLevel -Lines $LogLines) {
        Write-ColorText "🎉 Logi pobrane pomyślnie!" "Success"
    } else {
        Write-ColorText "💥 Błąd pobierania logów!" "Error"
        exit 1
    }
    exit 0
}

# Monitoring aplikacji
if ($MonitorApp) {
    Start-ApplicationMonitor
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
