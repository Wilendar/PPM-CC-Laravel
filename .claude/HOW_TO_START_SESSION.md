# JAK ROZPOCZĄĆ SESJĘ PPM-CC-LARAVEL

## ⚡ SUPER SZYBKI START (NOWE! ZALECANE!)

### Użyj slash command:
```
/kontynuuj_ppm
```

**To wszystko!** Komenda automatycznie:
- ✅ Przeczyta najnowszy raport z _REPORTS/
- ✅ Zapozna się z zasadami z CLAUDE.md
- ✅ Sprawdzi aktualny ETAP w Plan_Projektu/
- ✅ Przeczyta dokumentację projektu
- ✅ Rozpocznie pracę zgodnie z planem

**Wystarczy jedna komenda - reszta dzieje się automatycznie!**

---

## 📊 INNE PRZYDATNE KOMENDY

### Sprawdź status:
```
/status_ppm
```

### Zobacz plan:
```
/plan_ppm
```

### Przeczytaj raport:
```
/raport_ppm
```

### Deleguj do agenta:
```
/agent_ppm laravel-expert "Opis zadania"
```

### Deploy na produkcję:
```
/deploy_ppm
```

### Zobacz dokumentację:
```
/docs_ppm
```

**Pełna lista komend:** `.claude/commands/README.md`

---

## 🔧 ALTERNATYWNA METODA (Helper Script)

Jeśli wolisz ręczne podejście:

### 1. Wygeneruj prompt startowy:
```bash
pwsh .claude/scripts/generate-startup-prompt.ps1
```

### 2. Skopiuj wygenerowany prompt

### 3. Wklej do Claude Code CLI

### 4. Claude automatycznie wykona wszystkie kroki

---

## 📚 PEŁNA DOKUMENTACJA

- **Slash Commands:** `.claude/commands/README.md`
- **Auto-Startup System:** `_DOCS/AUTO_STARTUP_PROMPT_SYSTEM.md`
- **Hooks System:** `_DOCS/HOOKS_SYSTEM_V3_2025-09-30.md`

---

## ⚠️ WAŻNE UWAGI

✅ **UŻYWAJ:** Slash commands (`/kontynuuj_ppm`) - najprostsze i najbezpieczniejsze!
✅ **LUB:** Helper script - jeśli wolisz ręczną kontrolę
❌ **NIE UŻYWAJ:** SessionStart hook - może powodować freeze CLI!

---

## 🎯 WORKFLOW DNIA

```
1. Uruchom Claude Code CLI

2. Wpisz: /kontynuuj_ppm

3. Claude automatycznie rozpocznie pracę!
```

**Gotowe w 10 sekund!** 🚀