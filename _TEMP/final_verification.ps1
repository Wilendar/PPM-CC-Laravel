$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== FINAL VERIFICATION ===" -ForegroundColor Cyan

Write-Host "`n1. Check if CategoryMappingsConverter exists on production:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && test -f app/Services/CategoryMappingsConverter.php && echo 'EXISTS' || echo 'MISSING'"

Write-Host "`n2. Check recent error logs:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && tail -50 storage/logs/laravel.log" | Select-String -Pattern "ERROR|CRITICAL|Exception" -Context 0

Write-Host "`n3. Ready for testing!" -ForegroundColor Green
Write-Host "Test steps:" -ForegroundColor Cyan
Write-Host "  1. Open product 11034" -ForegroundColor White
Write-Host "  2. Switch to Shop Tab (Shop: Pitrally)" -ForegroundColor White
Write-Host "  3. Select/deselect categories" -ForegroundColor White
Write-Host "  4. Click 'Zapisz zmiany' button (sidebar or footer)" -ForegroundColor White
Write-Host "  5. Check if categories are saved to database" -ForegroundColor White
