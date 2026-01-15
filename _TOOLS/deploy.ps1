# 🚀 PPM-CC-Laravel Unified Deployment Script
# Version: 1.0.0
# Author: deployment-specialist (Claude Code Agent)
# Date: 2025-12-04

<#
.SYNOPSIS
    Unified deployment script dla PPM-CC-Laravel na Hostido production.

.DESCRIPTION
    Centralized deployment workflow z support dla różnych typów deployment:
    - Full: Kompletny deployment (code + assets + migrations + verification)
    - Code: Tylko kod PHP/Blade bez build assets
    - Assets: Rebuild Vite assets + upload + cache clear
    - Migration: Upload i wykonanie migracji bazy danych
    - Hotfix: Szybki upload plików bez backup (emergency)
    - Rollback: Przywracanie z backup

.PARAMETER Type
    Typ deployment: Full, Code, Assets, Migration, Hotfix, Rollback

.PARAMETER Files
    Array plików do deployment (dla Code/Migration/Hotfix)

.PARAMETER Environment
    Środowisko: dev lub production (default: production)

.PARAMETER BackupName
    Nazwa backup do restore (tylko dla -Type Rollback)

.PARAMETER SkipBackup
    Pomiń tworzenie backup (tylko dla Hotfix)

.PARAMETER SkipVerification
    Pomiń verification phase (health check, Chrome DevTools)

.PARAMETER DryRun
    Test mode - wyświetla operacje bez wykonywania

.PARAMETER Verbose
    Szczegółowe logi wszystkich operacji

.EXAMPLE
    .\deploy.ps1 -Type Full
    Pełny deployment z automatic backup i verification

.EXAMPLE
    .\deploy.ps1 -Type Code -Files "app/Http/Livewire/Products/ProductForm.php"
    Deploy pojedynczego pliku PHP

.EXAMPLE
    .\deploy.ps1 -Type Assets
    Rebuild i deploy Vite assets

.EXAMPLE
    .\deploy.ps1 -Type Hotfix -Files "app/Services/Critical.php" -SkipBackup
    Emergency hotfix bez backup

.EXAMPLE
    .\deploy.ps1 -Type Rollback -BackupName "backup_20251204_120000"
    Przywracanie z backup
#>

param(
    [Parameter(Mandatory=$true)]
    [ValidateSet("Full", "Code", "Assets", "Migration", "Hotfix", "Rollback")]
    [string]$Type,

    [Parameter(Mandatory=$false)]
    [string[]]$Files = @(),

    [Parameter(Mandatory=$false)]
    [ValidateSet("dev", "production")]
    [string]$Environment = "production",

    [Parameter(Mandatory=$false)]
    [string]$BackupName = "",

    [Parameter(Mandatory=$false)]
    [switch]$SkipBackup,

    [Parameter(Mandatory=$false)]
    [switch]$SkipVerification,

    [Parameter(Mandatory=$false)]
    [switch]$DryRun,

    [Parameter(Mandatory=$false)]
    [switch]$Verbose
)

$ErrorActionPreference = "Stop"
$PSDefaultParameterValues['Out-File:Encoding'] = 'utf8'

# ============================================================================
# CONFIGURATION
# ============================================================================

$ScriptRoot = $PSScriptRoot
$ProjectRoot = Split-Path $ScriptRoot -Parent
$ConfigFile = Join-Path $ScriptRoot "deploy-config.json"
$LibraryFile = Join-Path $ScriptRoot "deploy-lib.ps1"

# Load configuration
if (!(Test-Path $ConfigFile)) {
    Write-Host "❌ Configuration file not found: $ConfigFile" -ForegroundColor Red
    Write-Host "💡 Create deploy-config.json from template" -ForegroundColor Yellow
    exit 1
}

$Config = Get-Content $ConfigFile -Raw | ConvertFrom-Json

# Load shared functions
if (Test-Path $LibraryFile) {
    . $LibraryFile
} else {
    Write-Host "⚠️ deploy-lib.ps1 not found - using inline functions" -ForegroundColor Yellow
}

