#!/usr/bin/env pwsh
# SessionStart Hook - Flag System (SAFE, STDIN/STDOUT compliant)
# Reads stdin (Claude passes session info), creates flag if needed, exits clean
# Created: 2025-10-30 (FIXED - proper hook protocol)

$ErrorActionPreference = 'SilentlyContinue'

try {
    # Read stdin (Claude passes session info as JSON)
    # We don't need to parse it, but we MUST consume it
    $null = [Console]::In.ReadToEnd()

    $snapshotPath = "_TEMP\claude_session_state.json"
    $flagPath = "_TEMP\.recovery_pending"

    # Remove old flag if exists
    if (Test-Path $flagPath) {
        Remove-Item $flagPath -Force -ErrorAction SilentlyContinue
    }

    # Check if snapshot exists and is fresh
    if (Test-Path $snapshotPath) {
        $age = (Get-Date) - (Get-Item $snapshotPath).LastWriteTime

        if ($age.TotalHours -lt 24) {
            # Create flag file (recovery available)
            New-Item $flagPath -ItemType File -Force -ErrorAction SilentlyContinue | Out-Null
        }
    }

    # Optional: Return additional context to Claude
    # (commented out to keep minimal - uncomment if needed)
    # $output = @{
    #     hookSpecificOutput = @{
    #         hookEventName = "SessionStart"
    #         additionalContext = "Recovery system initialized"
    #     }
    # }
    # $output | ConvertTo-Json -Compress | Write-Output

} catch {
    # Silent failure - just exit clean
}

# CRITICAL: Always exit 0
exit 0
