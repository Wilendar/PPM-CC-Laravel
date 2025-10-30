# Comprehensive Dark Theme Fix - Remove ALL white backgrounds from PPM project
# PPM is DARK THEME ONLY - no light mode needed

Write-Host "`n=== PPM DARK THEME FIX - COMPREHENSIVE ===" -ForegroundColor Cyan
Write-Host "Removing ALL light mode fallbacks from entire project`n" -ForegroundColor Yellow

$projectRoot = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"
$viewsPath = Join-Path $projectRoot "resources\views"

# Find ALL blade files with bg-white
$filesWithWhite = Get-ChildItem -Path $viewsPath -Recurse -Filter "*.blade.php" |
    Where-Object { (Get-Content $_.FullName -Raw) -match 'bg-white' }

Write-Host "Found $($filesWithWhite.Count) files with bg-white`n" -ForegroundColor Yellow

$fixedCount = 0
$patterns = @{
    'bg-white dark:bg-gray-700' = 'bg-gray-700'
    'bg-white dark:bg-gray-800' = 'bg-gray-800'
    'bg-white dark:bg-gray-900' = 'bg-gray-900'
    'text-gray-900 dark:text-white' = 'text-white'
    'text-gray-800 dark:text-white' = 'text-white'
    'text-gray-700 dark:text-gray-300' = 'text-gray-300'
    'text-gray-700 dark:text-gray-200' = 'text-gray-200'
    'border-gray-300 dark:border-gray-600' = 'border-gray-600'
    'border-gray-200 dark:border-gray-700' = 'border-gray-700'
    'border-gray-300 dark:border-gray-700' = 'border-gray-700'
    'hover:bg-gray-50 dark:hover:bg-gray-700' = 'hover:bg-gray-700'
    'hover:bg-gray-50 dark:hover:bg-gray-600' = 'hover:bg-gray-600'
    'hover:bg-gray-100 dark:hover:bg-gray-700' = 'hover:bg-gray-700'
    'divide-gray-200 dark:divide-gray-700' = 'divide-gray-700'
}

foreach ($file in $filesWithWhite) {
    $relativePath = $file.FullName.Replace($projectRoot + '\', '')

    try {
        $content = Get-Content $file.FullName -Raw
        $originalContent = $content

        # Apply all pattern replacements
        foreach ($pattern in $patterns.GetEnumerator()) {
            $content = $content -replace [regex]::Escape($pattern.Key), $pattern.Value
        }

        # Only save if content changed
        if ($content -ne $originalContent) {
            Set-Content $file.FullName $content -NoNewline
            Write-Host "✅ Fixed: $relativePath" -ForegroundColor Green
            $fixedCount++
        }
    } catch {
        Write-Host "❌ ERROR fixing $relativePath : $($_.Exception.Message)" -ForegroundColor Red
    }
}

Write-Host "`n=== SUMMARY ===" -ForegroundColor Cyan
Write-Host "Files scanned: $($filesWithWhite.Count)" -ForegroundColor White
Write-Host "Files fixed: $fixedCount" -ForegroundColor Green

# Verify - check if any bg-white remain
$remaining = Get-ChildItem -Path $viewsPath -Recurse -Filter "*.blade.php" |
    Where-Object { (Get-Content $_.FullName -Raw) -match 'bg-white' }

if ($remaining.Count -gt 0) {
    Write-Host "`n⚠️  WARNING: $($remaining.Count) files still have bg-white (might be intentional)" -ForegroundColor Yellow
    $remaining | Select-Object -First 5 | ForEach-Object {
        Write-Host "  - $($_.FullName.Replace($projectRoot + '\', ''))" -ForegroundColor Yellow
    }
} else {
    Write-Host "`n✅ SUCCESS: NO bg-white remaining in views!" -ForegroundColor Green
}

Write-Host "`n✅ Dark theme fix complete!" -ForegroundColor Cyan
