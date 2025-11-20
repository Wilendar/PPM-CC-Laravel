# PowerShell script to fix groups parsing in AddShop.php
# Applies defensive parsing pattern to handle both wrapped and direct JSON structures

$filePath = "app/Http/Livewire/Admin/Shops/AddShop.php"

Write-Host "Fixing groups parsing in $filePath..." -ForegroundColor Cyan

# Read file content
$content = Get-Content $filePath -Raw

# Define old code (problematic)
$oldCode = @'
                foreach ($groups as $group) {
                    // Extract group data
                    $groupData = is_array($group['group']) ? $group['group'] : $group;
'@

# Define new code (defensive)
$newCode = @'
                foreach ($groups as $group) {
                    // DEFENSIVE PARSING: Support both wrapped and direct structures
                    // Check if 'group' key exists BEFORE accessing it
                    if (isset($group['group'])) {
                        // Wrapped structure: ['group' => ['id' => 1, 'name' => 'Guest']]
                        $groupData = is_array($group['group']) ? $group['group'] : $group;
                    } else {
                        // Direct structure: ['id' => 1, 'name' => 'Guest'] (standard JSON)
                        $groupData = $group;
                    }
'@

# Check if old code exists
if ($content -like "*$oldCode*") {
    # Apply fix
    $content = $content.Replace($oldCode, $newCode)

    # Write back to file
    Set-Content $filePath -Value $content -NoNewline

    Write-Host "SUCCESS: File updated with defensive parsing pattern" -ForegroundColor Green
    Write-Host ""
    Write-Host "Changes applied:" -ForegroundColor Yellow
    Write-Host "- Added isset() check before accessing 'group' key" -ForegroundColor Yellow
    Write-Host "- Supports both wrapped and direct JSON structures" -ForegroundColor Yellow

    exit 0
} else {
    Write-Host "ERROR: Old code pattern not found - file may have been already modified" -ForegroundColor Red
    exit 1
}
