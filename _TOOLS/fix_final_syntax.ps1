# Final fix for product-form.blade.php
# 1. Replace literal \n with actual newlines
# 2. Fix getAvailableCategories(; line

$filePath = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\resources\views\livewire\products\management\product-form.blade.php"

Write-Host "Reading file..." -ForegroundColor Cyan
$content = Get-Content $filePath -Raw -Encoding UTF8

Write-Host "Fixing literal \n sequences..." -ForegroundColor Yellow
# Replace literal \n with actual newline
$content = $content -replace '\\n', "`n"

Write-Host "Fixing getAvailableCategories line..." -ForegroundColor Yellow
# Fix: $availableCategories = $this->getAvailableCategories(; @endphp)
$replacement = '$availableCategories = $this->getAvailableCategories();' + "`n" + '                                        @endphp'
$content = $content -replace '\$availableCategories = \$this->getAvailableCategories\(;\s*@endphp\)', $replacement

Write-Host "Saving file..." -ForegroundColor Cyan
[System.IO.File]::WriteAllText($filePath, $content, [System.Text.UTF8Encoding]::new($false))

Write-Host "`n=== FINAL FIX COMPLETED ===" -ForegroundColor Green
Write-Host "File: product-form.blade.php" -ForegroundColor White
Write-Host "- Fixed literal \n sequences" -ForegroundColor White
Write-Host "- Fixed getAvailableCategories() call" -ForegroundColor White
