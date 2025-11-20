$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

& pscp -i $HostidoKey -P 64321 -q "_TEMP/analyze_bug13_changed_fields.php" "$RemoteBase/_TEMP/analyze_bug13_changed_fields.php"
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php _TEMP/analyze_bug13_changed_fields.php"
