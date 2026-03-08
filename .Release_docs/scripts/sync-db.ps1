#Requires -Version 7.0
<#
.SYNOPSIS
    Synchronizes production database to local development environment.

.DESCRIPTION
    Downloads a mysqldump from the Hostido production server via SSH (plink),
    transfers the gzipped dump locally (pscp), decompresses it, and imports
    into the local MySQL/MariaDB instance (XAMPP).

.PARAMETER Full
    Full database dump including all data (default if no mode specified).

.PARAMETER SchemaOnly
    Dump schema only (no data). Useful for fresh structure setup.

.PARAMETER ExcludeLarge
    Exclude large tables (price_history, audit_logs) from the dump.

.PARAMETER Table
    Dump only a specific table by name.

.PARAMETER Verbose
    Show detailed progress output.

.EXAMPLE
    .\sync-db.ps1 -Full
    .\sync-db.ps1 -SchemaOnly
    .\sync-db.ps1 -ExcludeLarge
    .\sync-db.ps1 -Table products
    .\sync-db.ps1 -Full -Verbose
#>

[CmdletBinding()]
param(
    [switch]$Full,
    [switch]$SchemaOnly,
    [switch]$ExcludeLarge,
    [string]$Table
)

# ============================================================
# CONFIGURATION
# ============================================================

# Load shared Hostido config (SSH key, host, port)
$ConfigPath = Join-Path $PSScriptRoot "hostido-config.ps1"
if (Test-Path $ConfigPath) {
    . $ConfigPath
} else {
    Write-Host "[WARN] hostido-config.ps1 not found at $ConfigPath - using inline defaults" -ForegroundColor Yellow

    # Fallback: resolve SSH key inline
    $KeyPaths = @(
        "D:\SSH\Hostido\HostidoSSHNoPass.ppk",
        "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
    )
    $HostidoKey = $KeyPaths | Where-Object { Test-Path $_ } | Select-Object -First 1
    $HostidoUser = "host379076"
    $HostidoHost = "host379076.hostido.net.pl"
    $HostidoPort = 64321
    $RemoteLaravelRoot = "domains/ppm.mpptrade.pl/public_html"
}

# Production database credentials
$ProdDbName   = "host379076_ppm"
$ProdDbUser   = "host379076_ppm"
$ProdDbPass   = "qkS4FuXMMDDN4DJhatg6"

# Local database credentials (XAMPP default)
$LocalDbName  = "ppm_local"
$LocalDbUser  = "root"
$LocalDbPass  = ""

# Temp directory for dump files
$TempDir      = Join-Path $env:TEMP "ppm-db-sync"
$Timestamp    = Get-Date -Format "yyyyMMdd_HHmmss"
$DumpFileName = "ppm_dump_${Timestamp}.sql"
$DumpFileGz   = "${DumpFileName}.gz"
$RemoteTempDir = "/tmp"

# ============================================================
# HELPER FUNCTIONS
# ============================================================

function Write-Step {
    param([string]$Message)
    Write-Host "`n>>> $Message" -ForegroundColor Cyan
}

function Write-Ok {
    param([string]$Message)
    Write-Host "    [OK] $Message" -ForegroundColor Green
}

function Write-Fail {
    param([string]$Message)
    Write-Host "    [FAIL] $Message" -ForegroundColor Red
}

function Write-Detail {
    param([string]$Message)
    if ($VerbosePreference -eq 'Continue') {
        Write-Host "    $Message" -ForegroundColor Gray
    }
}

function Test-CommandAvailable {
    param([string]$Command)
    $found = Get-Command $Command -ErrorAction SilentlyContinue
    return $null -ne $found
}

# ============================================================
# PREREQUISITE CHECKS
# ============================================================

Write-Step "Checking prerequisites..."

$prereqsFailed = $false

# Check plink
if (Test-CommandAvailable "plink") {
    Write-Ok "plink found"
} else {
    Write-Fail "plink not found in PATH. Install PuTTY or add to PATH."
    $prereqsFailed = $true
}

# Check pscp
if (Test-CommandAvailable "pscp") {
    Write-Ok "pscp found"
} else {
    Write-Fail "pscp not found in PATH. Install PuTTY or add to PATH."
    $prereqsFailed = $true
}

# Check mysql client
if (Test-CommandAvailable "mysql") {
    Write-Ok "mysql client found"
} else {
    # Try XAMPP locations (project root or C:\xampp)
    $xamppMysqlPaths = @(
        (Join-Path $PSScriptRoot "..\..\XAMPP\mysql\bin\mysql.exe"),
        "C:\xampp\mysql\bin\mysql.exe"
    )
    $xamppMysql = $xamppMysqlPaths | Where-Object { Test-Path $_ } | Select-Object -First 1
    if ($xamppMysql) {
        Write-Ok "mysql client found at XAMPP location"
        Set-Alias -Name mysql -Value $xamppMysql -Scope Script
    } else {
        Write-Fail "mysql client not found. Ensure XAMPP is installed or mysql is in PATH."
        $prereqsFailed = $true
    }
}

