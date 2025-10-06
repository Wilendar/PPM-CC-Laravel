# Track balance from line 93 (main-container opens) to line 995 (form footer opens)
$file = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\category-form-server.blade.php"
$lines = Get-Content $file

Write-Host "`n=== DIV BALANCE FROM LINE 93 TO 995 ===" -ForegroundColor Cyan

$balance = 0

for($i = 92; $i -lt 995; $i++) {
    $line = $lines[$i]
    $lineNum = $i + 1

    $opens = ([regex]::Matches($line, '<div[^>]*>')).Count
    $closes = ([regex]::Matches($line, '</div>')).Count

    $balanceBefore = $balance
    $balance += $opens - $closes

    # Show key lines and lines with changes
    $isKey = ($lineNum -in @(93, 95, 96, 990, 991, 992, 994, 995))
    $hasChange = ($opens -gt 0 -or $closes -gt 0)

    if($isKey -or $hasChange) {
        $change = ""
        if($opens -gt 0) { $change += "+$opens " }
        if($closes -gt 0) { $change += "-$closes " }

        $color = 'Gray'
        if($lineNum -eq 93) { $color = 'Yellow' }  # main-container opens
        if($lineNum -eq 95) { $color = 'Cyan' }    # left-column opens
        if($lineNum -eq 96) { $color = 'Green' }   # enterprise-card opens
        if($lineNum -eq 992) { $color = 'Magenta' } # enterprise-card should close

        if($isKey -or $hasChange) {
            Write-Host ("[Line {0}] {1} balance: {2} → {3} | {4}" -f $lineNum, $change.PadRight(8), $balanceBefore, $balance, $line.Trim().Substring(0, [Math]::Min(70, $line.Trim().Length))) -ForegroundColor $color
        }
    }
}

Write-Host "`n=== ANALYSIS ===" -ForegroundColor Yellow
Write-Host "Expected balance at line 995: 2 (main-container + left-column still open)"
Write-Host "Actual balance at line 995: $balance"

if($balance -ne 2) {
    Write-Host "`n❌ PROBLEM: Balance should be 2, but is $balance" -ForegroundColor Red
    Write-Host "This means: $(2 - $balance) extra closing divs between lines 93-994"
} else {
    Write-Host "`n✅ Balance is correct!" -ForegroundColor Green
}