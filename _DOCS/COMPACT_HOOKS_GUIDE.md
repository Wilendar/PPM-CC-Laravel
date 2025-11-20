# COMPACT HOOKS GUIDE - System Snapshots i Recovery

## Przegląd

System Python hooks dla zachowania kontekstu podczas kompaktowania (compaction) sesji Claude Code.

## Struktura Hooków

### 1. PreCompact Hook
**Plik:** `_TOOLS/pre_compact_snapshot.py`
**Uruchamiany:** PRZED kompaktowaniem kontekstu
**Timeout:** 5000ms

**Funkcje:**
- Tworzy snapshot kontekstu sesji
- Zapisuje krytyczne zasady projektu
- Zapisuje deployment workflow
- Zapisuje informacje o aktualnych zadaniach

**Output:**
```
📦 PRE-COMPACT SNAPSHOT
✅ Snapshot zapisany: snapshot_2025-10-31T10-30-00.json
⚠️ KRYTYCZNE ZASADY (zachowane po compact):
   • Context7 MANDATORY przed implementacją
   • NO HARDCODING - wszystko konfigurowane
   • NO MOCK DATA - tylko prawdziwe struktury
   [...]
```

**Snapshot Location:**
- `_TEMP/compact_snapshots/snapshot_TIMESTAMP.json`
- `_TEMP/compact_snapshots/latest_snapshot.json` (symlink do ostatniego)

### 2. PostAutoCompact Hook (SessionStart)
**Plik:** `_TOOLS/post_autocompact_recovery.py`
**Uruchamiany:** PO automatycznym kompaktowaniu (SessionStart z matcherem "compact")
**Timeout:** 5000ms

**Funkcje:**
- Wczytuje ostatni snapshot
- Wyświetla krytyczne zasady z CLAUDE.md
- Przypomina deployment workflow
- Pokazuje deployment info (SSH, credentials)
- Listuje kluczową dokumentację

**Output:**
```
======================================================================
🔄 POST-AUTOCOMPACT RECOVERY
======================================================================

📦 SNAPSHOT RECOVERY
   Timestamp: 2025-10-31T10:30:00
   Project: PPM-CC-Laravel

======================================================================
⚠️  KRYTYCZNE ZASADY PPM-CC-Laravel
======================================================================

🔹 Vite & Build:
   • ⚠️ KRYTYCZNA ZASADA: Vite działa TYLKO na lokalnej maszynie!
   • Deploy WSZYSTKIE pliki z public/build/assets/
   • Upload manifest do ROOT: public/build/manifest.json
   • HTTP 200 Verification MANDATORY dla wszystkich CSS

🔹 Frontend Verification:
   • ⚠️ OBOWIĄZKOWA WERYFIKACJA przed informowaniem użytkownika
   • PPM Verification Tool: _TOOLS/full_console_test.cjs
   • Screenshot verification MANDATORY

[...]

✅ RECOVERY COMPLETE - Kontekst przywrócony
======================================================================
```

## Konfiguracja

**Lokalizacja:** `.claude/settings.local.json`

```json
{
  "hooks": {
    "PreCompact": [
      {
        "hooks": [
          {
            "type": "command",
            "command": "python _TOOLS/pre_compact_snapshot.py",
            "timeout": 5000
          }
        ]
      }
    ],
    "SessionStart": [
      {
        "matcher": "compact",
        "hooks": [
          {
            "type": "command",
            "command": "python _TOOLS/post_autocompact_recovery.py",
            "timeout": 5000
          }
        ]
      }
    ]
  }
}
```

**Permissions (wymagane):**
```json
{
  "permissions": {
    "allow": [
      "Bash(python _TOOLS/pre_compact_snapshot.py:*)",
      "Bash(python _TOOLS/post_autocompact_recovery.py:*)"
    ]
  }
}
```

## Workflow Kompaktowania

```
[Claude Session]
       ↓
   (context too large)
       ↓
[TRIGGER: PreCompact]
       ↓
   pre_compact_snapshot.py
       ↓
   • Snapshot TODO list
   • Snapshot critical context
   • Save to _TEMP/compact_snapshots/
       ↓
[COMPACTION OCCURS]
   (Claude internal process)
       ↓
[NEW SESSION START]
       ↓
[TRIGGER: SessionStart with matcher="compact"]
       ↓
   post_autocompact_recovery.py
       ↓
   • Load latest snapshot
   • Display critical rules from CLAUDE.md
   • Display deployment workflow
   • Display documentation references
       ↓
[CONTEXT RESTORED]
```

## Snapshot Format

**Lokalizacja:** `_TEMP/compact_snapshots/latest_snapshot.json`

