# todo_snapshot_cleanup.ps1
# Archive Cleanup - Removes snapshot archives older than retention period
# Recommended: Run weekly via scheduled task
# Encoding: UTF-8 (NO BOM)

param(
    [string]$ProjectRoot = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel",
    [int]$RetentionDays = 7,  # Keep snapshots for 7 days
    [switch]$DryRun = $false  # Preview what would be deleted
)

$ErrorActionPreference = 'Stop'

# Paths
$archiveDir = Join-Path $ProjectRoot "_TEMP\claude_session_archive"

# Colors
$cyan = "`e[36m"
$yellow = "`e[33m"
$green = "`e[32m"
$red = "`e[31m"
$gray = "`e[90m"
$reset = "`e[0m"

# Function: Format file size
function Format-FileSize {
    param([long]$Bytes)
    if ($Bytes -gt 1MB) { return "{0:N2} MB" -f ($Bytes / 1MB) }
    if ($Bytes -gt 1KB) { return "{0:N2} KB" -f ($Bytes / 1KB) }
    return "$Bytes B"
}

# MAIN EXECUTION
try {
    Write-Host ""
    Write-Host "${cyan}=========================================${reset}"
    Write-Host "${cyan}  TODO SNAPSHOT CLEANUP${reset}"
    Write-Host "${cyan}=========================================${reset}"
    Write-Host ""

    if (-not (Test-Path $archiveDir)) {
        Write-Host "${yellow}No archive directory found. Nothing to clean.${reset}"
        Write-Host ""
        exit 0
    }

    # Get all snapshot archives
    $snapshots = Get-ChildItem -Path $archiveDir -Filter "snapshot_*.json" -File

    if ($snapshots.Count -eq 0) {
        Write-Host "${yellow}No snapshots found in archive.${reset}"
        Write-Host ""
        exit 0
    }

    Write-Host "Found $($snapshots.Count) snapshot(s) in archive"
    Write-Host "Retention period: ${yellow}${RetentionDays} days${reset}"
    Write-Host ""

    # Calculate cutoff date
    $cutoffDate = (Get-Date).AddDays(-$RetentionDays)

    # Filter old snapshots
    $oldSnapshots = $snapshots | Where-Object { $_.LastWriteTime -lt $cutoffDate }

    if ($oldSnapshots.Count -eq 0) {
        Write-Host "${green}No old snapshots to remove.${reset}"
        Write-Host ""
        exit 0
    }

    # Calculate total size
    $totalSize = ($oldSnapshots | Measure-Object -Property Length -Sum).Sum

    Write-Host "${yellow}Found $($oldSnapshots.Count) snapshot(s) older than $RetentionDays days:${reset}"
    Write-Host ""

    foreach ($snapshot in $oldSnapshots) {
        $age = (Get-Date) - $snapshot.LastWriteTime
        $ageStr = "{0} days ago" -f [math]::Round($age.TotalDays)
        $sizeStr = Format-FileSize -Bytes $snapshot.Length

        if ($DryRun) {
            Write-Host "  ${gray}[DRY RUN]${reset} Would delete: $($snapshot.Name) ${gray}($ageStr, $sizeStr)${reset}"
        }
        else {
            Write-Host "  ${red}Deleting:${reset} $($snapshot.Name) ${gray}($ageStr, $sizeStr)${reset}"
            Remove-Item -Path $snapshot.FullName -Force
        }
    }

    Write-Host ""
    if ($DryRun) {
        Write-Host "${yellow}DRY RUN: Would free $(Format-FileSize -Bytes $totalSize)${reset}"
        Write-Host "${gray}Run without -DryRun to actually delete files${reset}"
    }
    else {
        Write-Host "${green}Cleanup complete! Freed $(Format-FileSize -Bytes $totalSize)${reset}"
    }
    Write-Host ""
}
catch {
    Write-Host "${red}ERROR: Cleanup failed - $_${reset}"
    exit 1
}