# Check SSH key
if (-not $HostidoKey) {
    Write-Fail "SSH key not found. Checked paths:"
    $KeyPaths | ForEach-Object { Write-Host "      - $_" -ForegroundColor Gray }
    $prereqsFailed = $true
} else {
    Write-Ok "SSH key: $HostidoKey"
}

if ($prereqsFailed) {
    Write-Host "`n[ABORT] Prerequisites not met. Fix the issues above and retry." -ForegroundColor Red
    exit 1
}

# ============================================================
# DETERMINE DUMP MODE
# ============================================================

# Default to Full if no mode specified
if (-not $Full -and -not $SchemaOnly -and -not $ExcludeLarge -and -not $Table) {
    $Full = $true
}

# Base mysqldump options
$dumpOpts = @(
    "--single-transaction",
    "--routines",
    "--triggers",
    "--set-gtid-purged=OFF"
)

# Always-ignored tables (transient/cache data)
$ignoredTables = @(
    "${ProdDbName}.telescope_entries",
    "${ProdDbName}.telescope_entries_tags",
    "${ProdDbName}.telescope_monitoring",
    "${ProdDbName}.sessions",
    "${ProdDbName}.cache",
    "${ProdDbName}.cache_locks"
)

if ($SchemaOnly) {
    $dumpOpts += "--no-data"
    $modeLabel = "SCHEMA ONLY"
} elseif ($Table) {
    $dumpOpts += $Table
    $ignoredTables = @()  # No ignore when dumping single table
    $modeLabel = "SINGLE TABLE: $Table"
} elseif ($ExcludeLarge) {
    $ignoredTables += @(
        "${ProdDbName}.price_history",
        "${ProdDbName}.audit_logs"
    )
    $modeLabel = "FULL (excluding large tables)"
} else {
    $modeLabel = "FULL"
}

# Build ignore-table flags
$ignoreFlags = ($ignoredTables | ForEach-Object { "--ignore-table=$_" }) -join " "

Write-Step "Dump mode: $modeLabel"
if (($VerbosePreference -eq 'Continue') -and $ignoredTables.Count -gt 0) {
    Write-Detail "Ignored tables:"
    $ignoredTables | ForEach-Object { Write-Detail "  - $_" }
}

# ============================================================
# STEP 1: CREATE DUMP ON PRODUCTION SERVER
# ============================================================

Write-Step "Creating mysqldump on production server..."

$dumpOptsStr = $dumpOpts -join " "
$remoteDumpPath = "${RemoteTempDir}/${DumpFileGz}"

# Build the remote command
# mysqldump runs on the server (DB is localhost there), then gzip
$remoteCmd = "mysqldump -u ${ProdDbUser} -p'${ProdDbPass}' ${dumpOptsStr} ${ignoreFlags} ${ProdDbName} | gzip > ${remoteDumpPath} && echo DUMP_OK || echo DUMP_FAIL"

Write-Detail "Remote command: $remoteCmd"

$dumpResult = & plink -ssh "$HostidoUser@$HostidoHost" -P $HostidoPort -i $HostidoKey -batch $remoteCmd 2>&1

if ($dumpResult -match "DUMP_OK") {
    Write-Ok "Dump created on server: $remoteDumpPath"
} else {
    Write-Fail "mysqldump failed on server"
    Write-Host "    Output: $dumpResult" -ForegroundColor Yellow
    exit 1
}

# Get dump file size
$sizeResult = & plink -ssh "$HostidoUser@$HostidoHost" -P $HostidoPort -i $HostidoKey -batch "ls -lh ${remoteDumpPath} | awk '{print `$5}'" 2>&1
Write-Detail "Dump size (gzipped): $sizeResult"

# ============================================================
# STEP 2: DOWNLOAD DUMP TO LOCAL MACHINE
# ============================================================

Write-Step "Downloading dump to local machine..."

# Ensure temp directory exists
if (-not (Test-Path $TempDir)) {
    New-Item -ItemType Directory -Path $TempDir -Force | Out-Null
}

$localGzPath  = Join-Path $TempDir $DumpFileGz
$localSqlPath = Join-Path $TempDir $DumpFileName

Write-Detail "Target: $localGzPath"

& pscp -i $HostidoKey -P $HostidoPort "${HostidoUser}@${HostidoHost}:${remoteDumpPath}" $localGzPath 2>&1

if ($LASTEXITCODE -ne 0 -or -not (Test-Path $localGzPath)) {
    Write-Fail "Download failed"
    exit 1
}

$localSize = (Get-Item $localGzPath).Length
$localSizeMB = [math]::Round($localSize / 1MB, 2)
Write-Ok "Downloaded: $localGzPath ($localSizeMB MB)"

# ============================================================
# STEP 3: DECOMPRESS
# ============================================================

Write-Step "Decompressing dump..."

