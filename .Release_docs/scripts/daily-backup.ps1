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
    $result = @()
    foreach ($dir in $Dirs) {
        $result += Join-Path $ProjectRoot $dir
    }
    return $result
}

function Write-ProgressBar {
    param([double]$Ratio, [string]$Label = '', [int]$Width = 30)
    if ($Ratio -lt 0) { $Ratio = 0 }
    if ($Ratio -gt 1) { $Ratio = 1 }
    $pct = [math]::Floor($Ratio * 100)
    $filled = [math]::Floor($Ratio * $Width)
    $empty = $Width - $filled
    $bar = ([char]0x2588).ToString() * $filled
    $trail = ([char]0x2591).ToString() * $empty
    $line = "  [{0}{1}] {2,3}%  {3}" -f $bar, $trail, $pct, $Label
    Write-Host "`r$($line.PadRight(90))" -NoNewline
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

# --- Compress directly from source (no temp mirror needed with 7-Zip) ---
$7zExe = "C:\Program Files\7-Zip\7z.exe"
$use7z = Test-Path $7zExe

Write-Step "Compressing to ZIP$(if($use7z){' (7-Zip direct, multi-thread)'}else{' (.NET fallback)'})..."

if ($DryRun) {
    Write-Warn "DRY-RUN: Would create $zipFullPath"
    Write-Warn "Engine: $(if($use7z){'7-Zip deflate mx=5 mt (direct, no temp copy)'}else{'.NET Compress-Archive'})"
    Write-Warn "Excluded directories:"
    foreach ($dir in $ExcludeDirs) {
        Write-Host "  - $dir" -ForegroundColor Gray
    }
}
else {
    # Remove existing ZIP if present (overwrite for same day)
    if (Test-Path $zipFullPath) {
        Remove-Item -Path $zipFullPath -Force
    }

    $compressTimer = [System.Diagnostics.Stopwatch]::StartNew()

    if ($use7z) {
        # 7-Zip DIRECT from source - no temp mirror needed!
        # Benchmark: 194 MB in ~15s for 2.1 GB (multi-threaded deflate)
        $excludeArgs = @()
        foreach ($dir in $ExcludeDirs) {
            $excludeArgs += "-xr!$dir"
        }
        $excludeArgs += "-xr!nul"

        # Build argument string for .NET Process (proven to handle -xr! correctly)
        $argParts = @('a', '-tzip', '-mx=5', '-mmt=on', '-bso0', '-bsp0')
        $argParts += $excludeArgs
        $argParts += "`"$zipFullPath`""
        $argParts += "`"$ProjectRoot\*`""
        $argString = $argParts -join ' '

        # Start 7z via .NET Process (not Start-Process which mangles args)
        $psi = [System.Diagnostics.ProcessStartInfo]::new()
        $psi.FileName = $7zExe
        $psi.Arguments = $argString
        $psi.CreateNoWindow = $true
        $psi.UseShellExecute = $false
        $proc = [System.Diagnostics.Process]::Start($psi)

        # Monitor output file size for progress bar
        $estimatedSizeMB = 200
        $spinChars = @('|', '/', '-', '\')
        $spinIdx = 0

        while (-not $proc.HasExited) {
            $spinIdx++
            $spin = $spinChars[$spinIdx % 4]
            $currentMB = 0
            if (Test-Path $zipFullPath) {
                try { $currentMB = [math]::Round((Get-Item $zipFullPath).Length / 1MB, 1) } catch {}
            }
            $ratio = [math]::Min($currentMB / $estimatedSizeMB, 0.99)
            $elapsed = $compressTimer.Elapsed.TotalSeconds
            $speed = if ($elapsed -gt 0.5) { [math]::Round($currentMB / $elapsed, 1) } else { 0 }
            Write-ProgressBar -Ratio $ratio -Label "$spin  $currentMB MB  ($speed MB/s)"
            Start-Sleep -Milliseconds 500
        }
        $proc.WaitForExit()

        # Final state
        if (Test-Path $zipFullPath) {
            $finalMB = [math]::Round((Get-Item $zipFullPath).Length / 1MB, 1)
            Write-ProgressBar -Ratio 1.0 -Label "Done! $finalMB MB"
        }
        Write-Host ""
    }
    else {
        # Fallback: needs temp mirror (Compress-Archive has no exclude option)
        Write-Warn "7-Zip not found - using .NET with temp mirror (much slower). Install 7-Zip for 100x speedup."
        $tempDir = Join-Path $env:TEMP "PPM-Backup-$dateStamp"
        if (Test-Path $tempDir) {
            $emptyDir = Join-Path $env:TEMP "PPM-Backup-Empty"
            New-Item -ItemType Directory -Path $emptyDir -Force | Out-Null
            & robocopy $emptyDir $tempDir /MIR /NFL /NDL /NJH /NJS /NP 2>&1 | Out-Null
            Remove-Item -Path $tempDir -Recurse -Force -ErrorAction SilentlyContinue
            Remove-Item -Path $emptyDir -Recurse -Force -ErrorAction SilentlyContinue
        }
        $xdParams = Get-RobocopyExcludes -Dirs $ExcludeDirs
        $robocopyArgs = @($ProjectRoot, $tempDir, '/MIR', '/NFL', '/NDL', '/NJH', '/NJS', '/NP', '/XF', 'nul', '/XD') + $xdParams
        & robocopy @robocopyArgs | Out-Null
        Compress-Archive -Path "$tempDir\*" -DestinationPath $zipFullPath -CompressionLevel Fastest
        # Cleanup temp
        $emptyDir = Join-Path $env:TEMP "PPM-Backup-Empty"
        New-Item -ItemType Directory -Path $emptyDir -Force | Out-Null
        & robocopy $emptyDir $tempDir /MIR /NFL /NDL /NJH /NJS /NP 2>&1 | Out-Null
        Remove-Item -Path $tempDir -Recurse -Force -ErrorAction SilentlyContinue
        Remove-Item -Path $emptyDir -Recurse -Force -ErrorAction SilentlyContinue
    }

    $compressTimer.Stop()
    $compressElapsed = $compressTimer.Elapsed.ToString("mm\:ss")
    Write-Ok "Compression complete in $compressElapsed"

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
