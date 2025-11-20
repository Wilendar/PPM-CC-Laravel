$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

& pscp -i $HostidoKey -P 64321 -q "_TEMP/cleanup_test_job.php" "$RemoteBase/_TEMP/cleanup_test_job.php"
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php _TEMP/cleanup_test_job.php"