```json
{
  "timestamp": "2025-10-31T10:30:00",
  "session_context": {
    "project": "PPM-CC-Laravel",
    "critical_rules": [
      "Context7 MANDATORY przed implementacją",
      "NO HARDCODING - wszystko konfigurowane",
      "NO MOCK DATA - tylko prawdziwe struktury",
      "Agents MUST create reports in _AGENT_REPORTS/",
      "Frontend verification MANDATORY przed informowaniem użytkownika"
    ],
    "deployment": {
      "domain": "ppm.mpptrade.pl",
      "ssh_host": "host379076@host379076.hostido.net.pl:64321",
      "ssh_key": "D:\\OneDrive - MPP TRADE\\SSH\\Hostido\\HostidoSSHNoPass.ppk",
      "path": "domains/ppm.mpptrade.pl/public_html/"
    },
    "workflow": [
      "1. npm run build (lokalnie)",
      "2. pscp upload ALL assets + manifest (ROOT!)",
      "3. php artisan cache:clear (produkcja)",
      "4. PPM Verification Tool (_TOOLS/full_console_test.cjs)",
      "5. Screenshot verification"
    ]
  },
  "active_tasks": "Check current TODO list - may contain in-progress tasks",
  "last_actions": "Review recent operations before compact"
}
```

## Debugging

### Test PreCompact Hook
```bash
python _TOOLS/pre_compact_snapshot.py
```

**Expected output:**
- Kolorowy banner "PRE-COMPACT SNAPSHOT"
- Snapshot zapisany w `_TEMP/compact_snapshots/`
- Lista krytycznych zasad
- Deployment workflow

### Test PostAutoCompact Hook
```bash
python _TOOLS/post_autocompact_recovery.py
```

**Expected output:**
- Kolorowy banner "POST-AUTOCOMPACT RECOVERY"
- Wczytany snapshot (jeśli istnieje)
- Krytyczne zasady z 4 sekcji
- Deployment info
- Deployment workflow
- Dokumentacja references

### Weryfikacja Snapshot

```bash
# Sprawdź czy snapshot został utworzony
dir _TEMP\compact_snapshots\

# Odczytaj snapshot JSON
Get-Content _TEMP\compact_snapshots\latest_snapshot.json | ConvertFrom-Json | ConvertTo-Json -Depth 5
```

## Troubleshooting

### Hook nie uruchamia się

1. **Sprawdź permissions:**
   ```json
   "Bash(python _TOOLS/pre_compact_snapshot.py:*)"
   ```

2. **Sprawdź timeout:**
   - PreCompact: 5000ms
   - SessionStart: 5000ms

3. **Sprawdź ścieżkę Python:**
   ```bash
   python --version
   ```

### Brak kolorów w output

Windows PowerShell 7 wspiera ANSI colors domyślnie. Jeśli brak kolorów:
```powershell
$PSStyle.OutputRendering = 'Ansi'
```

### Snapshot nie zapisuje się

1. **Sprawdź folder:**
   ```bash
   New-Item -ItemType Directory -Force -Path "_TEMP/compact_snapshots"
   ```

2. **Sprawdź uprawnienia zapisu**

3. **Sprawdź logs w CLI**

## Integracja z CLAUDE.md

Hook `post_autocompact_recovery.py` wyciąga krytyczne zasady bezpośrednio z CLAUDE.md:

- **Vite & Build** - sekcja deployment checklist
- **Frontend Verification** - PPM Verification Tool
- **CSS & Styles** - zakaz inline styles
- **Context7 & Agents** - mandatory rules

**Aktualizacja:** Gdy dodasz nowe krytyczne zasady do CLAUDE.md, zaktualizuj funkcję `load_critical_rules()` w `post_autocompact_recovery.py`.

## Maintenance

### Czyszczenie starych snapshots

```bash
# Usuń snapshoty starsze niż 7 dni
Get-ChildItem _TEMP\compact_snapshots\snapshot_*.json |
    Where-Object {$_.LastWriteTime -lt (Get-Date).AddDays(-7)} |
    Remove-Item
```

### Monitoring rozmiaru

```bash
# Sprawdź rozmiar folderu snapshots
(Get-ChildItem _TEMP\compact_snapshots\ -Recurse | Measure-Object -Property Length -Sum).Sum / 1MB
```

## Best Practices

1. ✅ **Regularnie sprawdzaj snapshoty** - upewnij się że są aktualne
2. ✅ **Testuj hooki lokalnie** przed poleganiem na nich w produkcji
3. ✅ **Aktualizuj critical_rules** gdy zmieniasz CLAUDE.md
4. ✅ **Czyść stare snapshoty** (>7 dni)
5. ✅ **Monitoruj timeout** - jeśli hook timeout, zwiększ w settings.json

## Related Documentation

- `CLAUDE.md` - Projekt rules & architecture
- `_DOCS/dane_hostingu.md` - SSH & credentials
- `.claude/settings.local.json` - Hooks configuration
- `_TEMP/compact_snapshots/` - Snapshot storage

---

**Last Updated:** 2025-10-31
**Version:** 1.0
**Status:** ✅ ACTIVE
