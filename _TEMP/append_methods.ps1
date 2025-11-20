# Append prices/stock methods to ProductForm.php

$phpFile = "app\Http\Livewire\Products\Management\ProductForm.php"
$methodsFile = "_TEMP\prices_stock_methods.txt"

# Read all lines except the last one (closing brace)
$lines = Get-Content $phpFile
$linesWithoutLastBrace = $lines[0..($lines.Length - 2)]

# Write back without last brace
$linesWithoutLastBrace | Set-Content $phpFile -Encoding UTF8

# Append new methods (which include the closing brace)
Get-Content $methodsFile | Add-Content $phpFile -Encoding UTF8

Write-Host "Methods appended successfully!" -ForegroundColor Green
