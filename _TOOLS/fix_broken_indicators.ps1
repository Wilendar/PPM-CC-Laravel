# Fix broken @php blocks in product-form.blade.php
# Repairs lines where regex split method calls incorrectly

$filePath = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\resources\views\livewire\products\management\product-form.blade.php"

Write-Host "Reading file..." -ForegroundColor Cyan
$content = Get-Content $filePath -Raw -Encoding UTF8

# Fix pattern: lines ending with '; followed by @endphp) on next line
# Pattern 1: getFieldStatusIndicator('param'; @endphp)
$fixes = @{
    "getFieldStatusIndicator\('sku';\s*@endphp\)" = "getFieldStatusIndicator('sku');\n                                        @endphp"
    "getFieldStatusIndicator\('product_type_id';\s*@endphp\)" = "getFieldStatusIndicator('product_type_id');\n                                        @endphp"
    "getFieldStatusIndicator\('name';\s*@endphp\)" = "getFieldStatusIndicator('name');\n                                        @endphp"
    "getFieldStatusIndicator\('slug';\s*@endphp\)" = "getFieldStatusIndicator('slug');\n                                        @endphp"
    "getFieldStatusIndicator\('manufacturer';\s*@endphp\)" = "getFieldStatusIndicator('manufacturer');\n                                        @endphp"
    "getFieldStatusIndicator\('supplier_code';\s*@endphp\)" = "getFieldStatusIndicator('supplier_code');\n                                        @endphp"
    "getFieldStatusIndicator\('ean';\s*@endphp\)" = "getFieldStatusIndicator('ean');\n                                        @endphp"
    "getFieldStatusIndicator\('short_description';\s*@endphp\)" = "getFieldStatusIndicator('short_description');\n                                        @endphp"
    "getFieldStatusIndicator\('long_description';\s*@endphp\)" = "getFieldStatusIndicator('long_description');\n                                        @endphp"
    "getFieldStatusIndicator\('meta_title';\s*@endphp\)" = "getFieldStatusIndicator('meta_title');\n                                        @endphp"
    "getFieldStatusIndicator\('meta_description';\s*@endphp\)" = "getFieldStatusIndicator('meta_description');\n                                        @endphp"
    "getFieldStatusIndicator\('height';\s*@endphp\)" = "getFieldStatusIndicator('height');\n                                        @endphp"
    "getFieldStatusIndicator\('width';\s*@endphp\)" = "getFieldStatusIndicator('width');\n                                        @endphp"
    "getFieldStatusIndicator\('length';\s*@endphp\)" = "getFieldStatusIndicator('length');\n                                        @endphp"
    "getFieldStatusIndicator\('weight';\s*@endphp\)" = "getFieldStatusIndicator('weight');\n                                        @endphp"
    "getFieldStatusIndicator\('tax_rate';\s*@endphp\)" = "getFieldStatusIndicator('tax_rate');\n                                        @endphp"
    "getCategoryStatusIndicator\(;\s*@endphp\)" = "getCategoryStatusIndicator();\n                                        @endphp"
}

Write-Host "Applying fixes..." -ForegroundColor Yellow
$fixCount = 0
foreach ($pattern in $fixes.Keys) {
    $replacement = $fixes[$pattern]
    if ($content -match $pattern) {
        $content = $content -replace $pattern, $replacement
        $fixCount++
        Write-Host "  Fixed: $pattern" -ForegroundColor Green
    }
}

Write-Host "`nTotal fixes applied: $fixCount" -ForegroundColor Cyan

# Save with UTF8 encoding (no BOM for Blade files)
Write-Host "Saving file..." -ForegroundColor Cyan
[System.IO.File]::WriteAllText($filePath, $content, [System.Text.UTF8Encoding]::new($false))

Write-Host "`n=== FIX COMPLETED ===" -ForegroundColor Green
Write-Host "File: product-form.blade.php" -ForegroundColor White
Write-Host "Fixed: $fixCount broken @php blocks" -ForegroundColor White
