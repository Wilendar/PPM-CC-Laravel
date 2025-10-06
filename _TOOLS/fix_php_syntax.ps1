# Fix @php() syntax in product-form.blade.php
# ETAP_07 FAZA 2 - ParseError fix

$file = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\resources\views\livewire\products\management\product-form.blade.php"

Write-Host "Reading file..." -ForegroundColor Cyan
$content = Get-Content $file -Raw

Write-Host "Fixing @php() syntax..." -ForegroundColor Yellow

# Replace @php($var = ...) with proper @php ... @endphp blocks
$content = $content -creplace '@php\((\$[^)]+)\)', '@php
                                            $1;
                                        @endphp'

Write-Host "Saving fixed file..." -ForegroundColor Green
Set-Content $file -Value $content -NoNewline -Encoding UTF8

Write-Host "✓ All @php() occurrences fixed!" -ForegroundColor Green
