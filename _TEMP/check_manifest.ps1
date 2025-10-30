# Check manifest.json on production
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteHost = "host379076@host379076.hostido.net.pl"
$RemotePort = 64321
$RemotePath = "domains/ppm.mpptrade.pl/public_html"

Write-Host "`n=== CHECKING PRODUCTION MANIFEST ===" -ForegroundColor Cyan

plink -ssh $RemoteHost -P $RemotePort -i $HostidoKey -batch `
  "cd ${RemotePath} && cat public/build/manifest.json"
