# SubagentStop Hook - Automatyczne generowanie raportów

## Przegląd

Hook `subagent-report-generator.ts` automatycznie generuje raporty z prac subagentów zgodnie z formatem określonym w `CLAUDE.md`.

## Architektura: Dwa hooki z Smart Detection

System składa się z **dwóch hooków** które współpracują ze sobą:

### 🎯 Hook projektowy (PPM-CC-Laravel)
**Lokalizacja**: `D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\.claude\hooks\subagent-report-generator.ts`

**Funkcje**:
- ✅ Specyficzny dla projektu PPM-CC-Laravel
- ✅ Generuje raporty zgodnie z formatem CLAUDE.md (polski)
- ✅ Zapisuje do `_AGENT_REPORTS/`
- ✅ Ma priorytet nad hookiem globalnym

### 🌍 Hook globalny (wszystkie projekty)
**Lokalizacja**: `D:\OneDrive - MPP TRADE\AI Instrukcje\claude-config\.claude\hooks\subagent-report-generator.ts`

**Funkcje**:
- ✅ Działa dla wszystkich projektów
- ✅ **Smart Detection** - automatycznie wyłącza się jeśli projekt ma własny hook
- ✅ Generuje raporty w formacie angielskim
- ✅ Zapisuje do `agent-reports/` lub `_AGENT_REPORTS/`

### 🧠 Jak działa Smart Detection?

```typescript
function hasProjectHook(projectRoot: string): boolean {
  const projectHookPath = path.join(
    projectRoot,
    '.claude',
    'hooks',
    'subagent-report-generator.ts'
  );
  return fs.existsSync(projectHookPath);
}

// W głównej funkcji hooka globalnego
if (hasProjectHook(hookParams.cwd)) {
  console.log('[global] Project has own hook - skipping');
  return {}; // Exit silently
}
```

**Logika**:
1. Hook globalny uruchamia się dla każdego projektu
2. Sprawdza czy projekt ma własny hook w `.claude/hooks/`
3. Jeśli TAK → globalny hook wyłącza się
4. Jeśli NIE → globalny hook generuje raport

**Rezultat**:
- ✅ **PPM-CC-Laravel** → używa hooka projektowego (polski, `_AGENT_REPORTS/`)
- ✅ **Inne projekty** → używają hooka globalnego (angielski, `agent-reports/`)
- ✅ **Zero kolizji** → tylko jeden raport per subagent

## Nowe funkcje Claude Code

W najnowszej wersji Claude Code dodano do hooków **SubagentStop** dwa nowe pola:

- **`agent_id`** - unikalny identyfikator subagenta
- **`agent_transcript_path`** - ścieżka do transkryptu prac subagenta

**Problem który rozwiązują**: Wcześniej wszystkie subagenty współdzieliły ten sam `session_id`, co uniemożliwiało identyfikację konkretnego agenta który zakończył pracę.

## Funkcje hooka

Hook `subagent-report-generator.ts` automatycznie:

### ✅ Odczytuje transkrypt subagenta
- Parsuje plik JSONL z `agent_transcript_path`
- Wyekstrahowuje wszystkie komunikaty, tool uses i wyniki

### ✅ Analizuje wykonane prace
- Identyfikuje typ agenta (laravel-expert, frontend-specialist, itp.)
- Ekstrahuje opis zadania z initial prompt
- Zbiera listę zmodyfikowanych plików (Edit, Write, MultiEdit)
- Wykrywa błędy i ostrzeżenia
- Wyciąga następne kroki z końcowej odpowiedzi agenta

### ✅ Generuje raport markdown
Format zgodny z `CLAUDE.md`:
```markdown
# RAPORT PRACY AGENTA: {nazwa_agenta}
**Data**: {YYYY-MM-DD HH:MM}
**Agent ID**: {agent_id}
**Agent Type**: {agent_type}
**Zadanie**: {opis zadania}

## ✅ WYKONANE PRACE
- Lista plików i operacji

## ⚠️ PROBLEMY/BLOKERY
- Napotkane błędy
- Ostrzeżenia

## 📋 NASTĘPNE KROKI
- Co należy zrobić dalej

## 📁 PLIKI
- Szczegółowa lista plików
```

### ✅ Zapisuje raport
- Lokalizacja: `_AGENT_REPORTS/{YYYY-MM-DD}_{AGENT_TYPE}_{agent_id_short}_REPORT.md`
- Przykład: `2025-11-17_LARAVEL_EXPERT_a3f8c9b2_REPORT.md`