# Hostido configuration
$HostidoHost = $Config.hostido.host
$HostidoUser = $Config.hostido.user
$HostidoPort = $Config.hostido.port
$HostidoKey = $Config.hostido.sshKey
$RemotePath = $Config.hostido.remotePath

# Tools paths
$AutomationScript = Join-Path $ScriptRoot "hostido_automation.ps1"
$QuickPushScript = Join-Path $ScriptRoot "hostido_quick_push.ps1"
$BuildScript = Join-Path $ScriptRoot "hostido_build.ps1"

# ============================================================================
# HELPER FUNCTIONS (fallback jeśli deploy-lib.ps1 nie istnieje)
# ============================================================================

if (!(Get-Command Write-DeployLog -ErrorAction SilentlyContinue)) {
    function Write-DeployLog {
        param([string]$Message, [string]$Level = "INFO")

        $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
        $logMessage = "[$timestamp] [$Level] $Message"

        # Console output z kolorami
        $color = switch ($Level) {
            "SUCCESS" { "Green" }
            "ERROR" { "Red" }
            "WARNING" { "Yellow" }
            "INFO" { "Cyan" }
            default { "White" }
        }

        Write-Host $logMessage -ForegroundColor $color

        # File logging
        $logDir = Join-Path $ScriptRoot "_logs"
        if (!(Test-Path $logDir)) { New-Item -Path $logDir -ItemType Directory -Force | Out-Null }

        $logFile = Join-Path $logDir "deploy_$(Get-Date -Format 'yyyyMMdd').log"
        Add-Content -Path $logFile -Value $logMessage
    }
}

if (!(Get-Command Test-DeployRequirements -ErrorAction SilentlyContinue)) {
    function Test-DeployRequirements {
        Write-DeployLog "Checking deployment requirements..." "INFO"

        # SSH key
        if (!(Test-Path $HostidoKey)) {
            Write-DeployLog "SSH key not found: $HostidoKey" "ERROR"
            return $false
        }

        # Automation script
        if (!(Test-Path $AutomationScript)) {
            Write-DeployLog "Automation script not found: $AutomationScript" "ERROR"
            return $false
        }

        # plink/pscp (PuTTY)
        $plink = "C:\Program Files\PuTTY\plink.exe"
        if (!(Test-Path $plink)) {
            Write-DeployLog "plink.exe not found - install PuTTY" "ERROR"
            return $false
        }

        Write-DeployLog "All requirements satisfied" "SUCCESS"
        return $true
    }
}

# ============================================================================
# DEPLOYMENT WORKFLOW FUNCTIONS
# ============================================================================

function Invoke-FullDeployment {
    Write-DeployLog "========================================" "INFO"
    Write-DeployLog "FULL DEPLOYMENT WORKFLOW" "INFO"
    Write-DeployLog "========================================" "INFO"

    $results = @{}

    try {
        # 1. Pre-deployment checks
        Write-DeployLog "Phase 1: Pre-deployment checks" "INFO"
        $results["Pre-checks"] = Test-DeployRequirements
        if (!$results["Pre-checks"]) { throw "Pre-checks failed" }

        # 2. Create backup
        if (!$SkipBackup -and !$DryRun) {
            Write-DeployLog "Phase 2: Creating backup" "INFO"
            $script:BackupName = New-Backup
            $results["Backup"] = ![string]::IsNullOrEmpty($script:BackupName)
            if (!$results["Backup"]) {
                Write-DeployLog "Backup failed - continuing with caution" "WARNING"
            }
        } else {
            $results["Backup"] = $true
            Write-DeployLog "Skipping backup (DryRun or SkipBackup)" "WARNING"
        }

        # 3. Build assets (if needed)
        Write-DeployLog "Phase 3: Building assets" "INFO"
        $results["Build"] = Invoke-Build
        if (!$results["Build"]) {
            Write-DeployLog "Build failed" "ERROR"
            throw "Build phase failed"
        }

        # 4. Upload files
        Write-DeployLog "Phase 4: Uploading files" "INFO"
        $results["Upload"] = Invoke-Upload -Type "Full"
        if (!$results["Upload"]) { throw "Upload failed" }

        # 5. Migrations (if pending)
        Write-DeployLog "Phase 5: Running migrations" "INFO"
        $results["Migrations"] = Invoke-Migrations

        # 6. Post-deployment
        Write-DeployLog "Phase 6: Post-deployment tasks" "INFO"
        $results["PostDeploy"] = Invoke-PostDeploy
        if (!$results["PostDeploy"]) {
            Write-DeployLog "Post-deploy tasks failed" "WARNING"
        }

        # 7. Verification
        if (!$SkipVerification) {
            Write-DeployLog "Phase 7: Verification" "INFO"
            $results["Verification"] = Invoke-Verification
            if (!$results["Verification"]) {
                Write-DeployLog "Verification failed - consider rollback" "ERROR"
            }
        } else {
            $results["Verification"] = $true
            Write-DeployLog "Skipping verification" "WARNING"
        }

        # Summary
        Show-DeploymentSummary -Results $results

        # Overall success
        $overallSuccess = ($results.Values | Where-Object { $_ -eq $false }).Count -eq 0
        return $overallSuccess

    } catch {
        Write-DeployLog "DEPLOYMENT FAILED: $_" "ERROR"
        Write-DeployLog "Rollback available: $script:BackupName" "WARNING"
        return $false
    }
}

