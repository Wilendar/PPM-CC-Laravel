# 🚀 Deploy ETAP_07 Migrations to Hostido
# Upload 3 nowych migracji dla PrestaShop API Integration

# Konfiguracja
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$HostidoHost = "host379076@host379076.hostido.net.pl"
$HostidoPort = 64321
$LocalMigrationsPath = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\database\migrations"
$RemoteMigrationsPath = "domains/ppm.mpptrade.pl/public_html/database/migrations/"

# Kolory PowerShell 7
$Green = "`e[32m"
$Red = "`e[31m"
$Cyan = "`e[36m"
$Yellow = "`e[33m"
$Reset = "`e[0m"

function Write-Info { param([string]$Text) Write-Host "${Cyan}$Text${Reset}" }
function Write-Success { param([string]$Text) Write-Host "${Green}$Text${Reset}" }
function Write-Error { param([string]$Text) Write-Host "${Red}$Text${Reset}" }
function Write-Warning { param([string]$Text) Write-Host "${Yellow}$Text${Reset}" }

Write-Info "🚀 Deploy ETAP_07 Migrations"
Write-Info "================================"

# Sprawdź czy pscp jest dostępne
$pscp = "C:\Program Files\PuTTY\pscp.exe"
if (!(Test-Path $pscp)) {
    Write-Error "❌ pscp.exe nie znalezione w: $pscp"
    Write-Warning "💡 Zainstaluj PuTTY lub użyj WinSCP"
    exit 1
}

Write-Success "✅ pscp.exe znalezione"

# Lista migracji do uploadu
$migrations = @(
    "2025_10_01_000001_create_shop_mappings_table.php",
    "2025_10_01_000002_create_product_sync_status_table.php",
    "2025_10_01_000003_create_sync_logs_table.php"
)

# Upload każdej migracji
$uploadedCount = 0
foreach ($migration in $migrations) {
    $localFile = Join-Path $LocalMigrationsPath $migration

    if (!(Test-Path $localFile)) {
        Write-Error "❌ Nie znaleziono pliku: $migration"
        continue
    }

    Write-Info "📤 Uploading: $migration"

    try {
        $remotePath = "${HostidoHost}:${RemoteMigrationsPath}${migration}"
        & $pscp -i $HostidoKey -P $HostidoPort $localFile $remotePath

        if ($LASTEXITCODE -eq 0) {
            Write-Success "✅ Uploaded: $migration"
            $uploadedCount++
        } else {
            Write-Error "❌ Błąd uploadu: $migration"
        }
    } catch {
        Write-Error "❌ Wyjątek podczas uploadu: $_"
    }
}

Write-Info ""
Write-Info "📊 PODSUMOWANIE"
Write-Info "================================"
Write-Info "Uploaded: $uploadedCount / $($migrations.Count)"

if ($uploadedCount -eq $migrations.Count) {
    Write-Success "🎉 Wszystkie migracje uploadowane pomyślnie!"
    Write-Info ""
    Write-Info "🔧 NASTĘPNE KROKI:"
    Write-Info "1. Uruchom migracje: .\hostido_automation.ps1 -Command 'cd domains/ppm.mpptrade.pl/public_html && php artisan migrate --force'"
    Write-Info "2. Sprawdź status: .\hostido_automation.ps1 -Command 'cd domains/ppm.mpptrade.pl/public_html && php artisan migrate:status'"
    exit 0
} else {
    Write-Error "⚠️ Nie wszystkie migracje zostały uploadowane!"
    exit 1
}
