# _ISSUES_FIXES - Documentation Repository

**Cel**: Centralne repozytorium wszystkich znanych problemów, ich przyczyn i rozwiązań w projekcie PPM-CC-Laravel.

## 📋 ZASADY UŻYTKOWANIA

### Kiedy tworzyć nowy raport?
- Problem wymagał **>2 godzin debugowania**
- Problem może się **powtórzyć w przyszłości**
- Problem ma **nietrywialne rozwiązanie**
- Problem dotyczy **framework/architecture patterns**

### Struktura raportu
Każdy plik `.md` powinien zawierać:

```markdown
# NAZWA_PROBLEMU

**Status**: ✅ ROZWIĄZANY / ⚠️ ONGOING / ❌ NIEROZWIĄZANY
**Priorytet**: KRYTYCZNY / WYSOKIE / ŚREDNIE / NISKIE
**Typ**: Framework / UI/UX / Integration / Performance

## 🚨 OPIS PROBLEMU
[Szczegółowy opis co się dzieje]

## ✅ ROZWIĄZANIE
[Krok po kroku jak naprawić]

## 🛡️ ZAPOBIEGANIE
[Jak uniknąć w przyszłości]

## 📋 CHECKLIST
[Lista kroków do wykonania]

## 💡 PRZYKŁADY
[Konkretne przykłady z projektu]

## 🔗 POWIĄZANE PLIKI
[Ścieżki do istotnych plików]
```

## 📁 OBECNE RAPORTY

### 🔥 Krytyczne Issues
- **`LIVEWIRE_WIRE_SNAPSHOT_ISSUE.md`** - Problem z renderowaniem Livewire komponentów pokazujących surowy snapshot zamiast UI
- **`HARDCODE_SIMULATION_ISSUE.md`** - Zasady enterprise fallback i oznaczania symulowanych danych
- **`API_INTEGRATION_PATTERN_ISSUE.md`** - Wzorzec prawdziwe API + fallback dla wszystkich zewnętrznych integracji
- **`LIVEWIRE_EMIT_DISPATCH_ISSUE.md`** - Migracja z Livewire 2.x emit() do 3.x dispatch()

### 🎨 UI/UX Issues
- **`CSS_STACKING_CONTEXT_ISSUE.md`** - Problemy z z-index i dropdown chowającymi się pod komponenty

## 🚀 WORKFLOW

### 1. Napotkanie nowego problemu
```bash
# Gdy problem wymaga >2h debugowania:
# 1. Skopiuj template
cp _ISSUES_FIXES/TEMPLATE.md _ISSUES_FIXES/NOWY_PROBLEM.md

# 2. Wypełnij wszystkie sekcje
# 3. Dodaj odnośnik w CLAUDE.md sekcji "Issues & Fixes"
```

### 2. Rozwiązywanie istniejącego problemu
```bash
# Przeczytaj odpowiedni raport w _ISSUES_FIXES/
# Wykonaj checklist krok po kroku
# Zaktualizuj status w raporcie jeśli potrzeba
```

### 3. Dodawanie do CLAUDE.md
```markdown
#### 🔥 Krytyczne Issues
- **[Nazwa Problemu](_ISSUES_FIXES/NAZWA_PLIKU.md)** - Krótki opis
```

## 🎯 KORZYŚCI SYSTEMU

### Dla Claude Code
- ✅ **Szybka identyfikacja** - odnośniki zamiast długich sekcji w CLAUDE.md
- ✅ **Szczegółowe rozwiązania** - kompletne instrukcje naprawy
- ✅ **Consistency** - jednolity format wszystkich problemów
- ✅ **Aktualizacje** - łatwe dodawanie nowych discoveries

### Dla Projektu
- ⏰ **Oszczędność czasu** - brak powtarzania długiego debugowania
- 📚 **Baza wiedzy** - accumulation wszystkich naprawionych problemów
- 🔍 **Quality Assurance** - checklista zapobiegania problemom
- 📈 **Continuous Improvement** - tracking patterns w problemach

## 💡 BEST PRACTICES

### Pisanie raportów
1. **Konkretność** - użyj rzeczywistych przykładów z projektu
2. **Testowalne rozwiązania** - krok po kroku instrukcje
3. **Zapobieganie** - zasady unikania problemu w przyszłości
4. **Linki** - konkretne pliki i numery linii

### Maintenance
1. **Aktualizuj status** gdy problem zostanie definitywnie rozwiązany
2. **Dodawaj nowe discoveries** do istniejących raportów
3. **Sprawdzaj linki** do plików czy są aktualne
4. **Merguj podobne problemy** gdy znajdziesz duplicates

## 🔧 TEMPLATE NOWEGO RAPORTU

```bash
# Skopiuj i dostosuj:
cp _ISSUES_FIXES/LIVEWIRE_WIRE_SNAPSHOT_ISSUE.md _ISSUES_FIXES/NOWY_PROBLEM.md

# Zmień:
# - Tytuł i opis problemu
# - Status i priorytet
# - Sekcje rozwiązania
# - Przykłady z projektu
# - Powiązane pliki
```

---

**WAŻNE**: Ten system zastępuje długie sekcje troubleshooting w CLAUDE.md. Wszystkie szczegółowe rozwiązania problemów powinny być tutaj, a CLAUDE.md powinien zawierać tylko krótkie odnośniki.