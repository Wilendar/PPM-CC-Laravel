# Check prestashop_mapping data in database via MySQL
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

$cmd = "mysql -u host379076_ppm -pqkS4FuXMMDDN4DJhatg6 host379076_ppm -e 'SELECT id, sync_status, prestashop_mapping FROM media LIMIT 5;'"

Write-Host "Checking prestashop_mapping data..." -ForegroundColor Cyan
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch $cmd
