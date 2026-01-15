$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "Checking shops table structure..." -ForegroundColor Cyan
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute=\"echo 'Tables:'; \$tables = DB::select('SHOW TABLES'); foreach(\$tables as \$t) { \$n = array_values((array)\$t)[0]; if(strpos(\$n, 'shop') !== false) echo \$n.chr(10); }\""
