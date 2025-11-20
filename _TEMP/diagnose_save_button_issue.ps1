$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== DIAGNOSIS: Save Button Not Working ===" -ForegroundColor Cyan

Write-Host "`n1. Check database - product_shop_data categories:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute=`"DB::table('product_shop_data')->where('product_id', 11033)->get(['id', 'product_id', 'shop_id', 'category_mappings', 'updated_at'])->each(function(\`$psd) { echo 'ID: ' . \`$psd->id . ' | Product: ' . \`$psd->product_id . ' | Shop: ' . \`$psd->shop_id . PHP_EOL; echo 'Categories: ' . (\`$psd->category_mappings ?? 'NULL') . PHP_EOL; echo 'Updated: ' . \`$psd->updated_at . PHP_EOL . PHP_EOL; });`""

Write-Host "`n2. Search for saveAndClose in logs (should appear when button clicked):" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && tail -200 storage/logs/laravel.log" | Select-String -Pattern "saveAndClose|ProductFormSaver|Saving categories" -Context 1

Write-Host "`n3. Check for Livewire errors:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && tail -100 storage/logs/laravel.log" | Select-String -Pattern "Livewire|Exception|Error" -Context 1

Write-Host "`n=== CONCLUSION ===" -ForegroundColor Cyan
Write-Host "If no 'saveAndClose' logs appear, the button wire:click is not working!" -ForegroundColor Red
