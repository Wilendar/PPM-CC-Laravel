# Fix Claude Code cache paths after project relocation
# Old path: D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel
# New path: D:\Skrypty\PPM-CC-Laravel

$cacheDir = "C:\Users\kamil\.claude\projects\d--Skrypty-PPM-CC-Laravel"
$oldPath = "D:\\OneDrive - MPP TRADE\\Skrypty\\PPM-CC-Laravel"
$newPath = "D:\\Skrypty\\PPM-CC-Laravel"

# Also handle forward slashes and other variations
$oldPathVariants = @(
    "D:\\OneDrive - MPP TRADE\\Skrypty\\PPM-CC-Laravel",
    "D:/OneDrive - MPP TRADE/Skrypty/PPM-CC-Laravel",
    "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"
)

$newPathVariants = @(
    "D:\\Skrypty\\PPM-CC-Laravel",
    "D:/Skrypty/PPM-CC-Laravel",
    "D:\Skrypty\PPM-CC-Laravel"
)

Write-Host "Starting cache path fix..." -ForegroundColor Cyan
Write-Host "Cache directory: $cacheDir" -ForegroundColor Gray

$files = Get-ChildItem -Path $cacheDir -Filter "*.jsonl" -Recurse -ErrorAction SilentlyContinue
$totalFiles = $files.Count
$modifiedCount = 0

Write-Host "Found $totalFiles .jsonl files to process" -ForegroundColor Yellow

foreach ($file in $files) {
    try {
        $content = Get-Content $file.FullName -Raw -Encoding UTF8 -ErrorAction Stop
        $originalContent = $content

        # Replace all variants
        for ($i = 0; $i -lt $oldPathVariants.Count; $i++) {
            $content = $content -replace [regex]::Escape($oldPathVariants[$i]), $newPathVariants[$i]
        }

        # Check if content changed
        if ($content -ne $originalContent) {
            Set-Content -Path $file.FullName -Value $content -Encoding UTF8 -NoNewline
            $modifiedCount++
            Write-Host "  Modified: $($file.Name)" -ForegroundColor Green
        }
    }
    catch {
        Write-Host "  Error processing $($file.Name): $_" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "Done! Modified $modifiedCount of $totalFiles files." -ForegroundColor Cyan
