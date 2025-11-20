# Production Database Check - category_mappings formats
# This script connects to production database via SSH and analyzes actual data

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteHost = "host379076@host379076.hostido.net.pl"
$Port = 64321
$RemotePath = "domains/ppm.mpptrade.pl/public_html"

Write-Host "=== PRODUCTION DATABASE ANALYSIS: category_mappings ===" -ForegroundColor Cyan
Write-Host ""

# SQL Query to get samples
$sqlQuery = @"
SELECT
    id,
    product_id,
    shop_id,
    category_mappings,
    sync_status,
    last_success_sync_at,
    last_pulled_at
FROM product_shop_data
WHERE category_mappings IS NOT NULL
ORDER BY id DESC
LIMIT 20;
"@

Write-Host "1. Executing SQL query on production..." -ForegroundColor Yellow
$result = plink -ssh $RemoteHost -P $Port -i $HostidoKey -batch "cd $RemotePath && php artisan tinker --execute=`"echo json_encode(\App\Models\ProductShopData::whereNotNull('category_mappings')->orderBy('id', 'desc')->limit(20)->get(['id', 'product_id', 'shop_id', 'category_mappings', 'sync_status', 'last_success_sync_at', 'last_pulled_at'])->toArray());`""

if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ Query executed successfully" -ForegroundColor Green
    Write-Host ""
    Write-Host "Raw output:" -ForegroundColor Gray
    Write-Host $result
    Write-Host ""

    # Try to parse JSON
    try {
        $data = $result | ConvertFrom-Json
        Write-Host "2. PARSED DATA SAMPLES:" -ForegroundColor Yellow
        Write-Host ("-" * 80)

        foreach ($row in $data) {
            Write-Host "ID: $($row.id) | Product: $($row.product_id) | Shop: $($row.shop_id)" -ForegroundColor Cyan
            Write-Host "Status: $($row.sync_status) | Last Sync: $($row.last_success_sync_at)" -ForegroundColor Gray
            Write-Host "category_mappings: $($row.category_mappings | ConvertTo-Json -Compress)" -ForegroundColor White

            # Analyze structure
            if ($row.category_mappings) {
                $mappings = $row.category_mappings | ConvertFrom-Json -AsHashtable -ErrorAction SilentlyContinue

                if ($mappings) {
                    $hasSelected = $mappings.ContainsKey('selected')
                    $hasPrimary = $mappings.ContainsKey('primary')

                    Write-Host "FORMAT DETECTION:" -ForegroundColor Yellow
                    Write-Host "  - Has 'selected' key: $hasSelected" -ForegroundColor $(if ($hasSelected) { 'Green' } else { 'Red' })
                    Write-Host "  - Has 'primary' key: $hasPrimary" -ForegroundColor $(if ($hasPrimary) { 'Green' } else { 'Red' })

                    if (-not $hasSelected -and -not $hasPrimary) {
                        $keys = $mappings.Keys
                        $values = $mappings.Values

                        Write-Host "  - Keys: $($keys -join ', ')" -ForegroundColor Gray
                        Write-Host "  - Values: $($values -join ', ')" -ForegroundColor Gray

                        # Check if key == value
                        $firstKey = $keys[0]
                        $firstValue = $mappings[$firstKey]

                        if ($firstKey -eq $firstValue) {
                            Write-Host "  - LIKELY FORMAT: PrestaShop→PrestaShop (pullShopData)" -ForegroundColor Magenta
                        } else {
                            Write-Host "  - LIKELY FORMAT: PPM→PrestaShop (CategoryMapper)" -ForegroundColor Blue
                        }
                    } elseif ($hasSelected -or $hasPrimary) {
                        Write-Host "  - LIKELY FORMAT: UI Format (ProductFormSaver)" -ForegroundColor Yellow
                    }
                }
            }

            Write-Host ""
        }
    } catch {
        Write-Host "⚠ Failed to parse JSON: $_" -ForegroundColor Red
        Write-Host "Raw result: $result" -ForegroundColor Gray
    }
} else {
    Write-Host "✗ Query failed with exit code: $LASTEXITCODE" -ForegroundColor Red
    Write-Host "Output: $result" -ForegroundColor Gray
}

Write-Host ""
Write-Host "=== DIAGNOSIS COMPLETE ===" -ForegroundColor Cyan
