# 🚀 Unified Deployment System - Quick Start Guide

## Przegląd

Unified deployment system dla PPM-CC-Laravel zastępuje 111+ indywidualnych skryptów deployment jednym zunifikowanym narzędziem z type-based strategies.

## Główne Pliki

- **deploy.ps1** - Main deployment script (unified interface)
- **deploy-config.json** - Centralized configuration
- **deploy-lib.ps1** - Shared functions library
- **hostido_automation.ps1** - SSH backend (existing tool)
- **hostido_quick_push.ps1** - Fast file upload (existing tool)
- **hostido_build.ps1** - Asset build pipeline (existing tool)

## Typy Deployment

### 1. FULL DEPLOYMENT (Kompletny release)

```powershell
.\deploy.ps1 -Type Full -Environment production
```

**Kiedy używać:**
- Release nowej wersji aplikacji
- Major changes (multiple files/features)
- End of sprint deployment

**Co robi:**
- ✅ Pre-deployment checks
- ✅ Automatic backup
- ✅ Build Vite assets (npm run build)
- ✅ Upload ALL files (code + assets)
- ✅ Run pending migrations
- ✅ Post-deployment tasks (cache, permissions)
- ✅ Health check + verification

**Czas wykonania:** ~5-10 minut

---

### 2. CODE-ONLY DEPLOYMENT (Tylko kod PHP/Blade)

```powershell
# Single file
.\deploy.ps1 -Type Code -Files "app\Http\Livewire\Products\ProductForm.php"

# Multiple files
.\deploy.ps1 -Type Code -Files @(
    "app\Services\CategoryService.php",
    "app\Http\Controllers\CategoryController.php",
    "resources\views\livewire\categories\category-tree.blade.php"
)
```

**Kiedy używać:**
- Bugfix (logic changes)
- Backend updates (no UI changes)
- Livewire component changes (PHP only)

**Co robi:**
- ✅ Pre-checks
- ✅ Optional backup
- ✅ Upload specified files
- ✅ Clear selective cache (config, cache)
- ✅ Quick verification

**Czas wykonania:** ~1-2 minuty

---

### 3. ASSETS-ONLY DEPLOYMENT (Rebuild Vite assets)

```powershell
.\deploy.ps1 -Type Assets
```

**Kiedy używać:**
- CSS changes (resources/css/)
- JavaScript changes (resources/js/)
- Any Vite asset modification