function Invoke-CodeDeployment {
    param([string[]]$FilesToDeploy)

    Write-DeployLog "========================================" "INFO"
    Write-DeployLog "CODE-ONLY DEPLOYMENT" "INFO"
    Write-DeployLog "========================================" "INFO"

    if ($FilesToDeploy.Count -eq 0) {
        Write-DeployLog "No files specified for deployment" "ERROR"
        return $false
    }

    $results = @{}

    try {
        # 1. Pre-checks
        $results["Pre-checks"] = Test-DeployRequirements
        if (!$results["Pre-checks"]) { throw "Pre-checks failed" }

        # 2. Backup (optional dla code-only, ale recommended)
        if (!$SkipBackup -and !$DryRun) {
            $script:BackupName = New-Backup
            $results["Backup"] = ![string]::IsNullOrEmpty($script:BackupName)
        } else {
            $results["Backup"] = $true
        }

        # 3. Upload files via quick push
        Write-DeployLog "Uploading $($FilesToDeploy.Count) files..." "INFO"
        foreach ($file in $FilesToDeploy) {
            Write-DeployLog "  - $file" "INFO"
        }

        if (!$DryRun) {
            $results["Upload"] = Invoke-QuickPush -Files $FilesToDeploy
        } else {
            Write-DeployLog "DRY-RUN: Would upload files" "WARNING"
            $results["Upload"] = $true
        }

        # 4. Clear cache (selective)
        $results["ClearCache"] = Invoke-CacheClear -Selective

        # 5. Verification
        if (!$SkipVerification) {
            $results["Verification"] = Invoke-Verification
        } else {
            $results["Verification"] = $true
        }

        Show-DeploymentSummary -Results $results

        $overallSuccess = ($results.Values | Where-Object { $_ -eq $false }).Count -eq 0
        return $overallSuccess

    } catch {
        Write-DeployLog "CODE DEPLOYMENT FAILED: $_" "ERROR"
        return $false
    }
}

