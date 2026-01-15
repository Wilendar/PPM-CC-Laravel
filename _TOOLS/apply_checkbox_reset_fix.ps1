#!/usr/bin/env pwsh
<#
.SYNOPSIS
Apply Livewire Checkbox Reset Bug Fixes to CategoryTree component

.DESCRIPTION
Automates applying two critical fixes:
1. Add wire:key to tbody and tr elements in category-tree-ultra-clean.blade.php
2. Refactor toggleSelection() method to reset array keys in CategoryTree.php

.PARAMETER DryRun
Show what would be changed without applying changes (default: $false)

.EXAMPLE
./apply_checkbox_reset_fix.ps1 -DryRun
./apply_checkbox_reset_fix.ps1
#>

param(
    [switch]$DryRun = $false
)

# ================================================================
# CONFIGURATION
# ================================================================

$ProjectRoot = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"
$BladeFile = "$ProjectRoot\resources\views\livewire\products\categories\category-tree-ultra-clean.blade.php"
$PhpFile = "$ProjectRoot\app\Http\Livewire\Products\Categories\CategoryTree.php"

# ================================================================
# HELPERS
# ================================================================

function Write-Title {
    param([string]$Text)
    Write-Host "`n" -ForegroundColor White
    Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Cyan
    Write-Host " $Text" -ForegroundColor Cyan
    Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Cyan
}

function Write-Success {
    param([string]$Text)
    Write-Host "✅ $Text" -ForegroundColor Green
}

function Write-Info {
    param([string]$Text)
    Write-Host "ℹ️  $Text" -ForegroundColor Blue
}

function Write-Warning {
    param([string]$Text)
    Write-Host "⚠️  $Text" -ForegroundColor Yellow
}

function Write-Error {
    param([string]$Text)
    Write-Host "❌ $Text" -ForegroundColor Red
}

# ================================================================
# FIX #1: BLADE FILE - Add wire:key
# ================================================================

function Fix-BladeFile {
    param([bool]$DryRun)

    Write-Title "FIX #1: Add wire:key to Blade Template"

    if (-not (Test-Path $BladeFile)) {
        Write-Error "File not found: $BladeFile"
        return $false
    }

    Write-Info "Reading: $BladeFile"
    $content = Get-Content -Path $BladeFile -Raw -Encoding UTF8

    # Fix 1a: Add wire:key to tbody
    Write-Info "Checking tbody for wire:key..."

    $tbodyOld = '<tbody class="bg-gray-800 divide-y divide-gray-700 sortable-tbody"
                       style="overflow: visible !important;"
                       @if($viewMode === ''tree'')'

    $tbodyNew = '<tbody class="bg-gray-800 divide-y divide-gray-700 sortable-tbody"
                       style="overflow: visible !important;"
                       wire:key="category-list-{{ $viewMode }}"
                       @if($viewMode === ''tree'')'

    if ($content -like "*$tbodyOld*") {
        Write-Warning "Found tbody without wire:key"
        if ($DryRun) {
            Write-Info "[DRY RUN] Would add: wire:key=\"category-list-{{ \$viewMode }}\""
        } else {
            $content = $content -replace [regex]::Escape($tbodyOld), $tbodyNew
            Write-Success "Added wire:key to tbody"
        }
    } else {
        Write-Success "tbody already has wire:key or pattern not found (may be OK)"
    }

    # Fix 1b: Add wire:key to tr
    Write-Info "Checking tr for wire:key..."

    $trOld = '@forelse($categories as $category)
                        <tr class="transition-colors'

    $trNew = '@forelse($categories as $category)
                        <tr wire:key="category-row-{{ $category->id }}"
                            class="transition-colors'

    if ($content -like "*@forelse(`$categories*" -and -not ($content -like '*wire:key="category-row*')) {
        Write-Warning "Found tr without wire:key"

        # More precise pattern
        $content = $content -replace
            '@forelse\(\$categories as \$category\)\s*<tr class="transition-colors',
            '@forelse($categories as $category)
                        <tr wire:key="category-row-{{ $category->id }}"
                            class="transition-colors'

        Write-Success "Added wire:key to tr elements"
    } else {
        Write-Success "tr elements already have wire:key or pattern not found (may be OK)"
    }

    if (-not $DryRun) {
        Set-Content -Path $BladeFile -Value $content -Encoding UTF8
        Write-Success "Saved changes to blade file"
    }

    return $true
}

# ================================================================
# FIX #2: PHP FILE - Refactor toggleSelection
# ================================================================

