# ============================================================================
# PPM-CC-Laravel Windows Task Scheduler Setup
# Usage:  .\setup-scheduler.ps1           (register tasks, requires Admin)
#         .\setup-scheduler.ps1 -Unregister
#         .\setup-scheduler.ps1 -TestRun
# ============================================================================

param(
    [switch]$Unregister,
    [switch]$TestRun
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

# --- Load shared config (for Write-Step/Write-Ok/Write-Warn/Write-Err helpers) ---
. "$PSScriptRoot\hostido-config.ps1"

# --- Resolve paths ---
$ScriptsDir = $PSScriptRoot
$ProjectRoot = (Resolve-Path "$PSScriptRoot\..\..").Path

# --- Task definitions ---
$Tasks = @(
    @{
        Name        = 'PPM-AutoGitCommit'
        Description = 'PPM-CC-Laravel: Auto git commit and push (daily at 15:45)'
        Script      = Join-Path $ScriptsDir 'auto-git-commit.ps1'
        Time        = '15:45'
    },
    @{
        Name        = 'PPM-DailyBackup'
        Description = 'PPM-CC-Laravel: Daily ZIP backup with rotation (daily at 15:50)'
        Script      = Join-Path $ScriptsDir 'daily-backup.ps1'
        Time        = '15:50'
    }
)

# ============================================================================
# FUNCTIONS
# ============================================================================

function Test-Administrator {
    $identity = [Security.Principal.WindowsIdentity]::GetCurrent()
    $principal = [Security.Principal.WindowsPrincipal]::new($identity)
    return $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
}

function Get-PwshPath {
    # Find pwsh.exe (PowerShell 7+)
    $pwsh = Get-Command pwsh -ErrorAction SilentlyContinue
    if ($pwsh) { return $pwsh.Source }

    # Fallback paths
    $fallbacks = @(
        "$env:ProgramFiles\PowerShell\7\pwsh.exe",
        "$env:ProgramFiles(x86)\PowerShell\7\pwsh.exe"
    )
    foreach ($p in $fallbacks) {
        if (Test-Path $p) { return $p }
    }

    # Last resort: use powershell.exe (5.1)
    Write-Warn "pwsh.exe not found, falling back to powershell.exe"
    return "powershell.exe"
}

function Register-PpmTask {
    param([hashtable]$TaskDef)

    $taskName = $TaskDef.Name
    $scriptPath = $TaskDef.Script
    $triggerTime = $TaskDef.Time
    $description = $TaskDef.Description

    Write-Step "Registering task: $taskName"

    # Validate script exists
    if (-not (Test-Path $scriptPath)) {
        Write-Err "Script not found: $scriptPath"
        return $false
    }

    $pwshPath = Get-PwshPath

    # Build action
    $actionArgs = "-ExecutionPolicy Bypass -NonInteractive -WindowStyle Hidden -File `"$scriptPath`""
    $action = New-ScheduledTaskAction `
        -Execute $pwshPath `
        -Argument $actionArgs `
        -WorkingDirectory $ProjectRoot

    # Build trigger (daily)
    $trigger = New-ScheduledTaskTrigger -Daily -At $triggerTime

    # Build settings
    $settings = New-ScheduledTaskSettingsSet `
        -AllowStartIfOnBatteries `
        -DontStopIfGoingOnBatteries `
        -StartWhenAvailable `
        -ExecutionTimeLimit (New-TimeSpan -Minutes 30) `
        -RestartCount 1 `
        -RestartInterval (New-TimeSpan -Minutes 5)

    # Register (or update if exists)
    try {
        Register-ScheduledTask `
            -TaskName $taskName `
            -Description $description `
            -Action $action `
            -Trigger $trigger `
            -Settings $settings `
            -RunLevel Highest `
            -Force | Out-Null

        Write-Ok "Task registered: $taskName (daily at $triggerTime)"
        return $true
    }
    catch {
        Write-Err "Failed to register $taskName : $($_.Exception.Message)"
        return $false
    }
}

function Unregister-PpmTask {
    param([string]$TaskName)

    Write-Step "Removing task: $TaskName"

    $existing = Get-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue
    if ($existing) {
        Unregister-ScheduledTask -TaskName $TaskName -Confirm:$false
        Write-Ok "Task removed: $TaskName"
        return $true
    }
    else {
        Write-Warn "Task not found: $TaskName"
        return $false
    }
}

function Invoke-PpmTaskTest {
    param([string]$TaskName)

    Write-Step "Running task immediately: $TaskName"

    $existing = Get-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue
    if (-not $existing) {
        Write-Err "Task not found: $TaskName (register first)"
        return $false
    }

    Start-ScheduledTask -TaskName $TaskName
    Write-Ok "Task started: $TaskName"

    # Wait briefly and check result
    Start-Sleep -Seconds 3
    $info = Get-ScheduledTaskInfo -TaskName $TaskName -ErrorAction SilentlyContinue
    if ($info) {
        $lastResult = $info.LastTaskResult
        $lastRun = $info.LastRunTime
        Write-Host "  Last run: $lastRun" -ForegroundColor Gray
        Write-Host "  Result code: $lastResult (0 = success)" -ForegroundColor Gray
    }

    return $true
}

function Show-TaskSummary {
    Write-Host ""
    Write-Host "Registered PPM tasks:" -ForegroundColor Cyan
    Write-Host "---------------------------------------------" -ForegroundColor Gray

    foreach ($taskDef in $Tasks) {
        $task = Get-ScheduledTask -TaskName $taskDef.Name -ErrorAction SilentlyContinue
        if ($task) {
            $info = Get-ScheduledTaskInfo -TaskName $taskDef.Name -ErrorAction SilentlyContinue
            $state = $task.State
            $nextRun = if ($info.NextRunTime) { $info.NextRunTime.ToString("yyyy-MM-dd HH:mm") } else { "N/A" }
            Write-Host "  $($taskDef.Name)" -ForegroundColor Green -NoNewline
            Write-Host " | State: $state | Next: $nextRun" -ForegroundColor Gray
        }
        else {
            Write-Host "  $($taskDef.Name)" -ForegroundColor Red -NoNewline
            Write-Host " | NOT REGISTERED" -ForegroundColor Gray
        }
    }

    Write-Host "---------------------------------------------" -ForegroundColor Gray
}

# ============================================================================
# MAIN EXECUTION
# ============================================================================

Write-Host ""
Write-Host "============================================" -ForegroundColor Magenta
Write-Host " PPM Task Scheduler Setup" -ForegroundColor Magenta
Write-Host " Time: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" -ForegroundColor Magenta
Write-Host "============================================" -ForegroundColor Magenta
Write-Host ""

# --- Check elevation ---
if (-not (Test-Administrator)) {
    Write-Err "This script requires Administrator privileges!"
    Write-Err "Right-click PowerShell -> 'Run as Administrator'"
    Write-Err "Or: Start-Process pwsh -Verb RunAs -ArgumentList '-File `"$($MyInvocation.MyCommand.Path)`"'"
    exit 1
}
Write-Ok "Running as Administrator"

# --- Dispatch ---
if ($Unregister) {
    # Remove all PPM tasks
    foreach ($taskDef in $Tasks) {
        Unregister-PpmTask -TaskName $taskDef.Name
    }
    Write-Host ""
    Write-Ok "All PPM tasks unregistered"
}
elseif ($TestRun) {
    # Run all PPM tasks immediately
    foreach ($taskDef in $Tasks) {
        Invoke-PpmTaskTest -TaskName $taskDef.Name
    }
}
else {
    # Register all PPM tasks
    $allOk = $true
    foreach ($taskDef in $Tasks) {
        if (-not (Register-PpmTask -TaskDef $taskDef)) {
            $allOk = $false
        }
    }

    Write-Host ""
    if ($allOk) {
        Write-Ok "All tasks registered successfully!"
    }
    else {
        Write-Warn "Some tasks failed to register. Check errors above."
    }
}

# --- Show summary ---
Show-TaskSummary

Write-Host ""
Write-Host "============================================" -ForegroundColor Magenta
Write-Host " SCHEDULER SETUP COMPLETE" -ForegroundColor Green
Write-Host "============================================" -ForegroundColor Magenta
Write-Host ""

exit 0
