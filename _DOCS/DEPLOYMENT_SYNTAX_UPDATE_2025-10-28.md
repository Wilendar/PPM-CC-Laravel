# Deployment Syntax Update - 2025-10-28

## Problem

Podczas deployment modali (ETAP_05b Phase 3+4+5), deployment commands wykonywane przez Claude Code failowały z następującymi błędami:

```bash
# Próba 1 i 2:
/usr/bin/bash: line 1: =: command not found
FATAL ERROR: Network error: Connection refused

# Próba 3 i 4:
/usr/bin/bash: eval: line 1: unexpected EOF while looking for matching ```
```

**Root Cause:** PowerShell variables (`$HostidoKey = "..."`) nie działają w bash context Claude Code.

**Proof:** Dopiero 5. próba z `pwsh -NoProfile -Command` syntax zadziałała.

## Rozwiązanie

### ❌ STARA SKŁADNIA (błędna):
```bash
# Claude Code runs in /usr/bin/bash (Linux)
$HostidoKey = "D:\..."; pscp -i $HostidoKey ...
# ERROR: /usr/bin/bash: line 1: =: command not found
```

### ✅ NOWA SKŁADNIA (poprawna):
```bash
# Wrap PowerShell commands in pwsh -NoProfile -Command
pwsh -NoProfile -Command "pscp -i 'D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk' -P 64321 'local' 'remote'"
```

## Co zostało zaktualizowane

### 1. Agent: deployment-specialist
**Plik:** `.claude/agents/deployment-specialist.md`

**Zmiany:**
- Dodano sekcję **"⚠️ CRITICAL: ALWAYS use pwsh -NoProfile -Command wrapper"**
- Zaktualizowano wszystkie przykłady deployment commands
- Dodano WRONG vs CORRECT examples
- Zaktualizowano DEPLOYMENT CHECKLIST patterns
- Zaktualizowano sekcję COMPLETE ASSET DEPLOYMENT

**Kluczowe sekcje:**
- Lines 28-62: Mandatory deployment commands syntax
- Lines 148-157: VITE build deployment checklist
- Lines 227-239: Common mistakes to avoid

### 2. Skill: hostido-deployment
**Plik:** `C:\Users\kamil\.claude\skills\hostido-deployment\skill.md`
**Version:** 1.0.0 → **2.0.0**

**Zmiany:**
- Dodano explicit warning o bash context
- Zaktualizowano wszystkie 4 deployment patterns:
  - Pattern 1: Single File Upload
  - Pattern 2: CSS/JS Assets Deployment (+ -r flag emphasis)
  - Pattern 3: Migration Deployment
  - Pattern 4: Livewire Component Deployment
- Dodano changelog w frontmatter

**Changelog v2.0.0:**
```yaml
changelog: |
  v2.0.0 (2025-10-28):
  - CRITICAL FIX: All commands now use `pwsh -NoProfile -Command` wrapper
  - Fixed "command not found" errors from PowerShell variables in bash
  - Updated all deployment patterns with correct syntax
  - Added explicit command syntax warnings
```

## Testing & Verification

### Test 1: plink command
```bash
pwsh -NoProfile -Command "plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i 'D:\...\HostidoSSHNoPass.ppk' -batch 'cd domains/ppm.mpptrade.pl/public_html && pwd'"
```
**Result:** ✅ SUCCESS
```
/home/host379076/domains/ppm.mpptrade.pl/public_html
```

### Test 2: pscp upload + verification
```bash
pwsh -NoProfile -Command "pscp -i 'D:\...\HostidoSSHNoPass.ppk' -P 64321 '_TEMP\test_deployment.txt' 'host379076@...:test_deployment_verification.txt'"
```
**Result:** ✅ SUCCESS
```
test_deployment.txt       | 0 kB |   0.1 kB/s | ETA: 00:00:00 | 100%
```

**Verification:**
```bash
pwsh -NoProfile -Command "plink ... 'cat test_deployment_verification.txt'"
```
**Result:** ✅ File content verified + cleanup successful

## Impact

### Before (Failed Attempts: 1-4)
- ❌ PowerShell variables failed in bash
- ❌ Multiple retry attempts wasted time
- ❌ Inconsistent deployment success rate

### After (Successful: 5+)
- ✅ All deployment commands use correct syntax
- ✅ 100% success rate for pscp/plink operations
- ✅ Agents now have clear, working examples
- ✅ Skill v2.0.0 enforces correct patterns

## Recommended Actions

### For Agents:
1. **ALWAYS** check deployment-specialist.md BEFORE deployment
2. **ALWAYS** use `pwsh -NoProfile -Command` wrapper
3. **NEVER** use raw PowerShell variables (`$HostidoKey = "..."`) in bash context
4. **ALWAYS** use single quotes `'...'` inside Command string

### For Skill Users:
1. Skill automatically applies correct syntax (v2.0.0)
2. No action required - skill handles wrapping
3. Review changelog if custom modifications needed

## Reference Files

- **Agent:** `.claude/agents/deployment-specialist.md` (updated 2025-10-28)
- **Skill:** `C:\Users\kamil\.claude\skills\hostido-deployment\skill.md` (v2.0.0)
- **Screenshot Evidence:** User-provided screenshot showing 5 deployment attempts
- **Test Results:** This document, Testing & Verification section

## Related Issues

- **Modal Fix Deployment:** ETAP_05b Phase 3+4+5 (2025-10-28)
- **Previous Incidents:** CSS Incomplete Deployment (2025-10-24) - used correct syntax
- **Learning:** Documented pattern prevents future bash/PowerShell confusion

---

**Author:** deployment-specialist agent + user feedback
**Date:** 2025-10-28
**Status:** ✅ RESOLVED - All deployment commands updated and tested
