# Benchmark kompresji - porownanie metod dla projektu Laravel
Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

$ProjectRoot = (Resolve-Path "$PSScriptRoot\..\..").Path
$tempDir = Join-Path $env:TEMP "PPM-Backup-2026-03-09"
$7zExe = "C:\Program Files\7-Zip\7z.exe"

# --- Progress bar helper ---
function Write-Bar {
    param([long]$Current, [long]$Total, [string]$Label = '', [int]$Width = 30)
    if ($Total -le 0) { return }
    $ratio = [double]$Current / [double]$Total
    $pct = [math]::Floor($ratio * 100)
    $filled = [math]::Floor($ratio * $Width)
    $empty = $Width - $filled
    $bar = ([char]0x2588).ToString() * $filled
    $trail = ([char]0x2591).ToString() * $empty
    $line = "  [{0}{1}] {2,3}%  {3}" -f $bar, $trail, $pct, $Label
    Write-Host "`r$($line.PadRight(90))" -NoNewline
}

# --- 7z with progress tracking (monitors output file size) ---
function Invoke-7zWithProgress {
    param([string[]]$Args7z, [string]$OutFile, [long]$SourceBytes, [string]$TestName)

    $job = Start-Job -ScriptBlock {
        param($exe, $args7z)
        & $exe @args7z 2>&1
    } -ArgumentList $7zExe, $Args7z

    $spinChars = @('|', '/', '-', '\')
    $spinIdx = 0
    $startTime = [System.Diagnostics.Stopwatch]::StartNew()

    while ($job.State -eq 'Running') {
        $spinIdx++
        $spin = $spinChars[$spinIdx % 4]
        $currentSize = 0
        if (Test-Path $OutFile) {
            $currentSize = (Get-Item $OutFile -ErrorAction SilentlyContinue).Length
        }
        $currentMB = [math]::Round($currentSize / 1MB, 1)
        $elapsed = $startTime.Elapsed.TotalSeconds
        $speed = if ($elapsed -gt 1) { [math]::Round($currentMB / $elapsed, 1) } else { "..." }
        $barCurrent = if ($currentSize -lt $SourceBytes) { $currentSize } else { $SourceBytes }
        Write-Bar -Current ([long]$barCurrent) -Total ([long]$SourceBytes) -Label "$spin  $currentMB MB written  ($speed MB/s)"
        Start-Sleep -Milliseconds 500
    }

    $startTime.Stop()
    $result = Receive-Job -Job $job
    Remove-Job -Job $job -Force

    # Final size
    $finalSize = (Get-Item $OutFile).Length
    $finalMB = [math]::Round($finalSize / 1MB, 1)
    $totalSpeed = [math]::Round(($SourceBytes / 1MB) / $startTime.Elapsed.TotalSeconds, 1)
    Write-Bar -Current $SourceBytes -Total $SourceBytes -Label "Done! $finalMB MB ($totalSpeed MB/s)"
    Write-Host ""

    return @{
        SizeMB = $finalMB
        SpeedMBs = $totalSpeed
        Elapsed = $startTime.Elapsed
    }
}

Write-Host ""
Write-Host "=== BENCHMARK KOMPRESJI PPM ===" -ForegroundColor Cyan
Write-Host ""

# Ensure temp mirror exists
if (-not (Test-Path $tempDir)) {
    Write-Host "Tworzenie temp mirror..." -ForegroundColor Yellow
    $xdPaths = @(
        "node_modules","vendor",".git","storage\logs",
        "storage\framework\cache","storage\framework\sessions",
        "storage\framework\views",".claude",".subtask",
        ".coordination",".parallel-work",".playwright-mcp",
        "_TEMP","_BACKUP","_ARCHIVE","_DIAGNOSTICS"
    ) | ForEach-Object { Join-Path $ProjectRoot $_ }
    & robocopy $ProjectRoot $tempDir /MIR /NFL /NDL /NJH /NJS /NP /XF nul /XD @xdPaths 2>&1 | Out-Null
}

$files = Get-ChildItem $tempDir -Recurse -File
$fileCount = $files.Count
$totalBytes = ($files | Measure-Object Length -Sum).Sum
$totalMB = [math]::Round($totalBytes / 1MB, 1)
Write-Host "Zrodlo: $fileCount plikow, $totalMB MB" -ForegroundColor Green
Write-Host ""

# --- TEST 1: 7z ZIP deflate multi-threaded mx=5 ---
Write-Host "[TEST 1] 7z ZIP deflate (multi-thread, mx=5)..." -ForegroundColor Yellow
$out1 = Join-Path $env:TEMP "bench_deflate.zip"
Remove-Item $out1 -Force -ErrorAction SilentlyContinue
$r1 = Invoke-7zWithProgress -Args7z @('a', '-tzip', '-mx=5', '-mmt=on', $out1, "$tempDir\*") -OutFile $out1 -SourceBytes $totalBytes -TestName "deflate-5"
Write-Host "  >> Czas: $($r1.Elapsed.ToString('mm\:ss'))  Rozmiar: $($r1.SizeMB) MB  Predkosc: $($r1.SpeedMBs) MB/s" -ForegroundColor Green
Write-Host ""

# --- TEST 2: 7z LZMA2 solid multi-threaded mx=3 ---
Write-Host "[TEST 2] 7z LZMA2 solid (multi-thread, mx=3)..." -ForegroundColor Yellow
$out2 = Join-Path $env:TEMP "bench_lzma2.7z"
Remove-Item $out2 -Force -ErrorAction SilentlyContinue
$r2 = Invoke-7zWithProgress -Args7z @('a', '-t7z', '-mx=3', '-mmt=on', '-ms=on', $out2, "$tempDir\*") -OutFile $out2 -SourceBytes $totalBytes -TestName "lzma2-3"
Write-Host "  >> Czas: $($r2.Elapsed.ToString('mm\:ss'))  Rozmiar: $($r2.SizeMB) MB  Predkosc: $($r2.SpeedMBs) MB/s" -ForegroundColor Green
Write-Host ""

# --- TEST 3: 7z ZIP deflate fast mx=1 ---
Write-Host "[TEST 3] 7z ZIP deflate fast (multi-thread, mx=1)..." -ForegroundColor Yellow
$out3 = Join-Path $env:TEMP "bench_fast.zip"
Remove-Item $out3 -Force -ErrorAction SilentlyContinue
$r3 = Invoke-7zWithProgress -Args7z @('a', '-tzip', '-mx=1', '-mmt=on', $out3, "$tempDir\*") -OutFile $out3 -SourceBytes $totalBytes -TestName "deflate-1"
Write-Host "  >> Czas: $($r3.Elapsed.ToString('mm\:ss'))  Rozmiar: $($r3.SizeMB) MB  Predkosc: $($r3.SpeedMBs) MB/s" -ForegroundColor Green
Write-Host ""

# --- Cleanup ---
Remove-Item $out1, $out2, $out3 -Force -ErrorAction SilentlyContinue

# --- Summary ---
Write-Host "============================================" -ForegroundColor Cyan
Write-Host " PODSUMOWANIE" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  .NET Compress-Archive:     ~$totalMB MB   0.3 MB/s (baseline)" -ForegroundColor DarkGray
Write-Host "  7z ZIP deflate mx=5 (mt):  $($r1.SizeMB) MB  $($r1.SpeedMBs) MB/s  $($r1.Elapsed.ToString('mm\:ss'))" -ForegroundColor Green
Write-Host "  7z LZMA2 solid mx=3 (mt):  $($r2.SizeMB) MB  $($r2.SpeedMBs) MB/s  $($r2.Elapsed.ToString('mm\:ss'))" -ForegroundColor Green
Write-Host "  7z ZIP fast  mx=1 (mt):    $($r3.SizeMB) MB  $($r3.SpeedMBs) MB/s  $($r3.Elapsed.ToString('mm\:ss'))" -ForegroundColor Green
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Nacisnij Enter aby zamknac..." -ForegroundColor Gray
Read-Host