# Use PowerShell built-in or 7-Zip
$sevenZip = "C:\Program Files\7-Zip\7z.exe"
if (Test-Path $sevenZip) {
    & $sevenZip e $localGzPath "-o$TempDir" -y 2>&1 | Out-Null
    Write-Detail "Decompressed with 7-Zip"
} else {
    # Try using System.IO.Compression (GZip)
    try {
        $inputStream  = [System.IO.File]::OpenRead($localGzPath)
        $outputStream = [System.IO.File]::Create($localSqlPath)
        $gzipStream   = [System.IO.Compression.GZipStream]::new($inputStream, [System.IO.Compression.CompressionMode]::Decompress)
        $gzipStream.CopyTo($outputStream)
        $gzipStream.Close()
        $outputStream.Close()
        $inputStream.Close()
        Write-Detail "Decompressed with .NET GZipStream"
    } catch {
        Write-Fail "Decompression failed: $($_.Exception.Message)"
        Write-Host "    Install 7-Zip or ensure .NET compression is available." -ForegroundColor Yellow
        exit 1
    }
}

if (-not (Test-Path $localSqlPath)) {
    Write-Fail "Decompressed file not found: $localSqlPath"
    exit 1
}

$sqlSize = (Get-Item $localSqlPath).Length
$sqlSizeMB = [math]::Round($sqlSize / 1MB, 2)
Write-Ok "Decompressed: $localSqlPath ($sqlSizeMB MB)"

# ============================================================
# STEP 4: IMPORT INTO LOCAL DATABASE
# ============================================================

Write-Step "Importing into local database '${LocalDbName}'..."

# Ensure local database exists
Write-Detail "Ensuring database '${LocalDbName}' exists..."

$createDbCmd = "CREATE DATABASE IF NOT EXISTS ``${LocalDbName}`` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

if ($LocalDbPass) {
    & mysql -u $LocalDbUser -p"$LocalDbPass" -e $createDbCmd 2>&1
} else {
    & mysql -u $LocalDbUser -e $createDbCmd 2>&1
}

if ($LASTEXITCODE -ne 0) {
    Write-Fail "Could not create/verify local database. Is MySQL/MariaDB running?"
    exit 1
}

Write-Detail "Importing SQL dump (this may take a while)..."

$importStart = Get-Date

if ($LocalDbPass) {
    & mysql -u $LocalDbUser -p"$LocalDbPass" $LocalDbName -e "source $($localSqlPath -replace '\\', '/')" 2>&1
} else {
    # Use cmd /c with pipe for large files (avoids PowerShell encoding issues)
    $mysqlExe = (Get-Command mysql -ErrorAction SilentlyContinue).Source
    if (-not $mysqlExe) {
        $mysqlExe = "C:\xampp\mysql\bin\mysql.exe"
    }
    & cmd /c "type `"$localSqlPath`" | `"$mysqlExe`" -u $LocalDbUser $LocalDbName" 2>&1
}

$importDuration = (Get-Date) - $importStart

if ($LASTEXITCODE -ne 0) {
    Write-Fail "Import failed. Check MySQL error output above."
    exit 1
}

Write-Ok "Import completed in $($importDuration.ToString('mm\:ss'))"

# ============================================================
# STEP 5: CLEANUP
# ============================================================

Write-Step "Cleaning up temporary files..."

# Remove remote dump
Write-Detail "Removing remote dump..."
& plink -ssh "$HostidoUser@$HostidoHost" -P $HostidoPort -i $HostidoKey -batch "rm -f ${remoteDumpPath}" 2>&1 | Out-Null
Write-Ok "Remote dump removed"

# Remove local files
Write-Detail "Removing local temp files..."
Remove-Item -Path $localGzPath -Force -ErrorAction SilentlyContinue
Remove-Item -Path $localSqlPath -Force -ErrorAction SilentlyContinue
Write-Ok "Local temp files removed"

# ============================================================
# SUMMARY
# ============================================================

Write-Host "`n============================================================" -ForegroundColor Green
Write-Host "  DATABASE SYNC COMPLETE" -ForegroundColor Green
Write-Host "============================================================" -ForegroundColor Green
Write-Host "  Mode:        $modeLabel"
Write-Host "  Source:      $ProdDbName @ production (Hostido)"
Write-Host "  Target:      $LocalDbName @ localhost (XAMPP)"
Write-Host "  Dump size:   $sqlSizeMB MB (uncompressed)"
Write-Host "  Import time: $($importDuration.ToString('mm\:ss'))"
Write-Host "  Timestamp:   $Timestamp"
Write-Host "============================================================`n" -ForegroundColor Green

Write-Host "[TIP] Update .env to point to local database:" -ForegroundColor Yellow
Write-Host "      DB_HOST=127.0.0.1" -ForegroundColor Gray
Write-Host "      DB_DATABASE=ppm_local" -ForegroundColor Gray
Write-Host "      DB_USERNAME=root" -ForegroundColor Gray
Write-Host "      DB_PASSWORD=" -ForegroundColor Gray
Write-Host ""
Write-Host "[TIP] Then run: php artisan migrate --force" -ForegroundColor Yellow
Write-Host "      (applies any pending migrations not yet on production)" -ForegroundColor Gray
