# PPM-CC-Laravel - UI/UX Standards Compliance Fix Deployment
# Date: 2025-10-29
# Agent: frontend-specialist

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteHost = "host379076@host379076.hostido.net.pl"
$RemotePort = 64321
$RemoteBase = "domains/ppm.mpptrade.pl/public_html"

Write-Host "`n=== PPM UI/UX Standards Fix Deployment ===`n" -ForegroundColor Cyan

# Step 1: Upload ALL assets (CRITICAL - all files have new hashes!)
Write-Host "[1/5] Uploading ALL build assets..." -ForegroundColor Yellow
pscp -i $HostidoKey -P $RemotePort -r `
  "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\public\build\assets\*" `
  "${RemoteHost}:${RemoteBase}/public/build/assets/"

if ($LASTEXITCODE -eq 0) {
    Write-Host "Assets uploaded successfully!" -ForegroundColor Green
} else {
    Write-Host "ERROR: Assets upload failed!" -ForegroundColor Red
    exit 1
}

# Step 2: Upload manifest to ROOT (CRITICAL!)
Write-Host "`n[2/5] Uploading manifest.json to ROOT..." -ForegroundColor Yellow
pscp -i $HostidoKey -P $RemotePort `
  "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\public\build\.vite\manifest.json" `
  "${RemoteHost}:${RemoteBase}/public/build/manifest.json"

if ($LASTEXITCODE -eq 0) {
    Write-Host "Manifest uploaded successfully!" -ForegroundColor Green
} else {
    Write-Host "ERROR: Manifest upload failed!" -ForegroundColor Red
    exit 1
}

# Step 3: Clear caches
Write-Host "`n[3/5] Clearing Laravel caches..." -ForegroundColor Yellow
plink -ssh $RemoteHost -P $RemotePort -i $HostidoKey -batch `
  "cd ${RemoteBase} && php artisan view:clear && php artisan cache:clear && php artisan config:clear"

if ($LASTEXITCODE -eq 0) {
    Write-Host "Caches cleared successfully!" -ForegroundColor Green
} else {
    Write-Host "WARNING: Cache clear might have failed" -ForegroundColor Yellow
}

# Step 4: HTTP 200 Verification (MANDATORY!)
Write-Host "`n[4/5] Verifying CSS files (HTTP 200)..." -ForegroundColor Yellow

$cssFiles = @(
    'app-slbyj789.css',              # Main CSS (159 KB)
    'components-_dxPn2YF.css',       # Components CSS (70 KB) - NEW HASH!
    'layout-CBQLZIVc.css',           # Layout CSS
    'category-form-CBqfE0rW.css',    # Category forms
    'category-picker-DcGTkoqZ.css'   # Category picker
)

$allOk = $true
foreach ($file in $cssFiles) {
    $url = "https://ppm.mpptrade.pl/public/build/assets/$file"
    try {
        $response = Invoke-WebRequest -Uri $url -UseBasicParsing -Method Head -ErrorAction Stop
        if ($response.StatusCode -eq 200) {
            Write-Host "   OK $file : HTTP $($response.StatusCode)" -ForegroundColor Green
        } else {
            Write-Host "   WARN $file : HTTP $($response.StatusCode)" -ForegroundColor Yellow
            $allOk = $false
        }
    } catch {
        Write-Host "   ERROR $file : HTTP 404 NOT FOUND!" -ForegroundColor Red
        $allOk = $false
    }
}

if (-not $allOk) {
    Write-Host "`nWARNING: Some CSS files returned non-200 status!" -ForegroundColor Red
    Write-Host "Deployment may be incomplete - check manually!" -ForegroundColor Yellow
}

# Step 5: Screenshot verification reminder
Write-Host "`n[5/5] Next step: Screenshot verification" -ForegroundColor Yellow
Write-Host "Run: node _TOOLS/screenshot_page.cjs 'https://ppm.mpptrade.pl/admin/variants'" -ForegroundColor Cyan

Write-Host "`n=== Deployment Complete ===`n" -ForegroundColor Green
