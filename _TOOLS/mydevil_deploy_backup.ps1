# 🚀 MyDevil Deployment Script dla PPM-CC-Laravel
# Upload plików na serwer MyDevil + wykonywanie komend

param(
    [string]$SourcePath = ".",
    [string]$TargetPath = "/domains/ppm.mpptrade.pl/public_html/",
    [string]$Command = "",
    [switch]$UploadOnly,
    [switch]$CommandOnly,
    [switch]$InstallWinSCP,
    [switch]$SetupDirectories
)

# Konfiguracja MyDevil
$MyDevilHost = "s53.mydevil.net"
$MyDevilUser = "mpptrade"  
$MyDevilPassword = "Znighcnh861001"

# Ścieżki narzędzi  
$WinSCPPath = "C:\Program Files (x86)\WinSCP\WinSCP.com"
$WinSCPNetPath = "C:\Program Files (x86)\WinSCP\WinSCPnet.dll"

# Import modułu automatyzacji SSH
$AutomationScript = "$PSScriptRoot\mydevil_automation.ps1"

# Kolory PowerShell 7
$Colors = @{
    Success = "`e[32m"; Error = "`e[31m"; Info = "`e[36m"; Warning = "`e[33m"; Reset = "`e[0m"
}

function Write-ColorText {
    param([string]$Text, [string]$Color = "Info")
    Write-Host "$($Colors[$Color])$Text$($Colors.Reset)"
}

function Test-WinSCP {
    if ((Test-Path $WinSCPPath) -and (Test-Path $WinSCPNetPath)) {
        Write-ColorText "✅ WinSCP znaleziony: $WinSCPPath" "Success"
        return $true
    } else {
        Write-ColorText "❌ WinSCP nie znaleziony" "Error"
        return $false
    }
}

function Install-WinSCP {
    Write-ColorText "🔄 Instalacja WinSCP..." "Info"
    
    try {
        $WinSCPUrl = "https://winscp.net/download/WinSCP-6.3.5-Setup.exe"
        $TempFile = "$env:TEMP\winscp-setup.exe"
        
        Write-ColorText "📥 Pobieranie WinSCP..." "Info"
        Invoke-WebRequest -Uri $WinSCPUrl -OutFile $TempFile
        
        Write-ColorText "⚙️ Instalowanie WinSCP..." "Info"
        Start-Process -FilePath $TempFile -ArgumentList "/SILENT" -Wait
        
        Remove-Item $TempFile -Force
        Write-ColorText "✅ WinSCP zainstalowany" "Success"
        
    } catch {
        Write-ColorText "❌ Błąd instalacji WinSCP: $($_.Exception.Message)" "Error"
        Write-ColorText "💡 Zainstaluj WinSCP ręcznie z https://winscp.net/" "Warning"
    }
}

function Invoke-FileUpload {
    param([string]$LocalPath, [string]$RemotePath)
    
    if (-not (Test-WinSCP)) {
        if ($InstallWinSCP) {
            Install-WinSCP
            if (-not (Test-WinSCP)) { return $false }
        } else {
            Write-ColorText "💡 Użyj -InstallWinSCP aby zainstalować WinSCP" "Warning"
            return $false
        }
    }
    
    try {
        # Dodaj WinSCP .NET assembly
        Add-Type -Path $WinSCPNetPath
        
        # Konfiguracja sesji
        $SessionOptions = New-Object WinSCP.SessionOptions -Property @{
            Protocol = [WinSCP.Protocol]::Sftp
            HostName = $MyDevilHost
            UserName = $MyDevilUser
            Password = $MyDevilPassword
            GiveUpSecurityAndAcceptAnySshHostKey = $true
        }
        
        $Session = New-Object WinSCP.Session
        
        try {
            Write-ColorText "🔗 Łączenie z MyDevil SFTP..." "Info"
            $Session.Open($SessionOptions)
            
            Write-ColorText "📤 Upload: $LocalPath → $RemotePath" "Info"
            
            # Sprawdź czy ścieżka lokalna istnieje
            if (-not (Test-Path $LocalPath)) {
                throw "Ścieżka lokalna nie istnieje: $LocalPath"
            }
            
            # Upload plików
            $TransferOptions = New-Object WinSCP.TransferOptions
            $TransferOptions.TransferMode = [WinSCP.TransferMode]::Automatic
            
            $TransferResult = $Session.PutFiles($LocalPath, $RemotePath, $false, $TransferOptions)
            $TransferResult.Check()
            
            Write-ColorText "✅ Upload zakończony pomyślnie" "Success"
            
            foreach ($Transfer in $TransferResult.Transfers) {
                Write-ColorText "📁 Przesłano: $($Transfer.FileName)" "Info"
            }
            
            return $true
            
        } finally {
            $Session.Dispose()
        }
        
    } catch {
        Write-ColorText "❌ Błąd uploadu: $($_.Exception.Message)" "Error"
        return $false
    }
}

