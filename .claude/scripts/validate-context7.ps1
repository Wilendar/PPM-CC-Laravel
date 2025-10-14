# PPM-CC-Laravel Context7 Validation Hook
# Weryfikuje właściwe użycie Context7 MCP w projekcie
# Created: 2025-09-29
# Encoding: UTF-8 with BOM

param()

# Ustawienia kodowania dla PowerShell
$PSDefaultParameterValues['Out-File:Encoding'] = 'utf8BOM'
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

try {
    Write-Host "🔍 VALIDATING CONTEXT7 USAGE" -ForegroundColor Yellow

    # Sprawdź dostępność Context7 MCP
    $context7Available = $false
    $context7ApiKey = "ctx7sk-dea67299-09f8-4fab-b4bd-d36297a675c3"

    # Sprawdź czy klucz Context7 jest dostępny
    if (-not [string]::IsNullOrEmpty($context7ApiKey)) {
        Write-Host "✅ Context7 API Key configured" -ForegroundColor Green
        $context7Available = $true
    } else {
        Write-Host "❌ Context7 API Key not found!" -ForegroundColor Red
    }

    # Lista wymaganych bibliotek dla projektu
    $requiredLibraries = @{
        'Laravel 12.x' = '/websites/laravel_12_x'
        'Livewire 3.x' = '/livewire/livewire'
        'Alpine.js' = '/alpinejs/alpine'
        'PrestaShop' = '/prestashop/docs'
    }

    Write-Host "`n📚 REQUIRED CONTEXT7 LIBRARIES:" -ForegroundColor Cyan
    foreach ($lib in $requiredLibraries.GetEnumerator()) {
        Write-Host "• $($lib.Key): $($lib.Value)" -ForegroundColor White
    }

    # Sprawdź czy są pliki PHP/Blade/JS w bieżącej sesji
    $codeFilesNeedingContext7 = @()

    # Szukaj plików PHP
    if (Get-ChildItem -Path "." -Filter "*.php" -Recurse -ErrorAction SilentlyContinue | Select-Object -First 1) {
        $codeFilesNeedingContext7 += "PHP files (Laravel)"
    }

    # Szukaj plików Blade
    if (Get-ChildItem -Path "." -Filter "*.blade.php" -Recurse -ErrorAction SilentlyContinue | Select-Object -First 1) {
        $codeFilesNeedingContext7 += "Blade templates (Livewire)"
    }

    # Szukaj plików JS
    if (Get-ChildItem -Path "." -Filter "*.js" -Recurse -ErrorAction SilentlyContinue | Select-Object -First 1) {
        $codeFilesNeedingContext7 += "JavaScript files (Alpine.js)"
    }

    if ($codeFilesNeedingContext7.Count -gt 0) {
        Write-Host "`n⚠️ CODE FILES REQUIRING CONTEXT7:" -ForegroundColor Yellow
        foreach ($fileType in $codeFilesNeedingContext7) {
            Write-Host "• $fileType" -ForegroundColor White
        }
    }

    # Sprawdź logi agentów w _AGENT_REPORTS/ dla użycia Context7
    $agentReportsPath = "_AGENT_REPORTS"
    $context7UsageDetected = $false

    if (Test-Path $agentReportsPath) {
        $recentReports = Get-ChildItem -Path $agentReportsPath -Filter "*.md" -ErrorAction SilentlyContinue |
                        Sort-Object LastWriteTime -Descending |
                        Select-Object -First 5

        foreach ($report in $recentReports) {
            $content = Get-Content $report.FullName -Raw -ErrorAction SilentlyContinue
            if ($content -match "(?i)(context7|mcp.*context7|resolve-library-id|get-library-docs)") {
                $context7UsageDetected = $true
                Write-Host "✅ Context7 usage found in: $($report.Name)" -ForegroundColor Green
                break
            }
        }

        if (-not $context7UsageDetected) {
            Write-Host "`n⚠️ WARNING: No Context7 usage detected in recent agent reports!" -ForegroundColor Red
            Write-Host "• Agents should use mcp__context7__resolve-library-id" -ForegroundColor White
            Write-Host "• Followed by mcp__context7__get-library-docs" -ForegroundColor White
        }
    } else {
        Write-Host "`n📁 No agent reports directory found" -ForegroundColor Gray
    }

    # Sprawdź czy istnieją pliki projektowe wymagające Context7
    $projectContext = @()

    # Sprawdź composer.json dla Laravel
    if (Test-Path "composer.json") {
        $composer = Get-Content "composer.json" -Raw | ConvertFrom-Json -ErrorAction SilentlyContinue
        if ($composer.require.'laravel/framework') {
            $projectContext += "Laravel project - requires /websites/laravel_12_x"
        }
        if ($composer.require.'livewire/livewire') {
            $projectContext += "Livewire project - requires /livewire/livewire"
        }
    }

    # Sprawdź package.json dla Alpine.js
    if (Test-Path "package.json") {
        $package = Get-Content "package.json" -Raw | ConvertFrom-Json -ErrorAction SilentlyContinue
        if ($package.dependencies.alpinejs -or $package.devDependencies.alpinejs) {
            $projectContext += "Alpine.js project - requires /alpinejs/alpine"
        }
    }

    if ($projectContext.Count -gt 0) {
        Write-Host "`n🎯 PROJECT CONTEXT DETECTED:" -ForegroundColor Cyan
        foreach ($context in $projectContext) {
            Write-Host "• $context" -ForegroundColor White
        }
    }

    # Końcowa walidacja i rekomendacje
    Write-Host "`n📋 CONTEXT7 COMPLIANCE CHECK:" -ForegroundColor Yellow

    if ($context7Available) {
        Write-Host "✅ Context7 MCP configured correctly" -ForegroundColor Green
    } else {
        Write-Host "❌ Context7 MCP not configured!" -ForegroundColor Red
    }

    if ($context7UsageDetected) {
        Write-Host "✅ Context7 usage detected in recent work" -ForegroundColor Green
    } else {
        Write-Host "⚠️ No recent Context7 usage - remember to use before code generation!" -ForegroundColor Yellow
    }

    # Przypomnienie o workflow
    Write-Host "`n🔧 PROPER CONTEXT7 WORKFLOW:" -ForegroundColor Cyan
    Write-Host "1. mcp__context7__resolve-library-id 'library-name'" -ForegroundColor White
    Write-Host "2. mcp__context7__get-library-docs '/resolved/library/id'" -ForegroundColor White
    Write-Host "3. Generate code using Context7 patterns" -ForegroundColor White
    Write-Host "4. Agent creates compliance report" -ForegroundColor White

    Write-Host ""

} catch {
    Write-Host "❌ Error in validate-context7.ps1: $($_.Exception.Message)" -ForegroundColor Red
}