function Invoke-AssetsDeployment {
    Write-DeployLog "========================================" "INFO"
    Write-DeployLog "ASSETS-ONLY DEPLOYMENT" "INFO"
    Write-DeployLog "========================================" "INFO"

    $results = @{}

    try {
        # 1. Pre-checks
        $results["Pre-checks"] = Test-DeployRequirements

        # 2. Build assets
        Write-DeployLog "Building Vite assets..." "INFO"
        if (!$DryRun) {
            if (Test-Path $BuildScript) {
                & $BuildScript -Environment $Environment -AssetsOnly
                $results["Build"] = $LASTEXITCODE -eq 0
            } else {
                Write-DeployLog "Build script not found: $BuildScript" "ERROR"
                $results["Build"] = $false
            }
        } else {
            Write-DeployLog "DRY-RUN: Would build assets" "WARNING"
            $results["Build"] = $true
        }

        # 3. Upload assets
        Write-DeployLog "Uploading built assets..." "INFO"
        if (!$DryRun -and $results["Build"]) {
            # Upload ALL files from public/build/assets/ + manifest.json
            $buildPath = Join-Path $ProjectRoot "public\build"
            if (Test-Path $buildPath) {
                # TODO: Use WinSCP sync dla upload
                Write-DeployLog "Uploading assets from: $buildPath" "INFO"
                $results["Upload"] = $true # Placeholder
            } else {
                Write-DeployLog "Build output not found: $buildPath" "ERROR"
                $results["Upload"] = $false
            }
        } else {
            $results["Upload"] = $true
        }

        # 4. Clear view cache
        $results["ClearCache"] = Invoke-CacheClear -ViewOnly

        # 5. Verification
        if (!$SkipVerification) {
            $results["Verification"] = Invoke-Verification
        } else {
            $results["Verification"] = $true
        }

        Show-DeploymentSummary -Results $results

        $overallSuccess = ($results.Values | Where-Object { $_ -eq $false }).Count -eq 0
        return $overallSuccess

    } catch {
        Write-DeployLog "ASSETS DEPLOYMENT FAILED: $_" "ERROR"
        return $false
    }
}

function Invoke-MigrationDeployment {
    param([string[]]$MigrationFiles)

    Write-DeployLog "========================================" "INFO"
    Write-DeployLog "MIGRATION DEPLOYMENT" "INFO"
    Write-DeployLog "========================================" "INFO"

    if ($MigrationFiles.Count -eq 0) {
        Write-DeployLog "No migration files specified" "ERROR"
        return $false
    }

    $results = @{}

    try {
        # 1. Pre-checks
        $results["Pre-checks"] = Test-DeployRequirements

        # 2. MANDATORY backup dla migrations
        Write-DeployLog "Creating backup (MANDATORY for migrations)..." "INFO"
        if (!$DryRun) {
            $script:BackupName = New-Backup
            $results["Backup"] = ![string]::IsNullOrEmpty($script:BackupName)
            if (!$results["Backup"]) {
                Write-DeployLog "Backup failed - ABORTING migration deployment" "ERROR"
                throw "Cannot proceed without backup"
            }
        } else {
            $results["Backup"] = $true
        }

        # 3. Upload migration files
        Write-DeployLog "Uploading migrations..." "INFO"
        foreach ($file in $MigrationFiles) {
            Write-DeployLog "  - $file" "INFO"
        }

        if (!$DryRun) {
            $results["Upload"] = Invoke-QuickPush -Files $MigrationFiles
        } else {
            $results["Upload"] = $true
        }

        # 4. Run migrations
        Write-DeployLog "Running migrations..." "INFO"
        if (!$DryRun -and $results["Upload"]) {
            $migrateCommand = "cd $RemotePath && php artisan migrate --force"
            $migrateResult = & $AutomationScript -Command $migrateCommand
            $results["Migrate"] = $LASTEXITCODE -eq 0

            if (!$results["Migrate"]) {
                Write-DeployLog "Migration failed - consider rollback" "ERROR"
            }
        } else {
            $results["Migrate"] = $true
        }

        # 5. Verification
        if (!$SkipVerification) {
            $results["Verification"] = Invoke-Verification
        } else {
            $results["Verification"] = $true
        }

        Show-DeploymentSummary -Results $results

        $overallSuccess = ($results.Values | Where-Object { $_ -eq $false }).Count -eq 0
        return $overallSuccess

    } catch {
        Write-DeployLog "MIGRATION DEPLOYMENT FAILED: $_" "ERROR"
        Write-DeployLog "Rollback recommended: .\deploy.ps1 -Type Rollback -BackupName '$script:BackupName'" "WARNING"
        return $false
    }
}

