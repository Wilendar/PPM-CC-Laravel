# Context7 Integration Guide - PPM-CC-Laravel

**Dokument:** Przewodnik integracji MCP Context7 dla dostępu do aktualnej dokumentacji
**Ostatnia aktualizacja:** 2025-10-14
**Powiązane:** CLAUDE.md → Context7 Integration System

---

## 📚 CONTEXT7 INTEGRATION SYSTEM

**STATUS:** ✅ AKTYWNY (wdrożony 2025-09-27)

PPM-CC-Laravel używa MCP Context7 server dla dostępu do aktualnej dokumentacji bibliotek i best practices. Wszystkich agentów zaktualizowano z obowiązkową integracją Context7.

---

## 🎯 WYBRANE BIBLIOTEKI CONTEXT7

| Technologia | Library ID | Snippets | Trust | Agent Integration |
|-------------|------------|----------|-------|-------------------|
| **Laravel 12.x** | `/websites/laravel_12_x` | 4927 | 7.5 | laravel-expert, architect, debugger |
| **Livewire 3.x** | `/livewire/livewire` | 867 | 7.4 | livewire-specialist, debugger |
| **Alpine.js** | `/alpinejs/alpine` | 364 | 6.6 | frontend-specialist, livewire-specialist |
| **PrestaShop** | `/prestashop/docs` | 3289 | 8.2 | prestashop-api-expert |

---

## ⚠️ MANDATORY CONTEXT7 USAGE RULES

**WSZYSTKICH AGENTÓW ZAKTUALIZOWANO** z obowiązkową integracją Context7:

### Zasada 1: PRZED każdą implementacją

```markdown
Agent MUSI użyć `mcp__context7__get-library-docs` PRZED rozpoczęciem implementacji.
```

**Przykład:**
```
PRZED: Implementacja Livewire component
KROK 1: Użyj Context7 dla /livewire/livewire
KROK 2: Przeczytaj aktualne patterns
KROK 3: Implementuj zgodnie z dokumentacją
```

### Zasada 2: ZAWSZE weryfikować

```markdown
Aktualne patterns z oficjalnych źródeł MUSZĄ być zweryfikowane przez Context7.
```

**NIE polegaj na:**
- ❌ Pamięci z training data (może być przestarzała)
- ❌ Domysłach o API (sprawdź dokumentację)
- ❌ Starych przykładach (verify current version)

**UŻYWAJ:**
- ✅ Context7 dla current documentation
- ✅ Official library docs via Context7
- ✅ Latest best practices

### Zasada 3: REFERENCOWAĆ

```markdown
W odpowiedziach ZAWSZE referencuj oficjalną dokumentację z Context7.
```

**Przykład:**
```markdown
Zgodnie z dokumentacją Livewire 3.x (Context7: /livewire/livewire):
- Używaj `$this->dispatch()` zamiast `$this->emit()`
- Properties muszą być public lub mieć getters/setters
- Wire:key jest OBOWIĄZKOWY w pętlach
```

### Zasada 4: UŻYWAĆ właściwych Library IDs

```markdown
Każda technologia ma dedykowany Library ID - używaj właściwego!
```

**Mapping:**
- Laravel 12.x → `/websites/laravel_12_x`
- Livewire 3.x → `/livewire/livewire`
- Alpine.js → `/alpinejs/alpine`
- PrestaShop → `/prestashop/docs`

---

## 🔧 CONTEXT7 MCP CONFIGURATION

### Konfiguracja (Already Done)

```bash
# Context7 MCP Server już skonfigurowany
claude mcp list

# Expected output:
# context7: https://mcp.context7.com/mcp (HTTP) - ✓ Connected
```

### API Key

```
ctx7sk-dea67299-09f8-4fab-b4bd-d36297a675c3
```

**Status:** ✅ Aktywny, skonfigurowany w Claude Code MCP settings

---

## 🤖 AGENT CONTEXT7 IMPLEMENTATION STATUS

| Agent | Context7 Status | Primary Library | Updated |
|-------|----------------|-----------------|---------|
| **laravel-expert** | ✅ ACTIVE | `/websites/laravel_12_x` | 2025-09-27 |
| **livewire-specialist** | ✅ ACTIVE | `/livewire/livewire` | 2025-09-27 |
| **prestashop-api-expert** | ✅ ACTIVE | `/prestashop/docs` | 2025-09-27 |
| **frontend-specialist** | ✅ ACTIVE | `/alpinejs/alpine` | 2025-09-27 |
| **coding-style-agent** | ✅ ACTIVE | Multiple libraries | Pre-configured |
| **documentation-reader** | ✅ ACTIVE | All libraries | 2025-09-27 |
| **ask** | ✅ ACTIVE | Multiple libraries | 2025-09-27 |
| **debugger** | ✅ ACTIVE | `/websites/laravel_12_x` | 2025-09-27 |
| **architect** | ✅ ACTIVE | `/websites/laravel_12_x` | 2025-09-27 |
| **erp-integration-expert** | ✅ ACTIVE | `/websites/laravel_12_x` | 2025-09-27 |
| **import-export-specialist** | ✅ ACTIVE | `/websites/laravel_12_x` | 2025-09-27 |
| **deployment-specialist** | ✅ ACTIVE | `/websites/laravel_12_x` | 2025-09-27 |

**REZULTAT:** 100% agentów ma aktywną integrację Context7 dla zapewnienia aktualnych informacji i best practices.

---

## 📋 USAGE PATTERNS

### Pattern 1: Laravel Expert - Eloquent Query

