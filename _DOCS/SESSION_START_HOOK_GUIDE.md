# SESSION START HOOK GUIDE - Mandatory Rules Acknowledgment

## Przegląd

Hook SessionStart, który przy każdym uruchomieniu Claude Code przypomina o kluczowych zasadach projektu PPM-CC-Laravel i wymaga potwierdzenia zapoznania się z nimi.

## Cel

**Problem:** Claude może "zapomnieć" o krytycznych zasadach projektu między sesjami lub po kompaktowaniu kontekstu.

**Rozwiązanie:** Automatyczny reminder przy każdym SessionStart z wymogiem potwierdzenia zapoznania się z zasadami.

## Hook Details

**Plik:** `_TOOLS/session_start_rules_reminder.py`
**Trigger:** SessionStart (każde uruchomienie Claude Code)
**Timeout:** 5000ms
**Type:** command

## Wyświetlane Zasady

### 🔴 KATEGORYCZNE ZAKAZY

```
❌ NO HARDCODING - wszystko konfigurowane przez admin
❌ NO MOCK DATA - tylko prawdziwe struktury danych
❌ NO INLINE STYLES - zawsze CSS classes (kategoryczny zakaz!)
❌ NO NEW CSS FILES bez konsultacji - dodawaj do istniejących
❌ NO SKIPPING Context7 - MANDATORY przed każdą implementacją
```

### ⚠️ OBOWIĄZKOWE WORKFLOW

```
✅ Context7 docs lookup PRZED implementacją (mcp__context7__get-library-docs)
✅ Agent reports MANDATORY w _AGENT_REPORTS/ po ukończeniu pracy
✅ Frontend verification MANDATORY przed informowaniem użytkownika
✅ PPM Verification Tool (_TOOLS/full_console_test.cjs) po UI changes
✅ Deployment checklist: npm run build → upload ALL assets → manifest ROOT → cache clear
```

### 🏗️ VITE & BUILD ARCHITECTURE

```
⚠️ Vite działa TYLKO lokalnie (brak Node.js na produkcji!)
⚠️ Deploy WSZYSTKIE pliki z public/build/assets/ (nie tylko zmienione!)
⚠️ Upload manifest do ROOT: public/build/manifest.json (nie .vite/!)
⚠️ HTTP 200 verification MANDATORY dla wszystkich CSS po deployment
```

### 🤖 SYSTEM AGENTÓW

```
✅ 13 specjalistycznych agentów dostępnych (.claude/agents/)
✅ TYLKO JEDEN agent in_progress jednocześnie
✅ Agents MUST create reports w _AGENT_REPORTS/
✅ coding-style-agent PRZED completion (ZAWSZE)
```

### 📏 QUALITY STANDARDS

```
✅ Max 300 linii per file (idealnie 150-200, wyjątkowo 500)
✅ Separation of concerns - models, logic, UI, config w oddzielnych plikach
✅ Enterprise class - bez skrótów, pełna walidacja, error handling
✅ ZAWSZE aktualizuj TODO list podczas pracy
```

### 🚀 DEPLOYMENT INFORMATION

```
🚀 Domena: ppm.mpptrade.pl
🔑 SSH: host379076@host379076.hostido.net.pl:64321
🔐 Key: D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk
📁 Path: domains/ppm.mpptrade.pl/public_html/
👤 Admin: admin@mpptrade.pl / Admin123!MPP
```

### 📚 CONTEXT7 MCP INTEGRATION

```
API Key: ctx7sk-dea67299-09f8-4fab-b4bd-d36297a675c3
Libraries:
  • Laravel 12.x: /websites/laravel_12_x (4927 snippets)
  • Livewire 3.x: /livewire/livewire (867 snippets)
  • Alpine.js: /alpinejs/alpine (364 snippets)
  • PrestaShop: /prestashop/docs (3289 snippets)
```

### 📖 ESSENTIAL DOCUMENTATION

