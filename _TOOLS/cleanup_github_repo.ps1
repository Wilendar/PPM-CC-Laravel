# ============================================
# PPM-CC-Laravel - GitHub Repository Cleanup
# ============================================
# Usuwa niepotrzebne foldery z GitHub tracking
# BEZ USUWANIA ICH LOKALNIE (pozostają na dysku)
# ============================================

$ErrorActionPreference = "Stop"
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "GitHub Repository Cleanup Script" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

# Change to project root
Set-Location "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"

Write-Host "[INFO] Working directory: $(Get-Location)" -ForegroundColor Green

# ============================================
# Foldery do usuniecia z git tracking
# (Tylko kod aplikacji powinien byc w repo GitHub)
# ============================================
$foldersToRemove = @(
    "_AGENT_REPORTS",
    "_REPORTS",
    "_TOOLS",
    "_DIAGNOSTICS",
    "_ISSUES_FIXES",
    "_DOCS",
    "Plan_Projektu",
    "Plan_Projektu copy",
    ".claude"
)

# ============================================
# Pliki do usuniecia z git tracking
# ============================================
$filesToRemove = @(
    "CLAUDE.md",
    "AGENTS.md",
    "_TEMP_*",
    "_BACKUP_*",
    "_WORKING_*",
    "category-form-server.blade.php",
    "force-debug.blade.php",
    "debug-category.blade.php",
    "simple-test.blade.php",
    "route-test.blade.php"
)

Write-Host "`n[STEP 1] Checking git status..." -ForegroundColor Yellow
git status

Write-Host "`n[STEP 2] Removing folders from git tracking (keeping local files)..." -ForegroundColor Yellow
foreach ($folder in $foldersToRemove) {
    if (Test-Path $folder) {
        Write-Host "  Removing: $folder" -ForegroundColor Cyan
        git rm -r --cached $folder 2>&1 | Out-Null
        if ($LASTEXITCODE -eq 0) {
            Write-Host "    [OK] $folder removed from tracking" -ForegroundColor Green
        } else {
            Write-Host "    [SKIP] $folder not tracked or already removed" -ForegroundColor Gray
        }
    } else {
        Write-Host "  [SKIP] $folder does not exist locally" -ForegroundColor Gray
    }
}

Write-Host "`n[STEP 3] Removing files from git tracking..." -ForegroundColor Yellow
foreach ($filePattern in $filesToRemove) {
    $files = Get-ChildItem -Path . -Filter $filePattern -Recurse -ErrorAction SilentlyContinue
    if ($files) {
        foreach ($file in $files) {
            $relativePath = $file.FullName.Replace("$(Get-Location)\", "").Replace("\", "/")
            Write-Host "  Removing: $relativePath" -ForegroundColor Cyan
            git rm --cached $relativePath 2>&1 | Out-Null
            if ($LASTEXITCODE -eq 0) {
                Write-Host "    [OK] $relativePath removed from tracking" -ForegroundColor Green
            }
        }
    }
}

Write-Host "`n[STEP 4] Checking current git status..." -ForegroundColor Yellow
git status

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "Cleanup Complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan

Write-Host "`n[NEXT STEPS]" -ForegroundColor Yellow
Write-Host "1. Review changes: git status" -ForegroundColor White
Write-Host "2. Commit changes: git add .gitignore && git commit -m '...'" -ForegroundColor White
Write-Host "3. Push to GitHub: git push origin main" -ForegroundColor White
Write-Host "`n[IMPORTANT]" -ForegroundColor Yellow
Write-Host "- Wszystkie foldery i pliki POZOSTALY LOKALNIE" -ForegroundColor Green
Write-Host "- Usunieto TYLKO z GitHub tracking (git)" -ForegroundColor Green
Write-Host "- GitHub repo bedzie zawierac TYLKO kod Laravel aplikacji" -ForegroundColor Green

Write-Host "`n[CONFIRM] Czy chcesz od razu zrobic commit i push? (y/n): " -ForegroundColor Yellow -NoNewline
$confirm = Read-Host

if ($confirm -eq 'y' -or $confirm -eq 'Y') {
    Write-Host "`n[STEP 5] Creating commit..." -ForegroundColor Yellow
    git add .gitignore
    git commit -m "chore: cleanup repo - keep only Laravel application code

Removed from GitHub tracking (files remain locally):
- Development folders: _AGENT_REPORTS/, _REPORTS/, _TOOLS/, _DIAGNOSTICS/, _ISSUES_FIXES/, _DOCS/
- Project management: Plan_Projektu/, .claude/
- Documentation: CLAUDE.md, AGENTS.md
- Temporary/backup files

Updated .gitignore to prevent future tracking.

Repository now contains ONLY Laravel application code.
All development tools, docs, and project files remain locally for development."

    if ($LASTEXITCODE -eq 0) {
        Write-Host "[OK] Commit created successfully" -ForegroundColor Green

        Write-Host "`n[STEP 6] Pushing to GitHub..." -ForegroundColor Yellow
        git push origin main

        if ($LASTEXITCODE -eq 0) {
            Write-Host "`n[SUCCESS] Repository cleaned and pushed to GitHub!" -ForegroundColor Green
        } else {
            Write-Host "`n[ERROR] Push failed. Please check git credentials and try manually." -ForegroundColor Red
        }
    } else {
        Write-Host "`n[ERROR] Commit failed. Please review changes and try manually." -ForegroundColor Red
    }
} else {
    Write-Host "`n[INFO] Skipped auto-commit. Run git commands manually when ready." -ForegroundColor Cyan
}

Write-Host "`n========================================`n" -ForegroundColor Cyan