```markdown
# WORKFLOW:

1. User Request: "How to implement eager loading for Product relationships?"

2. Agent MUST:
   - Use mcp__context7__get-library-docs
   - Library ID: /websites/laravel_12_x
   - Topic: "eager loading"

3. Context7 Returns:
   - Current Laravel 12.x eager loading syntax
   - Best practices for N+1 query prevention
   - Performance optimization tips

4. Agent Implements:
   - Use current syntax from Context7
   - Reference documentation in response
   - Provide example with current Laravel 12.x patterns
```

### Pattern 2: Livewire Specialist - Component Creation

```markdown
# WORKFLOW:

1. User Request: "Create Livewire component for product listing"

2. Agent MUST:
   - Use mcp__context7__get-library-docs
   - Library ID: /livewire/livewire
   - Topic: "component lifecycle"

3. Context7 Returns:
   - Livewire 3.x component structure
   - Lifecycle hooks (mount, hydrate, updated)
   - Property binding syntax

4. Agent Implements:
   - Current Livewire 3.x syntax ($this->dispatch vs $this->emit)
   - Proper wire:key usage
   - Reference official patterns
```

### Pattern 3: PrestaShop API Expert - Product Sync

```markdown
# WORKFLOW:

1. User Request: "Sync product to PrestaShop API"

2. Agent MUST:
   - Use mcp__context7__get-library-docs
   - Library ID: /prestashop/docs
   - Topic: "product API"

3. Context7 Returns:
   - PrestaShop 8.x/9.x API endpoints
   - Authentication methods
   - Required fields for product creation

4. Agent Implements:
   - Use current API version endpoints
   - Follow authentication best practices
   - Handle errors according to docs
```

---

## 🎯 EXPECTED BEHAVIOR

### ✅ CORRECT Agent Response (with Context7)

```markdown
Agent: livewire-specialist

User: "How to emit event in Livewire 3.x?"

Response:
---
Zgodnie z dokumentacją Livewire 3.x (Context7: /livewire/livewire):

W Livewire 3.x metoda `emit()` została zastąpiona przez `dispatch()`:

```php
// ✅ Livewire 3.x (CURRENT)
$this->dispatch('productUpdated', productId: $this->product->id);

// ❌ Livewire 2.x (DEPRECATED)
$this->emit('productUpdated', $this->product->id);
```

**Pełna dokumentacja:** /livewire/livewire → Events section
```

### ❌ INCORRECT Agent Response (without Context7)

```markdown
Agent: livewire-specialist

User: "How to emit event in Livewire 3.x?"

Response:
---
W Livewire możesz użyć metody `emit()`:

```php
$this->emit('productUpdated', $this->product->id);
```

❌ PROBLEM: Użyto przestarzałej składni z Livewire 2.x!
❌ Brak weryfikacji przez Context7!
❌ User otrzyma błąd: "Call to undefined method emit()"
```

---

## 📊 CONTEXT7 METRICS

### Library Coverage

- **Laravel 12.x:** 4927 snippets (trust: 7.5/10)
- **Livewire 3.x:** 867 snippets (trust: 7.4/10)
- **Alpine.js:** 364 snippets (trust: 6.6/10)
- **PrestaShop:** 3289 snippets (trust: 8.2/10)

### Agent Adoption

- **Total Agents:** 12
- **Context7 Enabled:** 12 (100%)
- **Mandatory Usage:** ✅ All agents

---

## 🔍 TROUBLESHOOTING

### Problem: Context7 not returning results

```bash
# Check MCP connection
claude mcp list

# Expected: context7 - ✓ Connected
# If not connected: Restart Claude Code
```

### Problem: Wrong library ID

```markdown
# ❌ WRONG
mcp__context7__get-library-docs('/laravel/laravel')

# ✅ CORRECT
mcp__context7__get-library-docs('/websites/laravel_12_x')
```

### Problem: Topic too broad

```markdown
# ❌ TOO BROAD
Topic: "laravel"  # Returns too many results

# ✅ SPECIFIC
Topic: "eloquent relationships"  # Targeted results
```

---

## 📖 POWIĄZANA DOKUMENTACJA

- **CLAUDE.md** - Context7 Integration System
- **_DOCS/AGENT_USAGE_GUIDE.md** - Agent workflow patterns
- **`.claude/agents/`** - Agent configuration files

---


## 📝 AGENT CONFIGURATION EXAMPLE

```markdown
# .claude/agents/livewire-specialist.md

## Context7 Integration (MANDATORY)

**PRZED każdą implementacją:**

1. Use: mcp__context7__get-library-docs
2. Library ID: /livewire/livewire
3. Topic: [specific feature]
4. Implement: According to returned docs

**Example:**
```bash
mcp__context7__get-library-docs(
  context7CompatibleLibraryID: "/livewire/livewire",
  topic: "component properties"
)
```

**Always reference:** Documentation in responses
```

---

## ✅ CHECKLIST: Context7 Compliant Implementation

Przed ukończeniem implementacji sprawdź:

- [ ] Użyto Context7 dla primary library
- [ ] Zweryfikowano current syntax/patterns
- [ ] Referenced documentation w odpowiedzi
- [ ] Użyto właściwego Library ID
- [ ] Topic był specific (not too broad)
- [ ] Code follows returned patterns
- [ ] No outdated syntax (emit vs dispatch, etc.)

---

**PAMIĘTAJ:** Context7 to OBOWIĄZKOWY krok przed każdą implementacją! Zawsze weryfikuj aktualne patterns!
