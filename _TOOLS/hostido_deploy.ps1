# 🚀 Hostido Deployment Script dla PPM-CC-Laravel
# Upload plików na serwer Hostido + wykonywanie komend

param(
    [string]$SourcePath = ".",
    [string]$TargetPath = "/domains/ppm.mpptrade.pl/public_html/",
    [string]$Command = "",
    [switch]$UploadOnly,
    [switch]$CommandOnly,
    [switch]$InstallWinSCP,
    [switch]$SetupDirectories,
    [switch]$CreateBackup,
    [switch]$RestoreBackup,
    [switch]$DryRun,
    [switch]$NoDelete,
    [switch]$Force,
    [switch]$HealthCheck,
    [string]$BackupName = "",
    [switch]$Verbose
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

function Write-VerboseLog {
    param([string]$Message)
    if ($Verbose) {
        Write-ColorText "🔍 $Message" "Info"
    }
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
        
        Write-ColorText "⬇️  Pobieranie WinSCP..." "Info"
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
        "domains/ppm.mpptrade.pl/public_html/storage/logs",
        "domains/ppm.mpptrade.pl/public_html/storage/framework/cache",
        "domains/ppm.mpptrade.pl/public_html/storage/framework/sessions",
        "domains/ppm.mpptrade.pl/public_html/storage/framework/views",
        "domains/ppm.mpptrade.pl/public_html/bootstrap/cache",
        "domains/ppm.mpptrade.pl/backups"
    )
    
    foreach ($dir in $directories) {
        $createCommand = "mkdir -p $dir && chmod 775 $dir"
        Write-ColorText "📂 Tworzenie: $dir" "Info"
        
        try {
            & "$PSScriptRoot\hostido_automation.ps1" -Command $createCommand
        }
        catch {
            Write-ColorText "⚠️  Błąd tworzenia katalogu $dir" "Warning"
        }
    }
    
    Write-ColorText "✅ Struktura katalogów utworzona" "Success"
}

function New-ApplicationBackup {
    param([string]$BackupName)
    
    if ([string]::IsNullOrEmpty($BackupName)) {
        $BackupName = "backup_$(Get-Date -Format 'yyyyMMdd_HHmmss')"
    }
    
    Write-ColorText "💾 Tworzenie backup aplikacji: $BackupName..." "Info"
    
    if ($DryRun) {
        Write-ColorText "🔍 DRY-RUN: Backup aplikacji" "Warning"
        return $BackupName
    }
    
    $backupCommands = @(
        "cd domains/ppm.mpptrade.pl",
        "mkdir -p backups/$BackupName",
        "echo 'Creating application backup...'",
        "cp -r public_html backups/$BackupName/app_files 2>/dev/null || echo 'Some files skipped'",
        "mysqldump -u host379076_ppm -p host379076_ppm > backups/$BackupName/database.sql 2>/dev/null || echo 'Database backup may have failed - check manually'",
        "echo 'Backup completed: $(date)' > backups/$BackupName/backup_info.txt",
        "ls -la backups/$BackupName/"
    )
    
    $fullCommand = $backupCommands -join " && "
    
    try {
        Write-VerboseLog "Wykonywanie backup commands..."
        & "$PSScriptRoot\hostido_automation.ps1" -Command $fullCommand
        
        if ($LASTEXITCODE -eq 0) {
            Write-ColorText "✅ Backup utworzony: $BackupName" "Success"
        } else {
            Write-ColorText "⚠️  Backup może być niepełny - sprawdź manually" "Warning"
        }
        
        return $BackupName
    }
    catch {
        Write-ColorText "❌ Błąd tworzenia backup: $_" "Error"
        return $null
    }
}

function Restore-ApplicationBackup {
    param([string]$BackupName)
    
    if ([string]::IsNullOrEmpty($BackupName)) {
        Write-ColorText "❌ Nazwa backup jest wymagana dla restore" "Error"
        return $false
    }
    
    Write-ColorText "🔄 Przywracanie backup aplikacji: $BackupName..." "Info"
    Write-ColorText "⚠️  UWAGA: To zastąpi obecną aplikację!" "Warning"
    
    if (!$Force -and !$DryRun) {
        $confirm = Read-Host "Czy chcesz kontynuować? (tak/nie)"
        if ($confirm -ne "tak") {
            Write-ColorText "🛑 Restore anulowany przez użytkownika" "Warning"
            return $false
        }
    }
    
    if ($DryRun) {
        Write-ColorText "🔍 DRY-RUN: Restore backup $BackupName" "Warning"
        return $true
    }
    
    $restoreCommands = @(
        "cd domains/ppm.mpptrade.pl",
        "ls backups/$BackupName/ || { echo 'Backup not found'; exit 1; }",
        "echo 'Starting restore process...'",
        "rm -rf public_html_old 2>/dev/null || true",
        "mv public_html public_html_old",
        "cp -r backups/$BackupName/app_files public_html",
        "chmod -R 775 public_html/storage/ public_html/bootstrap/cache/",
        "echo 'Application files restored'",
        "echo 'Database restore requires manual intervention - check backups/$BackupName/database.sql'",
        "echo 'Restore completed: $(date)'"
    )
    
    $fullCommand = $restoreCommands -join " && "
    
    try {
        Write-VerboseLog "Wykonywanie restore commands..."
        & "$PSScriptRoot\hostido_automation.ps1" -Command $fullCommand
        
        if ($LASTEXITCODE -eq 0) {
            Write-ColorText "✅ Aplikacja przywrócona z backup: $BackupName" "Success"
            Write-ColorText "⚠️  Sprawdź bazę danych manually!" "Warning"
            return $true
        } else {
            Write-ColorText "❌ Błąd podczas restore" "Error"
            return $false
        }
    }
    catch {
        Write-ColorText "❌ Wyjątek podczas restore: $_" "Error"
        return $false
    }
}

