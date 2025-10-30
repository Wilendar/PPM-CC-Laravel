$file = 'D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\resources\views\livewire\products\management\product-form.blade.php'
$content = Get-Content $file -Raw
$content = $content -replace 'bg-white dark:bg-gray-700', 'bg-gray-700'
$content = $content -replace 'bg-white dark:bg-gray-800', 'bg-gray-800'
$content = $content -replace 'text-gray-900 dark:text-white', 'text-white'
$content = $content -replace 'text-gray-700 dark:text-gray-300', 'text-gray-300'
$content = $content -replace 'border-gray-300 dark:border-gray-600', 'border-gray-600'
$content = $content -replace 'border-gray-200 dark:border-gray-700', 'border-gray-700'
$content = $content -replace 'hover:bg-gray-50 dark:hover:bg-gray-700', 'hover:bg-gray-700'
$content = $content -replace 'hover:bg-gray-50 dark:hover:bg-gray-600', 'hover:bg-gray-600'
Set-Content $file $content -NoNewline
Write-Host 'Fixed product-form.blade.php - removed light mode fallbacks' -ForegroundColor Green

# Now fix categories views
$catFiles = @(
    'D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\resources\views\livewire\products\categories\category-tree-compact.blade.php',
    'D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\resources\views\livewire\products\categories\category-tree-enhanced.blade.php',
    'D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\resources\views\livewire\products\categories\category-tree-ultra-clean.blade.php'
)

foreach ($catFile in $catFiles) {
    if (Test-Path $catFile) {
        $content = Get-Content $catFile -Raw
        $content = $content -replace 'bg-white dark:bg-gray-700', 'bg-gray-700'
        $content = $content -replace 'bg-white dark:bg-gray-800', 'bg-gray-800'
        $content = $content -replace 'text-gray-900 dark:text-white', 'text-white'
        $content = $content -replace 'text-gray-700 dark:text-gray-300', 'text-gray-300'
        $content = $content -replace 'border-gray-300 dark:border-gray-600', 'border-gray-600'
        $content = $content -replace 'border-gray-200 dark:border-gray-700', 'border-gray-700'
        $content = $content -replace 'hover:bg-gray-50 dark:hover:bg-gray-700', 'hover:bg-gray-700'
        $content = $content -replace 'hover:bg-gray-50 dark:hover:bg-gray-600', 'hover:bg-gray-600'
        Set-Content $catFile $content -NoNewline
        Write-Host "Fixed $(Split-Path $catFile -Leaf)" -ForegroundColor Green
    }
}

Write-Host "`n✅ All files fixed - light mode removed!" -ForegroundColor Green
