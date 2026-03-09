# ============================================================================
# PPM-CC-Laravel Auto Git Commit + Push
# Usage:  .\auto-git-commit.ps1
#         .\auto-git-commit.ps1 -Branch main
#         .\auto-git-commit.ps1 -DryRun
#         .\auto-git-commit.ps1 -NoPush
# ============================================================================

param(
    [string]$Branch = 'develop',
    [switch]$DryRun,
    [switch]$NoPush
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

# --- Load shared config (for Write-Step/Write-Ok/Write-Warn/Write-Err helpers) ---
. "$PSScriptRoot\hostido-config.ps1"

# --- Resolve project root (two levels up from .Release_docs/scripts/) ---
$ProjectRoot = (Resolve-Path "$PSScriptRoot\..\..").Path

# --- Timing ---
$sw = [System.Diagnostics.Stopwatch]::StartNew()

# ============================================================================
# FUNCTIONS
# ============================================================================

function Get-ChangeScope {
    param([string[]]$Files)

    $scopes = [System.Collections.Generic.HashSet[string]]::new()

    foreach ($file in $Files) {
        $path = $file.Trim()
        if ($path -match '^app/') { [void]$scopes.Add('backend') }
        elseif ($path -match '^resources/') { [void]$scopes.Add('frontend') }
        elseif ($path -match '^config/') { [void]$scopes.Add('config') }
        elseif ($path -match '^database/') { [void]$scopes.Add('db') }
        elseif ($path -match '^routes/') { [void]$scopes.Add('routes') }
        elseif ($path -match '^tests/') { [void]$scopes.Add('tests') }
        elseif ($path -match '^public/') { [void]$scopes.Add('assets') }
        elseif ($path -match '^\.(Release_docs|claude)/') { [void]$scopes.Add('tooling') }
        else { [void]$scopes.Add('misc') }
    }

    if ($scopes.Count -eq 0) { return 'misc' }
    return ($scopes | Sort-Object) -join ','
}

function Build-CommitMessage {
    param([string]$StatusOutput)

    $lines = $StatusOutput -split "`n" | Where-Object { $_.Trim() -ne '' }

    $modified = 0
    $added = 0
    $deleted = 0
    $files = @()

    foreach ($line in $lines) {
        $status = $line.Substring(0, 2).Trim()
        $filePath = $line.Substring(3).Trim()
        # Handle renamed files (old -> new)
        if ($filePath -match '->') {
            $filePath = ($filePath -split '->' | Select-Object -Last 1).Trim()
        }
        $files += $filePath

        switch -Regex ($status) {
            '^M'  { $modified++ }
            '^A'  { $added++ }
            '^\?' { $added++ }
            '^D'  { $deleted++ }
            '^R'  { $modified++ }
            default { $modified++ }
        }
    }

    $scope = Get-ChangeScope -Files $files
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm"

    $parts = @()
    if ($modified -gt 0) { $parts += "$modified modified" }
    if ($added -gt 0)    { $parts += "$added added" }
    if ($deleted -gt 0)  { $parts += "$deleted deleted" }
    $summary = $parts -join ', '

    return "chore($scope): auto-sync $summary [$timestamp]"
}

# ============================================================================
# MAIN EXECUTION
# ============================================================================

Write-Host ""
Write-Host "============================================" -ForegroundColor Magenta
Write-Host " PPM Auto Git Commit$(if($DryRun){' [DRY-RUN]'})" -ForegroundColor Magenta
Write-Host " Branch: $Branch" -ForegroundColor Magenta
Write-Host " Time: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" -ForegroundColor Magenta
Write-Host "============================================" -ForegroundColor Magenta
Write-Host ""

# --- Check for .git/index.lock ---
$lockFile = Join-Path $ProjectRoot ".git\index.lock"
if (Test-Path $lockFile) {
    Write-Warn "Git index.lock exists! Another git process may be running."
    Write-Warn "If no other process is running, remove: $lockFile"
    exit 1
}

# --- Ensure we are on the correct branch ---
Write-Step "Checking current branch..."
Push-Location $ProjectRoot
try {
    $currentBranch = & git rev-parse --abbrev-ref HEAD 2>&1
    if ($currentBranch -ne $Branch) {
        Write-Warn "Current branch: $currentBranch (expected: $Branch)"
        Write-Step "Switching to $Branch..."
        if (-not $DryRun) {
            & git checkout $Branch 2>&1 | Out-Null
            if ($LASTEXITCODE -ne 0) {
                Write-Err "Failed to switch to branch $Branch"
                exit 1
            }
        }
    }
    Write-Ok "Branch: $Branch"

    # --- Check for changes ---
    Write-Step "Checking for changes..."
    $status = & git status --porcelain 2>&1
    $statusText = ($status | Out-String).Trim()

    if ([string]::IsNullOrWhiteSpace($statusText)) {
        Write-Ok "Nothing to commit - working tree clean"
        exit 0
    }

    $lineCount = ($statusText -split "`n").Count
    Write-Ok "Found $lineCount changed file(s)"

    # --- Show changed files list ---
    Write-Step "Changed files:"
    $statusLines = $statusText -split "`n"
    $i = 0
    foreach ($line in $statusLines) {
        $i++
        $statusCode = $line.Substring(0, 2).Trim()
        $filePath = $line.Substring(3).Trim()
        $color = switch -Regex ($statusCode) {
            '^\?'  { 'Green' }
            '^D'   { 'Red' }
            '^M'   { 'Yellow' }
            default { 'Gray' }
        }
        $label = switch -Regex ($statusCode) {
            '^\?'  { '+' }
            '^D'   { '-' }
            '^M'   { '~' }
            default { '?' }
        }
        Write-Host "  [$label] $filePath" -ForegroundColor $color
    }
    Write-Host ""

    # --- Stage all changes ---
    Write-Step "Staging all changes ($lineCount files)..."
    if ($DryRun) {
        Write-Warn "DRY-RUN: Would run git add -A"
    }
    else {
        & git add -A 2>&1 | Out-Null
        if ($LASTEXITCODE -ne 0) {
            Write-Err "git add -A failed"
            exit 1
        }
    }
    Write-Ok "All $lineCount files staged"

    # --- Generate commit message ---
    $commitMessage = Build-CommitMessage -StatusOutput $statusText
    Write-Step "Commit message: $commitMessage"

    # --- Commit ---
    if ($DryRun) {
        Write-Warn "DRY-RUN: Would commit with message above"
    }
    else {
        & git commit -m $commitMessage 2>&1 | Out-Null
        if ($LASTEXITCODE -ne 0) {
            Write-Err "git commit failed"
            exit 1
        }
        $commitHash = & git rev-parse --short HEAD 2>&1
        Write-Ok "Committed: $commitHash"
    }

    # --- Push ---
    if ($NoPush) {
        Write-Warn "Push skipped (-NoPush)"
    }
    elseif ($DryRun) {
        Write-Warn "DRY-RUN: Would push to origin/$Branch"
    }
    else {
        Write-Step "Pushing to origin/$Branch..."
        Write-Host "  Uploading..." -ForegroundColor Gray -NoNewline
        $pushResult = & git push origin $Branch 2>&1
        if ($LASTEXITCODE -ne 0) {
            Write-Host "" # newline
            Write-Warn "Push failed, retrying in 5 seconds..."
            Start-Sleep -Seconds 5
            Write-Host "  Retrying..." -ForegroundColor Gray -NoNewline
            $pushResult = & git push origin $Branch 2>&1
            if ($LASTEXITCODE -ne 0) {
                Write-Host "" # newline
                Write-Err "Push failed after retry!"
                Write-Err "Try: git pull --rebase origin $Branch"
                Write-Host ($pushResult | Out-String) -ForegroundColor Gray
                exit 1
            }
        }
        Write-Host " Done!" -ForegroundColor Green
        # Show push details
        $pushText = ($pushResult | Out-String).Trim()
        if ($pushText -match '([a-f0-9]+)\.\.([a-f0-9]+)') {
            Write-Host "  $($Matches[1]) -> $($Matches[2])" -ForegroundColor Gray
        }
        Write-Ok "Pushed to origin/$Branch"
    }
}
finally {
    Pop-Location
}

# --- Final report ---
$sw.Stop()
$elapsed = $sw.Elapsed.ToString("mm\:ss")

Write-Host ""
Write-Host "============================================" -ForegroundColor Magenta
Write-Host " AUTO-COMMIT COMPLETE ($elapsed)" -ForegroundColor Green
Write-Host "============================================" -ForegroundColor Magenta
Write-Host ""

exit 0
