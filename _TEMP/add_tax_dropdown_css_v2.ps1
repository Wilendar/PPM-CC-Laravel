# Add Tax Rate Dropdown CSS Styling (FAZA 5.2 UI Enhancement)

$file = 'D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\resources\css\products\product-form.css'
$content = Get-Content -Path $file -Raw -Encoding UTF8

# CSS block to add (at END of file, before closing)
$cssBlock = @"

/* ========================================
   TAX RATE DROPDOWN STYLING (FAZA 5.2 UI Enhancement - 2025-11-14)
   Dropdown options dla Shop Mode z visual differentiation
   ======================================== */

/* Default option - GREEN (zgodnosc z PPM default) */
.tax-option-default {
    background-color: #059669 !important; /* Emerald-600 (green success) */
    color: #ffffff !important;
    font-weight: 600 !important;
}

/* PrestaShop mapped options - WHITE text, DARK background (czytelne) */
.tax-option-mapped {
    background-color: #374151 !important; /* Gray-700 (dark background) */
    color: #f3f4f6 !important; /* Gray-100 (white text) */
    font-weight: 500 !important;
}

/* Custom option - WHITE text with GOLD accent (PPM style) */
.tax-option-custom {
    background-color: #374151 !important; /* Gray-700 (dark background) */
    color: #e0ac7e !important; /* PPM gold accent */
    font-weight: 500 !important;
}

/* Hover states for better interactivity */
.tax-option-default:hover {
    background-color: #047857 !important; /* Emerald-700 (darker green) */
}

.tax-option-mapped:hover {
    background-color: #4b5563 !important; /* Gray-600 (lighter gray) */
}

.tax-option-custom:hover {
    background-color: #4b5563 !important; /* Gray-600 (lighter gray) */
}

"@

# Simply append at END (safest approach)
$content += $cssBlock

# Save with UTF-8 BOM
[System.IO.File]::WriteAllText($file, $content, (New-Object System.Text.UTF8Encoding $true))

Write-Host ""
Write-Host "SUCCESS: Tax dropdown CSS styles added at end of file!" -ForegroundColor Green
Write-Host ""
Write-Host "CSS Classes:" -ForegroundColor Yellow
Write-Host "  - .tax-option-default: Green bg (#059669), white text" -ForegroundColor White
Write-Host "  - .tax-option-mapped: Dark gray bg (#374151), white text (#f3f4f6)" -ForegroundColor White
Write-Host "  - .tax-option-custom: Dark gray bg (#374151), PPM gold (#e0ac7e)" -ForegroundColor White
Write-Host "  - Hover states: Darker/lighter variations" -ForegroundColor White
Write-Host ""
Write-Host "Next: npm run build (CSS changes require rebuild!)" -ForegroundColor Cyan
