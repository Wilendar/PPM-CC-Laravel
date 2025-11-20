# -*- coding: utf-8 -*-
# Add CSS classes to Tax Rate dropdown options (FAZA 5.2 UI Enhancement)

$ErrorActionPreference = 'Stop'
$file = 'D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\resources\views\livewire\products\management\product-form.blade.php'

Write-Host "Reading file with UTF-8..." -ForegroundColor Cyan
$content = Get-Content -Path $file -Raw -Encoding UTF8

Write-Host "Applying CSS class replacements..." -ForegroundColor Cyan

# Replace 1: Add class + checkmark to "use_default" option
$oldPattern1 = '<option value="use_default">Użyj domyślnej PPM'
$newPattern1 = '<option value="use_default" class="tax-option-default">' + [char]0x2713 + ' Użyj domyślnej PPM'
$content = $content -replace [regex]::Escape($oldPattern1), $newPattern1
Write-Host "  [1/3] use_default option: OK" -ForegroundColor Green

# Replace 2: Add class to mapped PrestaShop options
$oldPattern2 = '<option value="{{ $taxRule[''rate''] }}">'
$newPattern2 = '<option value="{{ $taxRule[''rate''] }}" class="tax-option-mapped">'
$content = $content -replace [regex]::Escape($oldPattern2), $newPattern2
Write-Host "  [2/3] mapped options: OK" -ForegroundColor Green

# Replace 3: Add class to custom option (SHOP MODE - line 791)
$oldPattern3 = '@endif' + "`r`n" + '                                    <option value="custom">Własna stawka...</option>'
$newPattern3 = '@endif' + "`r`n" + '                                    <option value="custom" class="tax-option-custom">Własna stawka...</option>'
$content = $content -replace [regex]::Escape($oldPattern3), $newPattern3
Write-Host "  [3/3] custom option: OK" -ForegroundColor Green

# Save with UTF-8 BOM (Blade files need BOM)
Write-Host "Saving file with UTF-8 BOM..." -ForegroundColor Cyan
$Utf8BomEncoding = New-Object System.Text.UTF8Encoding $true
[System.IO.File]::WriteAllText($file, $content, $Utf8BomEncoding)

Write-Host ""
Write-Host "SUCCESS: CSS classes added to dropdown options!" -ForegroundColor Green
Write-Host ""
Write-Host "Changes applied:" -ForegroundColor Yellow
Write-Host "  - use_default option: class='tax-option-default' + checkmark icon" -ForegroundColor White
Write-Host "  - PrestaShop mapped: class='tax-option-mapped'" -ForegroundColor White
Write-Host "  - Custom option: class='tax-option-custom'" -ForegroundColor White