### ✅ Pokazuje podsumowanie
Po zakończeniu pracy subagenta, hook wyświetla w transkrypcie:
```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📊 RAPORT SUBAGENTA WYGENEROWANY
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

✅ Agent: laravel-expert
📁 Raport: _AGENT_REPORTS/2025-11-17_LARAVEL_EXPERT_a3f8c9b2_REPORT.md
📝 Zmodyfikowane pliki: 5
⚠️  Błędy: 0
⏱️  Czas wykonania: ~3 min

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

## Jak używać

### 1. Hook jest automatycznie aktywny

TypeScript hooki w `.claude/hooks/` są automatycznie wykrywane i uruchamiane przez Claude Code. Nie wymaga dodatkowej konfiguracji.

### 2. Uruchomienie subagenta

Każdorazowo gdy uruchamiasz subagenta za pomocą Task tool:

```
Użytkownik: Uruchom laravel-expert żeby naprawić błąd w ProductController

Claude: [Uruchamia Task tool z subagent_type="laravel-expert"]
```

### 3. Automatyczne generowanie raportu

Gdy subagent zakończy pracę:
1. Hook SubagentStop jest wywoływany
2. `subagent-report-generator.ts` otrzymuje:
   - `agent_id` - np. "a3f8c9b2-1234-5678-90ab-cdef12345678"
   - `agent_transcript_path` - np. ".claude/transcripts/agent-a3f8c9b2.jsonl"
3. Hook analizuje transkrypt
4. Generuje raport markdown
5. Zapisuje do `_AGENT_REPORTS/`

### 4. Przegląd raportu

Otwórz plik w `_AGENT_REPORTS/` żeby zobaczyć:
- Szczegółowe informacje o wykonanej pracy
- Listę zmodyfikowanych plików
- Napotkane problemy
- Zalecane następne kroki

## Struktura transkryptu JSONL

Hook parsuje transkrypt w formacie JSONL (JSON Lines):

```jsonl
{"type":"message","role":"user","content":"Napraw błąd w ProductController"}
{"type":"message","role":"assistant","content":"Analizuję problem..."}
{"type":"tool_use","name":"Read","args":{"file_path":"app/Http/Controllers/ProductController.php"}}
{"type":"tool_result","content":"<?php\nnamespace App\\Http\\Controllers..."}
{"type":"tool_use","name":"Edit","args":{"file_path":"app/Http/Controllers/ProductController.php","old_string":"...","new_string":"..."}}
{"type":"message","role":"assistant","content":"Naprawiłem błąd. Następne kroki: uruchom testy"}
```

Hook ekstrahuje:
- **Initial prompt** (`role:"user"`) - opis zadania
- **Tool uses** (`type:"tool_use"`) - operacje na plikach
- **Errors** (`type:"tool_result"` z "error") - napotkane błędy
- **Next steps** (ostatnia `role:"assistant"` message) - następne kroki

## Ekstrakcja danych

### Typ agenta
Hook szuka w initial prompt słów kluczowych:
- `architect`
- `debugger`
- `laravel-expert`
- `livewire-specialist`
- `frontend-specialist`
- itd.

Jeśli nie znajdzie, używa domyślnego: `general-purpose`

### Operacje na plikach
Wykrywa narzędzia:
- `Edit` - modyfikacja istniejącego pliku
- `Write` - utworzenie nowego pliku
- `MultiEdit` - wielokrotna edycja
- `NotebookEdit` - edycja Jupyter notebook

### Błędy
Szuka w `tool_result`:
- słowo "error"
- słowo "failed"
- słowo "exception"

Oraz w `assistant` messages:
- słowo "błąd"
- słowo "problem"
- słowo "niepowodzenie"

### Następne kroki
Szuka w ostatniej assistant message wzorców:
- "następne kroki:"
- "next steps:"
- "TODO:"
- "do zrobienia:"

## Korzyści

### ✅ Automatyzacja
Nie musisz ręcznie pisać raportów - hook robi to za Ciebie

### ✅ Spójność
Wszystkie raporty mają ten sam format zgodny z `CLAUDE.md`

### ✅ Śledzenie postępu
Łatwe przeglądanie historii prac subagentów

### ✅ Audit trail
Pełna dokumentacja kto, kiedy i co zmienił

### ✅ Integracja z workflow
Raporty automatycznie trafiają do `_AGENT_REPORTS/` bez dodatkowych kroków

## Troubleshooting

### Hook nie generuje raportu

**Problem**: Brak pliku w `_AGENT_REPORTS/` po zakończeniu pracy subagenta

**Rozwiązania**:
1. Sprawdź czy folder `_AGENT_REPORTS/` istnieje (hook go utworzy automatycznie)
2. Sprawdź logi w konsoli: `[subagent-report-generator]`
3. Sprawdź czy plik transkryptu istnieje w lokalizacji `agent_transcript_path`

### Raport jest pusty lub niekompletny

**Problem**: Raport został utworzony, ale brakuje informacji

**Rozwiązania**:
1. Sprawdź czy transkrypt subagenta zawiera dane (plik JSONL w `.claude/transcripts/`)
2. Sprawdź czy format transkryptu jest poprawny (każda linia to valid JSON)
3. Sprawdź logi w konsoli: `[subagent-report-generator] Failed to parse line`

### Błąd parsowania transkryptu

**Problem**: Hook zgłasza błędy przy parsowaniu JSONL

**Rozwiązania**:
1. Otwórz plik transkryptu i sprawdź czy każda linia to valid JSON
2. Sprawdź czy plik nie jest uszkodzony
3. Sprawdź uprawnienia do odczytu pliku

## Rozwój hooka

Hook można rozszerzyć o:

### 🔹 Integracja z Plan_Projektu
Automatyczna aktualizacja planu projektu na podstawie ukończonych zadań

### 🔹 Walidacja ukończenia
Sprawdzanie czy subagent faktycznie ukończył wszystkie zadania z TODO

### 🔹 Metryki i analytics
Zbieranie statystyk: średni czas wykonania per typ agenta, najczęściej używane narzędzia

### 🔹 Notyfikacje
Wysyłanie powiadomień gdy subagent napotka błędy

### 🔹 Orchestration
Automatyczne delegowanie kolejnych zadań na podstawie "następnych kroków"

## Pliki

### Hook projektowy (PPM-CC-Laravel)
- **Hook**: `D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\.claude/hooks/subagent-report-generator.ts`
- **Raporty**: `_AGENT_REPORTS/{timestamp}_{agent_type}_{agent_id}_REPORT.md`
- **Język**: Polski
- **Format**: Zgodny z CLAUDE.md projektu

### Hook globalny (wszystkie projekty)
- **Hook**: `D:\OneDrive - MPP TRADE\AI Instrukcje\claude-config\.claude/hooks/subagent-report-generator.ts`
- **Raporty**: `agent-reports/{timestamp}_{agent_type}_{agent_id}_REPORT.md` (lub `_AGENT_REPORTS/` jeśli istnieje)
- **Język**: Angielski
- **Smart Detection**: Automatycznie wyłącza się dla projektów z własnym hookiem

### Wspólne
- **Transkrypty**: `.claude/transcripts/agent-{agent_id}.jsonl` (zarządzane przez Claude Code)

## Porównanie hooków

| Cecha | Hook projektowy | Hook globalny |
|-------|----------------|---------------|
| **Lokalizacja** | `.claude/hooks/` w projekcie | `claude-config\.claude\hooks/` |
| **Zasięg** | Tylko PPM-CC-Laravel | Wszystkie projekty |
| **Język raportów** | Polski | Angielski |
| **Folder raportów** | `_AGENT_REPORTS/` | `agent-reports/` lub `_AGENT_REPORTS/` |
| **Format** | Zgodny z CLAUDE.md projektu | Uniwersalny format |
| **Smart Detection** | Nie dotyczy | ✅ Wyłącza się jeśli projekt ma własny hook |
| **Priorytet** | ⭐ Wysoki (uruchamia się) | Niski (wyłącza się jeśli projekt ma własny) |

### Kiedy używać którego?

**Hook projektowy**:
- ✅ Projekty z specyficznymi wymaganiami (format raportów, język, struktura)
- ✅ Projekty z systemem dokumentacji (jak PPM-CC-Laravel z `CLAUDE.md`)
- ✅ Gdy potrzebujesz pełnej kontroli nad formatem raportów

**Hook globalny**:
- ✅ Nowe projekty bez własnego hooka
- ✅ Projekty które potrzebują podstawowych raportów
- ✅ Szybkie prototypy gdzie nie chcesz konfigurować hooków
- ✅ Projekty gdzie angielskie raporty są wystarczające

### Dodawanie hooka do nowego projektu

Jeśli chcesz aby inny projekt używał własnego hooka (zamiast globalnego):

1. Skopiuj hook z PPM-CC-Laravel:
   ```bash
   cp "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\.claude\hooks\subagent-report-generator.ts" \
      "<nowy_projekt>\.claude\hooks\subagent-report-generator.ts"
   ```

2. Dostosuj format raportów (język, struktura, lokalizacja)

3. Hook globalny automatycznie się wyłączy dla tego projektu

## Więcej informacji

- GitHub Issue: [#7881 - SubagentStop hook identification](https://github.com/anthropics/claude-code/issues/7881)
- Claude Code Docs: [Hooks Reference](https://code.claude.com/docs/en/hooks)
- `CLAUDE.md` - format raportów agentów

---

## Historia zmian

### v1.1.0 - 2025-11-17
- ✅ Dodano globalny hook z Smart Detection
- ✅ Hook globalny automatycznie wyłącza się dla projektów z własnym hookiem
- ✅ Zaktualizowano dokumentację o architekturę dwóch hooków
- ✅ Dodano porównanie hooków i guidance kiedy używać którego

### v1.0.0 - 2025-11-17
- ✅ Pierwszy release hooka projektowego dla PPM-CC-Laravel
- ✅ Wykorzystuje nowe pola `agent_id` i `agent_transcript_path`
- ✅ Automatyczne generowanie raportów w formacie CLAUDE.md

---

**Utworzono**: 2025-11-17
**Autor**: Claude Code + Kamil Wiliński
**Wersja aktywna**:
- Hook projektowy: v1.0.0
- Hook globalny: v1.1.0
