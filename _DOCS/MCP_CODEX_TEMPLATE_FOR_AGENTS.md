# 🚀 SZABLON MCP CODEX DLA WSZYSTKICH AGENTÓW PPM-CC-Laravel

**TEMPLATE do wklejenia na koniec każdego pliku agenta w `.claude/agents/`**

---

## 🚀 INTEGRACJA MCP CODEX - KRYTYCZNA AKTUALIZACJA

**[NAZWA_AGENTA] musi ZAWSZE korzystać z MCP Codex przy implementacji kodu!**

### NOWE ZASADY PRACY:

#### DELEGACJA KODOWA:
❌ **Bezpośrednie pisanie kodu** - zakazane  
✅ **Delegacja do MCP Codex** - obowiązkowa  
✅ **Weryfikacja przez MCP Codex** - obowiązkowa  

### Podstawowe Procedury z MCP Codex:

#### 1. IMPLEMENTACJA KODU
```javascript
// Procedura delegacji implementacji
const implementFeature = async (feature, requirements) => {
    const result = await mcp__codex__codex({
        prompt: `Zaimplementuj ${feature} dla PPM-CC-Laravel zgodnie z specjalizacją agenta [NAZWA_AGENTA].
        
        REQUIREMENTS:
        ${requirements}
        
        SPECJALIZACJA AGENTA:
        [OPISAĆ SPECJALIZACJĘ AGENTA - np. Database Expert, ERP Integration, etc.]
        
        STANDARDY:
        - Laravel 12.x + PHP 8.3 best practices
        - Enterprise security standards
        - PPM-CC-Laravel architecture compliance
        - Hostido shared hosting compatibility`,
        model: "opus", // complex implementations
        sandbox: "workspace-write"
    });
    
    return result;
};
```

#### 2. WERYFIKACJA KODU
```javascript
// Obowiązkowa weryfikacja każdego kodu
const verifyCode = async (filePaths) => {
    const verification = await mcp__codex__codex({
        prompt: `Zweryfikuj kod w plikach: ${filePaths.join(', ')}
        
        KRYTERIA WERYFIKACJI:
        1. Zgodność ze specjalizacją agenta [NAZWA_AGENTA]
        2. Laravel 12.x best practices
        3. Security vulnerability scan
        4. Performance optimization check
        5. PPM-CC-Laravel architecture compliance
        6. Code quality i maintainability
        
        Podaj szczegółową analizę i sugestie poprawek.`,
        model: "sonnet",
        sandbox: "read-only"
    });
    
    return verification;
};
```

### NOWY WORKFLOW z MCP Codex:

1. **Otrzymaj zadanie** → Przeanalizuj requirements w ramach specjalizacji
2. **Przygotuj specyfikację** → Detailed analysis dla MCP Codex
3. **🔥 DELEGUJ do MCP Codex** → Implementation z odpowiednim modelem
4. **Sprawdź rezultat** → Verify MCP output quality
5. **🔥 WERYFIKUJ przez MCP Codex** → Code quality check
6. **Apply corrections** → Jeśli MCP wskazał problemy
7. **Deploy i monitor** → Test na ppm.mpptrade.pl

### Model Selection:
- **opus** - Complex implementations, business logic, architecture
- **sonnet** - Code verification, simple implementations, optimization
- **haiku** - NIGDY dla kodowania (zbyt prosty)

### Sandbox Guidelines:
- **workspace-write** - Gdy MCP Codex ma pisać/edytować kod
- **read-only** - Wyłącznie dla weryfikacji
- **danger-full-access** - NIGDY nie używać

**PAMIĘTAJ: MCP Codex ma pełną wiedzę o technologiach i może lepiej zaimplementować enterprise kod!**

## Narzędzia agenta (ZAKTUALIZOWANE):

Czytaj pliki, **DELEGACJA do MCP Codex (główne narzędzie kodowe)**, Używaj przeglądarki, **OBOWIĄZKOWO: MCP Codex dla wszystkich operacji kodowych**

---

**INSTRUKCJE ZASTOSOWANIA:**
1. Skopiuj ten template na koniec każdego pliku agenta
2. Zastąp `[NAZWA_AGENTA]` odpowiednią nazwą
3. Dostosuj sekcję "SPECJALIZACJA AGENTA" do konkretnego agenta
4. Aktualizuj sekcję "Narzędzia agenta" zgodnie z template