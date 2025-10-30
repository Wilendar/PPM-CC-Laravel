#!/usr/bin/env pwsh
# Minimal SessionStart Hook - Safe for Windows Terminal
# NO verbose output, NO I/O operations, FAST exit
# Created: 2025-10-30

$ErrorActionPreference = 'SilentlyContinue'

try {
    $snapshotPath = "_TEMP\claude_session_state.json"

    # Quick check - NO reading file content
    if (Test-Path $snapshotPath) {
        $age = (Get-Date) - (Get-Item $snapshotPath).LastWriteTime

        # Only notify if < 24h old
        if ($age.TotalHours -lt 24) {
            Write-Host "Session restored. Type /recovery for details." -ForegroundColor Cyan
        }
    }
} catch {
    # Silent failure
}

# CRITICAL: Always exit 0
exit 0
