$hooksPath = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\.claude\hooks"
Set-Location $hooksPath

Get-ChildItem "*.DISABLED" | ForEach-Object {
    $newName = $_.Name -replace '\.DISABLED$', ''
    Rename-Item -Path $_.FullName -NewName $newName -Force
    Write-Host "Przywrocono: $newName" -ForegroundColor Green
}

Write-Host ""
Write-Host "Wszystkie hooki przywrocone!" -ForegroundColor Cyan
