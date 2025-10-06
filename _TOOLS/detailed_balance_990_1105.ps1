# Detailed div balance analysis from line 990 to 1105
$file = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\category-form-server.blade.php"
$lines = Get-Content $file

Write-Host "`n=== DETAILED DIV BALANCE (Lines 990-1105) ===" -ForegroundColor Cyan

# Start from line 93 to track balance correctly
$balance = 0

# Count up to line 990
for($i = 92; $i -lt 989; $i++) {
    $opens = ([regex]::Matches($lines[$i], '<div[^>]*>')).Count
    $closes = ([regex]::Matches($lines[$i], '</div>')).Count
    $balance += $opens - $closes
}

Write-Host "`nBalance at line 990: $balance" -ForegroundColor Yellow
Write-Host ""

# Detailed output from 990 to 1105
for($i = 989; $i -lt 1105; $i++) {
    $line = $lines[$i]
    $lineNum = $i + 1

    $opens = ([regex]::Matches($line, '<div[^>]*>')).Count
    $closes = ([regex]::Matches($line, '</div>')).Count

    if($opens -gt 0 -or $closes -gt 0) {
        $balanceBefore = $balance
        $balance += $opens - $closes

        $color = 'White'
        if($lineNum -eq 992) { $color = 'Magenta' }  # enterprise-card close?
        if($lineNum -eq 1101) { $color = 'Cyan' }  # left-column close
        if($lineNum -eq 1104) { $color = 'Yellow' }  # right-column open

        $change = ""
        if($opens -gt 0) { $change += "+$opens " }
        if($closes -gt 0) { $change += "-$closes " }

        Write-Host ("[Line {0}] {1} balance: {2} → {3} | {4}" -f $lineNum, $change.PadRight(8), $balanceBefore, $balance, $line.Trim().Substring(0, [Math]::Min(70, $line.Trim().Length))) -ForegroundColor $color
    }
}

Write-Host "`n=== CRITICAL LINES ===" -ForegroundColor Red
Write-Host "Line 992: Should close enterprise-card"
Write-Host "Line 1101: Should close left-column (balance should be 1, not 0!)"
Write-Host "Line 1104: Should open right-column (needs balance >= 1)"