```
📖 CLAUDE.md - COMPLETE project rules (MUST READ!)
📖 _DOCS/dane_hostingu.md - SSH & credentials
📖 _DOCS/DEPLOYMENT_GUIDE.md - Complete deployment workflow
📖 _DOCS/FRONTEND_VERIFICATION_GUIDE.md - UI testing mandatory
📖 _DOCS/CSS_STYLING_GUIDE.md - Style rules & inline styles ban
📖 _DOCS/AGENT_USAGE_GUIDE.md - Agent delegation patterns
📖 Plan_Projektu/ - Current ETAP status
📖 _AGENT_REPORTS/ - Latest agent work reports
```

## Mandatory Acknowledgment

### Co musi potwierdzić Claude:

1. ✅ Przeczytałem i zrozumiałem wszystkie powyższe zasady
2. ✅ Będę stosować się do WSZYSTKICH zasad podczas tej sesji
3. ✅ Szczególnie będę pamiętać o:
   - Context7 MANDATORY przed każdą implementacją
   - NO HARDCODING, NO MOCK DATA
   - NO INLINE STYLES - kategoryczny zakaz
   - Frontend verification MANDATORY
   - Agent reports MANDATORY
   - TODO list updates during work

### Wymagana odpowiedź Claude:

```
✅ POTWIERDZAM ZAPOZNANIE Z ZASADAMI PPM-CC-LARAVEL
Będę stosować wszystkie reguły z CLAUDE.md podczas tej sesji.
```

## Konfiguracja

**Lokalizacja:** `.claude/settings.local.json`

```json
{
  "hooks": {
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
      },
      {
        "hooks": [
          {
            "type": "command",
            "command": "python _TOOLS/session_start_rules_reminder.py",
            "timeout": 5000
          }
        ]
      }
    ]
  },
  "permissions": {
    "allow": [
      "Bash(python _TOOLS/session_start_rules_reminder.py:*)"
    ]
  }
}
```

**UWAGA:**
- Hook z `"matcher": "compact"` uruchamia się TYLKO po compaction
- Hook BEZ matchera uruchamia się przy KAŻDYM SessionStart

## Workflow

```
[Claude Code Start]
       ↓
[TRIGGER: SessionStart]
       ↓
   session_start_rules_reminder.py
       ↓
   • Load critical rules from CLAUDE.md
   • Display 🔴 KATEGORYCZNE ZAKAZY
   • Display ⚠️ OBOWIĄZKOWE WORKFLOW
   • Display 🏗️ VITE & BUILD
   • Display 🤖 SYSTEM AGENTÓW
   • Display 📏 QUALITY STANDARDS
   • Display 🚀 DEPLOYMENT INFO
   • Display 📚 CONTEXT7 CONFIG
   • Display 📖 ESSENTIAL DOCS
       ↓
   Display banner:
   ⚠️ MANDATORY ACKNOWLEDGMENT REQUIRED
   🤖 RESPOND IN YOUR FIRST MESSAGE
       ↓
[Claude sees reminder in system context]
       ↓
[Claude MUST respond with acknowledgment]
       ↓
✅ POTWIERDZAM ZAPOZNANIE Z ZASADAMI
       ↓
[Session continues with rules in mind]
```

## Testing

### Test Hook Manually

```bash
python _TOOLS/session_start_rules_reminder.py
```

**Expected output:**
- Kolorowy banner z zasadami
- Wszystkie 5 sekcji zasad
- Deployment info
- Context7 config
- Essential docs list
- Mandatory acknowledgment banner
- Exit code 0

### Verify Hook Runs on SessionStart

1. Restart Claude Code
2. Check CLI output at start
3. Should see full rules reminder
4. Claude should acknowledge in first message

## Customization

### Dodawanie nowych zasad

**Plik:** `_TOOLS/session_start_rules_reminder.py`

**Funkcja:** `load_critical_rules()`

```python
def load_critical_rules():
    """Wczytaj najważniejsze zasady z CLAUDE.md"""
    rules = {
        "🔴 KATEGORYCZNE ZAKAZY": [
            "❌ NO HARDCODING - wszystko konfigurowane przez admin",
            # ... dodaj nowe zakazy tutaj
        ],
        "⚠️ OBOWIĄZKOWE WORKFLOW": [
            "✅ Context7 docs lookup PRZED implementacją",
            # ... dodaj nowe workflow rules tutaj
        ],
        # ... dodaj nowe sekcje tutaj
    }
    return rules
```

