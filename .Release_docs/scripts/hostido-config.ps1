# ============================================================================
# Hostido Connection Configuration
# Shared config for deploy.ps1 and sync-db.ps1
# Dot-source this file: . "$PSScriptRoot\hostido-config.ps1"
# ============================================================================

# --- Connection ---
$HostidoHost = "host379076.hostido.net.pl"
$HostidoUser = "host379076"
$HostidoPort = 64321
$HostidoRemotePath = "domains/ppm.mpptrade.pl/public_html"
$HostidoBackupPath = "backups"

# --- Database ---
$HostidoDBName = "host379076_ppm"
$HostidoDBUser = "host379076_ppm"
$HostidoDBPassword = $env:HOSTIDO_DB_PASSWORD
if (-not $HostidoDBPassword) {
    $HostidoDBPassword = "qkS4FuXMMDDN4DJhatg6"
}

# --- SSH Key Resolution ---
$KeyPaths = @(
    "D:\SSH\Hostido\HostidoSSHNoPass.ppk",
    "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
)
$HostidoKey = $KeyPaths | Where-Object { Test-Path $_ } | Select-Object -First 1
if (-not $HostidoKey) {
    Write-Host "[ERROR] SSH key not found! Checked:" -ForegroundColor Red
    $KeyPaths | ForEach-Object { Write-Host "  - $_" -ForegroundColor Yellow }
    exit 1
}

# --- Color Output Helpers ---
function Write-Step {
    param([string]$Message)
    Write-Host "[STEP] $Message" -ForegroundColor Cyan
}

function Write-Ok {
    param([string]$Message)
    Write-Host "[OK]   $Message" -ForegroundColor Green
}

function Write-Warn {
    param([string]$Message)
    Write-Host "[WARN] $Message" -ForegroundColor Yellow
}

function Write-Err {
    param([string]$Message)
    Write-Host "[ERR]  $Message" -ForegroundColor Red
}

function Write-Dbg {
    param([string]$Message, [switch]$On)
    if ($On) { Write-Host "[DBG]  $Message" -ForegroundColor DarkGray }
}

# --- SSH / SCP Wrappers ---
function Invoke-HostidoSSH {
    param([string]$Command, [int]$TimeoutSec = 120)
    $result = & plink -ssh "$HostidoUser@$HostidoHost" -P $HostidoPort -i $HostidoKey -batch $Command 2>&1
    return $result
}

function Send-HostidoFile {
    param([string]$LocalPath, [string]$RemotePath)
    & pscp -i $HostidoKey -P $HostidoPort $LocalPath "${HostidoUser}@${HostidoHost}:${RemotePath}" 2>&1
}

function Send-HostidoDir {
    param([string]$LocalDir, [string]$RemoteDir)
    & pscp -i $HostidoKey -P $HostidoPort -r $LocalDir "${HostidoUser}@${HostidoHost}:${RemoteDir}" 2>&1
}

# --- Validate SSH Connection ---
function Test-HostidoConnection {
    $test = Invoke-HostidoSSH "echo OK"
    if ($test -match "OK") {
        Write-Ok "SSH connection verified"
        return $true
    }
    Write-Err "SSH connection failed"
    return $false
}
