# Simple Production Check
$HostidoKey = "D:\SSH\Hostido\HostidoSSHNoPass.ppk"
$Host379 = "host379076@host379076.hostido.net.pl"
$Port = 64321
$LaravelPath = "domains/ppm.mpptrade.pl/public_html"

Write-Host "=== Production Jobs Check ===" -ForegroundColor Cyan

# 1. Check migration status for job-related tables
Write-Host "`n1. Migration status for job tables:" -ForegroundColor Yellow
plink -ssh $Host379 -P $Port -i $HostidoKey -batch "cd $LaravelPath && php artisan migrate:status 2>&1 | grep -E 'job|queue|sync'"

# 2. Check sync_jobs count via raw SQL
Write-Host "`n2. Sync jobs counts:" -ForegroundColor Yellow
plink -ssh $Host379 -P $Port -i $HostidoKey -batch "cd $LaravelPath && php -r `"
require 'vendor/autoload.php';
\`$app = require 'bootstrap/app.php';
\`$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
try {
    \`$pdo = DB::connection()->getPdo();

    // Check if sync_jobs exists
    \`$result = \`$pdo->query('SHOW TABLES LIKE \\\"sync_jobs\\\"');
    if (\`$result->rowCount() > 0) {
        \`$pending = \`$pdo->query('SELECT COUNT(*) FROM sync_jobs WHERE status = \\\"pending\\\"')->fetchColumn();
        \`$running = \`$pdo->query('SELECT COUNT(*) FROM sync_jobs WHERE status = \\\"running\\\"')->fetchColumn();
        \`$total = \`$pdo->query('SELECT COUNT(*) FROM sync_jobs')->fetchColumn();
        echo 'sync_jobs: pending=' . \`$pending . ', running=' . \`$running . ', total=' . \`$total . PHP_EOL;
    } else {
        echo 'sync_jobs table NOT FOUND' . PHP_EOL;
    }

    // Check if jobs exists
    \`$result = \`$pdo->query('SHOW TABLES LIKE \\\"jobs\\\"');
    if (\`$result->rowCount() > 0) {
        \`$jobs = \`$pdo->query('SELECT COUNT(*) FROM jobs')->fetchColumn();
        echo 'jobs queue: ' . \`$jobs . PHP_EOL;
    } else {
        echo 'jobs table NOT FOUND' . PHP_EOL;
    }

    // Check job_progress
    \`$result = \`$pdo->query('SHOW TABLES LIKE \\\"job_progress\\\"');
    if (\`$result->rowCount() > 0) {
        \`$progress = \`$pdo->query('SELECT COUNT(*) FROM job_progress WHERE status = \\\"pending\\\" OR status = \\\"running\\\"')->fetchColumn();
        echo 'job_progress pending/running: ' . \`$progress . PHP_EOL;
    } else {
        echo 'job_progress table NOT FOUND' . PHP_EOL;
    }
} catch (Exception \`$e) {
    echo 'Error: ' . \`$e->getMessage() . PHP_EOL;
}
`""

Write-Host "`n=== Done ===" -ForegroundColor Cyan
