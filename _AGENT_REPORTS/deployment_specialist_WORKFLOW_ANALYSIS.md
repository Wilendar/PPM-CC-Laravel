# RAPORT PRACY AGENTA: deployment-specialist
**Data**: 2025-12-04
**Agent**: deployment-specialist
**Zadanie**: Analiza i projektowanie unified deployment workflow dla PPM-CC-Laravel

## ANALIZA ISTNIEJĄCYCH NARZĘDZI

### Statystyki
- **Łączna liczba skryptów deploy*.ps1**: 111 plików
- **Główne skrypty infrastruktury**: 9 plików (hostido_*.ps1)
- **Architektura**: Hybridowa - skrypty specjalizowane + narzędzia uniwersalne

### Kategoryzacja Skryptów

#### 1. NARZĘDZIA UNIWERSALNE (Core Infrastructure)

**hostido_automation.ps1** - SSH command runner
- Cel: Wykonywanie pojedynczych komend SSH na serwerze
- Funkcje: TestConnection, HealthCheck, GetLogs, MonitorApp
- Użycie: Backend dla innych skryptów deployment
- Status: ✅ Dojrzały, produkcyjny

**hostido_deploy.ps1** - Full deployment orchestrator
- Cel: Kompletny deployment aplikacji (pełny pipeline)
- Funkcje:
  - Upload plików (WinSCP synchronization)
  - Backup/Restore management
  - Post-deployment commands (cache, permissions)
  - Health check verification
  - Dry-run mode
- Parametry: -CreateBackup, -RestoreBackup, -HealthCheck, -UploadOnly, -CommandOnly, -DryRun
- Status: ✅ Enterprise-ready, kompletny workflow

**hostido_quick_push.ps1** - Fast single/multiple file upload
- Cel: Szybki upload wybranych plików bez pełnego deployment
- Użycie: Hot-fix scenariusze, pojedyncze zmiany
- Parametry: -Files (array), -PostCommand
- Status: ✅ Optymalizowany pod szybkość

