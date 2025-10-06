# 🚀 Deploy ETAP_07 FAZA 1C - Sync Strategies to Hostido

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$HostidoHost = "host379076@host379076.hostido.net.pl"
$HostidoPort = 64321
$LocalPath = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"

$Green = "`e[32m"; $Red = "`e[31m"; $Cyan = "`e[36m"; $Reset = "`e[0m"
function Write-Info { param([string]$Text) Write-Host "${Cyan}$Text${Reset}" }
function Write-Success { param([string]$Text) Write-Host "${Green}$Text${Reset}" }
function Write-Error { param([string]$Text) Write-Host "${Red}$Text${Reset}" }

Write-Info "🚀 Deploy ETAP_07 FAZA 1C - Sync Strategies"
Write-Info "=========================================="

$pscp = "C:\Program Files\PuTTY\pscp.exe"
if (!(Test-Path $pscp)) {
    Write-Error "❌ pscp.exe not found"
    exit 1
}

$files = @(
    @{
        Local = "$LocalPath\app\Services\PrestaShop\Sync\ProductSyncStrategy.php"
        Remote = "domains/ppm.mpptrade.pl/public_html/app/Services/PrestaShop/Sync/ProductSyncStrategy.php"
    },
    @{
        Local = "$LocalPath\app\Services\PrestaShop\Sync\CategorySyncStrategy.php"
        Remote = "domains/ppm.mpptrade.pl/public_html/app/Services/PrestaShop/Sync/CategorySyncStrategy.php"
    }
)

$uploadedCount = 0
foreach ($file in $files) {
    if (!(Test-Path $file.Local)) {
        Write-Error "❌ File not found: $($file.Local)"
        continue
    }

    $fileName = Split-Path $file.Local -Leaf
    Write-Info "📤 Uploading: $fileName"

    try {
        $remotePath = "${HostidoHost}:$($file.Remote)"
        & $pscp -i $HostidoKey -P $HostidoPort $file.Local $remotePath

        if ($LASTEXITCODE -eq 0) {
            Write-Success "✅ Uploaded: $fileName"
            $uploadedCount++
        } else {
            Write-Error "❌ Upload failed: $fileName"
        }
    } catch {
        Write-Error "❌ Exception: $_"
    }
}

Write-Info ""
Write-Info "📊 SUMMARY: $uploadedCount / $($files.Count) uploaded"

if ($uploadedCount -eq $files.Count) {
    Write-Success "🎉 All files uploaded successfully!"
    Write-Info "✅ FAZA 1C (Sync Strategies) COMPLETED"
    exit 0
} else {
    Write-Error "⚠️ Not all files uploaded!"
    exit 1
}
