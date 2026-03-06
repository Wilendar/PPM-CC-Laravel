# ============================================================================
# PPM-CC-Laravel Deployment Script (pscp/plink based)
# Usage:  .\deploy.ps1 -Mode full
#         .\deploy.ps1 -Mode assets
#         .\deploy.ps1 -Mode php -SkipMigrate
#         .\deploy.ps1 -Mode rollback
# ============================================================================

param(
    [ValidateSet('full','assets','php','migrate','rollback')]
    [string]$Mode = 'full',
    [switch]$SkipBackup,
    [switch]$SkipBuild,
    [switch]$SkipMigrate,
    [switch]$DryRun,
    [switch]$Verbose
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

# --- Load shared config ---
. "$PSScriptRoot\hostido-config.ps1"

# --- Resolve project root (two levels up from .Release_docs/scripts/) ---
$ProjectRoot = (Resolve-Path "$PSScriptRoot\..\..").Path

# --- Timing ---
$sw = [System.Diagnostics.Stopwatch]::StartNew()

# --- Excluded dirs/files for PHP upload ---
$ExcludeDirs = @(
    '.git', '.claude', '.Release_docs', '_TOOLS', '_DOCS', '_ISSUES_FIXES',
    '_AGENT_REPORTS', '_TEST', '_OTHER', 'node_modules', 'vendor',
    'storage', '.github', 'tests'
)
$ExcludeFiles = @('.env', '.env.example', '.gitignore', '.gitattributes')

# ============================================================================
# FUNCTIONS
# ============================================================================

function Test-Prerequisites {
    Write-Step "Checking prerequisites..."
    $ok = $true

    # SSH key (already validated in hostido-config.ps1)
    Write-Dbg "SSH key: $HostidoKey" -On:$Verbose

    # plink / pscp
    foreach ($tool in @('plink', 'pscp')) {
        $found = Get-Command $tool -ErrorAction SilentlyContinue
        if (-not $found) {
            Write-Err "$tool not found in PATH"
            $ok = $false
        }
    }

    # Node/npm (only when build is needed)
    if ($Mode -in @('full', 'assets') -and -not $SkipBuild) {
        foreach ($tool in @('node', 'npm')) {
            $found = Get-Command $tool -ErrorAction SilentlyContinue
            if (-not $found) {
                Write-Err "$tool not found in PATH (required for asset build)"
                $ok = $false
            }
        }
    }

    # PHP (for local artisan if needed)
    $phpFound = Get-Command php -ErrorAction SilentlyContinue
    Write-Dbg "Local PHP: $(if($phpFound){$phpFound.Source}else{'not found'})" -On:$Verbose

    # Test SSH connection
    if ($ok) {
        $ok = Test-HostidoConnection
    }

    if ($ok) { Write-Ok "All prerequisites passed" }
    return $ok
}

# --- BACKUP ---
function Invoke-RemoteBackup {
    if ($SkipBackup) {
        Write-Warn "Backup skipped (-SkipBackup)"
        return "skipped"
    }
    Write-Step "Creating remote database backup..."
    if ($DryRun) {
        Write-Warn "DRY-RUN: Would create mysqldump on server"
        return "dryrun"
    }

    $ts = Get-Date -Format "yyyyMMdd_HHmmss"
    $backupFile = "backup_${ts}.sql.gz"
    $cmd = @(
        "mkdir -p ~/$HostidoBackupPath",
        "mysqldump --single-transaction -u $HostidoDBUser -p'$HostidoDBPassword' $HostidoDBName | gzip > ~/$HostidoBackupPath/$backupFile",
        "echo BACKUP_CREATED:$backupFile"
    ) -join " && "

    $result = Invoke-HostidoSSH $cmd
    Write-Dbg "Backup result: $result" -On:$Verbose

    if ($result -match "BACKUP_CREATED:(.+)") {
        Write-Ok "Backup created: $($Matches[1])"

        # Cleanup old backups (keep last 5)
        $cleanCmd = "cd ~/$HostidoBackupPath && ls -t backup_*.sql.gz | tail -n +6 | xargs rm -f 2>/dev/null; echo CLEANUP_DONE"
        Invoke-HostidoSSH $cleanCmd | Out-Null
        Write-Dbg "Old backups cleaned (keeping last 5)" -On:$Verbose

        return $backupFile
    }
    else {
        Write-Err "Backup may have failed. Output: $result"
        return $null
    }
}

# --- BUILD ---
function Invoke-AssetBuild {
    if ($SkipBuild) {
        Write-Warn "Build skipped (-SkipBuild)"
        return $true
    }
    Write-Step "Building frontend assets (npm run build)..."
    if ($DryRun) {
        Write-Warn "DRY-RUN: Would run npm run build in $ProjectRoot"
        return $true
    }

    Push-Location $ProjectRoot
    try {
        $buildOutput = & npm run build 2>&1
        $buildText = $buildOutput -join "`n"
        Write-Dbg $buildText -On:$Verbose

        if ($buildText -match "built in") {
            Write-Ok "Vite build completed"
        }
        else {
            Write-Err "Build output does not contain success marker"
            Write-Host $buildText -ForegroundColor Gray
            return $false
        }

        # Verify manifest exists
        $manifestPath = Join-Path $ProjectRoot "public\build\.vite\manifest.json"
        if (-not (Test-Path $manifestPath)) {
            Write-Err "Manifest not found at: $manifestPath"
            return $false
        }
        Write-Ok "Manifest verified: $manifestPath"
        return $true
    }
    finally {
        Pop-Location
    }
}

# --- UPLOAD: Assets ---
function Deploy-Assets {
    Write-Step "Uploading Vite assets..."
    if ($DryRun) {
        Write-Warn "DRY-RUN: Would upload public/build/assets/* and manifest"
        return $true
    }

    $localAssets = Join-Path $ProjectRoot "public\build\assets"
    $remoteAssets = "$HostidoRemotePath/public/build/assets"

    if (-not (Test-Path $localAssets)) {
        Write-Err "Local assets dir not found: $localAssets"
        return $false
    }

    # Ensure remote directory exists
    Invoke-HostidoSSH "mkdir -p $remoteAssets" | Out-Null

    # Upload all assets recursively
    Write-Dbg "pscp -r $localAssets -> $remoteAssets" -On:$Verbose
    $result = Send-HostidoDir "$localAssets\*" $remoteAssets
    Write-Dbg "Upload result: $result" -On:$Verbose

    # Upload manifest to ROOT location (Laravel requirement)
    $localManifest = Join-Path $ProjectRoot "public\build\.vite\manifest.json"
    $remoteManifest = "$HostidoRemotePath/public/build/manifest.json"
    Write-Dbg "Uploading manifest -> $remoteManifest" -On:$Verbose
    $mResult = Send-HostidoFile $localManifest $remoteManifest
    Write-Dbg "Manifest result: $mResult" -On:$Verbose

    # Also upload to .vite/ subdir for consistency
    $remoteViteDir = "$HostidoRemotePath/public/build/.vite"
    Invoke-HostidoSSH "mkdir -p $remoteViteDir" | Out-Null
    Send-HostidoFile $localManifest "$remoteViteDir/manifest.json" | Out-Null

    Write-Ok "Assets and manifest uploaded"
    return $true
}

# --- UPLOAD: PHP Application ---
function Deploy-PhpFiles {
    Write-Step "Uploading PHP application files..."
    if ($DryRun) {
        Write-Warn "DRY-RUN: Would upload app/, config/, routes/, etc."
        return $true
    }

    $uploadDirs = @(
        'app', 'bootstrap', 'config', 'database', 'lang',
        'resources/views', 'routes'
    )
    $uploadFiles = @(
        'composer.json', 'composer.lock', 'artisan'
    )

    $errors = 0

    foreach ($dir in $uploadDirs) {
        $localDir = Join-Path $ProjectRoot $dir
        if (-not (Test-Path $localDir)) {
            Write-Warn "Directory not found, skipping: $dir"
            continue
        }
        $remoteDir = "$HostidoRemotePath/$dir"
        Invoke-HostidoSSH "mkdir -p $remoteDir" | Out-Null

        Write-Dbg "Uploading dir: $dir" -On:$Verbose
        $r = Send-HostidoDir "$localDir\*" $remoteDir
        if ($LASTEXITCODE -ne 0) {
            Write-Warn "Upload issue with $dir"
            $errors++
        }
    }

    foreach ($file in $uploadFiles) {
        $localFile = Join-Path $ProjectRoot $file
        if (-not (Test-Path $localFile)) {
            Write-Warn "File not found, skipping: $file"
            continue
        }
        Write-Dbg "Uploading file: $file" -On:$Verbose
        Send-HostidoFile $localFile "$HostidoRemotePath/$file" | Out-Null
        if ($LASTEXITCODE -ne 0) {
            Write-Warn "Upload issue with $file"
            $errors++
        }
    }

    if ($errors -gt 0) {
        Write-Warn "$errors upload issue(s) detected"
    }
    else {
        Write-Ok "PHP files uploaded"
    }
    return ($errors -eq 0)
}

# --- POST-DEPLOY: Composer + Migrate + Cache ---
function Invoke-PostDeploy {
    param([switch]$CacheOnly)

    if ($CacheOnly) {
        Write-Step "Clearing and rebuilding caches..."
    }
    else {
        Write-Step "Running post-deployment commands..."
    }

    if ($DryRun) {
        Write-Warn "DRY-RUN: Would run composer install + artisan commands"
        return $true
    }

    $commands = @()

    # Composer install (only for full/php modes)
    if (-not $CacheOnly) {
        $commands += "composer install --no-dev --optimize-autoloader --no-interaction 2>&1"
    }

    # Migrations (only if not skipped and not cache-only)
    if (-not $CacheOnly -and -not $SkipMigrate) {
        $commands += "php artisan migrate --force --no-interaction 2>&1"
    }

    # Cache clear + rebuild
    $commands += @(
        "php artisan config:clear",
        "php artisan cache:clear",
        "php artisan view:clear",
        "php artisan route:clear",
        "php artisan config:cache",
        "php artisan route:cache",
        "php artisan view:cache",
        "php artisan optimize"
    )

    $fullCmd = "cd $HostidoRemotePath && " + ($commands -join " && ")
    Write-Dbg "Remote command: $fullCmd" -On:$Verbose

    $result = Invoke-HostidoSSH $fullCmd
    $resultText = ($result | Out-String)
    Write-Dbg $resultText -On:$Verbose

    if ($resultText -match "error|failed|exception" -and $resultText -notmatch "Compiled views cleared|Configuration cache cleared") {
        Write-Warn "Post-deploy output may contain errors. Review:"
        Write-Host $resultText -ForegroundColor Gray
    }
    else {
        Write-Ok "Post-deployment commands completed"
    }
    return $true
}

# --- VERIFY ---
function Test-ProductionHealth {
    Write-Step "Verifying production site..."
    if ($DryRun) {
        Write-Warn "DRY-RUN: Would check https://ppm.mpptrade.pl"
        return $true
    }

    try {
        $response = Invoke-WebRequest -Uri "https://ppm.mpptrade.pl" `
            -UseBasicParsing -TimeoutSec 15 -ErrorAction Stop
        $code = $response.StatusCode
        if ($code -eq 200) {
            Write-Ok "Production site OK (HTTP $code)"
            return $true
        }
        else {
            Write-Warn "Production returned HTTP $code"
            return $false
        }
    }
    catch {
        Write-Err "Could not reach production: $($_.Exception.Message)"
        return $false
    }
}

# --- ROLLBACK ---
function Invoke-Rollback {
    Write-Step "Listing available backups..."

    $listResult = Invoke-HostidoSSH "ls -t ~/$HostidoBackupPath/backup_*.sql.gz 2>/dev/null"
    $backups = ($listResult | Out-String).Trim() -split "`n" | Where-Object { $_ -match '\.sql\.gz$' }

    if (-not $backups -or $backups.Count -eq 0) {
        Write-Err "No backups found on server"
        return $false
    }

    Write-Host ""
    Write-Host "Available backups:" -ForegroundColor Cyan
    for ($i = 0; $i -lt $backups.Count; $i++) {
        $name = [System.IO.Path]::GetFileName($backups[$i])
        $marker = if ($i -eq 0) { " (latest)" } else { "" }
        Write-Host "  [$i] $name$marker" -ForegroundColor Gray
    }
    Write-Host ""

    $selection = Read-Host "Select backup number (default: 0 = latest)"
    if ([string]::IsNullOrWhiteSpace($selection)) { $selection = "0" }
    $idx = [int]$selection

    if ($idx -lt 0 -or $idx -ge $backups.Count) {
        Write-Err "Invalid selection: $idx"
        return $false
    }

    $chosen = $backups[$idx].Trim()
    $chosenName = [System.IO.Path]::GetFileName($chosen)
    Write-Warn "Will restore: $chosenName"
    Write-Warn "This will OVERWRITE the current database!"

    if (-not $DryRun) {
        $confirm = Read-Host "Type YES to confirm"
        if ($confirm -ne "YES") {
            Write-Warn "Rollback cancelled"
            return $false
        }
    }

    if ($DryRun) {
        Write-Warn "DRY-RUN: Would restore $chosenName"
        return $true
    }

    Write-Step "Restoring database from $chosenName..."
    $restoreCmd = "cd ~/$HostidoBackupPath && gunzip -c $chosenName | mysql -u $HostidoDBUser -p'$HostidoDBPassword' $HostidoDBName && echo RESTORE_OK"
    $result = Invoke-HostidoSSH $restoreCmd
    Write-Dbg "Restore result: $result" -On:$Verbose

    if ($result -match "RESTORE_OK") {
        Write-Ok "Database restored from $chosenName"

        # Clear caches after restore
        Write-Step "Clearing caches after restore..."
        Invoke-HostidoSSH "cd $HostidoRemotePath && php artisan config:clear && php artisan cache:clear && php artisan view:clear" | Out-Null
        Write-Ok "Caches cleared"

        Test-ProductionHealth | Out-Null
        return $true
    }
    else {
        Write-Err "Restore may have failed. Output: $result"
        return $false
    }
}

# ============================================================================
# MAIN EXECUTION
# ============================================================================

Write-Host ""
Write-Host "============================================" -ForegroundColor Magenta
Write-Host " PPM-CC-Laravel Deployment" -ForegroundColor Magenta
Write-Host " Mode: $Mode $(if($DryRun){' [DRY-RUN]'})" -ForegroundColor Magenta
Write-Host " Time: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" -ForegroundColor Magenta
Write-Host "============================================" -ForegroundColor Magenta
Write-Host ""

# --- Pre-checks ---
if (-not (Test-Prerequisites)) {
    Write-Err "Prerequisites failed. Aborting."
    exit 1
}

# --- Dispatch by mode ---
$success = $true

switch ($Mode) {

    'full' {
        # 1. Backup
        $backup = Invoke-RemoteBackup
        if (-not $backup -and -not $SkipBackup) {
            Write-Err "Backup failed. Use -SkipBackup to proceed without backup."
            exit 1
        }

        # 2. Build
        if (-not (Invoke-AssetBuild)) {
            Write-Err "Asset build failed. Aborting."
            exit 1
        }

        # 3. Upload assets
        if (-not (Deploy-Assets)) {
            Write-Err "Asset upload failed."
            $success = $false
        }

        # 4. Upload PHP
        if (-not (Deploy-PhpFiles)) {
            Write-Warn "PHP upload had issues."
        }

        # 5. Post-deploy
        Invoke-PostDeploy | Out-Null

        # 6. Verify
        if (-not (Test-ProductionHealth)) {
            $success = $false
        }
    }

    'assets' {
        # Build + upload assets + cache clear
        if (-not (Invoke-AssetBuild)) {
            Write-Err "Asset build failed."
            exit 1
        }
        if (-not (Deploy-Assets)) {
            Write-Err "Asset upload failed."
            exit 1
        }
        Invoke-PostDeploy -CacheOnly | Out-Null
        Test-ProductionHealth | Out-Null
    }

    'php' {
        # Upload PHP files + composer + migrate + cache
        if (-not (Deploy-PhpFiles)) {
            Write-Warn "PHP upload had issues."
        }
        Invoke-PostDeploy | Out-Null
        Test-ProductionHealth | Out-Null
    }

    'migrate' {
        # Run migrations + cache clear only
        Write-Step "Running migrations on production..."
        if (-not $DryRun) {
            $migrateCmd = "cd $HostidoRemotePath && php artisan migrate --force --no-interaction 2>&1"
            $result = Invoke-HostidoSSH $migrateCmd
            Write-Host ($result | Out-String) -ForegroundColor Gray
        }
        else {
            Write-Warn "DRY-RUN: Would run php artisan migrate --force"
        }
        Invoke-PostDeploy -CacheOnly | Out-Null
    }

    'rollback' {
        if (-not (Invoke-Rollback)) {
            Write-Err "Rollback failed."
            $success = $false
        }
    }
}

# --- Final report ---
$sw.Stop()
$elapsed = $sw.Elapsed.ToString("mm\:ss")

Write-Host ""
Write-Host "============================================" -ForegroundColor Magenta
if ($success) {
    Write-Host " DEPLOYMENT COMPLETE ($elapsed)" -ForegroundColor Green
    Write-Host " https://ppm.mpptrade.pl" -ForegroundColor Green
}
else {
    Write-Host " DEPLOYMENT FINISHED WITH WARNINGS ($elapsed)" -ForegroundColor Yellow
    Write-Host " Check output above for details" -ForegroundColor Yellow
}
Write-Host "============================================" -ForegroundColor Magenta
Write-Host ""

exit $(if ($success) { 0 } else { 1 })
