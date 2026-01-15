$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== RUNNING FEATURE SEEDERS ===" -ForegroundColor Cyan

Write-Host "[1/4] Uploading FeatureGroupsSeeder..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\database\seeders\FeatureGroupsSeeder.php" "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/database/seeders/FeatureGroupsSeeder.php"

Write-Host "[2/4] Uploading VehicleFeaturesSeeder..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\database\seeders\VehicleFeaturesSeeder.php" "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/database/seeders/VehicleFeaturesSeeder.php"

Write-Host "[3/4] Uploading VehicleTemplatesSeeder..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\database\seeders\VehicleTemplatesSeeder.php" "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/database/seeders/VehicleTemplatesSeeder.php"

Write-Host "[4/4] Running VehicleTemplatesSeeder (calls all others)..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan db:seed --class=VehicleTemplatesSeeder --force"

Write-Host "=== DONE ===" -ForegroundColor Green
