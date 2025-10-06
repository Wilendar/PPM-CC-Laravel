# Count opening and closing divs in specific section
$file = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\resources\views\livewire\products\management\product-form.blade.php"
$lines = Get-Content $file

Write-Host "`n=== DIV COUNT IN FORM FOOTER SECTION ===" -ForegroundColor Cyan

$startLine = 994  # Form footer opens
$endLine = 1097   # First closing div

$opening = 0
$closing = 0

for($i = $startLine - 1; $i -lt $endLine; $i++) {
    $line = $lines[$i]

    $opens = ([regex]::Matches($line, '<div[^>]*>')).Count
    $closes = ([regex]::Matches($line, '</div>')).Count

    if($opens -gt 0 -or $closes -gt 0) {
        Write-Host "[Line $($i+1)] +$opens -$closes : $($line.Trim().Substring(0, [Math]::Min(80, $line.Trim().Length)))"
    }

    $opening += $opens
    $closing += $closes
}

Write-Host "`n--- TOTALS ---" -ForegroundColor Yellow
Write-Host "Opening <div>: $opening"
Write-Host "Closing </div>: $closing"
Write-Host "Balance: $($opening - $closing)" -ForegroundColor $(if($opening -eq $closing){' Green'}else{'Red'})
Write-Host "`nExpected: +3 (form footer has 3 opening divs: 994, 995, 997 or 1012)"
Write-Host "Need 3 closing divs to close form footer properly"