# Check if wire:poll is in deployed file
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
Write-Host "Checking deployed job-progress-bar.blade.php for wire:poll..." -ForegroundColor Cyan
$cmd = "head -10 domains/ppm.mpptrade.pl/public_html/resources/views/livewire/components/job-progress-bar.blade.php"
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch $cmd