function Test-ApplicationHealth {
    Write-ColorText "🏥 Sprawdzanie stanu aplikacji po deployment..." "Info"
    
    try {
        # Test basic Laravel health
        Write-VerboseLog "Sprawdzanie podstawowych funkcji Laravel..."
        $healthResult = & "$PSScriptRoot\hostido_automation.ps1" -HealthCheck
        
        if ($LASTEXITCODE -ne 0) {
            Write-ColorText "❌ Laravel health check failed" "Error"
            return $false
        }
        
        # Test HTTP response (if curl available)
        Write-VerboseLog "Sprawdzanie odpowiedzi HTTP..."
        $httpTest = & "$PSScriptRoot\hostido_automation.ps1" -Command "curl -s -o /dev/null -w '%{http_code}' https://ppm.mpptrade.pl/ 2>/dev/null || echo '000'"
        
        if ($httpTest -like "*200*" -or $httpTest -like "*302*") {
            Write-ColorText "✅ HTTP response OK" "Success"
        } else {
            Write-ColorText "⚠️  HTTP response: $httpTest" "Warning"
        }
        
        Write-ColorText "✅ Health check zakończony" "Success"
        return $true
    }
    catch {
        Write-ColorText "❌ Błąd podczas health check: $_" "Error"
        return $false
    }
}

