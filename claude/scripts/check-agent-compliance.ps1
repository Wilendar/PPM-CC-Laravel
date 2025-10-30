# PPM-CC-Laravel Agent Compliance Checker
# Sprawdza czy agenci przestrzegają workflow i zasad projektu
# Created: 2025-09-29
# Encoding: UTF-8 with BOM

param()

# Ustawienia kodowania dla PowerShell
$PSDefaultParameterValues['Out-File:Encoding'] = 'utf8BOM'
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

try {
    Write-Host "🔍 CHECKING AGENT COMPLIANCE" -ForegroundColor Yellow

    # Sprawdź czy istnieje folder z raportami agentów
    $agentReportsPath = "_AGENT_REPORTS"

    if (-not (Test-Path $agentReportsPath)) {
        Write-Host "❌ _AGENT_REPORTS directory not found!" -ForegroundColor Red
        Write-Host "• Agents MUST create reports after completing work" -ForegroundColor White
        return
    }

    # Lista wszystkich dostępnych agentów
    $availableAgents = @(
        'architect', 'ask', 'coding-style-agent', 'debugger',
        'deployment-specialist', 'documentation-reader',
        'erp-integration-expert', 'frontend-specialist',
        'import-export-specialist', 'laravel-expert',
        'livewire-specialist', 'prestashop-api-expert'
    )

    Write-Host "🤖 AVAILABLE AGENTS: $($availableAgents.Count)" -ForegroundColor Cyan

    # Sprawdź ostatnie raporty (ostatnie 24h lub 10 najnowszych)
    $recentReports = Get-ChildItem -Path $agentReportsPath -Filter "*.md" -ErrorAction SilentlyContinue |
                    Sort-Object LastWriteTime -Descending |
                    Select-Object -First 10

    if ($recentReports.Count -eq 0) {
        Write-Host "⚠️ No agent reports found" -ForegroundColor Yellow
        Write-Host "• If agents were used, they should create reports" -ForegroundColor White
        return
    }

    Write-Host "`n📊 RECENT AGENT ACTIVITY ($($recentReports.Count) reports):" -ForegroundColor Cyan

    $complianceIssues = @()
    $context7Usage = 0
    $agentReportCompliance = 0

    foreach ($report in $recentReports) {
        $reportName = $report.Name
        $content = Get-Content $report.FullName -Raw -ErrorAction SilentlyContinue

        if ([string]::IsNullOrEmpty($content)) {
            $complianceIssues += "Empty report: $reportName"
            continue
        }

        Write-Host "• $reportName" -ForegroundColor White -NoNewline
        Write-Host " ($(Get-Date $report.LastWriteTime -Format 'yyyy-MM-dd HH:mm'))" -ForegroundColor Gray

        # Sprawdź strukturę raportu
        $hasExecutedWorks = $content -match "(?i)(wykonane.*prace|executed.*work|✅.*WYKONANE)"
        $hasProblems = $content -match "(?i)(problemy|blokery|problems|issues|⚠️)"
        $hasNextSteps = $content -match "(?i)(następne.*kroki|next.*steps|📋.*NASTĘPNE)"
        $hasFiles = $content -match "(?i)(pliki|files|📁.*PLIKI)"

        # Sprawdź użycie Context7
        $hasContext7 = $content -match "(?i)(context7|mcp.*context7|resolve-library-id|get-library-docs)"

        if ($hasContext7) {
            $context7Usage++
        }

        # Sprawdź completeness raportu
        $reportCompliance = 0
        if ($hasExecutedWorks) { $reportCompliance++ }
        if ($hasProblems) { $reportCompliance++ }
        if ($hasNextSteps) { $reportCompliance++ }
        if ($hasFiles) { $reportCompliance++ }

        if ($reportCompliance -ge 3) {
            $agentReportCompliance++
            Write-Host "  ✅ Complete report structure" -ForegroundColor Green
        } else {
            Write-Host "  ⚠️ Incomplete report structure (missing sections)" -ForegroundColor Yellow
            $complianceIssues += "Incomplete report structure: $reportName"
        }

        if ($hasContext7) {
            Write-Host "  ✅ Context7 usage documented" -ForegroundColor Green
        } else {
            Write-Host "  ❌ No Context7 usage documented" -ForegroundColor Red
            $complianceIssues += "No Context7 usage: $reportName"
        }
    }

    # Podsumowanie compliance
    Write-Host "`n📈 COMPLIANCE SUMMARY:" -ForegroundColor Yellow

    $context7Percentage = if ($recentReports.Count -gt 0) { [math]::Round(($context7Usage / $recentReports.Count) * 100) } else { 0 }
    $reportCompliancePercentage = if ($recentReports.Count -gt 0) { [math]::Round(($agentReportCompliance / $recentReports.Count) * 100) } else { 0 }

    Write-Host "• Context7 Usage: $context7Usage/$($recentReports.Count) reports ($context7Percentage%)" -ForegroundColor $(if ($context7Percentage -ge 80) {'Green'} elseif ($context7Percentage -ge 50) {'Yellow'} else {'Red'})
    Write-Host "• Report Structure: $agentReportCompliance/$($recentReports.Count) complete ($reportCompliancePercentage%)" -ForegroundColor $(if ($reportCompliancePercentage -ge 80) {'Green'} elseif ($reportCompliancePercentage -ge 50) {'Yellow'} else {'Red'})

    # Sprawdź czy najbardziej wymagający agenci używają Context7
    $criticalAgents = @('coding-style-agent', 'laravel-expert', 'livewire-specialist', 'prestashop-api-expert')
    $criticalAgentReports = $recentReports | Where-Object {
        $name = $_.Name.ToLower()
        $criticalAgents | Where-Object { $name -match $_.ToLower() }
    }

    if ($criticalAgentReports.Count -gt 0) {
        Write-Host "`n🔥 CRITICAL AGENT COMPLIANCE:" -ForegroundColor Red
        foreach ($report in $criticalAgentReports) {
            $content = Get-Content $report.FullName -Raw -ErrorAction SilentlyContinue
            $hasContext7 = $content -match "(?i)(context7|mcp.*context7)"
            $agentType = ($criticalAgents | Where-Object { $report.Name.ToLower() -match $_.ToLower() })[0]

            if ($hasContext7) {
                Write-Host "• ${agentType}: OK Context7 compliant" -ForegroundColor Green
            } else {
                Write-Host "• ${agentType}: ERROR Context7 NOT used!" -ForegroundColor Red
                $complianceIssues += "Critical agent without Context7: $agentType"
            }
        }
    }

    # Sprawdź workflow patterns
    Write-Host "`n🔄 WORKFLOW PATTERNS:" -ForegroundColor Cyan

    # Pattern 1: architect → specialist → coding-style-agent
    $hasArchitectReport = $recentReports | Where-Object { $_.Name.ToLower() -match 'architect' }
    $hasCodingStyleReport = $recentReports | Where-Object { $_.Name.ToLower() -match 'coding-style' }

    if ($hasArchitectReport -and $hasCodingStyleReport) {
        Write-Host "✅ Proper workflow: architect → coding-style-agent detected" -ForegroundColor Green
    } elseif ($hasCodingStyleReport) {
        Write-Host "✅ coding-style-agent used (good practice)" -ForegroundColor Green
    } else {
        Write-Host "⚠️ No coding-style-agent usage detected" -ForegroundColor Yellow
    }

    # Issues summary
    if ($complianceIssues.Count -gt 0) {
        Write-Host "`n❌ COMPLIANCE ISSUES FOUND:" -ForegroundColor Red
        foreach ($issue in $complianceIssues) {
            Write-Host "• $issue" -ForegroundColor White
        }

        Write-Host "`n🔧 REQUIRED ACTIONS:" -ForegroundColor Yellow
        Write-Host "• Agents MUST use Context7 before code generation" -ForegroundColor White
        Write-Host "• Reports MUST include all required sections" -ForegroundColor White
        Write-Host "• Critical agents (coding-style, laravel-expert, etc.) MUST document Context7 usage" -ForegroundColor White
        Write-Host "• Check _DOCS/AGENT_USAGE_GUIDE.md for proper patterns" -ForegroundColor White
    } else {
        Write-Host "`n✅ ALL AGENTS COMPLIANT!" -ForegroundColor Green
        Write-Host "• Context7 usage: Good" -ForegroundColor White
        Write-Host "• Report structure: Complete" -ForegroundColor White
        Write-Host "• Workflow patterns: Followed" -ForegroundColor White
    }

    Write-Host ""

} catch {
    Write-Host "❌ Error in check-agent-compliance.ps1: $($_.Exception.Message)" -ForegroundColor Red
}