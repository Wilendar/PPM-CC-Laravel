# Plan: Porządkowanie Repozytorium Git PPM-CC-Laravel

## Cel
1. Zaktualizować `.gitignore` aby ignorować pliki tymczasowe/testowe
2. Usunąć śledzone pliki które nie powinny być w repo
3. Przenieść projekt z `feature/productform-redesign` do `main`

---

## FAZA 1: Aktualizacja .gitignore

### 1.1 Naprawić format istniejącego pliku
**Problem:** Linie 46-59 brakują `#` - Git traktuje je jako ignore rules zamiast komentarzy!

### 1.2 Dodać brakujące wpisy (KRYTYCZNE)

```gitignore
# Claude Code cache (326+ plików!)
tmpclaude-*-cwd

# Pliki bez rozszerzenia (debug artifacts)
/nul
/id
/ppm_id
/prestashop_product_id
/product-
/shop
/shop-
/language[0]
/name
/name-
/name}n;
/_ul*
/echo

# Debug/test skrypty w root
/test_*.php
/debug_*.php
/check_*.php
/fix_*.php
/run_*.php
/verify_*.php
/count_*.py

# Pliki tymczasowe w root
/_BACKUP_*
/_TEMP_*
/_WORKING_*
/temp_*.txt
/temp_*.log
/temp_*.blade.php

# Duplikaty
/CLAUDE\ —\ kopia.md
/GEMINI.md
/Plan_Projektu\ copy/
/*.sln

# Obrazki debug
/*.png

# Skrypty instalacyjne
/dotnet-install.ps1

# .NET build artifacts
/_TOOLS/SubiektGT_REST_API_DotNet/bin/
/_TOOLS/SubiektGT_REST_API_DotNet/obj/
/_TOOLS/SubiektGT_REST_API_DotNet/publish/
/_TOOLS/SubiektApi_backup/logs/

# Foldery robocze
/_TEMP/
/_TEST/
/_OTHER/
/_ARCHIVE/
/_BACKUP/
/_DIAGNOSTICS/
/_REPORTS/
/.subtask/
/.parallel-work/
/.ma-orchestrator/
/.playwright-mcp/
/.Release_docs/
```

---

## FAZA 2: Usunięcie śledzonych plików z Git

### 2.1 Lista plików do usunięcia z Git (git rm --cached)

**Pliki tmpclaude-*-cwd (~326 plików):**
```bash
git rm --cached tmpclaude-*-cwd
```

**Pliki bez rozszerzenia:**
```bash
git rm --cached nul id ppm_id prestashop_product_id product- shop shop- "language[0]" name name- "name}n;" _ul* echo 2>/dev/null
```

**Pliki debug/test:**
```bash
git rm --cached test_*.php debug_*.php check_*.php fix_*.php run_*.php verify_*.php count_*.py 2>/dev/null
```

**Pliki tymczasowe:**
```bash
git rm --cached _BACKUP_* _TEMP_* _WORKING_* temp_*.txt temp_*.log temp_*.blade.php 2>/dev/null
```

**Duplikaty:**
```bash
git rm --cached "CLAUDE — kopia.md" GEMINI.md "Plan_Projektu copy/" PPM-CC-Laravel.sln dotnet-install.ps1 2>/dev/null
```

**Foldery:**
```bash
git rm -r --cached _TEMP/ _TEST/ _OTHER/ _ARCHIVE/ _BACKUP/ _DIAGNOSTICS/ _REPORTS/ .subtask/ .parallel-work/ 2>/dev/null
```

### 2.2 Commit zmian
```bash
git add .gitignore
git commit -m "chore(git): cleanup repository - update .gitignore and remove temp files"
```

---

## FAZA 3: Merge do main

### 3.1 Synchronizacja z remote
```bash
git fetch origin
```

### 3.2 Sprawdzenie stanu main
```bash
git log main..feature/productform-redesign --oneline | wc -l  # 89 commitów
git log feature/productform-redesign..main --oneline | wc -l  # 1 commit
```

### 3.3 Force replace (REKOMENDOWANE)

Feature branch zawiera WSZYSTKO co jest na main + 89 nowych commitów.

```bash
# 1. Backup main (na wszelki wypadek)
git branch backup-main main

# 2. Usuń lokalny main
git branch -D main

# 3. Stwórz nowy main z feature
git checkout -b main

# 4. Force push do origin
git push origin main --force

# 5. Usuń stary feature branch (opcjonalnie)
git branch -d feature/productform-redesign
git push origin --delete feature/productform-redesign
```

### 3.4 Cleanup starych branchy (opcjonalnie)
```bash
# Usunięcie starych branchy lokalnych
git branch -d admin/livewire-enhancements admin/role-list-view charming-morse dazzling-cray ...
```

---

## FAZA 4: Weryfikacja

### 4.1 Sprawdzenie statusu
```bash
git status
git log --oneline -5
```

### 4.2 Sprawdzenie że pliki nie są śledzone
```bash
git ls-files | grep -E "tmpclaude|_TEMP|_TEST|test_.*.php"
# Powinno zwrócić pustą listę
```

---

## Pliki do modyfikacji

| Plik | Akcja |
|------|-------|
| `.gitignore` | Zaktualizować (FAZA 1) |

## Pliki do usunięcia z Git (nie z dysku!)

| Kategoria | Ilość | Przykłady |
|-----------|-------|-----------|
| tmpclaude-*-cwd | ~326 | Cache Claude Code |
| Pliki bez rozszerzenia | ~15 | nul, id, shop, name |
| Skrypty debug | ~15 | test_*.php, debug_*.php |
| Pliki temp | ~10 | _TEMP_*, _BACKUP_* |
| Foldery | ~8 | _TEMP/, _TEST/, _OTHER/ |

---

## Analiza commitu permissions

### Porównanie:

| Branch | Commit | Data | Opis |
|--------|--------|------|------|
| **main** | `0576ab3` | 23 sty 14:34 | `feat(permissions): implement modular permissions architecture for AI agents` |
| **feature** | `9c5a26a` | 23 sty 16:28 | `feat(permissions): implement modular permission architecture with auto-discovery` |

### Wniosek:
- **Feature branch ma NOWSZĄ wersję** (2h później) tej samej funkcjonalności
- Feature branch zawiera **wszystkie pliki permissions** plus dodatki (`import.php`, `scan.php`)
- Lokalnie istnieją: `config/permissions/` (14 plików), `app/Services/Permissions/` (1 plik)
- **Nie tracimy nic** używając Opcji B - feature jest NADZBIORIEM main

### Rekomendacja: **Opcja B (Force replace)** - bezpieczna

---

## Uwagi

1. **Pliki pozostaną na dysku** - tylko usuwamy je z Git tracking
2. **Backup:** Przed operacją można zrobić backup brancha: `git branch backup-main main`
3. **Po cleanup:** Repo będzie znacznie mniejsze (usunięcie ~400+ niepotrzebnych plików)
