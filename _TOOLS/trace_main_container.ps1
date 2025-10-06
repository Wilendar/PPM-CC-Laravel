# Trace exact opening and closing of main-container
$file = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\category-form-server.blade.php"
$lines = Get-Content $file

Write-Host "`n=== TRACING MAIN-CONTAINER ===" -ForegroundColor Cyan

# Find opening
for($i = 0; $i -lt $lines.Count; $i++) {
    if($lines[$i] -match 'category-form-main-container') {
        $lineNum = $i + 1
        Write-Host "[Line $lineNum] $($lines[$i].Trim())" -ForegroundColor $(if($lines[$i] -match '<div'){' Yellow'}else{'Green'})
    }
}

Write-Host "`n=== ANALYZING DIV BALANCE IN MAIN-CONTAINER ===" -ForegroundColor Cyan

$mainStart = 92  # Line 93 (0-indexed)
$balance = 1  # Opening main-container

Write-Host "[Line 93] MAIN-CONTAINER OPENS (balance: +1)" -ForegroundColor Yellow

for($i = $mainStart + 1; $i -lt $lines.Count; $i++) {
    $line = $lines[$i]

    $opens = ([regex]::Matches($line, '<div[^>]*>')).Count
    $closes = ([regex]::Matches($line, '</div>')).Count

    $balance += $opens
    $balance -= $closes

    $lineNum = $i + 1

    # Show key transitions
    if($lineNum -eq 1101) {
        Write-Host "[Line $lineNum] LEFT-COLUMN CLOSES (balance: $balance)" -ForegroundColor Cyan
    }

    if($lineNum -eq 1104) {
        Write-Host "[Line $lineNum] RIGHT-COLUMN OPENS (balance: $balance)" -ForegroundColor Cyan
    }

    if($balance -eq 0) {
        Write-Host "[Line $lineNum] MAIN-CONTAINER CLOSES (balance: $balance)" -ForegroundColor Green
        Write-Host "$($line.Trim())"
        break
    }
}

Write-Host "`n=== EXPECTED STRUCTURE ===" -ForegroundColor Yellow
Write-Host "Line 93: main-container opens"
Write-Host "Line 95: left-column opens"
Write-Host "Line 1101: left-column closes"
Write-Host "Line 1104: right-column opens"
Write-Host "Line 1184: right-column closes"
Write-Host "Line 1185: main-container closes"