# 🚀 MyDevil SSH Automation Script
# Automatyzacja połączeń SSH z MyDevil shared hosting

param(
    [string]$Command = "hostname && whoami && php -v",
    [switch]$TestConnection,
    [switch]$InstallPuTTY
)

# Konfiguracja MyDevil
$MyDevilHost = "s53.mydevil.net"
$MyDevilUser = "mpptrade"  
$MyDevilPassword = "Znighcnh861001"
$MyDevilPort = 22

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

function Test-PuTTY {
    if (Test-Path $PlinkPath) {
        Write-ColorText "✅ PuTTY/plink znaleziony: $PlinkPath" "Success"
        return $true
    } else {
        Write-ColorText "❌ PuTTY/plink nie znaleziony w: $PlinkPath" "Error"
        return $false
    }
}

function Install-PuTTY {
    Write-ColorText "🔄 Instalacja PuTTY..." "Info"
    
    try {
        # Download PuTTY
        $PuTTYUrl = "https://the.earth.li/~sgtatham/putty/latest/w64/putty-64bit-0.81-installer.msi"
        $TempFile = "$env:TEMP\putty-installer.msi"
        
        Write-ColorText "📥 Pobieranie PuTTY z $PuTTYUrl" "Info"
        Invoke-WebRequest -Uri $PuTTYUrl -OutFile $TempFile
        
        Write-ColorText "⚙️ Instalowanie PuTTY..." "Info"
        Start-Process -FilePath "msiexec.exe" -ArgumentList "/i `"$TempFile`" /quiet" -Wait
        
        Remove-Item $TempFile -Force
        Write-ColorText "✅ PuTTY zainstalowany pomyślnie" "Success"
        
    } catch {
        Write-ColorText "❌ Błąd instalacji PuTTY: $($_.Exception.Message)" "Error"
        Write-ColorText "💡 Zainstaluj PuTTY ręcznie z https://www.putty.org/" "Warning"
    }
}

function Invoke-MyDevilSSH {
    param([string]$SSHCommand)
    
    if (-not (Test-PuTTY)) {
        if ($InstallPuTTY) {
            Install-PuTTY
            if (-not (Test-PuTTY)) { return $false }
        } else {
            Write-ColorText "💡 Użyj parametru -InstallPuTTY aby zainstalować PuTTY automatycznie" "Warning"
            return $false
        }
    }
    
    try {
        Write-ColorText "🔗 Łączenie z MyDevil: $MyDevilUser@$MyDevilHost" "Info"
        Write-ColorText "⚡ Wykonywanie: $SSHCommand" "Info"
        
        # Użyj plink z hasłem przez stdin
        $ProcessInfo = New-Object System.Diagnostics.ProcessStartInfo
        $ProcessInfo.FileName = $PlinkPath
        $ProcessInfo.Arguments = "-ssh $MyDevilUser@$MyDevilHost -batch -pw $MyDevilPassword `"$SSHCommand`""
        $ProcessInfo.UseShellExecute = $false
        $ProcessInfo.RedirectStandardOutput = $true
        $ProcessInfo.RedirectStandardError = $true
        
        $Process = New-Object System.Diagnostics.Process
        $Process.StartInfo = $ProcessInfo
        $Process.Start() | Out-Null
        
        $Output = $Process.StandardOutput.ReadToEnd()
        $Error = $Process.StandardError.ReadToEnd()
        $Process.WaitForExit()
        
        if ($Process.ExitCode -eq 0) {
            Write-ColorText "✅ Polecenie wykonane pomyślnie" "Success"
            Write-ColorText "📤 Wynik:" "Info"
            Write-Host $Output
            return $true
        } else {
            Write-ColorText "❌ Błąd wykonania polecenia (Exit Code: $($Process.ExitCode))" "Error"
            if ($Error) { Write-ColorText "Błąd: $Error" "Error" }
            return $false
        }
        
    } catch {
        Write-ColorText "❌ Błąd połączenia SSH: $($_.Exception.Message)" "Error"
        return $false
    }
}

function Test-MyDevilConnection {
    Write-ColorText "🧪 Testowanie połączenia z MyDevil..." "Info"
    
    $TestCommands = @(
        "hostname && whoami",
        "php -v | head -3", 
        "which composer && composer --version",
        "ls -la /domains/ppm.mpptrade.pl/ 2>/dev/null || echo 'Katalog nie istnieje'",
        "/opt/alt/alt-nodejs22/root/usr/bin/node --version 2>/dev/null || echo 'Node.js nie znaleziony'"
    )
    
    foreach ($TestCmd in $TestCommands) {
        Write-ColorText "🔍 Test: $TestCmd" "Info"
        if (Invoke-MyDevilSSH -SSHCommand $TestCmd) {
            Write-ColorText "✅ Test zakończony pomyślnie`n" "Success"
        } else {
            Write-ColorText "❌ Test nie powiódł się`n" "Error"
        }
        Start-Sleep -Seconds 1
    }
}

# Główna logika skryptu
Write-ColorText "🤖 MyDevil SSH Automation dla PPM-CC-Laravel" "Info"
Write-ColorText "=========================================" "Info"

if ($InstallPuTTY) {
    Install-PuTTY
    exit 0
}

if ($TestConnection) {
    Test-MyDevilConnection
} elseif ($Command) {
    Invoke-MyDevilSSH -SSHCommand $Command
} else {
    Write-ColorText "💡 Użycie:" "Info"
    Write-ColorText "./mydevil_automation.ps1 -Command 'php -v'" "Info"  
    Write-ColorText "./mydevil_automation.ps1 -TestConnection" "Info"
    Write-ColorText "./mydevil_automation.ps1 -InstallPuTTY" "Info"
}