function Fix-PhpFile {
    param([bool]$DryRun)

    Write-Title "FIX #2: Refactor toggleSelection() Method"

    if (-not (Test-Path $PhpFile)) {
        Write-Error "File not found: $PhpFile"
        return $false
    }

    Write-Info "Reading: $PhpFile"
    $content = Get-Content -Path $PhpFile -Raw -Encoding UTF8

    # Pattern to find and replace toggleSelection method
    $oldPattern = @'
    public function toggleSelection\(int \$categoryId\): void
    \{
        if \(in_array\(\$categoryId, \$this->selectedCategories\)\) \{
            \$this->selectedCategories = array_diff\(\$this->selectedCategories, \[\$categoryId\]\);
        \} else \{
            \$this->selectedCategories\[\] = \$categoryId;
        \}
    \}
'@

    $newCode = @'
    /**
     * Select/deselect category for bulk operations
     *
     * @param int $categoryId
     */
    public function toggleSelection(int $categoryId): void
    {
        if (in_array($categoryId, $this->selectedCategories)) {
            // Remove category from selection and reset numeric keys
            $this->selectedCategories = array_values(
                array_filter($this->selectedCategories, fn($id) => $id !== $categoryId)
            );
        } else {
            // Add category to selection
            $this->selectedCategories[] = $categoryId;
        }
    }
'@

    if ($content -match 'array_diff\(\$this->selectedCategories') {
        Write-Warning "Found toggleSelection with old array_diff pattern"

        # Simple string replacement
        $oldSimple = @'
    public function toggleSelection(int $categoryId): void
    {
        if (in_array($categoryId, $this->selectedCategories)) {
            $this->selectedCategories = array_diff($this->selectedCategories, [$categoryId]);
        } else {
            $this->selectedCategories[] = $categoryId;
        }
    }
'@

        $newSimple = @'
    /**
     * Select/deselect category for bulk operations
     *
     * @param int $categoryId
     */
    public function toggleSelection(int $categoryId): void
    {
        if (in_array($categoryId, $this->selectedCategories)) {
            // Remove category from selection and reset numeric keys
            $this->selectedCategories = array_values(
                array_filter($this->selectedCategories, fn($id) => $id !== $categoryId)
            );
        } else {
            // Add category to selection
            $this->selectedCategories[] = $categoryId;
        }
    }
'@

        if ($DryRun) {
            Write-Info "[DRY RUN] Would refactor toggleSelection() method"
            Write-Info "  FROM: array_diff(\$this->selectedCategories, [\$categoryId])"
            Write-Info "  TO:   array_values(array_filter(...))"
        } else {
            $content = $content -replace [regex]::Escape($oldSimple), $newSimple
            Write-Success "Refactored toggleSelection() method"
        }
    } else {
        Write-Success "toggleSelection() already uses array_filter pattern (may be OK)"
    }

    if (-not $DryRun) {
        Set-Content -Path $PhpFile -Value $content -Encoding UTF8
        Write-Success "Saved changes to PHP file"
    }

    return $true
}

# ================================================================
# MAIN EXECUTION
# ================================================================

function Main {
    Write-Host "`n╔════════════════════════════════════════════════════╗" -ForegroundColor Cyan
    Write-Host "║  Livewire Checkbox Reset Bug - Fix Applicator       ║" -ForegroundColor Cyan
    Write-Host "║  PPM-CC-Laravel Category Management                 ║" -ForegroundColor Cyan
    Write-Host "╚════════════════════════════════════════════════════╝" -ForegroundColor Cyan

    if ($DryRun) {
        Write-Warning "Running in DRY RUN mode - no changes will be applied"
    }

    # Verify project root exists
    if (-not (Test-Path $ProjectRoot)) {
        Write-Error "Project root not found: $ProjectRoot"
        exit 1
    }

    Write-Success "Project root verified: $ProjectRoot`n"

    # Apply fixes
    $fix1 = Fix-BladeFile -DryRun $DryRun
    $fix2 = Fix-PhpFile -DryRun $DryRun

    # Summary
    Write-Title "Summary"

    if ($DryRun) {
        Write-Info "DRY RUN COMPLETE - No files were modified"
        Write-Info "To apply fixes, run: ./apply_checkbox_reset_fix.ps1"
    } else {
        if ($fix1 -and $fix2) {
            Write-Success "✅ All fixes applied successfully!"
            Write-Info ""
            Write-Info "Next steps:"
            Write-Info "  1. Run: npm run build"
            Write-Info "  2. Deploy to production"
            Write-Info "  3. Test in Chrome DevTools (verify no wire:snapshot artifacts)"
            Write-Info "  4. Test checkbox reset after bulk delete"
        } else {
            Write-Error "Some fixes failed - please review output above"
            exit 1
        }
    }

    Write-Host ""
}

Main
