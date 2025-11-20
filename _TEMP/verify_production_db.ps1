$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "   PRODUCTION DATABASE VERIFICATION" -ForegroundColor Yellow
Write-Host "═══════════════════════════════════════════════════════════════`n" -ForegroundColor Cyan

Write-Host "1️⃣ Checking product_shop_data for product 11034, shop 1...`n" -ForegroundColor White

$tinkerCmd = @"
`$psd = DB::table('product_shop_data')
    ->where('product_id', 11034)
    ->where('shop_id', 1)
    ->first();

if (!`$psd) {
    echo "ERROR: product_shop_data NOT FOUND\n";
    exit(1);
}

echo "Updated at: " . `$psd->updated_at . "\n\n";

`$categoryMappings = json_decode(`$psd->category_mappings, true);

if (!`$categoryMappings) {
    echo "ERROR: category_mappings is NULL\n";
    exit(1);
}

echo "CATEGORY MAPPINGS:\n";
echo json_encode(`$categoryMappings, JSON_PRETTY_PRINT) . "\n\n";

`$uiSelected = `$categoryMappings['ui']['selected'] ?? [];
`$mappings = `$categoryMappings['mappings'] ?? [];

echo "CHECKS:\n";

// PITGANG (PPM 41 → PS 12)
if (in_array(41, `$uiSelected)) {
    echo "✅ PITGANG (PPM 41) FOUND\n";
    `$psId = `$mappings['41'] ?? null;
    echo "   Mapping: PPM 41 → PS `$psId " . (`$psId === 12 ? "✅" : "❌") . "\n";
} else {
    echo "❌ PITGANG (PPM 41) NOT FOUND\n";
}

// Root Baza (PPM 1 → PS 1)
if (in_array(1, `$uiSelected)) {
    echo "✅ Root Baza (PPM 1) FOUND\n";
    `$psId = `$mappings['1'] ?? null;
    echo "   Mapping: PPM 1 → PS `$psId " . (`$psId === 1 ? "✅" : "❌") . "\n";
} else {
    echo "❌ Root Baza (PPM 1) NOT FOUND\n";
}

// Root Wszystko (PPM 36 → PS 2)
if (in_array(36, `$uiSelected)) {
    echo "✅ Root Wszystko (PPM 36) FOUND\n";
    `$psId = `$mappings['36'] ?? null;
    echo "   Mapping: PPM 36 → PS `$psId " . (`$psId === 2 ? "✅" : "❌") . "\n";
} else {
    echo "❌ Root Wszystko (PPM 36) NOT FOUND\n";
}

echo "\nMETADATA:\n";
`$metadata = `$categoryMappings['metadata'] ?? [];
echo "Last updated: " . (`$metadata['last_updated'] ?? 'N/A') . "\n";
echo "Source: " . (`$metadata['source'] ?? 'N/A') . "\n";
"@

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute=`"$tinkerCmd`""

Write-Host "`n═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "   VERIFICATION COMPLETED" -ForegroundColor Green
Write-Host "═══════════════════════════════════════════════════════════════`n" -ForegroundColor Cyan