### Aktualizacja deployment info

**Funkcja:** `load_deployment_info()`

```python
def load_deployment_info():
    """Kluczowe info deployment"""
    return {
        "🚀 Domena": "ppm.mpptrade.pl",
        # ... aktualizuj dane tutaj
    }
```

### Aktualizacja docs list

**Funkcja:** `load_essential_docs()`

```python
def load_essential_docs():
    """Kluczowa dokumentacja do przeczytania"""
    return [
        "📖 CLAUDE.md - COMPLETE project rules (MUST READ!)",
        # ... dodaj nowe dokumenty tutaj
    ]
```

## Integration z innymi Hooks

### SessionStart Hooks Hierarchy

```json
"SessionStart": [
  {
    "matcher": "compact",           // ← Uruchamia się TYLKO po compact
    "hooks": [
      {
        "type": "command",
        "command": "python _TOOLS/post_autocompact_recovery.py",
        "timeout": 5000
      }
    ]
  },
  {                                  // ← Uruchamia się ZAWSZE (no matcher)
    "hooks": [
      {
        "type": "command",
        "command": "python _TOOLS/session_start_rules_reminder.py",
        "timeout": 5000
      }
    ]
  }
]
```

### Execution Order

**Po compact:**
1. `post_autocompact_recovery.py` (matcher="compact")
2. `session_start_rules_reminder.py` (no matcher)

**Normal start (bez compact):**
1. `session_start_rules_reminder.py` (no matcher)

## Troubleshooting

### Hook nie wyświetla się

1. **Sprawdź permissions:**
   ```json
   "Bash(python _TOOLS/session_start_rules_reminder.py:*)"
   ```

2. **Sprawdź timeout:**
   - Minimum: 5000ms

3. **Sprawdź Python path:**
   ```bash
   python --version
   ```

### Brak kolorów

Windows PowerShell 7:
```powershell
$PSStyle.OutputRendering = 'Ansi'
```

### Claude nie odpowiada z acknowledgment

**To normalne!** Hook wyświetla reminder, ale nie wymusza odpowiedzi technicznie. To przypomnienie dla Claude, który POWINIEN odpowiedzieć potwierdzeniem jako dobra praktyka.

Jeśli Claude nie potwierdza, użytkownik może:
```
User: "Proszę o potwierdzenie zapoznania z zasadami z session start hook"
```

## Best Practices

1. ✅ **Aktualizuj zasady regularnie** - gdy dodajesz nowe reguły do CLAUDE.md
2. ✅ **Testuj hook lokalnie** przed merge do main
3. ✅ **Monitor Claude responses** - upewnij się że Claude faktycznie czyta zasady
4. ✅ **Keep rules concise** - im więcej zasad, tym mniej prawdopodobne że będą przestrzegane
5. ✅ **Highlight most critical rules** - użyj 🔴 dla absolutnych zakazów

## Maintenance

### Monthly Review

1. Review `load_critical_rules()` - czy aktualne?
2. Review `load_deployment_info()` - czy credentials aktualne?
3. Review `load_essential_docs()` - czy lista docs aktualna?
4. Test hook: `python _TOOLS/session_start_rules_reminder.py`

### Gdy CLAUDE.md się zmienia

1. Zaktualizuj `load_critical_rules()` w hook
2. Test hook lokalnie
3. Verify colors render correctly
4. Commit changes

## Related Documentation

- `CLAUDE.md` - Master project rules
- `_DOCS/COMPACT_HOOKS_GUIDE.md` - Compact hooks system
- `.claude/settings.local.json` - Hooks configuration

## Impact

### Before SessionStart Hook

- Claude mógł zapomnieć o zasadach
- Przypadkowe inline styles
- Pominięcie Context7
- Brak frontend verification
- No TODO updates

### After SessionStart Hook

- Claude widzi zasady przy każdym starcie
- Przypomnienie o critical rules
- Świadomość deployment workflow
- Awareness of Context7 requirement
- TODO list reminder

---

**Last Updated:** 2025-10-31
**Version:** 1.0
**Status:** ✅ ACTIVE - Runs on every SessionStart
**Author:** PPM-CC-Laravel Team