**hostido_build.ps1** - Asset build & upload pipeline
- Cel: Lokalne buildy Vite → upload assets → cache clear
- Funkcje:
  - npm install dependencies
  - Build assets (dev/production)
  - Upload public/build/* na serwer
  - Clear/optimize Laravel cache
- Parametry: -Environment (dev/production), -AssetsOnly, -CacheOnly, -LocalBuild
- Status: ✅ Dedicated dla Vite workflow

#### 2. SKRYPTY SPECJALIZOWANE (111 plików)

**Kategorie:**
- **Feature deployment** (deploy_etap07_*, deploy_faza_*): 15+ plików
- **Fix deployment** (deploy_*_fix.ps1): 40+ plików
- **Component deployment** (deploy_productform_*, deploy_category_*): 20+ plików
- **UI deployment** (deploy_ui_*, deploy_style_*, deploy_css_*): 25+ plików
- **Infrastructure** (deploy_migrations, deploy_models): 10+ plików

**Wzorce w specjalizowanych skryptach:**
```powershell
# Common pattern:
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$HostidoHost = "host379076@host379076.hostido.net.pl"
$HostidoPort = 64321

# Upload files via pscp
pscp -i $HostidoKey -P $HostidoPort "local/file" "${HostidoHost}:remote/path"

# Clear cache via plink
plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch "cd ... && php artisan cache:clear"
```

**Problemy zidentyfikowane:**
- ❌ Duplikacja kodu (każdy skrypt definiuje te same zmienne)
- ❌ Brak standardyzacji (różne podejścia do error handling)
- ❌ Manualne zarządzanie kolejnością operacji
- ❌ Brak centralnego loggingu deployment
- ⚠️ Wiele skryptów do jednorazowych fix'ów (historia projektu)

## ZAPROJEKTOWANY UNIFIED WORKFLOW

### Architektura Deployment Pipeline

```
[LOCAL DEVELOPMENT]
       ↓
   Code Changes (PHP/Blade/CSS/JS)
       ↓
┌──────────────────────┐
│  PRE-DEPLOYMENT      │
│  - Git status check  │
│  - Lint/validation   │
│  - Local tests       │
└──────────────────────┘
       ↓
┌──────────────────────────────────────┐
│  BUILD PHASE (CONDITIONAL)           │
│  - npm install (if package.json Δ)   │
│  - npm run build (if assets Δ)       │
│  - Vite manifest generation          │
└──────────────────────────────────────┘
       ↓
┌──────────────────────────────────────┐
│  BACKUP PHASE                        │
│  - Auto backup before deployment     │
│  - Database snapshot (optional)      │
│  - Rollback point creation           │
└──────────────────────────────────────┘
       ↓
┌──────────────────────────────────────┐
│  DEPLOYMENT PHASE                    │
│  ┌─ Upload Code Files                │
│  │  - PHP (app/*, routes/*, config/)│
│  │  - Blade (resources/views/)      │
│  │  - Exclude: node_modules, .git   │
│  ├─ Upload Assets (if built)         │
│  │  - public/build/assets/*         │
│  │  - public/build/manifest.json    │
│  ├─ Migrations (if pending)          │
│  │  - php artisan migrate --force   │
│  └─ Dependencies (if composer.lock Δ)│
│     - composer install --no-dev     │
└──────────────────────────────────────┘
       ↓
┌──────────────────────────────────────┐
│  POST-DEPLOYMENT PHASE               │
│  - chmod storage/ bootstrap/cache/   │
│  - php artisan cache:clear           │
│  - php artisan config:cache          │
│  - php artisan route:cache           │
│  - php artisan view:clear            │
└──────────────────────────────────────┘
       ↓
┌──────────────────────────────────────┐
│  VERIFICATION PHASE                  │
│  - Health check (Laravel version)    │
│  - HTTP response (curl 200/302)      │
│  - Chrome DevTools MCP (UI/Console)  │
│  - Error log check                   │
└──────────────────────────────────────┘
       ↓
┌──────────────────────────────────────┐
│  REPORTING PHASE                     │
│  - Deployment log generation         │
│  - Slack/email notification (future) │
│  - Success/failure summary           │
└──────────────────────────────────────┘

[PRODUCTION HOSTIDO]
```

### Deployment Types (Typy Scenariuszy)

**1. FULL DEPLOYMENT** (Pełny deployment aplikacji)
```powershell
.\deploy.ps1 -Type Full -Environment production
```
- Wszystkie fazy (build → backup → upload → post → verify)
- Użycie: Release nowej wersji, major changes
- Czas: ~5-10 minut

**2. CODE-ONLY DEPLOYMENT** (Tylko kod PHP/Blade bez assets)
```powershell
.\deploy.ps1 -Type Code -Files "app/Http/Livewire/Products/*.php"
```
- Skip build phase
- Upload tylko code files
- Fast cache clear
- Użycie: Bugfix, logic changes
- Czas: ~1-2 minuty

**3. ASSETS-ONLY DEPLOYMENT** (Tylko Vite assets)
```powershell
.\deploy.ps1 -Type Assets
```
- Build lokalnie (npm run build)
- Upload WSZYSTKIE public/build/assets/* + manifest
- Clear view cache
- Użycie: CSS/JS changes
- Czas: ~2-3 minuty

**4. MIGRATION DEPLOYMENT** (Migracje bazy danych)
```powershell
.\deploy.ps1 -Type Migration -Files "database/migrations/2025_*.php"
```
- Backup database
- Upload migrations
- Run php artisan migrate --force
- Verification
- Użycie: Schema changes
- Czas: ~2-5 minut

**5. HOTFIX DEPLOYMENT** (Pilne poprawki)
```powershell
.\deploy.ps1 -Type Hotfix -Files "app/Services/Critical.php" -SkipBackup
```
- Skip backup (szybkość)
- Upload pliku
- Clear tylko related cache
- Minimal verification
- Użycie: Production emergency
- Czas: ~30 sekund

**6. ROLLBACK** (Przywracanie z backup)
```powershell
.\deploy.ps1 -Type Rollback -BackupName "backup_20251204_143022"
```
- Restore z backupu
- Database restore (manual confirmation)
- Full verification
- Użycie: Deployment failure recovery
- Czas: ~3-5 minut

### Proposed Tools Structure

```
_TOOLS/
├── deploy.ps1                      # 🆕 MAIN unified deployment script
├── deploy-config.json              # 🆕 Centralized configuration
├── deploy-lib.ps1                  # 🆕 Shared functions library
│
├── hostido_automation.ps1          # ✅ Keep (SSH backend)
├── hostido_deploy.ps1              # ⚠️ Integrate into deploy.ps1
├── hostido_quick_push.ps1          # ⚠️ Integrate as -Type Hotfix
├── hostido_build.ps1               # ⚠️ Integrate as -Type Assets
│
├── _archive/                       # 🆕 Move old deploy_* scripts here
│   └── deploy_*.ps1 (111 files)    # Historical reference
│
└── _logs/                          # 🆕 Deployment logs directory
    └── deploy_YYYYMMDD_HHMMSS.log
```

## ZAPROPONOWANE NARZĘDZIA

### 1. deploy.ps1 (Main Unified Script)

**Sygnatura:**
```powershell
.\deploy.ps1 `
    -Type <Full|Code|Assets|Migration|Hotfix|Rollback> `
    [-Files <string[]>] `
    [-Environment <dev|production>] `
    [-BackupName <string>] `
    [-SkipBackup] `
    [-SkipVerification] `
    [-DryRun] `
    [-Verbose]
```

**Przykłady użycia:**

```powershell
# Full deployment (release)
.\deploy.ps1 -Type Full -Environment production

# Code update (bugfix)
.\deploy.ps1 -Type Code -Files "app/Http/Livewire/Products/ProductForm.php"

# Multiple files (feature)
.\deploy.ps1 -Type Code -Files @(
    "app/Services/CategoryService.php",
    "app/Http/Controllers/CategoryController.php",
    "resources/views/livewire/categories/*.blade.php"
)

# Assets rebuild + deploy
.\deploy.ps1 -Type Assets

# Migration deployment
.\deploy.ps1 -Type Migration -Files "database/migrations/2025_12_04_*.php"

# Emergency hotfix (skip backup)
.\deploy.ps1 -Type Hotfix -Files "app/Services/Payment.php" -SkipBackup

# Rollback
.\deploy.ps1 -Type Rollback -BackupName "backup_20251204_120000"

# Dry-run test (no actual changes)
.\deploy.ps1 -Type Full -DryRun
```

**Features:**
- ✅ Type-based deployment strategies
- ✅ Automatic backup before changes
- ✅ Smart file detection (PHP/Blade/CSS/JS)
- ✅ Incremental migrations
- ✅ Health check verification
- ✅ Chrome DevTools MCP integration (UI verification)
- ✅ Detailed logging
- ✅ Rollback capability
- ✅ Dry-run mode

### 2. deploy-config.json (Centralized Configuration)

```json
{
  "hostido": {
    "host": "host379076.hostido.net.pl",
    "user": "host379076",
    "port": 64321,
    "sshKey": "D:\\OneDrive - MPP TRADE\\SSH\\Hostido\\HostidoSSHNoPass.ppk",
    "remotePath": "domains/ppm.mpptrade.pl/public_html"
  },
  "project": {
    "localRoot": "D:\\OneDrive - MPP TRADE\\Skrypty\\PPM-CC-Laravel",
    "buildOutput": "public/build",
    "logPath": "_TOOLS/_logs"
  },
  "deployment": {
    "autoBackup": true,
    "backupRetention": 10,
    "verifyAfterDeploy": true,
    "clearCacheAfterDeploy": true
  },
  "excludePatterns": [
    "node_modules/*",
    ".git/*",
    "tests/*",
    "storage/logs/*",
    "storage/framework/cache/*",
    ".env*",
    "*.log",
    "_TOOLS/*",
    "_AGENT_REPORTS/*"
  ],
  "notifications": {
    "slack": {
      "enabled": false,
      "webhook": ""
    },
    "email": {
      "enabled": false,
      "recipients": []
    }
  }
}
```

### 3. deploy-lib.ps1 (Shared Functions Library)

**Główne funkcje:**

```powershell
# Configuration Management
function Get-DeployConfig { }
function Test-DeployRequirements { }

# File Operations
function Get-ChangedFiles { param([string[]]$Patterns) }
function Get-FileCategory { param([string]$File) } # PHP|Blade|CSS|JS|Migration
function Sync-FilesToServer { param([string[]]$Files, [string]$RemotePath) }

# Build Operations
function Test-BuildRequired { param([string[]]$Files) }
function Invoke-AssetBuild { param([string]$Environment) }
function Test-BuildOutput { }

# Backup Operations
function New-DeploymentBackup { param([string]$Type) }
function Restore-DeploymentBackup { param([string]$BackupName) }
function Get-BackupList { }
function Remove-OldBackups { param([int]$RetentionCount) }

# SSH Operations (wrapper dla hostido_automation.ps1)
function Invoke-RemoteCommand { param([string]$Command) }
function Test-RemoteHealth { }
function Get-RemoteLogs { param([string]$Level, [int]$Lines) }

# Cache Operations
function Clear-RemoteCache { param([string[]]$Types) } # config|route|view|all
function Optimize-RemoteCache { }

# Migration Operations
function Get-PendingMigrations { }
function Invoke-Migrations { param([string[]]$Files) }

# Verification Operations
function Test-DeploymentHealth { }
function Test-HttpResponse { param([string]$Url) }
function Test-ChromeDevTools { param([string]$Url) } # MCP integration
function Get-DeploymentReport { param([hashtable]$Results) }

# Logging
function Write-DeployLog { param([string]$Message, [string]$Level) }
function New-DeploymentLogFile { }
function Export-DeploymentSummary { param([hashtable]$Results) }
```

### 4. Deployment Checklist Generator

**Funkcja:** Automatyczna generacja checklist dla manual testing po deployment

```powershell
function New-DeploymentChecklist {
    param([string[]]$Files, [string]$Type)

    # Analiza zmienionych plików
    $categories = $Files | ForEach-Object { Get-FileCategory $_ } | Select-Object -Unique

    # Generacja checklist based on categories
    $checklist = @()

    if ($categories -contains "Livewire") {
        $checklist += "□ Test Livewire component interactions"
        $checklist += "□ Verify wire:loading states"
        $checklist += "□ Check console for Livewire errors"
    }

    if ($categories -contains "Migration") {
        $checklist += "□ Verify database schema changes"
        $checklist += "□ Check migration status: php artisan migrate:status"
        $checklist += "□ Test affected queries/models"
    }

    if ($categories -contains "CSS" -or $categories -contains "JS") {
        $checklist += "□ Clear browser cache (Ctrl+Shift+R)"
        $checklist += "□ Verify styles loaded (Chrome DevTools Network)"
        $checklist += "□ Check for 404 errors on assets"
        $checklist += "□ Screenshot comparison (before/after)"
    }

    # Output
    $checklistFile = "_TOOLS/_logs/checklist_$(Get-Date -Format 'yyyyMMdd_HHmmss').md"
    $checklist | Out-File $checklistFile

    Write-Host "📋 Checklist created: $checklistFile"
}
```

## DEPLOYMENT WORKFLOW EXAMPLES

### Example 1: Feature Deployment (ETAP_07 PrestaShop API)

**Przed unified workflow:**
```powershell
# Poprzednio: 6 osobnych skryptów
.\deploy_etap07_migrations.ps1
.\deploy_etap07_models.ps1
.\deploy_etap07_api_clients.ps1
.\deploy_etap07_transformers_mappers.ps1
.\deploy_etap07_sync_strategies.ps1
.\deploy_etap07_queue_jobs.ps1
```

**Z unified workflow:**
```powershell
# Teraz: Jeden skrypt z automatyczną detekcją
.\deploy.ps1 -Type Full -Environment production -Verbose

# Lub precyzyjny deployment tylko zmian:
.\deploy.ps1 -Type Code -Files @(
    "app/Models/PrestaShopShop.php",
    "app/Services/PrestaShop/*.php",
    "app/Jobs/PrestaShop/*.php"
)

# Plus migracje osobno (best practice):
.\deploy.ps1 -Type Migration -Files "database/migrations/2025_10_01_*.php"
```

### Example 2: UI Fix Deployment

**Przed:**
```powershell
# CSS fix
.\deploy_css_quick.ps1

# Blade fix
.\deploy_productform_blade_fix.ps1

# Cache clear manually
plink ... "php artisan cache:clear"
```

**Teraz:**
```powershell
# Assets rebuild + deploy (jeśli zmiana CSS w resources/css/)
.\deploy.ps1 -Type Assets

# Lub tylko Blade (jeśli zmiana template bez CSS)
.\deploy.ps1 -Type Code -Files "resources/views/livewire/products/management/product-form.blade.php"

# Automatic cache clear included!
```

### Example 3: Emergency Hotfix

**Przed:**
```powershell
# Manual pscp + cache clear
pscp -i ... "app/Services/CriticalService.php" "host:..."
plink ... "cd ... && php artisan cache:clear"
```

**Teraz:**
```powershell
# One command, skip backup (speed)
.\deploy.ps1 -Type Hotfix -Files "app/Services/CriticalService.php" -SkipBackup

# Auto verification included
```

## MIGRATION STRATEGY

### Faza 1: Przygotowanie (Week 1)
- ✅ Utworzenie deploy.ps1 z podstawową funkcjonalnością
- ✅ Utworzenie deploy-lib.ps1 z shared functions
- ✅ Utworzenie deploy-config.json
- ✅ Integracja z istniejącymi hostido_*.ps1 scripts
- ✅ Testowanie na dev environment

### Faza 2: Wdrożenie (Week 2)
- ✅ Przeniesienie 111 deploy_*.ps1 do _archive/
- ✅ Dokumentacja nowego workflow w _DOCS/DEPLOYMENT_GUIDE.md
- ✅ Training session (dokumentacja + przykłady)
- ✅ First production deployment z nowym systemem

### Faza 3: Optymalizacja (Week 3-4)
- ✅ Chrome DevTools MCP integration (UI verification)
- ✅ Automated rollback triggers (health check failure)
- ✅ Deployment analytics (success rate, timing)
- ✅ Slack/email notifications
- ✅ CI/CD pipeline integration (future)

## DEPLOYMENT CHECKLIST (Template)

### PRE-DEPLOYMENT
- [ ] Git commit all changes
- [ ] Local tests passing (`php artisan test`)
- [ ] Code review completed
- [ ] Migration files validated (if applicable)
- [ ] package.json/composer.json changes reviewed

### DEPLOYMENT
- [ ] Backup created successfully
- [ ] Files uploaded without errors
- [ ] Migrations executed (if applicable)
- [ ] Cache cleared
- [ ] Permissions set correctly (storage/, bootstrap/cache/)

### VERIFICATION
- [ ] Laravel health check passing
- [ ] HTTP 200/302 response from homepage
- [ ] Chrome DevTools: No console errors
- [ ] Chrome DevTools: Assets HTTP 200 (manifest verification)
- [ ] Chrome DevTools: No wire:snapshot rendering issues
- [ ] Screenshot comparison (before/after UI changes)
- [ ] Functional testing (critical paths)

### POST-DEPLOYMENT
- [ ] Deployment log saved
- [ ] Checklist completed
- [ ] Team notified (if major release)
- [ ] Documentation updated (if needed)
- [ ] Rollback plan confirmed (backup name noted)

## KORZYŚCI Z UNIFIED WORKFLOW

### Dla Zespołu
- ✅ **Consistency**: Jeden standardowy sposób deployment
- ✅ **Speed**: Mniej manual steps, automated verification
- ✅ **Safety**: Automatic backups, health checks, rollback capability
- ✅ **Visibility**: Detailed logs, deployment history
- ✅ **Learning Curve**: Prostsza struktura (1 główny skrypt vs 111 plików)

### Dla Projektu
- ✅ **Maintainability**: Centralized logic, easier updates
- ✅ **Reliability**: Fewer human errors, consistent process
- ✅ **Scalability**: Easy to add new deployment types
- ✅ **Auditing**: Full deployment history, compliance-ready
- ✅ **CI/CD Ready**: Foundation dla automated deployments

### Dla Development Flow
- ✅ **Faster iteration**: Hotfix w 30 sekund zamiast 5 minut
- ✅ **Confidence**: Automatic verification przed go-live
- ✅ **Recovery**: One-command rollback
- ✅ **Documentation**: Auto-generated checklists

## TECHNICAL DECISIONS

### Dlaczego PowerShell 7?
- ✅ Cross-platform (Windows primary, Linux future)
- ✅ Bogata standardowa biblioteka
- ✅ Native SSH support (OpenSSH)
- ✅ JSON handling (ConvertFrom-Json)
- ✅ Color output (ANSI escape codes)
- ✅ Async operations (Start-Job)

### Dlaczego WinSCP + plink?
- ✅ Mature, enterprise-ready tools
- ✅ Batch scripting support
- ✅ Private key authentication
- ✅ Resume capability (large files)
- ✅ Shared hosting compatible (Hostido)

### Dlaczego JSON config?
- ✅ Easy to edit (non-technical users)
- ✅ Version control friendly
- ✅ Native PowerShell parsing
- ✅ Extensible (add fields bez zmian w code)
- ✅ Environment-specific configs (dev/staging/prod)

### Dlaczego Chrome DevTools MCP?
- ✅ **MANDATORY dla Livewire apps** - wykrywa wire:snapshot rendering issues
- ✅ Console error detection (JS/Livewire runtime errors)
- ✅ Network verification (HTTP 200 dla assets, manifest verification)
- ✅ Interactive testing (clicks, forms, state changes)
- ✅ Visual verification (screenshots)
- ❌ curl/HTTP checks DON'T catch Livewire component errors
- ❌ Node.js scripts CAN'T interact z Livewire wire:loading states

**Reference:** `_DOCS/CHROME_DEVTOOLS_MCP_GUIDE.md`

## NEXT STEPS

### Immediate Actions (Priorytet 1)
1. Utworzenie `deploy.ps1` z core functionality
2. Utworzenie `deploy-lib.ps1` z shared functions
3. Utworzenie `deploy-config.json` z production values
4. Testing na dev environment

### Short-term (Priorytet 2)
5. Dokumentacja w `_DOCS/DEPLOYMENT_GUIDE.md`
6. Migration guide dla zespołu
7. Archive starych skryptów
8. Chrome DevTools MCP integration dla UI verification

### Long-term (Priorytet 3)
9. Slack/email notifications
10. CI/CD pipeline integration (GitHub Actions)
11. Deployment analytics dashboard
12. Multi-environment management (dev/staging/prod)

## PLIKI DO UTWORZENIA

- `_TOOLS/deploy.ps1` - Main deployment script
- `_TOOLS/deploy-lib.ps1` - Shared functions library
- `_TOOLS/deploy-config.json` - Centralized configuration
- `_DOCS/DEPLOYMENT_GUIDE.md` - Complete deployment documentation
- `_TOOLS/_archive/` - Directory dla starych skryptów
- `_TOOLS/_logs/` - Directory dla deployment logs

## RECOMMENDED READING

Dla zespołu deployment przed wdrożeniem:
- `_DOCS/DEPLOYMENT_GUIDE.md` - Pełny przewodnik
- `CLAUDE.md` - Sekcja "Deployment Environment"
- `_ISSUES_FIXES/CSS_INCOMPLETE_DEPLOYMENT_ISSUE.md` - Vite manifest lessons learned
- `_DOCS/CHROME_DEVTOOLS_MCP_GUIDE.md` - UI verification mandatory workflow

## PODSUMOWANIE

**Status obecny:**
- 111 skryptów deployment (historical growth)
- 4 główne narzędzia infrastruktury (mature, stable)
- Duplikacja kodu, brak standardizacji
- Manual workflow, prone to errors

**Docelowy stan:**
- 1 unified deployment script (`deploy.ps1`)
- Type-based deployment strategies (Full/Code/Assets/Migration/Hotfix/Rollback)
- Automated backup/verification/rollback
- Chrome DevTools MCP integration (mandatory UI verification)
- Centralized configuration
- Complete audit trail

**Effort estimation:**
- Faza 1 (Development): 2-3 dni
- Faza 2 (Migration): 1 dzień
- Faza 3 (Optimization): 1-2 tygodnie (iterative)

**Risk mitigation:**
- Keep old scripts in _archive/ (reference + emergency fallback)
- Dry-run mode dla testing
- Mandatory backup przed każdym deployment
- Health check verification
- Rollback capability

---

**Rekomendacja:** APPROVE deployment workflow redesign. Benefits (consistency, safety, speed) znacznie przewyższają cost (development time, learning curve).
