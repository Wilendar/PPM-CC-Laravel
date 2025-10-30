$snapshotPath = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\_TEMP\claude_session_state.json"

$snapshot = Get-Content $snapshotPath -Raw -Encoding UTF8 | ConvertFrom-Json
$age = (Get-Date) - $snapshot.timestamp

Write-Host "Age (minutes): $([math]::Round($age.TotalMinutes))" -ForegroundColor Cyan
Write-Host "Age < 24h: $($age.TotalHours -lt 24)" -ForegroundColor Green

Write-Host "`nContext: $($snapshot.context_summary)" -ForegroundColor Yellow
Write-Host "Agent: $($snapshot.agent_in_progress)" -ForegroundColor Magenta

# Count tasks
$completed = ($snapshot.todos | Where-Object {$_.status -eq 'completed'}).Count
$inProgress = ($snapshot.todos | Where-Object {$_.status -eq 'in_progress'}).Count
$pending = ($snapshot.todos | Where-Object {$_.status -eq 'pending'}).Count

Write-Host "`nTODO Status:" -ForegroundColor White
Write-Host "  Completed: $completed" -ForegroundColor Green
Write-Host "  In Progress: $inProgress" -ForegroundColor Yellow
Write-Host "  Pending: $pending" -ForegroundColor Red

Write-Host "`nInterrupted task:" -ForegroundColor Yellow
$interruptedTask = $snapshot.todos | Where-Object {$_.status -eq 'in_progress'} | Select-Object -First 1
if ($interruptedTask) {
    Write-Host "  $($interruptedTask.content)" -ForegroundColor Yellow
}
else {
    Write-Host "  (none)" -ForegroundColor Gray
}