function Setup-MyDevilDirectories {
    Write-ColorText "📁 Tworzenie struktury katalogów na MyDevil..." "Info"
    
    $Directories = @(
        "/domains/ppm.mpptrade.pl/public_html/",
        "/domains/ppm.mpptrade.pl/public_html/storage/",
        "/domains/ppm.mpptrade.pl/public_html/bootstrap/cache/",
        "/domains/ppm.mpptrade.pl/logs/"
    )
    
    foreach ($Dir in $Directories) {
        $Command = "mkdir -p `"$Dir`" && chmod 755 `"$Dir`""
        Write-ColorText "🔧 Tworzenie: $Dir" "Info"
        
        if (Test-Path $AutomationScript) {
            & $AutomationScript -Command $Command
        } else {
            Write-ColorText "⚠️ Brak skryptu automatyzacji SSH" "Warning"
        }
    }
}

function Invoke-DeploymentCommand {
    param([string]$DeployCommand)
    
    Write-ColorText "⚡ Wykonywanie polecenia na serwerze: $DeployCommand" "Info"
    
    if (Test-Path $AutomationScript) {
        & $AutomationScript -Command $DeployCommand
    } else {
        Write-ColorText "❌ Brak skryptu automatyzacji SSH: $AutomationScript" "Error"
        return $false
    }
}

# Główna logika
Write-ColorText "🚀 MyDevil Deployment dla PPM-CC-Laravel" "Info"
Write-ColorText "=======================================" "Info"

if ($InstallWinSCP) {
    Install-WinSCP
    exit 0
}

if ($SetupDirectories) {
    Setup-MyDevilDirectories
    exit 0
}

if ($CommandOnly -and $Command) {
    Invoke-DeploymentCommand -DeployCommand $Command
} elseif ($UploadOnly) {
    Invoke-FileUpload -LocalPath $SourcePath -RemotePath $TargetPath
} elseif (-not $CommandOnly -and -not $UploadOnly) {
    # Pełny deployment: upload + command
    Write-ColorText "🔄 Pełny deployment..." "Info"
    
    if (Invoke-FileUpload -LocalPath $SourcePath -RemotePath $TargetPath) {
        if ($Command) {
            Invoke-DeploymentCommand -DeployCommand $Command
        }
    }
} else {
    Write-ColorText "💡 Użycie:" "Info"
    Write-ColorText "./mydevil_deploy.ps1 -SourcePath './build/*' -TargetPath '/domains/ppm.mpptrade.pl/public_html/'" "Info"
    Write-ColorText "./mydevil_deploy.ps1 -Command 'php artisan migrate'" "Info"
    Write-ColorText "./mydevil_deploy.ps1 -SetupDirectories" "Info"
    Write-ColorText "./mydevil_deploy.ps1 -InstallWinSCP" "Info"
}