function Deploy-ToHostido {
    param([string]$Source, [string]$Target)
    
    Write-ColorText "🚀 Rozpoczynanie deployment na Hostido..." "Info"
    Write-ColorText "Source: $Source" "Info"
    Write-ColorText "Target: $Target" "Info"

    # Dry-run: skip file sync
    if ($DryRun) {
        Write-ColorText "DRY-RUN: Skipping file synchronization" "Warning"
        return $true
    }

    # Wykluczenia dla upload
    $ExcludePatterns = @(
        "node_modules\*",
        ".git\*",
        "tests\*",
        "storage\*",
        "bootstrap\cache\*",
        "vendor\*",
        ".env*",
        "*.log",
        "_TOOLS\*",
        "_REPORTS\*",
        "_OTHER\*"
    )
    
    try {
        # Tworzenie skryptu WinSCP
        $ScriptPath = "$env:TEMP\hostido_deploy_script.txt"
        $RemoteTargetPath = ($Target -replace '^/','')
        $DeleteFlag = if ($NoDelete) { "" } else { " -delete" }
        $ScriptContent = @"
open sftp://${HostidoUser}@${HostidoHost}:${HostidoPort} -privatekey="${HostidoKeyPath}" -hostkey="ssh-ed25519 255 s5jsBvAUexZAUyZgYF3ONT2RvrcsHjhso6DCiTBICiM"
cd ${RemoteTargetPath}
lcd ${Source}
synchronize remote$DeleteFlag -criteria=time -transfer=binary -preservetime -filemask="|$($ExcludePatterns -join ';')"
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
    Write-ColorText "⚙️  Wykonywanie komend post-deployment..." "Info"
    
    if ($DryRun) {
        Write-ColorText "DRY-RUN: Skipping post-deployment commands" "Warning"
        return $true
    }
    
    $remoteTarget = ($TargetPath -replace '^/','')
    $commands = @(
        "cd $remoteTarget",
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
Write-ColorText "Mode: $(if($DryRun){'DRY-RUN'}else{'EXECUTION'})" $(if($DryRun){'Warning'}else{'Info'})

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

# Tylko backup
if ($CreateBackup) {
    Write-ColorText "💾 Tryb: Tworzenie backup" "Info"
    if (!(Test-Requirements)) { exit 1 }
    $backupResult = New-ApplicationBackup -BackupName $BackupName
    if ($backupResult) {
        Write-ColorText "🎉 Backup utworzony: $backupResult" "Success"
        exit 0
    } else {
        Write-ColorText "💥 Błąd tworzenia backup!" "Error"
        exit 1
    }
}

# Restore backup
if ($RestoreBackup) {
    Write-ColorText "🔄 Tryb: Restore backup" "Info"
    if (!(Test-Requirements)) { exit 1 }
    if (Restore-ApplicationBackup -BackupName $BackupName) {
        Write-ColorText "🎉 Backup przywrócony pomyślnie!" "Success"
        exit 0
    } else {
        Write-ColorText "💥 Błąd przywracania backup!" "Error"
        exit 1
    }
}

# Tylko health check
if ($HealthCheck) {
    Write-ColorText "🏥 Tryb: Health Check" "Info"
    if (!(Test-Requirements)) { exit 1 }
    if (Test-ApplicationHealth) {
        Write-ColorText "🎉 Health Check przeszedł pomyślnie!" "Success"
        exit 0
    } else {
        Write-ColorText "💥 Health Check wykrył problemy!" "Error"
        exit 1
    }
}

# Sprawdzenie wymagań
if (!(Test-Requirements)) {
    exit 1
}

# Wykonanie tylko komendy
if ($CommandOnly -and $Command) {
    Write-ColorText "🎯 Wykonywanie tylko komendy..." "Info"
    if ($DryRun) {
        Write-ColorText "🔍 DRY-RUN: $Command" "Warning"
        exit 0
    }
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

# Tracking rezultatów
$Results = @{}
$BackupCreated = $null

try {
    # 1. Tworzenie backup przed deployment (jeśli nie DryRun)
    if (!$DryRun) {
        Write-ColorText "💾 Tworzenie backup przed deployment..." "Info"
        $BackupCreated = New-ApplicationBackup
        $Results["Create Backup"] = $BackupCreated -ne $null
        
        if (!$Results["Create Backup"]) {
            Write-ColorText "⚠️  Kontynuowanie bez backup - użyj -Force aby wymusić" "Warning"
            if (!$Force) {
                Write-ColorText "💥 Deployment przerwany - brak backup!" "Error"
                exit 1
            }
        }
    } else {
        $Results["Create Backup"] = $true
    }
    
    # 2. Upload plików
    $Results["Upload Files"] = Deploy-ToHostido -Source $SourcePath -Target $TargetPath
    if (!$Results["Upload Files"]) {
        throw "Upload files failed"
    }
    
    # 3. Wykonanie komend post-deployment
    $Results["Post Deploy Commands"] = Invoke-PostDeployCommands
    if (!$Results["Post Deploy Commands"]) {
        Write-ColorText "⚠️  Post-deployment commands failed - może wymagać manual intervention" "Warning"
    }
    
    # 4. Wykonanie custom komendy jeśli podana
    if ($Command) {
        Write-ColorText "🎯 Wykonywanie custom komendy..." "Info"
        & $AutomationScript -Command $Command
        $Results["Custom Command"] = $LASTEXITCODE -eq 0
    }

    # 4b. Ponowne komendy post-deployment po custom
    $Results["Post Deploy Commands"] = Invoke-PostDeployCommands

    # 5. Health check
    $Results["Health Check"] = Test-ApplicationHealth
    if (!$Results["Health Check"]) {
        Write-ColorText "⚠️  Health check wykrył problemy - sprawdź aplikację!" "Warning"
    }
    
    # Podsumowanie
    Write-ColorText "" "Info"
    Write-ColorText "📊 PODSUMOWANIE DEPLOYMENT" "Info"
    Write-ColorText "==========================" "Info"
    
    foreach ($key in $Results.Keys) {
        $status = if ($Results[$key]) { "✅ SUCCESS" } else { "❌ FAILED" }
        $color = if ($Results[$key]) { "Success" } else { "Error" }
        Write-ColorText "$key : $status" $color
    }
    
    if ($BackupCreated) {
        Write-ColorText "" "Info"
        Write-ColorText "💾 Backup dostępny: $BackupCreated" "Info"
        Write-ColorText "🔄 Rollback: .\hostido_deploy.ps1 -RestoreBackup -BackupName '$BackupCreated'" "Info"
    }
    
    $overallSuccess = ($Results.Values | Where-Object { $_ -eq $false }).Count -eq 0
    
    if ($overallSuccess) {
        Write-ColorText "🎉 Deployment zakończony pełnym sukcesem!" "Success"
        Write-ColorText "🌐 Sprawdź aplikację: https://ppm.mpptrade.pl" "Info"
        exit 0
    } else {
        Write-ColorText "⚠️  Deployment zakończony z ostrzeżeniami!" "Warning"
        Write-ColorText "🌐 Sprawdź aplikację: https://ppm.mpptrade.pl" "Info"
        exit 1
    }
}
catch {
    Write-ColorText "💥 Deployment nieudany: $_" "Error"
    
    if ($BackupCreated -and !$DryRun) {
        Write-ColorText "🔄 Dostępny rollback: .\hostido_deploy.ps1 -RestoreBackup -BackupName '$BackupCreated'" "Warning"
    }
    
    exit 1
}
