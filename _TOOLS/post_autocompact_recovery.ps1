# post_autocompact_recovery.ps1
# MESSAGE_START HOOK - Post-AutoCompact TODO Recovery System
# Wykrywa przerwane sesje Claude i prezentuje opcje kontynuacji
# Encoding: UTF-8 (NO BOM for PowerShell compatibility)

param(
    [string]$ProjectRoot = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"
)

$ErrorActionPreference = 'SilentlyContinue'

# Paths
$snapshotPath = Join-Path $ProjectRoot "_TEMP\claude_session_state.json"
$claudeMdPath = Join-Path $ProjectRoot "CLAUDE.md"

# ANSI Colors (PowerShell 7)
$cyan = "`e[36m"
$yellow = "`e[33m"
$green = "`e[32m"
$red = "`e[31m"
$magenta = "`e[35m"
$gray = "`e[90m"
$white = "`e[97m"
$reset = "`e[0m"

# Function: Display banner
function Show-Banner {
    Write-Host ""
    Write-Host "${cyan}=========================================${reset}"
    Write-Host "${cyan}  CLAUDE POST-AUTOCOMPACT RECOVERY${reset}"
    Write-Host "${cyan}=========================================${reset}"
}

# Function: Display snapshot summary
function Show-SnapshotSummary {
    param($Snapshot)

    $age = (Get-Date) - $Snapshot.timestamp  # timestamp already DateTime after ConvertFrom-Json
    $ageMinutes = [math]::Round($age.TotalMinutes)

    Write-Host ""
    Write-Host "${yellow}Timestamp:${reset} $($Snapshot.timestamp) ${gray}(${ageMinutes}m ago)${reset}"

    if ($Snapshot.context_summary) {
        Write-Host "${yellow}Context:${reset} $($Snapshot.context_summary)"
    }

    # Count tasks by status
    $completed = ($Snapshot.todos | Where-Object {$_.status -eq 'completed'}).Count
    $inProgress = ($Snapshot.todos | Where-Object {$_.status -eq 'in_progress'}).Count
    $pending = ($Snapshot.todos | Where-Object {$_.status -eq 'pending'}).Count
    $total = $Snapshot.todos.Count

    Write-Host ""
    Write-Host "${white}TODO Status (${total} total):${reset}"
    Write-Host "  ${green}Completed:${reset} $completed"
    Write-Host "  ${yellow}In Progress:${reset} $inProgress"
    Write-Host "  ${red}Pending:${reset} $pending"

    # Show interrupted task
    if ($inProgress -gt 0) {
        $interruptedTask = $Snapshot.todos | Where-Object {$_.status -eq 'in_progress'} | Select-Object -First 1
        Write-Host ""
        Write-Host "${yellow}Przerwane zadanie:${reset}"
        Write-Host "  ${yellow}  $($interruptedTask.content)${reset}"
    }

    # Show agent info
    if ($Snapshot.agent_in_progress) {
        Write-Host ""
        Write-Host "${magenta}Agent:${reset} $($Snapshot.agent_in_progress) ${gray}(was in progress)${reset}"
    }

    # Show last action
    if ($Snapshot.last_file_read) {
        Write-Host "${gray}Last file:${reset} $($Snapshot.last_file_read)"
    }
}

# Function: Display continuation options
function Show-ContinuationOptions {
    Write-Host ""
    Write-Host "${cyan}PROPOZYCJE KONTYNUACJI:${reset}"
    Write-Host ""
    Write-Host "${white}A)${reset} KONTYNUUJ od przerwania"
    Write-Host "   ${gray}Wczytam pelne TODO i wznowie prace${reset}"
    Write-Host ""
    Write-Host "${white}B)${reset} AKTUALIZUJ PLAN"
    Write-Host "   ${gray}Przeanalizuje postepy i zaproponuje zmiany${reset}"
    Write-Host ""
    Write-Host "${white}C)${reset} NOWE ZADANIE"
    Write-Host "   ${gray}Zacznij od nowa (poprzednie TODO archiwizowane)${reset}"
    Write-Host ""
    Write-Host "${white}D)${reset} PRZEGLAD KONTEKSTU"
    Write-Host "   ${gray}Czytaj Plan_Projektu + Reports (WARNING: high tokens!)${reset}"
    Write-Host ""
    Write-Host "${cyan}=========================================${reset}"
    Write-Host "${gray}TIP: Skopiuj snapshot JSON do Claude prompt dla opcji A/B${reset}"
    Write-Host ""
}

# Function: Show minimal reminder (no snapshot case)
function Show-MinimalReminder {
    Write-Host ""
    Write-Host "${gray}Project Context:${reset} CLAUDE.md + 13 agents available"
    Write-Host "${gray}Resources:${reset} Plan_Projektu/ | _AGENT_REPORTS/ | Context7"
    Write-Host ""
}

# MAIN LOGIC
try {
    # Read stdin (Claude passes session info as JSON)
    # We don't need to parse it, but we MUST consume it
    $null = [Console]::In.ReadToEnd()

    # Check if snapshot exists
    if (Test-Path $snapshotPath) {
        $snapshot = Get-Content $snapshotPath -Raw -Encoding UTF8 | ConvertFrom-Json
        $age = (Get-Date) - $snapshot.timestamp  # timestamp already DateTime object after ConvertFrom-Json

        # Only show if < 24h old (1440 minutes)
        if ($age.TotalHours -lt 24) {
            Show-Banner
            Write-Host "${yellow}Wykryto przerwana sesje z poprzedniego kontekstu!${reset}"
            Show-SnapshotSummary -Snapshot $snapshot
            Show-ContinuationOptions

            # Export snapshot to clipboard-friendly format (optional)
            # $snapshot | ConvertTo-Json -Depth 10 | Set-Clipboard
        }
        else {
            # Snapshot too old - show minimal reminder
            Show-MinimalReminder
        }
    }
    else {
        # No snapshot - show minimal reminder
        Show-MinimalReminder
    }
}
catch {
    # Silent failure - just show minimal reminder
    Show-MinimalReminder
}

# CRITICAL: Hook MUST exit with code 0 (success) or 2 (blocking)
exit 0
