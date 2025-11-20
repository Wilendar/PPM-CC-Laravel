$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== CHECKING variant_attributes STRUCTURE ===" -ForegroundColor Cyan
Write-Host ""

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute='echo \"=== variant_attributes COLUMNS ===\"; echo json_encode(DB::select(\"DESCRIBE variant_attributes\"), JSON_PRETTY_PRINT);'"
