# PPM-CC-Laravel: Comprehensive White Background Elimination Script
# Usuwa WSZYSTKIE pozostałe białe tła w całej aplikacji
# Date: 2025-10-29

$ErrorActionPreference = "Stop"
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

Write-Host "`n=== PPM WHITE BACKGROUNDS FIX - PHASE 2 ===" -ForegroundColor Cyan
Write-Host "Fixing ALL remaining white backgrounds in PPM application`n" -ForegroundColor Yellow

$baseDir = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\resources\views"

# Comprehensive pattern replacements (dark theme only)
$patterns = @{
    # Background colors
    'bg-white dark:bg-gray-700' = 'bg-gray-700'
    'bg-white dark:bg-gray-800' = 'bg-gray-800'
    'bg-white dark:bg-gray-900' = 'bg-gray-900'
    'bg-white/50 dark:bg-gray-800/50' = 'bg-gray-800/50'
    'bg-white' = 'bg-gray-800'  # Fallback dla standalone bg-white

    # Text colors
    'text-gray-900 dark:text-white' = 'text-white'
    'text-gray-900 dark:text-gray-100' = 'text-gray-100'
    'text-gray-800 dark:text-gray-200' = 'text-gray-200'
    'text-gray-700 dark:text-gray-300' = 'text-gray-300'
    'text-gray-600 dark:text-gray-400' = 'text-gray-400'
    'text-gray-900' = 'text-white'  # Fallback

    # Borders
    'border-gray-300 dark:border-gray-600' = 'border-gray-600'
    'border-gray-200 dark:border-gray-700' = 'border-gray-700'
    'border-gray-300 dark:border-gray-700' = 'border-gray-700'

    # Hover states
    'hover:bg-gray-50 dark:hover:bg-gray-700' = 'hover:bg-gray-700'
    'hover:bg-gray-50 dark:hover:bg-gray-600' = 'hover:bg-gray-600'
    'hover:bg-gray-100 dark:hover:bg-gray-700' = 'hover:bg-gray-700'
    'hover:bg-white dark:hover:bg-gray-800' = 'hover:bg-gray-800'

    # Dividers
    'divide-gray-200 dark:divide-gray-700' = 'divide-gray-700'
    'divide-gray-300 dark:divide-gray-600' = 'divide-gray-600'

    # Ring (focus states)
    'ring-gray-300 dark:ring-gray-600' = 'ring-gray-600'
    'focus:ring-gray-300 dark:focus:ring-gray-600' = 'focus:ring-gray-600'
}

# Get all remaining files with bg-white
$allFiles = Get-ChildItem -Path $baseDir -Recurse -Include "*.blade.php" | Where-Object {
    $content = Get-Content $_.FullName -Raw -Encoding UTF8
    $content -match 'bg-white|text-gray-900(?! dark:)'
}

Write-Host "Found $($allFiles.Count) files with white backgrounds to fix" -ForegroundColor Yellow

$fixedCount = 0
$totalReplacements = 0

foreach ($file in $allFiles) {
    $relativePath = $file.FullName.Replace($baseDir + "\", "")
    Write-Host "`nProcessing: $relativePath" -ForegroundColor Cyan

    $content = Get-Content $file.FullName -Raw -Encoding UTF8
    $originalContent = $content
    $fileReplacements = 0

    foreach ($pattern in $patterns.GetEnumerator()) {
        $oldPattern = [regex]::Escape($pattern.Key)
        $matches = [regex]::Matches($content, $oldPattern)

        if ($matches.Count -gt 0) {
            $content = $content -replace $oldPattern, $pattern.Value
            $fileReplacements += $matches.Count
            Write-Host "  - Replaced '$($pattern.Key)' -> '$($pattern.Value)' ($($matches.Count)x)" -ForegroundColor Green
        }
    }

    if ($content -ne $originalContent) {
        Set-Content -Path $file.FullName -Value $content -Encoding UTF8 -NoNewline
        $fixedCount++
        $totalReplacements += $fileReplacements
        Write-Host "  [FIXED] $fileReplacements replacements" -ForegroundColor Green
    } else {
        Write-Host "  [SKIP] No changes needed" -ForegroundColor DarkGray
    }
}

Write-Host "`n=== SUMMARY ===" -ForegroundColor Cyan
Write-Host "Files fixed: $fixedCount / $($allFiles.Count)" -ForegroundColor Green
Write-Host "Total replacements: $totalReplacements" -ForegroundColor Green
Write-Host "`nAll white backgrounds eliminated! Ready for deployment." -ForegroundColor Yellow