**Co robi:**
- ✅ Pre-checks
- ✅ npm run build (lokalnie)
- ✅ Upload **ALL** public/build/assets/* + manifest.json
- ✅ Clear view cache
- ✅ Verification (HTTP 200 na assets)

**⚠️ KRYTYCZNE:** Zawsze uploaduj WSZYSTKIE assets, nie tylko zmienione pliki!

**Czas wykonania:** ~2-3 minuty

---

### 4. MIGRATION DEPLOYMENT (Migracje bazy danych)

```powershell
# Single migration
.\deploy.ps1 -Type Migration -Files "database\migrations\2025_12_04_create_products_table.php"

# Multiple migrations
.\deploy.ps1 -Type Migration -Files "database\migrations\2025_12_04_*.php"
```

**Kiedy używać:**
- Schema changes
- Database structure updates
- Seeder deployment

**Co robi:**
- ✅ Pre-checks
- ✅ **MANDATORY backup** (database + files)
- ✅ Upload migration files
- ✅ Run: php artisan migrate --force
- ✅ Verification

**⚠️ UWAGA:** Backup jest OBOWIĄZKOWY dla migrations (nie można pominąć)

**Czas wykonania:** ~2-5 minut

---

### 5. HOTFIX DEPLOYMENT (Emergency fixes)

```powershell
.\deploy.ps1 -Type Hotfix -Files "app\Services\CriticalService.php" -SkipBackup
```

**Kiedy używać:**
- Production emergency
- Critical bugfix
- Time-sensitive patches

**Co robi:**
- ✅ Upload files IMMEDIATELY (no backup)
- ✅ Clear only related cache
- ✅ Minimal verification

**⚠️ UWAGA:** Używać TYLKO w sytuacjach emergency! Brak backup = ryzyko!

**Czas wykonania:** ~30 sekund

---

### 6. ROLLBACK (Przywracanie z backup)

```powershell
.\deploy.ps1 -Type Rollback -BackupName "backup_20251204_143022"
```

**Kiedy używać:**
- Deployment failure
- Critical bugs after deployment
- Need to revert to previous state

**Co robi:**
- ✅ Confirmation prompt (safety)
- ✅ Restore files from backup
- ✅ Database restore (manual confirmation)
- ✅ Full verification

**⚠️ UWAGA:** Wymaga manual confirmation - critical operation!

**Czas wykonania:** ~3-5 minut

---

## Parametry Dodatkowe

### -Environment

```powershell
.\deploy.ps1 -Type Full -Environment dev
.\deploy.ps1 -Type Assets -Environment production
```

- `dev` - Development build (npm run dev)
- `production` - Production build (npm run build) - DEFAULT

### -SkipBackup

```powershell
.\deploy.ps1 -Type Code -Files "app\Services\Test.php" -SkipBackup
```

Pomija tworzenie backup (tylko dla Hotfix i Code deployment).

⚠️ **NIE DZIAŁA** dla Migration (backup zawsze mandatory).

### -SkipVerification

```powershell
.\deploy.ps1 -Type Full -SkipVerification
```

Pomija verification phase (health check, HTTP check, DevTools).

⚠️ **Nie rekomendowane** - używać tylko dla trusted deployments.

### -DryRun

```powershell
.\deploy.ps1 -Type Full -DryRun
```

Test mode - wyświetla co zostałoby wykonane BEZ faktycznego wykonania.

✅ **Rekomendowane** przed pierwszym użyciem każdego typu deployment.

### -Verbose

```powershell
.\deploy.ps1 -Type Full -Verbose
```

Szczegółowe logi wszystkich operacji (debug mode).

---

## Przykłady Użycia (Real-world Scenarios)

### Scenario 1: Feature Release (ETAP_07 PrestaShop API)

**Przed unified system:**
```powershell
.\deploy_etap07_migrations.ps1
.\deploy_etap07_models.ps1
.\deploy_etap07_api_clients.ps1
.\deploy_etap07_transformers_mappers.ps1
.\deploy_etap07_sync_strategies.ps1
.\deploy_etap07_queue_jobs.ps1
```

**Z unified system:**
```powershell
# Option A: Full deployment (recommended)
.\deploy.ps1 -Type Full

# Option B: Incremental (migrations + code)
.\deploy.ps1 -Type Migration -Files "database\migrations\2025_10_01_*.php"
.\deploy.ps1 -Type Code -Files "app\Models\PrestaShopShop.php","app\Services\PrestaShop\*.php","app\Jobs\PrestaShop\*.php"
```

### Scenario 2: UI Fix (CSS + Blade)

**Przed:**
```powershell
.\deploy_css_quick.ps1
.\deploy_productform_blade_fix.ps1
plink ... "php artisan cache:clear"
```

**Teraz:**
```powershell
# Assets rebuild (jeśli zmiana CSS w resources/css/)
.\deploy.ps1 -Type Assets

# Lub tylko Blade (jeśli template bez CSS)
.\deploy.ps1 -Type Code -Files "resources\views\livewire\products\management\product-form.blade.php"
```

### Scenario 3: Production Hotfix

**Przed:**
```powershell
pscp -i ... "app\Services\Critical.php" "host:..."
plink ... "cd ... && php artisan cache:clear"
```

**Teraz:**
```powershell
.\deploy.ps1 -Type Hotfix -Files "app\Services\CriticalService.php" -SkipBackup
```

### Scenario 4: End-of-Sprint Release

```powershell
# 1. DRY-RUN first (verify)
.\deploy.ps1 -Type Full -DryRun

# 2. Actual deployment
.\deploy.ps1 -Type Full -Environment production -Verbose

# 3. If failed - rollback
.\deploy.ps1 -Type Rollback -BackupName "backup_20251204_143022"
```

---

## Verification Checklist (Po Deployment)

### Automatic Verification (w skrypcie)
- ✅ Laravel health check (php artisan --version)
- ✅ HTTP response check (curl https://ppm.mpptrade.pl)
- ✅ Chrome DevTools MCP (if enabled)

### Manual Verification (zalecane)
- [ ] Login to application
- [ ] Test critical paths (products, categories, sync)
- [ ] Check console for errors (Chrome DevTools F12)
- [ ] Verify network requests (all HTTP 200)
- [ ] Screenshot comparison (before/after)
- [ ] Check Laravel logs: `.\hostido_automation.ps1 -GetLogs`

---

## Troubleshooting

### Problem: "Configuration file not found"

```powershell
# Rozwiązanie: Sprawdź czy deploy-config.json istnieje
Test-Path "_TOOLS\deploy-config.json"

# Jeśli nie - utwórz z template w dokumentacji
```

### Problem: "Upload failed"

```powershell
# Sprawdź połączenie SSH
.\hostido_automation.ps1 -TestConnection

# Sprawdź czy klucz SSH jest dostępny
Test-Path "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
```

### Problem: "Build failed"

```powershell
# Sprawdź lokalne buildy
npm run build

# Sprawdź logi
cat "_TOOLS\_logs\deploy_$(Get-Date -Format 'yyyyMMdd').log"
```

### Problem: "Verification failed"

```powershell
# Health check manual
.\hostido_automation.ps1 -HealthCheck

# Sprawdź logi Laravel
.\hostido_automation.ps1 -GetLogs -LogLevel error -LogLines 100
```

### Problem: "Need to rollback"

```powershell
# Lista dostępnych backupów (via SSH)
plink ... "ls -la domains/ppm.mpptrade.pl/backups/"

# Rollback do konkretnego backup
.\deploy.ps1 -Type Rollback -BackupName "backup_20251204_120000"
```

---

## Migration Path (Z old scripts → unified system)

### Phase 1: Testing (Week 1)
1. **Dry-run testing:**
   ```powershell
   .\deploy.ps1 -Type Full -DryRun
   .\deploy.ps1 -Type Code -Files "test.php" -DryRun
   ```

2. **Dev environment testing:**
   ```powershell
   .\deploy.ps1 -Type Full -Environment dev
   ```

3. **Small production test:**
   ```powershell
   .\deploy.ps1 -Type Code -Files "app\Services\Test.php"
   ```

### Phase 2: Adoption (Week 2)
1. Use unified system dla nowych deployments
2. Keep old scripts w `_TOOLS\_archive\` (reference)
3. Dokumentacja team usage patterns

### Phase 3: Full Migration (Week 3-4)
1. Archive ALL old deploy_*.ps1 scripts
2. Update team documentation
3. Training session
4. Remove old scripts (after 1 month safety period)

---

## Best Practices

### ✅ DO:
- Zawsze używaj `-DryRun` przed first-time deployment typu
- Twórz backup przed migrations (automatic)
- Verify deployment ręcznie po automatic verification
- Use Chrome DevTools dla UI changes
- Keep deployment logs (automatic w `_TOOLS\_logs\`)
- Review backup list regularnie (retention: 10 backups)

### ❌ DON'T:
- Nie używaj `-SkipBackup` dla migrations (blocked by script)
- Nie uploaduj tylko wybranych assets (ZAWSZE wszystkie!)
- Nie pomijaj verification dla production deployments
- Nie używaj Hotfix dla non-emergency (use Code instead)
- Nie usuwaj backupów ręcznie (automatic retention)

---

## Support & Documentation

### Dodatkowa Dokumentacja:
- `_DOCS/DEPLOYMENT_GUIDE.md` - Complete deployment manual
- `_DOCS/CHROME_DEVTOOLS_MCP_GUIDE.md` - UI verification guide
- `_ISSUES_FIXES/CSS_INCOMPLETE_DEPLOYMENT_ISSUE.md` - Vite asset lessons learned
- `CLAUDE.md` - Project context + deployment section

### Logi i Monitoring:
- Deployment logs: `_TOOLS\_logs\deploy_YYYYMMDD.log`
- Summary reports: `_TOOLS\_logs\summary_YYYYMMDD_HHMMSS.txt`
- Laravel logs: `.\hostido_automation.ps1 -GetLogs`

### Helpful Commands:
```powershell
# Test connection
.\hostido_automation.ps1 -TestConnection

# Health check
.\hostido_automation.ps1 -HealthCheck

# Monitor application
.\hostido_automation.ps1 -MonitorApp

# Get recent logs
.\hostido_automation.ps1 -GetLogs -LogLevel error -LogLines 50
```

---

## Changelog

### Version 1.0.0 (2025-12-04)
- Initial release
- 6 deployment types (Full, Code, Assets, Migration, Hotfix, Rollback)
- Centralized configuration (deploy-config.json)
- Shared functions library (deploy-lib.ps1)
- Integration z existing tools (hostido_*.ps1)
- Automatic backup/verification/rollback
- Comprehensive logging

---

## Quick Reference Card

```powershell
# FULL DEPLOYMENT
.\deploy.ps1 -Type Full

# CODE UPDATE
.\deploy.ps1 -Type Code -Files "path\to\file.php"

# ASSETS REBUILD
.\deploy.ps1 -Type Assets

# MIGRATION
.\deploy.ps1 -Type Migration -Files "database\migrations\*.php"

# HOTFIX (emergency)
.\deploy.ps1 -Type Hotfix -Files "path\to\file.php" -SkipBackup

# ROLLBACK
.\deploy.ps1 -Type Rollback -BackupName "backup_YYYYMMDD_HHMMSS"

# DRY-RUN (test)
.\deploy.ps1 -Type Full -DryRun

# VERBOSE (debug)
.\deploy.ps1 -Type Full -Verbose
```

---

**Pytania lub problemy?** Check logs w `_TOOLS\_logs\` lub run `.\hostido_automation.ps1 -HealthCheck`
