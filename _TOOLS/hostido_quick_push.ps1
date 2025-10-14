# Quick push wybranych plikow na Hostido (SFTP/WinSCP)

param(
    [Parameter(Mandatory=$true)]
    [string[]]$Files,
    [string]$TargetPath = "/domains/ppm.mpptrade.pl/public_html/",
    [string]$RootPath = ".",
    [string]$PostCommand = "",
    [switch]$VerboseLog
)

# Konfiguracja Hostido/WinSCP
$HostidoHost = "host379076.hostido.net.pl"
$HostidoUser = "host379076"
$HostidoPort = 64321
$HostidoKeyPath = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$WinSCPPath = "C:\Program Files (x86)\WinSCP\WinSCP.com"

function Write-Info($m){ Write-Host "[INFO] $m" }
function Write-Err($m){ Write-Host "[ERROR] $m" -ForegroundColor Red }

if (!(Test-Path $WinSCPPath)) { Write-Err "WinSCP.com nie znaleziony: $WinSCPPath"; exit 1 }
if (!(Test-Path $HostidoKeyPath)) { Write-Err "Klucz SSH nie znaleziony: $HostidoKeyPath"; exit 1 }

# Normalizacja sciezek i walidacja
$root = Resolve-Path $RootPath
$normalized = @()
foreach($f in $Files){
    if (!(Test-Path $f)) { Write-Err "Plik nie istnieje: $f"; exit 1 }
    $full = Resolve-Path $f
    $rel = ([System.IO.Path]::GetRelativePath($root, $full))
    $rel = $rel -replace "\\","/"
    $normalized += $rel
}

$remoteRoot = ($TargetPath -replace '^/','')

# Budowa skryptu WinSCP
$script = @()
$script += "open sftp://${HostidoUser}@${HostidoHost}:${HostidoPort} -privatekey=`"$HostidoKeyPath`" -hostkey=`"ssh-ed25519 255 s5jsBvAUexZAUyZgYF3ONT2RvrcsHjhso6DCiTBICiM`""
$script += "lcd `"$root`""
$script += "cd ${remoteRoot}"

foreach($rel in $normalized){
    $remoteDir = Split-Path $rel -Parent
    if ([string]::IsNullOrEmpty($remoteDir)) { $remoteDir = "." }
    $remoteDir = $remoteDir -replace "\\","/"
    $remoteFile = $rel -replace "\\","/"
    # Tworzenie katalogu zdalnie i upload tylko wybranego pliku
    if ($remoteDir -ne ".") { $script += "mkdir ${remoteDir}" }
    $script += "put -preservetime -transfer=binary `"$rel`" `"$remoteFile`""
}

$script += "close"
$script += "exit"

$tmp = Join-Path $env:TEMP ("hostido_quick_push_" + [Guid]::NewGuid().ToString('N') + ".txt")
$script -join "`n" | Out-File -FilePath $tmp -Encoding UTF8

try {
    Write-Info "Wysylanie plikow: $($normalized -join ', ')"
    & $WinSCPPath /console /script=$tmp | Out-Null
    if ($LASTEXITCODE -ne 0) { Write-Err "Upload nie powiodl sie (kod $LASTEXITCODE)"; exit $LASTEXITCODE }
    Write-Info "Upload zakonczony"
}
finally {
    if (Test-Path $tmp) { Remove-Item $tmp -Force }
}

if ($PostCommand) {
    Write-Info "Wykonuje post-command na serwerze"
    & "$PSScriptRoot/hostido_automation.ps1" -Command $PostCommand
    exit $LASTEXITCODE
}

exit 0
