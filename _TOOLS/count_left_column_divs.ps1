$file = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\resources\views\livewire\products\management\product-form.blade.php"
$lines = Get-Content $file

$open = 0
$close = 0

# Count from line 95 (left-column opens) to 1101 (left-column closes)
for($i=94; $i -lt 1101; $i++){
    $opens = ([regex]::Matches($lines[$i], '<div[^>]*>')).Count
    $closes = ([regex]::Matches($lines[$i], '</div>')).Count
    $open += $opens
    $close += $closes
}

Write-Host "Left-column section (lines 95-1101):"
Write-Host "Opening divs: $open"
Write-Host "Closing divs: $close"
Write-Host "Balance: $($open - $close)"
Write-Host "`nExpected: +1 (left-column itself should remain open)"