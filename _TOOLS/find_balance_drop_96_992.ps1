# Find where balance drops below 3 between lines 96-992
$file = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\category-form-server.blade.php"
$lines = Get-Content $file

Write-Host "`n=== FINDING BALANCE DROP BETWEEN LINES 96-992 ===" -ForegroundColor Cyan

# Count balance up to line 96
$balance = 0
for($i = 92; $i -lt 96; $i++) {
    $opens = ([regex]::Matches($lines[$i], '<div[^>]*>')).Count
    $closes = ([regex]::Matches($lines[$i], '</div>')).Count
    $balance += $opens - $closes
}

Write-Host "Balance at line 96 (after enterprise-card opens): $balance" -ForegroundColor Yellow
Write-Host ""

# Track from line 97 to 992
for($i = 96; $i -lt 992; $i++) {
    $line = $lines[$i]
    $lineNum = $i + 1

    $opens = ([regex]::Matches($line, '<div[^>]*>')).Count
    $closes = ([regex]::Matches($line, '</div>')).Count

    $balanceBefore = $balance
    $balance += $opens - $closes

    # Show when balance drops below 3 or equals 2
    if($balance -lt 3) {
        $change = ""
        if($opens -gt 0) { $change += "+$opens " }
        if($closes -gt 0) { $change += "-$closes " }

        Write-Host ("[Line {0}] {1} balance: {2} → {3} | {4}" -f $lineNum, $change.PadRight(8), $balanceBefore, $balance, $line.Trim().Substring(0, [Math]::Min(70, $line.Trim().Length))) -ForegroundColor Red
    }
}

Write-Host "`n=== ANALYSIS ===" -ForegroundColor Yellow
Write-Host "Enterprise-card opens at line 96 with balance = 3"
Write-Host "Enterprise-card should close at line 992 with balance dropping from 3 to 2"
Write-Host "But actual balance at line 992 is: $balance"

if($balance -eq 2) {
    Write-Host "`n✅ Balance is correct (2 = main-container + left-column)" -ForegroundColor Green
} else {
    Write-Host "`n❌ Balance is WRONG! Expected 2, got $balance" -ForegroundColor Red
}