$ErrorActionPreference = 'Stop'
$ProgressPreference = 'SilentlyContinue'

$outDir = Join-Path -Path (Get-Location) -ChildPath '_OTHER/http_test'
New-Item -ItemType Directory -Force -Path $outDir | Out-Null

$sess = New-Object Microsoft.PowerShell.Commands.WebRequestSession
$loginUri = 'https://ppm.mpptrade.pl/login'
$res1 = Invoke-WebRequest -Uri $loginUri -WebSession $sess -Headers @{ 'Cache-Control'='no-cache' }
$html = $res1.Content
$loginPath = Join-Path $outDir 'login.html'
$html | Set-Content -Path $loginPath -Encoding UTF8

# Extract CSRF token from hidden input
$token = $null
$regex = 'name="_token"\s+value="([^"]+)"'
$m = [regex]::Match($html, $regex)
if ($m.Success) {
    $token = $m.Groups[1].Value
} else {
    $regex2 = 'meta name="csrf-token"\s+content="([^"]+)"'
    $m2 = [regex]::Match($html, $regex2)
if ($m2.Success) { $token = $m2.Groups[1].Value }
}
if (-not $token) { throw 'CSRF token not found' }

$body = @{ email = 'admin@mpptrade.pl'; password = 'Admin123!MPP'; _token = $token; remember = '1' }

# Post login, capture 302 redirect
try {
    $res2 = Invoke-WebRequest -Uri 'https://ppm.mpptrade.pl/login' -WebSession $sess -Method Post -Body $body -ContentType 'application/x-www-form-urlencoded' -MaximumRedirection 0 -ErrorAction Stop
    $status = [int]$res2.StatusCode
    $location = ''
} catch {
    $resp = $_.Exception.Response
    $status = [int]$resp.StatusCode
    $location = $resp.Headers['Location']
}

$result = [ordered]@{
    PostStatus = $status
    Location   = $location
}

# Follow redirect to target page
if ($location) {
    $next = if ($location -like 'http*') { $location } else { 'https://ppm.mpptrade.pl' + $location }
    $res3 = Invoke-WebRequest -Uri $next -WebSession $sess -Headers @{ 'Cache-Control'='no-cache' }
    $finalHtml = $res3.Content
    $finalPath = Join-Path $outDir 'final.html'
    $finalHtml | Set-Content -Path $finalPath -Encoding UTF8
    $result['FinalUrl'] = $res3.BaseResponse.ResponseUri.AbsoluteUri
    $result['FinalStatus'] = [int]$res3.StatusCode
    $result['HasAdminMarker'] = [bool]([regex]::IsMatch($finalHtml, 'Admin Dashboard|Admin Panel|admin-dashboard', 'IgnoreCase'))
}

# Probe admin and dashboard explicitly using the authenticated session
try {
    $adm = Invoke-WebRequest -Uri 'https://ppm.mpptrade.pl/admin' -WebSession $sess -Headers @{ 'Cache-Control'='no-cache' }
    $result['AdminStatus'] = [int]$adm.StatusCode
    $result['AdminHasMarker'] = [bool]([regex]::IsMatch($adm.Content, 'Admin Dashboard|Admin Panel|admin-dashboard', 'IgnoreCase'))
    $adm.Content | Set-Content -Path (Join-Path $outDir 'admin.html') -Encoding UTF8
} catch {
    $resp = $_.Exception.Response
    if ($resp) { $result['AdminStatus'] = [int]$resp.StatusCode }
}

try {
    $dash = Invoke-WebRequest -Uri 'https://ppm.mpptrade.pl/dashboard' -WebSession $sess -Headers @{ 'Cache-Control'='no-cache' }
    $result['DashboardStatus'] = [int]$dash.StatusCode
    $dash.Content | Set-Content -Path (Join-Path $outDir 'dashboard.html') -Encoding UTF8
} catch {
    $resp = $_.Exception.Response
    if ($resp) { $result['DashboardStatus'] = [int]$resp.StatusCode }
}

$jsonPath = Join-Path $outDir 'result.json'
$result | ConvertTo-Json -Depth 3 | Set-Content -Path $jsonPath -Encoding UTF8
Write-Output (Get-Content -Raw $jsonPath)