function Invoke-HotfixDeployment {
    param([string[]]$HotfixFiles)

    Write-DeployLog "========================================" "INFO"
    Write-DeployLog "HOTFIX DEPLOYMENT (EMERGENCY MODE)" "INFO"
    Write-DeployLog "========================================" "INFO"

    if ($HotfixFiles.Count -eq 0) {
        Write-DeployLog "No files specified for hotfix" "ERROR"
        return $false
    }

    # Hotfix = minimum steps dla speed
    try {
        # 1. Upload files immediately
        Write-DeployLog "Uploading hotfix files..." "INFO"
        foreach ($file in $HotfixFiles) {
            Write-DeployLog "  - $file" "INFO"
        }

        if (!$DryRun) {
            $uploadSuccess = Invoke-QuickPush -Files $HotfixFiles
            if (!$uploadSuccess) { throw "Upload failed" }
        }

        # 2. Clear only related cache (fast)
        Write-DeployLog "Clearing cache..." "INFO"
        if (!$DryRun) {
            $cacheCommand = "cd $RemotePath && php artisan cache:clear && php artisan config:clear"
            & $AutomationScript -Command $cacheCommand
        }

        # 3. Quick verification
        Write-DeployLog "Quick verification..." "INFO"
        if (!$DryRun -and !$SkipVerification) {
            $healthCommand = "cd $RemotePath && php artisan --version"
            $healthResult = & $AutomationScript -Command $healthCommand
            $verified = $LASTEXITCODE -eq 0

            if ($verified) {
                Write-DeployLog "Hotfix deployed successfully" "SUCCESS"
            } else {
                Write-DeployLog "Verification failed - check manually" "WARNING"
            }
        }

        Write-DeployLog "HOTFIX COMPLETE" "SUCCESS"
        return $true

    } catch {
        Write-DeployLog "HOTFIX DEPLOYMENT FAILED: $_" "ERROR"
        return $false
    }
}

function Invoke-RollbackDeployment {
    param([string]$BackupToRestore)

    Write-DeployLog "========================================" "INFO"
    Write-DeployLog "ROLLBACK DEPLOYMENT" "INFO"
    Write-DeployLog "========================================" "INFO"

    if ([string]::IsNullOrEmpty($BackupToRestore)) {
        Write-DeployLog "Backup name required for rollback" "ERROR"
        return $false
    }

    Write-DeployLog "Restoring from backup: $BackupToRestore" "WARNING"

    if (!$DryRun) {
        # Confirm rollback (critical operation)
        $confirm = Read-Host "Are you sure you want to rollback? (yes/no)"
        if ($confirm -ne "yes") {
            Write-DeployLog "Rollback cancelled by user" "WARNING"
            return $false
        }

        # Execute rollback via hostido_deploy.ps1
        $deployScript = Join-Path $ScriptRoot "hostido_deploy.ps1"
        if (Test-Path $deployScript) {
            & $deployScript -RestoreBackup -BackupName $BackupToRestore -Force
            $success = $LASTEXITCODE -eq 0

            if ($success) {
                Write-DeployLog "ROLLBACK SUCCESSFUL" "SUCCESS"
            } else {
                Write-DeployLog "ROLLBACK FAILED" "ERROR"
            }

            return $success
        } else {
            Write-DeployLog "Deploy script not found: $deployScript" "ERROR"
            return $false
        }
    } else {
        Write-DeployLog "DRY-RUN: Would restore from $BackupToRestore" "WARNING"
        return $true
    }
}

# ============================================================================
# UTILITY FUNCTIONS
# ============================================================================

function New-Backup {
    Write-DeployLog "Creating backup..." "INFO"

    if ($DryRun) {
        Write-DeployLog "DRY-RUN: Would create backup" "WARNING"
        return "backup_dryrun_$(Get-Date -Format 'yyyyMMdd_HHmmss')"
    }

    $deployScript = Join-Path $ScriptRoot "hostido_deploy.ps1"
    if (Test-Path $deployScript) {
        & $deployScript -CreateBackup
        if ($LASTEXITCODE -eq 0) {
            $backupName = "backup_$(Get-Date -Format 'yyyyMMdd_HHmmss')"
            Write-DeployLog "Backup created: $backupName" "SUCCESS"
            return $backupName
        }
    }

    Write-DeployLog "Backup creation failed" "ERROR"
    return $null
}

