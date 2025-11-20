$file = 'D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\resources\views\livewire\products\management\product-form.blade.php'
$content = Get-Content -Path $file -Raw -Encoding UTF8

# Simple replace for line 791
$old = '<option value="custom">Własna stawka...</option>'
$new = '<option value="custom" class="tax-option-custom">Własna stawka...</option>'

# Count occurrences BEFORE
$countBefore = ([regex]::Matches($content, [regex]::Escape($old))).Count

# Replace ALL occurrences (both DEFAULT MODE line 773 and SHOP MODE line 791)
$content = $content.Replace($old, $new)

# Count occurrences AFTER
$countAfter = ([regex]::Matches($content, [regex]::Escape($new))).Count

# Save
[System.IO.File]::WriteAllText($file, $content, (New-Object System.Text.UTF8Encoding $true))

Write-Host "DONE: Replaced $countBefore occurrences" -ForegroundColor Green
Write-Host "Verified: $countAfter instances of tax-option-custom class" -ForegroundColor Cyan
