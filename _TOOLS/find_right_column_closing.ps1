# Find where right-column closes
$file = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\resources\views\livewire\products\management\product-form.blade.php"
$lines = Get-Content $file

Write-Host "`n=== FINDING RIGHT-COLUMN CLOSING ===" -ForegroundColor Cyan

$rightStart = 1103  # Line 1104 (0-indexed: 1103)
$balance = 1  # We opened right-column

Write-Host "[Line 1104] RIGHT-COLUMN OPENS (balance: +1)" -ForegroundColor Yellow

for($i = $rightStart + 1; $i -lt $lines.Count; $i++) {
    $line = $lines[$i]

    $opens = ([regex]::Matches($line, '<div[^>]*>')).Count
    $closes = ([regex]::Matches($line, '</div>')).Count

    if($opens -gt 0) {
        $balance += $opens
    }

    if($closes -gt 0) {
        $balance -= $closes

        $lineNum = $i + 1
        Write-Host "[Line $lineNum] -$closes divs (balance: $balance): $($line.Trim().Substring(0, [Math]::Min(80, $line.Trim().Length)))" -ForegroundColor $(if($balance -eq 0){'Green'}else{'Gray'})

        if($balance -eq 0) {
            Write-Host "`n=== RIGHT-COLUMN CLOSES ON LINE $lineNum ===" -ForegroundColor Green
            Write-Host "Main-container closes on line 1185" -ForegroundColor Yellow

            if($lineNum -lt 1185) {
                Write-Host "`n✅ CORRECT: Right-column closes BEFORE main-container ($lineNum < 1185)" -ForegroundColor Green
            } else {
                Write-Host "`n❌ PROBLEM: Right-column closes AFTER main-container ($lineNum > 1185)" -ForegroundColor Red
            }
            break
        }
    }
}