$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

# Upload fix script
pscp -i $HostidoKey -P 64321 "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\_TOOLS\fix_test_visual_desc.php" "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/_TOOLS/"

# Run the script
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php _TOOLS/fix_test_visual_desc.php"
