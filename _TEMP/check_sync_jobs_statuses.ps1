$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "Checking sync_jobs statuses distribution..." -ForegroundColor Cyan
Write-Host ""

& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch @"
cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute="
\$stats = DB::table('sync_jobs')
    ->select('status', DB::raw('COUNT(*) as count'), DB::raw('MIN(created_at) as oldest'), DB::raw('MAX(created_at) as newest'))
    ->groupBy('status')
    ->get();

echo '\n=== SYNC JOBS STATUS DISTRIBUTION ===\n';
foreach (\$stats as \$stat) {
    echo sprintf('%-25s: %4d jobs (oldest: %s, newest: %s)\n',
        \$stat->status,
        \$stat->count,
        \$stat->oldest,
        \$stat->newest
    );
}

\$total = DB::table('sync_jobs')->count();
echo sprintf('\nTOTAL: %d jobs\n', \$total);
"
"@
