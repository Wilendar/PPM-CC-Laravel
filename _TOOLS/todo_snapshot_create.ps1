# todo_snapshot_create.ps1
# Manual TODO Snapshot Creator - Saves current session state before autocompact
# Can be invoked manually or automatically on TodoWrite
# Encoding: UTF-8 (NO BOM)

param(
    [string]$ProjectRoot = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel",
    [string]$ContextSummary = "Manual snapshot",
    [string]$AgentInProgress = $null,
    [string]$LastFileRead = $null,
    [string]$LastCommand = $null
)

$ErrorActionPreference = 'Stop'

# Paths
$tempDir = Join-Path $ProjectRoot "_TEMP"
$snapshotPath = Join-Path $tempDir "claude_session_state.json"
$archiveDir = Join-Path $tempDir "claude_session_archive"

# Ensure directories exist
if (-not (Test-Path $tempDir)) {
    New-Item -ItemType Directory -Path $tempDir -Force | Out-Null
}
if (-not (Test-Path $archiveDir)) {
    New-Item -ItemType Directory -Path $archiveDir -Force | Out-Null
}

# Function: Extract TODO from conversation (placeholder - requires manual input)
function Get-CurrentTodos {
    # In real implementation, this would parse TodoWrite output
    # For now, return sample structure
    @(
        @{
            content = "Read CLAUDE.md and understand project rules"
            activeForm = "Reading CLAUDE.md"
            status = "completed"
        },
        @{
            content = "Implement TODO snapshot system"
            activeForm = "Implementing TODO snapshot system"
            status = "in_progress"
        },
        @{
            content = "Test autocompact recovery"
            activeForm = "Testing autocompact recovery"
            status = "pending"
        }
    )
}

# Function: Create snapshot
function New-Snapshot {
    param(
        [array]$Todos,
        [string]$Summary,
        [string]$Agent,
        [string]$LastFile,
        [string]$LastCmd
    )

    $timestamp = Get-Date -Format "yyyy-MM-ddTHH:mm:ssZ"
    $sessionId = (Get-Date -Format "yyyyMMdd-HHmmss")

    $snapshot = @{
        timestamp = $timestamp
        session_id = $sessionId
        todos = $Todos
        context_summary = $Summary
        agent_in_progress = $Agent
        last_file_read = $LastFile
        last_command = $LastCmd
        project_root = $ProjectRoot
    }

    return $snapshot
}

# Function: Archive old snapshot
function Save-ArchiveSnapshot {
    param($OldSnapshotPath)

    if (Test-Path $OldSnapshotPath) {
        $timestamp = Get-Date -Format "yyyy-MM-dd_HH-mm"
        $archivePath = Join-Path $archiveDir "snapshot_$timestamp.json"
        Copy-Item -Path $OldSnapshotPath -Destination $archivePath -Force
        Write-Host "  Archived old snapshot: snapshot_$timestamp.json" -ForegroundColor Gray
    }
}

# MAIN EXECUTION
try {
    Write-Host ""
    Write-Host "Creating TODO snapshot..." -ForegroundColor Cyan

    # Get current TODOs (in real impl, parse from TodoWrite)
    $todos = Get-CurrentTodos

    # Archive existing snapshot
    if (Test-Path $snapshotPath) {
        Save-ArchiveSnapshot -OldSnapshotPath $snapshotPath
    }

    # Create new snapshot
    $snapshot = New-Snapshot `
        -Todos $todos `
        -Summary $ContextSummary `
        -Agent $AgentInProgress `
        -LastFile $LastFileRead `
        -LastCmd $LastCommand

    # Save to file
    $snapshot | ConvertTo-Json -Depth 10 | Out-File -FilePath $snapshotPath -Encoding UTF8 -Force

    Write-Host "  Snapshot saved: claude_session_state.json" -ForegroundColor Green
    Write-Host "  Timestamp: $($snapshot.timestamp)" -ForegroundColor Gray
    Write-Host "  TODO items: $($todos.Count)" -ForegroundColor Gray
    Write-Host ""
}
catch {
    Write-Host "ERROR: Failed to create snapshot - $_" -ForegroundColor Red
    exit 1
}
