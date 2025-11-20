$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== CHECKING product_variants STRUCTURE ===" -ForegroundColor Cyan
Write-Host ""

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && mysql -u host379076_ppm -p'cXgv2pS!G,z2wBd' -D host379076_ppm -e 'DESCRIBE product_variants;'"