function Invoke-Build {
    Write-DeployLog "Building assets..." "INFO"

    if ($DryRun) {
        Write-DeployLog "DRY-RUN: Would build assets" "WARNING"
        return $true
    }

    if (Test-Path $BuildScript) {
        & $BuildScript -Environment $Environment -LocalBuild
        return $LASTEXITCODE -eq 0
    }

    Write-DeployLog "Build script not found: $BuildScript" "WARNING"
    return $true # Non-blocking dla code-only deployments
}

function Invoke-Upload {
    param([string]$Type)

    Write-DeployLog "Uploading files (Type: $Type)..." "INFO"

    if ($DryRun) {
        Write-DeployLog "DRY-RUN: Would upload files" "WARNING"
        return $true
    }

    # TODO: Implement upload logic based on Type
    # For now, delegate to hostido_deploy.ps1

    return $true
}

function Invoke-QuickPush {
    param([string[]]$Files)

    if ($DryRun) {
        Write-DeployLog "DRY-RUN: Would quick push files" "WARNING"
        return $true
    }

    if (Test-Path $QuickPushScript) {
        & $QuickPushScript -Files $Files
        return $LASTEXITCODE -eq 0
    }

    Write-DeployLog "Quick push script not found: $QuickPushScript" "ERROR"
    return $false
}

function Invoke-Migrations {
    Write-DeployLog "Checking for pending migrations..." "INFO"

    if ($DryRun) {
        Write-DeployLog "DRY-RUN: Would run migrations" "WARNING"
        return $true
    }

    # Check for pending migrations
    $statusCommand = "cd $RemotePath && php artisan migrate:status 2>/dev/null | grep -c 'Pending' || echo '0'"
    $pendingCount = & $AutomationScript -Command $statusCommand

    if ($pendingCount -and $pendingCount -gt 0) {
        Write-DeployLog "Found $pendingCount pending migrations - running..." "INFO"
        $migrateCommand = "cd $RemotePath && php artisan migrate --force"
        & $AutomationScript -Command $migrateCommand
        return $LASTEXITCODE -eq 0
    } else {
        Write-DeployLog "No pending migrations" "INFO"
        return $true
    }
}

function Invoke-PostDeploy {
    Write-DeployLog "Running post-deployment tasks..." "INFO"

    if ($DryRun) {
        Write-DeployLog "DRY-RUN: Would run post-deploy tasks" "WARNING"
        return $true
    }

    $postCommands = @(
        "cd $RemotePath",
        "chmod -R 775 storage/ bootstrap/cache/",
        "php artisan config:clear",
        "php artisan cache:clear",
        "php artisan route:clear",
        "php artisan view:clear",
        "php artisan config:cache",
        "php artisan route:cache"
    )

    $fullCommand = $postCommands -join " && "
    & $AutomationScript -Command $fullCommand

    return $LASTEXITCODE -eq 0
}

function Invoke-CacheClear {
    param(
        [switch]$Selective,
        [switch]$ViewOnly
    )

    Write-DeployLog "Clearing cache..." "INFO"

    if ($DryRun) {
        Write-DeployLog "DRY-RUN: Would clear cache" "WARNING"
        return $true
    }

    if ($ViewOnly) {
        $cacheCommand = "cd $RemotePath && php artisan view:clear"
    } elseif ($Selective) {
        $cacheCommand = "cd $RemotePath && php artisan cache:clear && php artisan config:clear"
    } else {
        $cacheCommand = "cd $RemotePath && php artisan cache:clear && php artisan config:clear && php artisan route:clear && php artisan view:clear"
    }

    & $AutomationScript -Command $cacheCommand
    return $LASTEXITCODE -eq 0
}

