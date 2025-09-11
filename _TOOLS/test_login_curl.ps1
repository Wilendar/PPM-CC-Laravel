$ErrorActionPreference = 'Stop'
$ProgressPreference = 'SilentlyContinue'

$dir = Join-Path -Path (Get-Location) -ChildPath '_OTHER/http_test'
New-Item -ItemType Directory -Force -Path $dir | Out-Null
$login = Join-Path $dir 'login.html'
$jar = Join-Path $dir 'cookies.txt'
$adminOut = Join-Path $dir 'admin.html'
$dashOut = Join-Path $dir 'dashboard.html'

& curl.exe -sS 'https://ppm.mpptrade.pl/login' -H 'Cache-Control: no-cache' -c $jar -o $login
$html = Get-Content -Raw $login
$line = ($html -split "`n") | Where-Object { $_ -match 'name="_token"' } | Select-Object -First 1
if (-not $line) { throw 'No _token found' }
$token = [regex]::Match($line, 'value="([^"]+)"').Groups[1].Value
if (-not $token) { throw 'No token value' }

$email = 'admin@mpptrade.pl'
$password = 'Admin123!MPP'
$post = 'email=' + [uri]::EscapeDataString($email) + '&password=' + [uri]::EscapeDataString($password) + '&remember=1&_token=' + [uri]::EscapeDataString($token)

& cmd /c "curl -sS -L -c `"$jar`" -b `"$jar`" -d `"$post`" -o NUL `"https://ppm.mpptrade.pl/login`""

& curl.exe -sS -b $jar -o $adminOut 'https://ppm.mpptrade.pl/admin'
& curl.exe -sS -b $jar -o $dashOut 'https://ppm.mpptrade.pl/dashboard'

Write-Output "Admin.html: $(Get-Item $adminOut).Length bytes"
Write-Output "Dashboard.html: $(Get-Item $dashOut).Length bytes"

