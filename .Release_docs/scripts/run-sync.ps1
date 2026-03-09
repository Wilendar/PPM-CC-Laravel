# ============================================================================
# PPM-CC-Laravel Manual Sync Runner
# Runs git commit+push AND daily backup in sequence (outside scheduler)
# Usage:  .\run-sync.ps1              (commit + push + backup)
#         .\run-sync.ps1 -GitOnly     (only commit + push)
#         .\run-sync.ps1 -BackupOnly  (only ZIP backup)
#         .\run-sync.ps1 -DryRun      (preview without changes)
# ============================================================================

param(
    [switch]$GitOnly,
    [switch]$BackupOnly,
    [string]$Branch = 'develop',
    [switch]$NoPush,
    [string]$BackupDir = 'D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel',
    [int]$KeepCount = 7,
    [switch]$DryRun
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

# --- Load shared config ---
. "$PSScriptRoot\hostido-config.ps1"

$sw = [System.Diagnostics.Stopwatch]::StartNew()

# --- Header ---
Write-Host ""
Write-Host "============================================" -ForegroundColor Magenta
Write-Host " PPM Manual Sync$(if($DryRun){' [DRY-RUN]'})" -ForegroundColor Magenta
$modeLabel = if ($GitOnly) { "Git Only" } elseif ($BackupOnly) { "Backup Only" } else { "Full (Git + Backup)" }
Write-Host " Mode: $modeLabel" -ForegroundColor Magenta
Write-Host " Time: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" -ForegroundColor Magenta
Write-Host "============================================" -ForegroundColor Magenta
Write-Host ""

$gitOk = $true
$backupOk = $true

# --- Step 1: Git Commit + Push ---
if (-not $BackupOnly) {
    Write-Host ">>> STEP 1/2: Git Commit + Push <<<" -ForegroundColor Cyan
    Write-Host "--------------------------------------------" -ForegroundColor Gray

    $gitArgs = @()
    $gitArgs += "-ExecutionPolicy", "Bypass"
    $gitArgs += "-NonInteractive"
    $gitArgs += "-File", (Join-Path $PSScriptRoot "auto-git-commit.ps1")
    $gitArgs += "-Branch", $Branch
    if ($DryRun) { $gitArgs += "-DryRun" }
    if ($NoPush) { $gitArgs += "-NoPush" }

    & pwsh @gitArgs
    $gitOk = ($LASTEXITCODE -eq 0)

    if ($gitOk) {
        Write-Host ""
        Write-Ok "Git sync completed"
    }
    else {
        Write-Err "Git sync failed (exit code: $LASTEXITCODE)"
    }
    Write-Host ""
}

# --- Step 2: Daily Backup ---
if (-not $GitOnly) {
    $stepNum = if ($BackupOnly) { "1/1" } else { "2/2" }
    Write-Host ">>> STEP $stepNum`: ZIP Backup <<<" -ForegroundColor Cyan
    Write-Host "--------------------------------------------" -ForegroundColor Gray

    $backupArgs = @()
    $backupArgs += "-ExecutionPolicy", "Bypass"
    $backupArgs += "-NonInteractive"
    $backupArgs += "-File", (Join-Path $PSScriptRoot "daily-backup.ps1")
    $backupArgs += "-BackupDir", $BackupDir
    $backupArgs += "-KeepCount", $KeepCount
    if ($DryRun) { $backupArgs += "-DryRun" }

    & pwsh @backupArgs
    $backupOk = ($LASTEXITCODE -eq 0)

    if ($backupOk) {
        Write-Host ""
        Write-Ok "Backup completed"
    }
    else {
        Write-Err "Backup failed (exit code: $LASTEXITCODE)"
    }
    Write-Host ""
}

# --- Final Summary ---
$sw.Stop()
$elapsed = $sw.Elapsed.ToString("mm\:ss")
$allOk = $gitOk -and $backupOk

Write-Host "============================================" -ForegroundColor Magenta
Write-Host " MANUAL SYNC SUMMARY ($elapsed)" -ForegroundColor $(if($allOk){'Green'}else{'Yellow'})
if (-not $BackupOnly) {
    $gitStatus = if ($gitOk) { "OK" } else { "FAILED" }
    $gitColor = if ($gitOk) { "Green" } else { "Red" }
    Write-Host "  Git:    $gitStatus" -ForegroundColor $gitColor
}
if (-not $GitOnly) {
    $backupStatus = if ($backupOk) { "OK" } else { "FAILED" }
    $backupColor = if ($backupOk) { "Green" } else { "Red" }
    Write-Host "  Backup: $backupStatus" -ForegroundColor $backupColor
}
Write-Host "============================================" -ForegroundColor Magenta
Write-Host ""

exit $(if ($allOk) { 0 } else { 1 })