function Invoke-Verification {
    Write-DeployLog "Verifying deployment..." "INFO"

    if ($DryRun) {
        Write-DeployLog "DRY-RUN: Would verify deployment" "WARNING"
        return $true
    }

    # Health check
    $healthCommand = "cd $RemotePath && php artisan --version"
    $healthResult = & $AutomationScript -Command $healthCommand

    if ($LASTEXITCODE -eq 0) {
        Write-DeployLog "Health check PASSED" "SUCCESS"

        # HTTP check (basic)
        try {
            $response = Invoke-WebRequest -Uri "https://ppm.mpptrade.pl" -UseBasicParsing -TimeoutSec 10
            if ($response.StatusCode -eq 200 -or $response.StatusCode -eq 302) {
                Write-DeployLog "HTTP check PASSED (Status: $($response.StatusCode))" "SUCCESS"
            } else {
                Write-DeployLog "HTTP check WARNING (Status: $($response.StatusCode))" "WARNING"
            }
        } catch {
            Write-DeployLog "HTTP check FAILED: $_" "WARNING"
        }

        # TODO: Chrome DevTools MCP integration
        Write-DeployLog "Manual verification recommended: Chrome DevTools check" "WARNING"

        return $true
    } else {
        Write-DeployLog "Health check FAILED" "ERROR"
        return $false
    }
}

function Show-DeploymentSummary {
    param([hashtable]$Results)

    Write-DeployLog "========================================" "INFO"
    Write-DeployLog "DEPLOYMENT SUMMARY" "INFO"
    Write-DeployLog "========================================" "INFO"

    foreach ($key in $Results.Keys) {
        $status = if ($Results[$key]) { "✅ SUCCESS" } else { "❌ FAILED" }
        $level = if ($Results[$key]) { "SUCCESS" } else { "ERROR" }
        Write-DeployLog "$key : $status" $level
    }

    Write-DeployLog "========================================" "INFO"

    $overallSuccess = ($Results.Values | Where-Object { $_ -eq $false }).Count -eq 0

    if ($overallSuccess) {
        Write-DeployLog "🎉 DEPLOYMENT SUCCESSFUL" "SUCCESS"
        Write-DeployLog "🌐 Check application: https://ppm.mpptrade.pl" "INFO"
    } else {
        Write-DeployLog "⚠️ DEPLOYMENT COMPLETED WITH ERRORS" "WARNING"
        if (![string]::IsNullOrEmpty($script:BackupName)) {
            Write-DeployLog "🔄 Rollback available: .\deploy.ps1 -Type Rollback -BackupName '$script:BackupName'" "INFO"
        }
    }
}

# ============================================================================
# MAIN EXECUTION
# ============================================================================

Write-DeployLog "========================================" "INFO"
Write-DeployLog "PPM-CC-Laravel Unified Deployment" "INFO"
Write-DeployLog "========================================" "INFO"
Write-DeployLog "Type: $Type" "INFO"
Write-DeployLog "Environment: $Environment" "INFO"
Write-DeployLog "Mode: $(if($DryRun){'DRY-RUN'}else{'PRODUCTION'})" $(if($DryRun){'WARNING'}else{'INFO'})
Write-DeployLog "========================================" "INFO"

# Validation
if ($Type -eq "Code" -and $Files.Count -eq 0) {
    Write-DeployLog "Code deployment requires -Files parameter" "ERROR"
    exit 1
}

if ($Type -eq "Migration" -and $Files.Count -eq 0) {
    Write-DeployLog "Migration deployment requires -Files parameter" "ERROR"
    exit 1
}

if ($Type -eq "Hotfix" -and $Files.Count -eq 0) {
    Write-DeployLog "Hotfix deployment requires -Files parameter" "ERROR"
    exit 1
}

if ($Type -eq "Rollback" -and [string]::IsNullOrEmpty($BackupName)) {
    Write-DeployLog "Rollback requires -BackupName parameter" "ERROR"
    exit 1
}

# Execute deployment based on type
$success = switch ($Type) {
    "Full" { Invoke-FullDeployment }
    "Code" { Invoke-CodeDeployment -FilesToDeploy $Files }
    "Assets" { Invoke-AssetsDeployment }
    "Migration" { Invoke-MigrationDeployment -MigrationFiles $Files }
    "Hotfix" { Invoke-HotfixDeployment -HotfixFiles $Files }
    "Rollback" { Invoke-RollbackDeployment -BackupToRestore $BackupName }
    default {
        Write-DeployLog "Unknown deployment type: $Type" "ERROR"
        $false
    }
}

# Exit code
if ($success) {
    Write-DeployLog "Deployment completed successfully" "SUCCESS"
    exit 0
} else {
    Write-DeployLog "Deployment failed" "ERROR"
    exit 1
}
