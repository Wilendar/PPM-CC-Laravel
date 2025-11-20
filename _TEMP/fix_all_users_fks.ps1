# Fix All FK to users table in migrations
# Zamienia wszystkie FK do users na komentarze z notatką o OAuth phase

$migrationFiles = @(
    "2024_01_01_000017_create_audit_logs_table.php",
    "2024_01_01_000020_create_oauth_audit_logs_table.php",
    "2024_01_01_000030_create_system_settings_table.php",
    "2024_01_01_000031_create_backup_jobs_table.php",
    "2024_01_01_000032_create_maintenance_tasks_table.php",
    "2024_01_01_000033_create_admin_notifications_table.php",
    "2024_01_01_000034_create_system_reports_table.php",
    "2024_01_01_000035_create_api_usage_logs_table.php",
    "2025_09_17_000001_create_price_history_table.php",
    "2025_10_08_151342_create_import_jobs_table.php",
    "2025_10_17_100013_create_vehicle_compatibility_table.php",
    "2025_11_04_100003_create_conflict_logs_table.php"
)

$basePath = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\database\migrations"
$fixed = 0
$skipped = 0

foreach ($file in $migrationFiles) {
    $filePath = Join-Path $basePath $file

    if (!(Test-Path $filePath)) {
        Write-Host "SKIP: $file (nie istnieje)" -ForegroundColor Yellow
        $skipped++
        continue
    }

    $content = Get-Content $filePath -Raw -Encoding UTF8
    $originalContent = $content

    # Pattern 1: $table->foreign('column')->references('id')->on('users')->...
    $content = $content -replace "(\s+)`$`table->foreign\('([^']+)'\)->references\('id'\)->on\('users'\)([^;]+);", "`$1// Note: FK to users will be added later (OAuth implementation phase)`n`$1// `$`table->foreign('`$2')->references('id')->on('users')`$3;"

    # Pattern 2: $table->foreignId('column')->constrained('users')->...
    $content = $content -replace "(\s+)`$`table->foreignId\('([^']+)'\)(\s*->nullable\(\))?\s*->constrained\('users'\)([^;]+);", "`$1`$`table->unsignedBigInteger('`$2')`$3->comment('Users.id - will be constrained after OAuth implementation');`n`$1// Note: FK to users will be added later (OAuth implementation phase)`n`$1// `$`table->foreign('`$2')->references('id')->on('users')`$4;"

    if ($content -ne $originalContent) {
        Set-Content $filePath -Value $content -Encoding UTF8 -NoNewline
        Write-Host "FIXED: $file" -ForegroundColor Green
        $fixed++
    } else {
        Write-Host "NO CHANGE: $file" -ForegroundColor Gray
    }
}

Write-Host "`n=== SUMMARY ===" -ForegroundColor Cyan
Write-Host "Fixed: $fixed files" -ForegroundColor Green
Write-Host "Skipped: $skipped files" -ForegroundColor Yellow
