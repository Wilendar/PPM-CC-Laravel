$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "   TESTING saveAndClose() VIA TINKER" -ForegroundColor Yellow
Write-Host "═══════════════════════════════════════════════════════════════`n" -ForegroundColor Cyan

Write-Host "📋 Creating and calling Livewire component:`n" -ForegroundColor White

$tinkerCmd = @'
use App\Http\Livewire\Products\Management\ProductForm;
use App\Models\Product;

$product = Product::find(11034);
if (!$product) {
    echo "ERROR: Product 11034 not found\n";
    exit(1);
}

echo "Product found: {$product->name}\n";

$component = new ProductForm();
$component->product = $product;
$component->product_id = 11034;

echo "Calling saveAndClose()...\n";

try {
    $result = $component->saveAndClose();
    echo "Method returned: " . var_export($result, true) . "\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\nChecking logs...\n";
'@

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute=`"$tinkerCmd`""

Write-Host "`n═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
