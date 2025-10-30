$snapshotPath = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\_TEMP\claude_session_state.json"

if (Test-Path $snapshotPath) {
    Write-Host "Snapshot EXISTS" -ForegroundColor Green

    $snapshot = Get-Content $snapshotPath -Raw -Encoding UTF8 | ConvertFrom-Json
    Write-Host "Timestamp: $($snapshot.timestamp)" -ForegroundColor Cyan

    try {
        $parsedDate = [DateTime]::Parse($snapshot.timestamp)
        Write-Host "Parsed date: $parsedDate" -ForegroundColor Yellow

        $age = (Get-Date) - $parsedDate
        Write-Host "Age (hours): $($age.TotalHours)" -ForegroundColor Magenta
        Write-Host "Age < 24h: $($age.TotalHours -lt 24)" -ForegroundColor $(if ($age.TotalHours -lt 24) {"Green"} else {"Red"})
    }
    catch {
        Write-Host "ERROR parsing timestamp: $_" -ForegroundColor Red
    }
}
else {
    Write-Host "Snapshot NOT FOUND" -ForegroundColor Red
}
