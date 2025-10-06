# Find extra closing divs by analyzing each major section
$file = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\resources\views\livewire\products\management\product-form.blade.php"
$lines = Get-Content $file

Write-Host "`n=== SECTION-BY-SECTION DIV BALANCE ===" -ForegroundColor Cyan

$balance = 0
$sectionStart = 0
$inLeftColumn = $false

for($i = 0; $i -lt $lines.Count; $i++) {
    $line = $lines[$i]

    # Track when we enter left-column
    if($line -match 'class="category-form-left-column"') {
        $inLeftColumn = $true
        $sectionStart = $i
        $balance = 1  # Account for the opening left-column div
        Write-Host "`n[Line $($i+1)] LEFT COLUMN STARTS - balance: +1" -ForegroundColor Yellow
        continue
    }

    # Track when we reach right-column (end of left)
    if($line -match 'class="category-form-right-column"') {
        Write-Host "`n[Line $($i+1)] RIGHT COLUMN STARTS - Final balance: $balance" -ForegroundColor $(if($balance -eq 1){'Green'}else{'Red'})
        if($balance -ne 1) {
            Write-Host "❌ PROBLEM: Expected balance +1 (left-column still open), got $balance" -ForegroundColor Red
        }
        break
    }

    if($inLeftColumn) {
        # Count opening divs
        $opens = ([regex]::Matches($line, '<div[^>]*>')).Count
        if($opens -gt 0) {
            $balance += $opens
            if($opens -gt 2) {
                Write-Host "[Line $($i+1)] +$opens divs: $($line.Trim().Substring(0, [Math]::Min(60, $line.Trim().Length)))..." -ForegroundColor Gray
            }
        }

        # Count closing divs
        $closes = ([regex]::Matches($line, '</div>')).Count
        if($closes -gt 0) {
            $balance -= $closes
            Write-Host "[Line $($i+1)] -$closes divs (balance: $balance): $($line.Trim())" -ForegroundColor $(if($balance -lt 1){'Red'}elseif($balance -eq 1){'Yellow'}else{'White'})

            if($balance -lt 1) {
                Write-Host "  ⚠️ WARNING: Balance went below +1! Possible extra closing div!" -ForegroundColor Red
            }
        }

        # Report major sections
        if($line -match 'activeTab.*===') {
            $tabName = if($line -match "activeTab.*===\s*'(\w+)'") { $matches[1] } else { "unknown" }
            Write-Host "`n--- TAB SECTION: $tabName (Line $($i+1), Balance: $balance) ---" -ForegroundColor Cyan
        }
    }
}

Write-Host "`n=== ANALYSIS COMPLETE ===" -ForegroundColor Cyan