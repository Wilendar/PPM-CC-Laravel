# Analyze div balance in blade template
$file = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\resources\views\livewire\products\management\product-form.blade.php"
$content = Get-Content $file -Raw

# Find sections
$mainStart = $content.IndexOf('class="category-form-main-container"')
$leftStart = $content.IndexOf('class="category-form-left-column"')
$rightStart = $content.IndexOf('class="category-form-right-column"')

Write-Host "`n=== DIV BALANCE ANALYSIS ===" -ForegroundColor Cyan
Write-Host "Main container starts at: $mainStart"
Write-Host "Left column starts at: $leftStart"
Write-Host "Right column starts at: $rightStart"

# Count divs between left and right
$between = $content.Substring($leftStart, $rightStart - $leftStart)
$openDivs = ([regex]::Matches($between, '<div[^>]*>')).Count
$closeDivs = ([regex]::Matches($between, '</div>')).Count

Write-Host "`n--- Between LEFT and RIGHT ---" -ForegroundColor Yellow
Write-Host "Opening <div> tags: $openDivs"
Write-Host "Closing </div> tags: $closeDivs"
Write-Host "Balance: $($openDivs - $closeDivs)" -ForegroundColor $(if($openDivs -eq $closeDivs){'Green'}else{'Red'})

if($openDivs -ne $closeDivs + 1) {
    Write-Host "`n❌ PROBLEM FOUND!" -ForegroundColor Red
    Write-Host "Expected balance: +1 (left-column opening div should be unclosed)"
    Write-Host "Actual balance: $($openDivs - $closeDivs)"
    Write-Host "`nThis means there are EXTRA closing </div> tags that close main-container too early!"
}

Write-Host "`n=== ANALYSIS COMPLETE ===" -ForegroundColor Cyan