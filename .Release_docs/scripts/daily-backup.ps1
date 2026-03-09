# ============================================================================
# PPM-CC-Laravel Daily ZIP Backup with Rotation
# Usage:  .\daily-backup.ps1
#         .\daily-backup.ps1 -BackupDir "D:\Backups\PPM"
#         .\daily-backup.ps1 -KeepCount 14
#         .\daily-backup.ps1 -DryRun
# ============================================================================

param(
    [string]$BackupDir = 'D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel',
    [int]$KeepCount = 7,
    [switch]$DryRun
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

# --- Load shared config (for Write-Step/Write-Ok/Write-Warn/Write-Err helpers) ---
. "$PSScriptRoot\hostido-config.ps1"

# --- Resolve project root (two levels up from .Release_docs/scripts/) ---
$ProjectRoot = (Resolve-Path "$PSScriptRoot\..\..").Path

# --- Timing ---
$sw = [System.Diagnostics.Stopwatch]::StartNew()

# --- Exclusion list (directories to skip in backup) ---
$ExcludeDirs = @(
    'node_modules',
    'vendor',
    '.git',
    'storage\logs',
    'storage\framework\cache',
    'storage\framework\sessions',
    'storage\framework\views',
    '_TEMP',
    '_BACKUP',
    '_ARCHIVE',
    '_DIAGNOSTICS',
    '.claude',
    '.subtask',
    '.coordination',
    '.parallel-work',
    '.playwright-mcp'
)

# ============================================================================
# FUNCTIONS
# ============================================================================

function Get-RobocopyExcludes {
    param([string[]]$Dirs)
    # Build /XD parameters for robocopy
    $result = @()
    foreach ($dir in $Dirs) {
        $result += Join-Path $ProjectRoot $dir
    }
    return $result
}

# ============================================================================
# MAIN EXECUTION
# ============================================================================

Write-Host ""
Write-Host "============================================" -ForegroundColor Magenta
Write-Host " PPM Daily Backup$(if($DryRun){' [DRY-RUN]'})" -ForegroundColor Magenta
Write-Host " Source: $ProjectRoot" -ForegroundColor Magenta
Write-Host " Target: $BackupDir" -ForegroundColor Magenta
Write-Host " Keep: $KeepCount versions" -ForegroundColor Magenta
Write-Host " Time: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" -ForegroundColor Magenta
Write-Host "============================================" -ForegroundColor Magenta
Write-Host ""

# --- Validate source ---
Write-Step "Validating source directory..."
if (-not (Test-Path (Join-Path $ProjectRoot "artisan"))) {
    Write-Err "Not a Laravel project root: $ProjectRoot"
    exit 1
}
Write-Ok "Source validated: $ProjectRoot"

# --- Validate/create backup directory ---
Write-Step "Validating backup directory..."
if (-not (Test-Path $BackupDir)) {
    if ($DryRun) {
        Write-Warn "DRY-RUN: Would create $BackupDir"
    }
    else {
        New-Item -ItemType Directory -Path $BackupDir -Force | Out-Null
        Write-Ok "Created backup directory: $BackupDir"
    }
}
else {
    Write-Ok "Backup directory exists: $BackupDir"
}

# --- Build filename ---
$dateStamp = Get-Date -Format "yyyy-MM-dd"
$zipFilename = "PPM-CC-Laravel_$dateStamp.zip"
$zipFullPath = Join-Path $BackupDir $zipFilename

if ((Test-Path $zipFullPath) -and -not $DryRun) {
    Write-Warn "Backup already exists for today, will overwrite: $zipFilename"
}

# --- Create temp mirror with robocopy ---
$tempDir = Join-Path $env:TEMP "PPM-Backup-$dateStamp"

Write-Step "Creating temporary mirror (excluding large/generated dirs)..."
if ($DryRun) {
    Write-Warn "DRY-RUN: Would robocopy to $tempDir"
    Write-Warn "Excluded directories:"
    foreach ($dir in $ExcludeDirs) {
        Write-Host "  - $dir" -ForegroundColor Gray
    }
}
else {
    # Clean temp if exists from previous failed run
    # Use robocopy /MIR trick to handle Windows reserved filenames (nul, con, etc.)
    if (Test-Path $tempDir) {
        $emptyDir = Join-Path $env:TEMP "PPM-Backup-Empty"
        New-Item -ItemType Directory -Path $emptyDir -Force | Out-Null
        & robocopy $emptyDir $tempDir /MIR /NFL /NDL /NJH /NJS /NP 2>&1 | Out-Null
        Remove-Item -Path $tempDir -Recurse -Force -ErrorAction SilentlyContinue
        Remove-Item -Path $emptyDir -Recurse -Force -ErrorAction SilentlyContinue
    }

    $xdParams = Get-RobocopyExcludes -Dirs $ExcludeDirs

    # Robocopy: /MIR mirror, /XD exclude dirs, /XF exclude files
    # Show progress with /NJH /NJS (suppress header) but keep /NP (no per-file progress)
    $robocopyArgs = @(
        $ProjectRoot,
        $tempDir,
        '/MIR',
        '/NFL', '/NDL', '/NJH', '/NJS', '/NP',
        '/XF', 'nul',
        '/XD'
    ) + $xdParams

    Write-Host "  Copying files..." -ForegroundColor Gray -NoNewline
    & robocopy @robocopyArgs | Out-Null

    # Robocopy exit codes: 0-7 = success, 8+ = error
    if ($LASTEXITCODE -ge 8) {
        Write-Host "" # newline
        Write-Err "Robocopy failed with exit code $LASTEXITCODE"
        exit 1
    }

    # Verify temp dir has content
    Write-Host " Counting..." -ForegroundColor Gray -NoNewline
    $tempFileCount = (Get-ChildItem -Path $tempDir -Recurse -File).Count
    Write-Host " Done!" -ForegroundColor Green
    Write-Ok "Mirror created: $tempFileCount files in temp directory"
}

# --- Compress to ZIP ---
Write-Step "Compressing to ZIP..."
if ($DryRun) {
    Write-Warn "DRY-RUN: Would create $zipFullPath"
}
else {
    # Remove existing ZIP if present (overwrite for same day)
    if (Test-Path $zipFullPath) {
        Remove-Item -Path $zipFullPath -Force
    }

    Write-Host "  Compressing $tempFileCount files..." -ForegroundColor Gray -NoNewline

    # Use .NET ZipFile for progress (Compress-Archive has no progress callback)
    Add-Type -AssemblyName System.IO.Compression.FileSystem
    $compressionLevel = [System.IO.Compression.CompressionLevel]::Optimal

    # Get all files for progress tracking
    $allFiles = Get-ChildItem -Path $tempDir -Recurse -File
    $totalFiles = $allFiles.Count
    $processedFiles = 0
    $lastPercent = -1

    # Create ZIP manually with progress
    $zipStream = [System.IO.File]::Create($zipFullPath)
    $archive = [System.IO.Compression.ZipArchive]::new($zipStream, [System.IO.Compression.ZipArchiveMode]::Create)

    foreach ($file in $allFiles) {
        $relativePath = $file.FullName.Substring($tempDir.Length + 1)
        $entry = $archive.CreateEntry($relativePath, $compressionLevel)
        $entryStream = $entry.Open()
        $fileStream = [System.IO.File]::OpenRead($file.FullName)
        $fileStream.CopyTo($entryStream)
        $fileStream.Close()
        $entryStream.Close()

        $processedFiles++
        $percent = [math]::Floor(($processedFiles / $totalFiles) * 100)
        if ($percent -ne $lastPercent -and ($percent % 10 -eq 0)) {
            Write-Host " ${percent}%" -ForegroundColor Cyan -NoNewline
            $lastPercent = $percent
        }
    }

    $archive.Dispose()
    $zipStream.Close()
    Write-Host " Done!" -ForegroundColor Green

    # Verify ZIP
    if (-not (Test-Path $zipFullPath)) {
        Write-Err "ZIP file was not created: $zipFullPath"
        exit 1
    }

    $zipSize = (Get-Item $zipFullPath).Length
    $zipSizeMB = [math]::Round($zipSize / 1MB, 1)

    if ($zipSize -lt 1MB) {
        Write-Warn "ZIP is suspiciously small: $zipSizeMB MB"
    }
    else {
        Write-Ok "ZIP created: $zipFilename ($zipSizeMB MB)"
    }
}

# --- Cleanup temp mirror ---
if (-not $DryRun -and (Test-Path $tempDir)) {
    Write-Step "Cleaning up temporary files..."
    # Use robocopy /MIR trick for safe cleanup (handles reserved filenames)
    $emptyDir = Join-Path $env:TEMP "PPM-Backup-Empty"
    New-Item -ItemType Directory -Path $emptyDir -Force | Out-Null
    & robocopy $emptyDir $tempDir /MIR /NFL /NDL /NJH /NJS /NP 2>&1 | Out-Null
    Remove-Item -Path $tempDir -Recurse -Force -ErrorAction SilentlyContinue
    Remove-Item -Path $emptyDir -Recurse -Force -ErrorAction SilentlyContinue
    Write-Ok "Temp directory removed"
}

# --- Rotate old backups ---
Write-Step "Rotating old backups (keeping $KeepCount)..."
if ($DryRun) {
    $existing = @(Get-ChildItem -Path $BackupDir -Filter "PPM-CC-Laravel_*.zip" -ErrorAction SilentlyContinue |
        Sort-Object Name -Descending)
    Write-Warn "DRY-RUN: Found $($existing.Count) existing backup(s)"
    if ($existing.Count -gt $KeepCount) {
        $toRemove = $existing | Select-Object -Skip $KeepCount
        foreach ($old in $toRemove) {
            Write-Warn "  Would delete: $($old.Name)"
        }
    }
}
else {
    $existing = @(Get-ChildItem -Path $BackupDir -Filter "PPM-CC-Laravel_*.zip" -ErrorAction SilentlyContinue |
        Sort-Object Name -Descending)

    if ($existing.Count -gt $KeepCount) {
        $toRemove = $existing | Select-Object -Skip $KeepCount
        foreach ($old in $toRemove) {
            Remove-Item -Path $old.FullName -Force
            Write-Ok "Removed old backup: $($old.Name)"
        }
    }
    else {
        Write-Ok "No rotation needed ($($existing.Count) of $KeepCount max)"
    }
}

# --- Final report ---
$sw.Stop()
$elapsed = $sw.Elapsed.ToString("mm\:ss")

Write-Host ""
Write-Host "============================================" -ForegroundColor Magenta
Write-Host " BACKUP COMPLETE ($elapsed)" -ForegroundColor Green
if (-not $DryRun) {
    Write-Host " File: $zipFullPath" -ForegroundColor Green
    Write-Host " Size: $zipSizeMB MB" -ForegroundColor Green
}
Write-Host "============================================" -ForegroundColor Magenta
Write-Host ""

exit 0
