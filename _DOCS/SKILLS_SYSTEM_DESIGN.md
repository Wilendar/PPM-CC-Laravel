# PROJEKT SYSTEMU SKILLS DLA PPM-CC-LARAVEL-TEST

**Data:** 2025-11-04
**Based on:** Reddit guide "Claude Code is a Beast"
**Status:** Design Document

---

## 📋 SPIS TREŚCI

1. [Executive Summary](#executive-summary)
2. [Architektura Skills](#architektura-skills)
3. [Skills Catalog](#skills-catalog)
4. [skill-rules.json Configuration](#skill-rules-json-configuration)
5. [Hooks System](#hooks-system)
6. [Dev Docs System](#dev-docs-system)
7. [Agents System](#agents-system)
8. [Slash Commands](#slash-commands)
9. [Implementation Roadmap](#implementation-roadmap)

---

## 1. EXECUTIVE SUMMARY

### Problem Statement

Projekt PPM ma:
- 60+ komponentów Livewire
- Kompleksową architekturę multi-store
- SKU-first wzorce architektoniczne
- PrestaShop integration z wieloma mapperami
- 60+ plików dokumentacji

**Wyzwanie:** Claude Code często:
- Nie stosuje SKU-first architecture
- Tworzy inline styles (ZAKAZ w projekcie!)
- Nie używa Service Layer patterns
- Zapomina o multi-store validation
- Nie czyta istniejącej dokumentacji

### Rozwiązanie: Auto-Activation Skills System

Zaimplementujemy system z Reddit guide:
1. **Skills** - Portable guidelines (< 500 linii + resource files)
2. **skill-rules.json** - Trigger configuration (keywords, paths, content)
3. **Hooks** - Auto-activation system
4. **Dev Docs** - Task tracking (plan/context/tasks)
5. **Agents** - Specialized subagents
6. **Slash Commands** - Workflow automation

---

## 2. ARCHITEKTURA SKILLS

### 2.1 Hierarchia Skills

```
.claude/skills/
├── guidelines/                          # Best Practices (domain skills)
│   ├── laravel-dev-guidelines/
│   │   ├── SKILL.md                     # Main file (< 500 lines)
│   │   └── resources/
│   │       ├── controllers.md           # Controller patterns
│   │       ├── services.md              # Service layer
│   │       ├── models.md                # Eloquent patterns
│   │       ├── migrations.md            # Migration best practices
│   │       ├── validation.md            # Form Request patterns
│   │       ├── policies.md              # Authorization
│   │       ├── jobs.md                  # Queue patterns
│   │       ├── events.md                # Event patterns
│   │       ├── middleware.md            # Middleware patterns
│   │       └── testing.md               # PHPUnit patterns
│   │
│   ├── livewire-dev-guidelines/
│   │   ├── SKILL.md                     # Main file (< 500 lines)
│   │   └── resources/
│   │       ├── component-structure.md   # Single Responsibility
│   │       ├── traits.md                # Trait composition
│   │       ├── services.md              # Service injection
│   │       ├── validation.md            # Livewire validation
│   │       ├── lifecycle.md             # Lifecycle hooks
│   │       ├── wire-model.md            # Data binding
│   │       ├── wire-actions.md          # Actions patterns
│   │       ├── alpine-integration.md    # Alpine.js patterns
│   │       ├── performance.md           # Lazy loading, polling
│   │       └── troubleshooting.md       # Known issues (9 patterns!)
│   │
│   └── frontend-dev-guidelines/
│       ├── SKILL.md                     # Main file (< 500 lines)
│       └── resources/
│           ├── css-architecture.md      # Tailwind + custom CSS
│           ├── styling-rules.md         # ZAKAZ inline styles!
│           ├── alpine-patterns.md       # Alpine.js best practices
│           ├── vite-build.md            # Vite configuration
│           └── verification.md          # Mandatory screenshot tests
│
├── domain/                              # Domain-Specific Skills
│   ├── prestashop-sync-manager/
│   │   ├── SKILL.md                     # PrestaShop integration
│   │   ├── scripts/
│   │   │   └── test-prestashop-sync.php # Testing script
│   │   └── resources/
│   │       ├── api-clients.md           # PS 8.x/9.x clients
│   │       ├── transformers.md          # Data transformation
│   │       ├── mappers.md               # Category/Price/Warehouse
│   │       ├── sync-jobs.md             # Async operations
│   │       ├── conflict-resolution.md   # Conflict handling
│   │       └── troubleshooting.md       # Common sync errors
│   │
│   ├── sku-architecture-validator/
│   │   ├── SKILL.md                     # SKU-first enforcement
│   │   ├── scripts/
│   │   │   └── validate-sku-usage.php   # Validation script
│   │   └── resources/
│   │       ├── patterns.md              # SKU-first patterns
│   │       ├── anti-patterns.md         # Hard-coded IDs (ZAKAZ!)
│   │       └── refactoring.md           # Refactoring guide
│   │
│   ├── multi-store-data-manager/
│   │   ├── SKILL.md                     # Multi-store patterns
│   │   └── resources/
│   │       ├── per-shop-data.md         # ProductShopData patterns
│   │       ├── sync-status.md           # Sync status tracking
│   │       ├── conflicts.md             # Conflict detection
│   │       └── pending-changes.md       # Pending changes system
│   │
│   ├── variant-system-manager/
│   │   ├── SKILL.md                     # Variant system
│   │   └── resources/
│   │       ├── attribute-types.md       # AttributeType patterns
│   │       ├── combinations.md          # Variant generation
│   │       ├── pricing.md               # Variant prices
│   │       └── prestashop-mapping.md    # PS attribute mapping
│   │
│   └── database-migration-manager/
│       ├── SKILL.md                     # Migration best practices
│       ├── scripts/
│       │   └── migration-safety-check.php
│       └── resources/
│           ├── conventions.md           # Naming conventions
│           ├── rollback-safety.md       # Rollback checks
│           ├── indexes.md               # Index optimization
│           └── data-migrations.md       # Data transformation
│
└── workflow/                            # Workflow Skills (już istnieją!)
    ├── hostido-deployment/
    ├── livewire-troubleshooting/
    ├── frontend-verification/
    ├── agent-report-writer/
    ├── project-plan-manager/
    ├── context7-docs-lookup/
    ├── issue-documenter/
    ├── debug-log-cleanup/
    └── ppm-architecture-compliance/
```

### 2.2 Wzorzec Struktury Skill

```markdown
# [Skill Name]

**Type:** [domain/workflow/guardrail]
**Enforcement:** [suggest/require/block]
**Priority:** [low/medium/high/critical]

## Quick Reference

[1-2 paragraphs overview]

## When to Use This Skill

- [Trigger scenario 1]
- [Trigger scenario 2]
- [Trigger scenario 3]

## Core Principles

[3-5 key principles, numbered]

## Common Patterns

### Pattern 1: [Name]
[Description + example]

### Pattern 2: [Name]
[Description + example]

## Anti-Patterns (ZAKAZ!)

### ❌ Anti-Pattern 1
[What NOT to do + why]

### ✅ Correct Approach
[What to do instead]

## Scripts & Tools

[List of attached scripts with usage]

## Resource Files

- [Resource 1] - [Description]
- [Resource 2] - [Description]

## Related Skills

- [Skill 1] - [When to use together]
- [Skill 2] - [When to use together]
```

---

## 3. SKILLS CATALOG

### 3.1 Guidelines Skills (Best Practices)

#### **laravel-dev-guidelines**

**Type:** domain
**Enforcement:** suggest
**Priority:** high

**Triggers:**
- Keywords: `laravel`, `controller`, `service`, `model`, `eloquent`, `migration`
- Intent patterns: `(create|add).*?(controller|service|model|migration)`
- File paths: `app/**/*.php`, `database/migrations/*.php`
- Content patterns: `namespace App\\`, `use Illuminate\\`

**Main file topics:**
- Architecture overview (Service Layer pattern)
- Controller patterns (slim controllers!)
- Service layer (fat services)
- Model patterns (Eloquent best practices)
- Migration conventions
- Validation (Form Requests)

**Resource files (10):**
- `controllers.md` - Controller patterns + examples
- `services.md` - Service layer architecture
- `models.md` - Eloquent relationships, scopes, traits
- `migrations.md` - Migration best practices + rollback safety
- `validation.md` - Form Request validation patterns
- `policies.md` - Authorization patterns (Spatie permissions)
- `jobs.md` - Queue patterns + job chaining
- `events.md` - Event/Listener patterns
- `middleware.md` - Middleware patterns
- `testing.md` - PHPUnit patterns + Feature/Unit tests

---

#### **livewire-dev-guidelines**

**Type:** domain
**Enforcement:** require
**Priority:** critical

**Triggers:**
- Keywords: `livewire`, `component`, `wire:`, `alpine`, `@livewire`
- Intent patterns: `(create|add).*?(livewire|component)`, `wire:model`, `wire:click`
- File paths: `app/Livewire/**/*.php`, `resources/views/livewire/**/*.blade.php`
- Content patterns: `use Livewire\\Component`, `extends Component`, `#[Layout]`

**Main file topics:**
- Single Responsibility Components
- Trait composition (ProductFormValidation pattern)
- Service injection (ProductMultiStoreManager pattern)
- Lifecycle hooks (mount, updated, etc.)
- Data binding patterns

**Resource files (10):**
- `component-structure.md` - File organization + naming
- `traits.md` - Trait composition patterns (jak ProductForm!)
- `services.md` - Service injection + dependency management
- `validation.md` - Livewire validation + real-time
- `lifecycle.md` - Lifecycle hooks + best practices
- `wire-model.md` - Data binding patterns + defer/blur
- `wire-actions.md` - Actions + parameters + confirmation
- `alpine-integration.md` - Alpine.js + Livewire coordination
- `performance.md` - Lazy loading, wire:poll, wire:key
- `troubleshooting.md` - 9 known Livewire issues (z _ISSUES_FIXES!)

**KRYTYCZNE PATTERNS:**
```php
// ✅ CORRECT: Trait Composition
class ProductForm extends Component
{
    use ProductFormValidation;
    use ProductFormUpdates;
    use ProductFormComputed;
    use ProductFormVariants;

    public function save()
    {
        $this->validate();
        app(ProductFormSaver::class)->save($this);
    }
}

// ❌ WRONG: Monolithic component (2000+ lines)
class ProductForm extends Component
{
    // 2000 lines of spaghetti...
}
```

---

#### **frontend-dev-guidelines**

**Type:** domain
**Enforcement:** require
**Priority:** critical

**Triggers:**
- Keywords: `css`, `tailwind`, `alpine`, `blade`, `frontend`, `ui`
- Intent patterns: `(style|design|ui|layout)`, `(add|create).*?(modal|dropdown|button)`
- File paths: `resources/css/**/*.css`, `resources/views/**/*.blade.php`
- Content patterns: `@apply`, `x-data`, `class="`, `style="`

**Main file topics:**
- **ZAKAZ inline styles!** (`style="..."` ❌)
- **ZAKAZ arbitrary Tailwind!** (`class="z-[9999]"` ❌)
- CSS architecture (dedicated files)
- Tailwind configuration
- Alpine.js patterns

**Resource files (5):**
- `css-architecture.md` - File organization + naming conventions
- `styling-rules.md` - **CRITICAL:** Zakazy + prawidłowe podejście
- `alpine-patterns.md` - Alpine.js best practices
- `vite-build.md` - Vite configuration + production builds
- `verification.md` - **MANDATORY:** Screenshot testing workflow

**KRYTYCZNE ZASADY:**

```blade
{{-- ❌ WRONG: Inline style --}}
<div style="z-index: 9999; background: red;">

{{-- ❌ WRONG: Arbitrary Tailwind --}}
<div class="z-[9999] bg-[#ff0000]">

{{-- ✅ CORRECT: Dedicated CSS class --}}
<div class="modal-overlay">
```

```css
/* resources/css/components/modal.css */
.modal-overlay {
    z-index: var(--z-modal-overlay); /* 1050 */
    background-color: var(--color-overlay);
}
```

---

### 3.2 Domain Skills (Specific Features)

#### **prestashop-sync-manager**

**Type:** domain
**Enforcement:** suggest
**Priority:** high

**Triggers:**
- Keywords: `prestashop`, `sync`, `import`, `export`, `ps8`, `ps9`
- Intent patterns: `sync.*?prestashop`, `import.*?prestashop`, `export.*?product`
- File paths: `app/Services/PrestaShop/**/*.php`, `app/Livewire/Admin/Shops/*.php`
- Content patterns: `PrestaShopService`, `PrestaShopClient`, `SyncJob`

**Main file topics:**
- PrestaShop API clients (8.x vs 9.x)
- Transformers (PPM → PrestaShop)
- Mappers (Category, Price, Warehouse)
- Sync jobs (async operations)
- Conflict resolution

**Scripts:**
- `test-prestashop-sync.php` - Test sync dla pojedynczego produktu

**Resource files (6):**
- `api-clients.md` - BaseClient + PS8/PS9 clients
- `transformers.md` - ProductTransformer + CategoryTransformer
- `mappers.md` - Mapping strategies + database tables
- `sync-jobs.md` - Queue patterns + job chaining
- `conflict-resolution.md` - Handling conflicts + pending changes
- `troubleshooting.md` - Common sync errors + solutions

---

#### **sku-architecture-validator**

**Type:** guardrail
**Enforcement:** require
**Priority:** critical

**Triggers:**
- Keywords: `product`, `find`, `where`, `id`, `primary key`
- Intent patterns: `Product::find`, `->id`, `where\('id'`
- File paths: `app/**/*.php`
- Content patterns: `Product::find\(`, `->where\('id',`

**Main file topics:**
- **SKU-first architecture principles**
- **ZAKAZ hard-coded IDs!**
- Refactoring patterns
- Validation script

**Scripts:**
- `validate-sku-usage.php` - Skanuje kod w poszukiwaniu hard-coded IDs

**Resource files (3):**
- `patterns.md` - SKU-first patterns + examples
- `anti-patterns.md` - **CRITICAL:** Co NIE robić + dlaczego
- `refactoring.md` - Jak refaktorować istniejący kod

**KRYTYCZNE PATTERNS:**

```php
// ❌ WRONG: Hard-coded ID
$product = Product::find($request->input('id'));

// ❌ WRONG: ID in relationships
$category->products()->attach($productId);

// ✅ CORRECT: SKU-first
$product = Product::where('sku', $request->input('sku'))->first();

// ✅ CORRECT: SKU in relationships
$product = Product::where('sku', $sku)->first();
$category->products()->attach($product->id);
```

---

#### **multi-store-data-manager**

**Type:** domain
**Enforcement:** suggest
**Priority:** high

**Triggers:**
- Keywords: `multi-store`, `per-shop`, `shop_id`, `ProductShopData`, `shop-specific`
- Intent patterns: `per.?shop`, `multi.?store`, `shop.?override`
- File paths: `app/Models/ProductShopData.php`, `app/Services/**/*MultiStore*.php`
- Content patterns: `ProductShopData`, `shop_id`, `shopData()`

**Main file topics:**
- Per-shop data patterns
- Sync status tracking
- Conflict detection
- Pending changes system

**Resource files (4):**
- `per-shop-data.md` - ProductShopData relationship patterns
- `sync-status.md` - Tracking sync status per shop
- `conflicts.md` - Conflict detection algorithms
- `pending-changes.md` - Pending changes UI patterns

---

#### **variant-system-manager**

**Type:** domain
**Enforcement:** suggest
**Priority:** medium

**Triggers:**
- Keywords: `variant`, `attribute`, `combination`, `sku_variantu`
- Intent patterns: `create.*?variant`, `generate.*?combination`, `attribute.*?mapping`
- File paths: `app/Models/ProductVariant.php`, `app/Services/**/*Variant*.php`
- Content patterns: `ProductVariant`, `AttributeType`, `AttributeValue`

**Main file topics:**
- Variant generation algorithms
- Attribute mapping (PrestaShop)
- Combination matrix
- Pricing strategies

**Resource files (4):**
- `attribute-types.md` - AttributeType + AttributeValue patterns
- `combinations.md` - Variant generation algorithms
- `pricing.md` - Variant pricing strategies
- `prestashop-mapping.md` - PrestaShop attribute mapping

---

#### **database-migration-manager**

**Type:** domain
**Enforcement:** suggest
**Priority:** medium

**Triggers:**
- Keywords: `migration`, `schema`, `table`, `column`, `index`
- Intent patterns: `create.*?migration`, `add.*?column`, `create.*?index`
- File paths: `database/migrations/*.php`
- Content patterns: `Schema::create`, `Schema::table`, `->index()`

**Main file topics:**
- Migration naming conventions
- Rollback safety
- Index optimization
- Data migrations

**Scripts:**
- `migration-safety-check.php` - Checks destructive operations

**Resource files (4):**
- `conventions.md` - Naming conventions + structure
- `rollback-safety.md` - Rollback checks + testing
- `indexes.md` - Index optimization strategies
- `data-migrations.md` - Data transformation patterns

---

## 4. SKILL-RULES.JSON CONFIGURATION

```json
{
  "laravel-dev-guidelines": {
    "type": "domain",
    "enforcement": "suggest",
    "priority": "high",
    "promptTriggers": {
      "keywords": [
        "laravel",
        "controller",
        "service",
        "model",
        "eloquent",
        "migration",
        "artisan",
        "php"
      ],
      "intentPatterns": [
        "(create|add|build).*?(controller|service|model|migration)",
        "(how to|best practice).*?(laravel|backend|api)",
        "service layer",
        "eloquent.*?(relationship|query|scope)"
      ]
    },
    "fileTriggers": {
      "pathPatterns": [
        "app/**/*.php",
        "database/migrations/*.php",
        "routes/*.php"
      ],
      "contentPatterns": [
        "namespace App\\\\",
        "use Illuminate\\\\",
        "extends Model",
        "extends Controller"
      ]
    },
    "description": "Laravel best practices: Service Layer, Controllers, Models, Migrations"
  },

  "livewire-dev-guidelines": {
    "type": "domain",
    "enforcement": "require",
    "priority": "critical",
    "promptTriggers": {
      "keywords": [
        "livewire",
        "component",
        "wire:",
        "alpine",
        "@livewire",
        "trait composition",
        "livewire validation"
      ],
      "intentPatterns": [
        "(create|add|build).*?(livewire|component)",
        "wire:(model|click|submit|poll)",
        "(how to|best practice).*?livewire",
        "trait.*?composition",
        "component.*?(refactor|split)"
      ]
    },
    "fileTriggers": {
      "pathPatterns": [
        "app/Livewire/**/*.php",
        "resources/views/livewire/**/*.blade.php"
      ],
      "contentPatterns": [
        "use Livewire\\\\Component",
        "extends Component",
        "#\\[Layout\\]",
        "wire:model",
        "wire:click"
      ]
    },
    "description": "Livewire 3.x patterns: Single Responsibility, Trait Composition, Service Injection"
  },

  "frontend-dev-guidelines": {
    "type": "domain",
    "enforcement": "require",
    "priority": "critical",
    "promptTriggers": {
      "keywords": [
        "css",
        "tailwind",
        "alpine",
        "blade",
        "frontend",
        "ui",
        "style",
        "modal",
        "dropdown"
      ],
      "intentPatterns": [
        "(style|design|ui|layout)",
        "(add|create).*?(modal|dropdown|button|form)",
        "inline.*?style",
        "arbitrary.*?tailwind",
        "z-index"
      ]
    },
    "fileTriggers": {
      "pathPatterns": [
        "resources/css/**/*.css",
        "resources/views/**/*.blade.php",
        "resources/js/**/*.js"
      ],
      "contentPatterns": [
        "@apply",
        "x-data",
        "class=\"",
        "style=\"",
        "z-\\["
      ]
    },
    "description": "Frontend rules: ZAKAZ inline styles, ZAKAZ arbitrary Tailwind, dedicated CSS classes"
  },

  "prestashop-sync-manager": {
    "type": "domain",
    "enforcement": "suggest",
    "priority": "high",
    "promptTriggers": {
      "keywords": [
        "prestashop",
        "sync",
        "import",
        "export",
        "ps8",
        "ps9",
        "transformer",
        "mapper"
      ],
      "intentPatterns": [
        "sync.*?(prestashop|product|category)",
        "import.*?prestashop",
        "export.*?product",
        "prestashop.*?(api|client|integration)",
        "conflict.*?resolution"
      ]
    },
    "fileTriggers": {
      "pathPatterns": [
        "app/Services/PrestaShop/**/*.php",
        "app/Livewire/Admin/Shops/*.php",
        "app/Models/ProductShopData.php"
      ],
      "contentPatterns": [
        "PrestaShopService",
        "PrestaShopClient",
        "SyncJob",
        "ProductTransformer",
        "CategoryMapper"
      ]
    },
    "description": "PrestaShop integration: API clients, Transformers, Mappers, Sync jobs"
  },

  "sku-architecture-validator": {
    "type": "guardrail",
    "enforcement": "require",
    "priority": "critical",
    "promptTriggers": {
      "keywords": [
        "product",
        "find",
        "where",
        "id",
        "primary key",
        "product id"
      ],
      "intentPatterns": [
        "Product::find",
        "->id",
        "where\\('id'",
        "find.*?product",
        "product.*?by.*?id"
      ]
    },
    "fileTriggers": {
      "pathPatterns": [
        "app/**/*.php"
      ],
      "contentPatterns": [
        "Product::find\\(",
        "->where\\('id',",
        "whereId\\(",
        "findOrFail\\("
      ]
    },
    "description": "SKU-first architecture enforcement: ZAKAZ hard-coded IDs, WYMAGANE SKU usage"
  },

  "multi-store-data-manager": {
    "type": "domain",
    "enforcement": "suggest",
    "priority": "high",
    "promptTriggers": {
      "keywords": [
        "multi-store",
        "per-shop",
        "shop_id",
        "ProductShopData",
        "shop-specific",
        "shop override"
      ],
      "intentPatterns": [
        "per.?shop",
        "multi.?store",
        "shop.?(override|specific|data)",
        "sync.*?status",
        "pending.*?change"
      ]
    },
    "fileTriggers": {
      "pathPatterns": [
        "app/Models/ProductShopData.php",
        "app/Services/**/*MultiStore*.php",
        "app/Livewire/Products/**/*.php"
      ],
      "contentPatterns": [
        "ProductShopData",
        "shop_id",
        "shopData\\(",
        "per_shop_",
        "shop_override"
      ]
    },
    "description": "Multi-store patterns: Per-shop data, Sync status, Conflict detection"
  },

  "variant-system-manager": {
    "type": "domain",
    "enforcement": "suggest",
    "priority": "medium",
    "promptTriggers": {
      "keywords": [
        "variant",
        "attribute",
        "combination",
        "sku_variantu",
        "attribute type",
        "attribute value"
      ],
      "intentPatterns": [
        "create.*?variant",
        "generate.*?combination",
        "attribute.*?mapping",
        "variant.*?(price|stock|image)"
      ]
    },
    "fileTriggers": {
      "pathPatterns": [
        "app/Models/ProductVariant.php",
        "app/Models/AttributeType.php",
        "app/Services/**/*Variant*.php",
        "app/Livewire/Admin/Variants/*.php"
      ],
      "contentPatterns": [
        "ProductVariant",
        "AttributeType",
        "AttributeValue",
        "variant_attributes",
        "generateCombinations"
      ]
    },
    "description": "Variant system: Attribute mapping, Combination generation, Variant pricing"
  },

  "database-migration-manager": {
    "type": "domain",
    "enforcement": "suggest",
    "priority": "medium",
    "promptTriggers": {
      "keywords": [
        "migration",
        "schema",
        "table",
        "column",
        "index",
        "foreign key"
      ],
      "intentPatterns": [
        "create.*?migration",
        "add.*?column",
        "create.*?index",
        "drop.*?(table|column)",
        "alter.*?table"
      ]
    },
    "fileTriggers": {
      "pathPatterns": [
        "database/migrations/*.php"
      ],
      "contentPatterns": [
        "Schema::create",
        "Schema::table",
        "->index\\(",
        "->foreign\\(",
        "->dropColumn"
      ]
    },
    "description": "Migration best practices: Naming, Rollback safety, Index optimization"
  }
}
```

---

## 5. HOOKS SYSTEM

### 5.1 Hook Types

#### **UserPromptSubmit Hook** (BEFORE Claude sees message)

**Plik:** `.claude/hooks/user-prompt-submit.ts`

**Funkcja:**
1. Analizuje prompt użytkownika
2. Sprawdza keywords + intent patterns z skill-rules.json
3. Sprawdza ostatnio edytowane pliki (file paths)
4. Generuje skill activation reminder
5. Wstrzykuje reminder do kontekstu Claude

**Przykład output:**
```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🎯 SKILL ACTIVATION CHECK
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Based on your request, consider using:

🔴 CRITICAL: livewire-dev-guidelines
   → You're working with Livewire components
   → Remember: Single Responsibility + Trait Composition

🔴 CRITICAL: frontend-dev-guidelines
   → You mentioned "styling" and "modal"
   → ZAKAZ: inline styles, arbitrary Tailwind
   → WYMAGANE: Dedicated CSS classes

🟡 SUGGESTED: sku-architecture-validator
   → You're working with Product model
   → Remember: SKU-first, NO hard-coded IDs

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

---

#### **Stop Event Hook** (AFTER Claude finishes)

**Plik:** `.claude/hooks/stop-event.ts`

**Funkcja:**
1. Analizuje edytowane pliki
2. Sprawdza file paths + content patterns z skill-rules.json
3. Wykrywa risky patterns:
   - Inline styles (`style="`)
   - Arbitrary Tailwind (`class="z-[`)
   - Hard-coded IDs (`Product::find($id)`)
   - Missing SKU usage
4. Generuje gentle self-check reminder
5. Pokazuje reminder (non-blocking)

**Przykład output:**
```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📋 SELF-CHECK REMINDER
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

⚠️  Frontend Changes Detected
   2 file(s) edited: product-form.blade.php, modal.blade.php

   ❓ Did you avoid inline styles? (style="..." ❌)
   ❓ Did you avoid arbitrary Tailwind? (z-[9999] ❌)
   ❓ Did you use dedicated CSS classes? (.modal-overlay ✅)

   💡 Frontend Rule:
      - All styles must be in dedicated CSS files
      - Reference: resources/css/components/*.css

⚠️  Product Model Changes Detected
   1 file(s) edited: ProductService.php

   ❓ Did you use SKU instead of ID?
   ❓ Did you avoid Product::find($id)? ❌
   ❓ Did you use Product::where('sku', $sku)? ✅

   💡 SKU-First Rule:
      - ALWAYS use SKU as primary identifier
      - Reference: _DOCS/SKU_ARCHITECTURE_GUIDE.md

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

---

#### **Post-Tool-Use Hook** (After Edit/Write)

**Plik:** `.claude/hooks/post-tool-use.ts`

**Funkcja:**
1. Loguje edytowane pliki
2. Identyfikuje repository
3. Zapisuje timestamp
4. Tworzy log file: `.claude/edit-logs.json`

**Format logu:**
```json
{
  "edits": [
    {
      "timestamp": "2025-11-04T10:30:00Z",
      "file": "app/Livewire/Products/ProductForm.php",
      "repository": "main",
      "tool": "Edit"
    }
  ]
}
```

---

#### **Build Checker Hook** (Stop Event)

**Plik:** `.claude/hooks/build-checker.ts`

**Funkcja:**
1. Czyta `.claude/edit-logs.json`
2. Identyfikuje zmienione repositories
3. Uruchamia build scripts:
   - **Main repo:** `composer phpstan` (static analysis)
   - **Frontend:** `npm run build` (Vite)
4. Parsuje błędy
5. Jeśli < 5 błędów: pokazuje Claude
6. Jeśli ≥ 5 błędów: proponuje `build-error-resolver` agent
7. Loguje wyniki: `.claude/build-logs.json`

**Przykład output:**
```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🔨 BUILD CHECK
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Repository: main
Command: composer phpstan

✅ Build successful! No errors found.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

---

#### **Error Handling Reminder Hook** (Stop Event)

**Plik:** `.claude/hooks/error-handling-reminder.ts`

**Funkcja:**
1. Analizuje edytowane pliki
2. Wykrywa risky patterns:
   - `try-catch` blocks
   - Async operations (`async`, `await`)
   - Database calls (`DB::`, `Eloquent`)
   - Controllers (`extends Controller`)
3. Sprawdza czy error handling obecny
4. Generuje gentle reminder

**Przykład output:**
```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📋 ERROR HANDLING SELF-CHECK
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

⚠️  Backend Changes Detected
   2 file(s) edited: PrestaShopService.php, ProductController.php

   ❓ Did you add try-catch blocks?
   ❓ Did you log errors? (Log::error())
   ❓ Did you handle API failures gracefully?

   💡 Backend Best Practice:
      - All API calls should have error handling
      - All database operations should be wrapped
      - All errors should be logged

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

---

### 5.2 Complete Hook Pipeline

```
User submits prompt
  ↓
UserPromptSubmit Hook → Skill activation reminder
  ↓
Claude processes request
  ↓
Claude uses Edit/Write tools
  ↓
Post-Tool-Use Hook → Log edited files
  ↓
Claude finishes responding
  ↓
Stop Event Hook 1: Error Handling Reminder
  ↓
Stop Event Hook 2: Build Checker
  ↓
Stop Event Hook 3: Skill Reminder (based on edited files)
  ↓
If errors found → Claude sees them and fixes
  ↓
If too many errors → build-error-resolver agent recommended
  ↓
Result: Clean, error-free code
```

---

## 6. DEV DOCS SYSTEM

### 6.1 Struktura Dev Docs

```
C:\Projects\PPM-CC-Laravel-TEST\dev\
├── active/                          # Active tasks
│   ├── [task-name]/
│   │   ├── [task-name]-plan.md      # Approved plan
│   │   ├── [task-name]-context.md   # Key files, decisions
│   │   └── [task-name]-tasks.md     # Checklist
│   │
│   └── variant-system-implementation/
│       ├── variant-system-plan.md
│       ├── variant-system-context.md
│       └── variant-system-tasks.md
│
├── completed/                       # Completed tasks (archive)
│   └── [task-name]/
│       ├── [task-name]-plan.md
│       ├── [task-name]-context.md
│       ├── [task-name]-tasks.md
│       └── [task-name]-final-report.md
│
└── templates/                       # Templates
    ├── plan-template.md
    ├── context-template.md
    └── tasks-template.md
```

### 6.2 Workflow

#### **Starting Large Task:**

1. **Planning Mode**
   - Prompt: "Zaplanuj implementację [feature]"
   - Claude uses planning mode (research + analysis)
   - Generates comprehensive plan

2. **Create Dev Docs** (slash command: `/dev-docs`)
   - Creates `dev/active/[task-name]/` directory
   - Generates 3 files:
     - `[task-name]-plan.md` - Approved plan
     - `[task-name]-context.md` - Key files, decisions, architecture notes
     - `[task-name]-tasks.md` - Checklist with ❌/🛠️/✅ statuses
   - Records "Last Updated" timestamp

3. **Implementation**
   - Claude reads all 3 files before starting
   - Updates tasks.md immediately after completing each task
   - Updates context.md with new decisions/files
   - Updates plan.md if scope changes

4. **Continuing After Compaction** (slash command: `/update-dev-docs`)
   - Claude updates all 3 files
   - Marks completed tasks ✅
   - Adds new tasks if discovered
   - Records next steps
   - User compacts conversation
   - New session: "Continue variant system implementation"
   - Claude reads all 3 files → seamless continuation!

5. **Completion**
   - All tasks marked ✅
   - Generates `[task-name]-final-report.md`
   - Moves directory to `dev/completed/`

---

### 6.3 Template Files

#### **plan-template.md**

```markdown
# [Task Name] - Implementation Plan

**Created:** [YYYY-MM-DD HH:MM]
**Last Updated:** [YYYY-MM-DD HH:MM]
**Status:** [Planning/In Progress/Completed]
**ETAP:** [ETAP_XX if applicable]

## Executive Summary

[2-3 paragraphs overview]

## Goals

1. [Goal 1]
2. [Goal 2]
3. [Goal 3]

## Scope

### In Scope
- [Item 1]
- [Item 2]

### Out of Scope
- [Item 1]
- [Item 2]

## Phases

### Phase 1: [Name]
**Estimated:** [time]

- [Task 1]
- [Task 2]

### Phase 2: [Name]
**Estimated:** [time]

- [Task 1]
- [Task 2]

## Risks

| Risk | Impact | Mitigation |
|------|--------|-----------|
| [Risk 1] | [High/Med/Low] | [Strategy] |

## Success Metrics

- [Metric 1]
- [Metric 2]

## Timeline

- Phase 1: [date range]
- Phase 2: [date range]

## Dependencies

- [Dependency 1]
- [Dependency 2]

## Related Documentation

- [Doc 1]: [path]
- [Doc 2]: [path]
```

---

#### **context-template.md**

```markdown
# [Task Name] - Context

**Last Updated:** [YYYY-MM-DD HH:MM]

## Key Files

### Models
- `app/Models/[Model].php` - [Description]

### Services
- `app/Services/[Service].php` - [Description]

### Livewire Components
- `app/Livewire/[Component].php` - [Description]

### Views
- `resources/views/livewire/[view].blade.php` - [Description]

### Migrations
- `database/migrations/[migration].php` - [Description]

## Architecture Decisions

### Decision 1: [Title]
**Date:** [YYYY-MM-DD]
**Decision:** [What was decided]
**Rationale:** [Why]
**Impact:** [Consequences]

### Decision 2: [Title]
**Date:** [YYYY-MM-DD]
**Decision:** [What was decided]
**Rationale:** [Why]
**Impact:** [Consequences]

## Database Schema Changes

### New Tables
- `table_name` - [Purpose]

### Modified Tables
- `table_name` - [Changes]

## API Changes

### New Endpoints
- `POST /api/[endpoint]` - [Description]

### Modified Endpoints
- `PUT /api/[endpoint]` - [Changes]

## Configuration Changes

- `.env` variables: [List]
- Config files: [List]

## Dependencies Added

- Composer: [Package name] - [Why]
- NPM: [Package name] - [Why]

## Skills Used

- [Skill 1] - [When/Why]
- [Skill 2] - [When/Why]

## Agents Used

- [Agent 1] - [Task]
- [Agent 2] - [Task]

## Issues Encountered

### Issue 1: [Title]
**Date:** [YYYY-MM-DD]
**Problem:** [Description]
**Solution:** [How resolved]
**Reference:** [_ISSUES_FIXES/file.md if documented]

## Next Steps

1. [Next step 1]
2. [Next step 2]
3. [Next step 3]

## Notes

[Additional notes, gotchas, warnings]
```

---

#### **tasks-template.md**

```markdown
# [Task Name] - Tasks Checklist

**Last Updated:** [YYYY-MM-DD HH:MM]

## Phase 1: [Name]

### ❌ 1.1 [Task Name]
**Status:** Not Started
**Estimated:** [time]

- [ ] Subtask 1
- [ ] Subtask 2

### 🛠️ 1.2 [Task Name]
**Status:** In Progress
**Started:** [YYYY-MM-DD HH:MM]

- [x] Subtask 1
- [ ] Subtask 2

### ✅ 1.3 [Task Name]
**Status:** Completed
**Completed:** [YYYY-MM-DD HH:MM]

- [x] Subtask 1
- [x] Subtask 2

**Files Created/Modified:**
- `app/Models/Model.php` - [Description]

## Phase 2: [Name]

### ❌ 2.1 [Task Name]
**Status:** Not Started
**Estimated:** [time]

- [ ] Subtask 1
- [ ] Subtask 2

## Blockers

### ⚠️ Blocker 1: [Title]
**Blocking:** Task 2.3
**Description:** [What's blocking]
**Resolution:** [How to unblock]

## Progress Summary

**Completed:** 2/10 tasks (20%)
**In Progress:** 1 task
**Blocked:** 1 task
**Remaining:** 6 tasks

**Estimated Completion:** [Date]
```

---

## 7. AGENTS SYSTEM

### 7.1 Specialized Agents

#### **Quality Control Agents**

**code-architecture-reviewer**
```markdown
Type: Quality Control
Purpose: Review code for best practices adherence

Instructions:
- Read changed files
- Check against relevant skills
- Verify architectural patterns
- Check for anti-patterns
- Generate detailed report

Output Format:
- ✅ Compliant patterns
- ⚠️ Concerns
- ❌ Violations
- 💡 Recommendations
```

**build-error-resolver**
```markdown
Type: Quality Control
Purpose: Systematically fix build errors

Instructions:
- Read build log
- Categorize errors (syntax, type, import, etc.)
- Fix errors one by one
- Re-run build after each fix
- Verify no new errors introduced

Output Format:
- Error count: [before] → [after]
- Fixed errors list
- Remaining errors (if any)
```

---

#### **Testing & Debugging Agents**

**frontend-error-fixer**
```markdown
Type: Debugging
Purpose: Diagnose and fix frontend errors

Instructions:
- Read console logs
- Identify error type (JS, Livewire, Alpine, CSS)
- Check relevant troubleshooting docs
- Apply fix
- Verify with screenshot test

Output Format:
- Error diagnosis
- Root cause
- Fix applied
- Verification steps
```

**prestashop-sync-debugger**
```markdown
Type: Debugging
Purpose: Debug PrestaShop sync failures

Instructions:
- Read sync logs (sync_logs table)
- Identify failure type (API, mapping, conflict)
- Check PrestaShop API response
- Verify mappings (category, price, warehouse)
- Apply fix

Output Format:
- Sync failure type
- Root cause
- Fix applied
- Re-sync verification
```

---

#### **Planning & Strategy Agents**

**strategic-plan-architect**
```markdown
Type: Planning
Purpose: Create comprehensive implementation plans

Instructions:
- Research codebase context
- Analyze existing patterns
- Identify dependencies
- Create phased plan
- Estimate timelines
- Identify risks
- Generate 3 files: plan.md, context.md, tasks.md

Output Format:
- Executive summary
- Phased plan with tasks
- Risk analysis
- Success metrics
- Timeline
```

**plan-reviewer**
```markdown
Type: Planning
Purpose: Review plans before implementation

Instructions:
- Read plan.md
- Check feasibility
- Identify missing dependencies
- Verify scope completeness
- Suggest improvements

Output Format:
- ✅ Strong points
- ⚠️ Concerns
- ❌ Missing elements
- 💡 Suggestions
```

---

#### **Specialized Domain Agents**

**prestashop-sync-manager**
```markdown
Type: Domain
Purpose: Manage PrestaShop sync operations

Instructions:
- Analyze product/category for sync
- Verify mappings
- Execute sync (via PrestaShopService)
- Handle conflicts
- Verify sync status
- Update sync logs

Output Format:
- Sync status (success/failure)
- Conflicts (if any)
- Sync log ID
- Verification steps
```

**sku-validator**
```markdown
Type: Domain
Purpose: Validate SKU-first architecture compliance

Instructions:
- Scan changed files
- Detect hard-coded IDs
- Detect Product::find($id) usage
- Suggest SKU-first refactoring
- Generate report

Output Format:
- ✅ Compliant code
- ❌ Violations found
- 💡 Refactoring suggestions
```

**variant-generator**
```markdown
Type: Domain
Purpose: Generate product variants from attributes

Instructions:
- Read AttributeType + AttributeValue
- Generate combination matrix
- Create ProductVariant records
- Assign SKUs (sku_variantu)
- Create default prices/stock
- Map to PrestaShop attributes

Output Format:
- Variants generated count
- SKU assignments
- PrestaShop mapping status
```

---

### 7.2 Agent Orchestration

**Workflow Example: Feature Implementation**

```
User: "Implement variant bulk edit feature"
  ↓
Main Claude: Enters planning mode, launches strategic-plan-architect
  ↓
strategic-plan-architect:
  - Researches variant system
  - Analyzes existing UI
  - Creates plan.md, context.md, tasks.md
  - Returns plan
  ↓
Main Claude: Reviews plan with user, gets approval
  ↓
Main Claude: Runs /dev-docs slash command
  - Creates dev/active/variant-bulk-edit/
  - Saves 3 files
  ↓
Main Claude: Implements Phase 1
  - Reads skills: livewire-dev-guidelines, variant-system-manager
  - Creates Livewire component
  - Hooks catch inline styles → Claude fixes
  - Updates tasks.md ✅
  ↓
Main Claude: Launches code-architecture-reviewer agent
  ↓
code-architecture-reviewer:
  - Reviews BulkEditVariants component
  - Checks trait composition
  - Checks service injection
  - Returns report
  ↓
Main Claude: Fixes issues from review
  ↓
Main Claude: Implements Phase 2
  - Creates variant-generator service
  - Updates context.md with decisions
  - Updates tasks.md ✅
  ↓
Main Claude: Launches frontend-verification skill
  - Screenshots UI
  - Checks console errors
  - Verifies Livewire init
  - Returns report
  ↓
Main Claude: Feature complete!
  - All tasks ✅
  - Generates final-report.md
  - Moves to dev/completed/
```

---

## 8. SLASH COMMANDS

### 8.1 Planning & Docs Commands

#### **/dev-docs**

**Purpose:** Create comprehensive strategic plan + dev docs

**Prompt:**
```
You are tasked with creating a comprehensive strategic plan for implementing a new feature or task.

Follow these steps:

1. Research Phase:
   - Analyze codebase context
   - Identify existing patterns
   - Review related documentation
   - Identify dependencies

2. Planning Phase:
   - Create phased implementation plan
   - Break down into specific tasks
   - Estimate timelines
   - Identify risks

3. Documentation Phase:
   - Generate plan.md (use template from dev/templates/)
   - Generate context.md (record key files, decisions)
   - Generate tasks.md (checklist with ❌/🛠️/✅)

4. Save Files:
   - Create directory: dev/active/[task-name]/
   - Save all 3 files
   - Set "Last Updated" timestamps

Output:
- Confirmation of files created
- Link to dev/active/[task-name]/ directory
- Summary of plan

IMPORTANT: Use Polish language for all documentation.
```

---

#### **/update-dev-docs**

**Purpose:** Update dev docs before compaction

**Prompt:**
```
Update the dev docs for the current task before conversation compaction.

Follow these steps:

1. Read Current Dev Docs:
   - Read [task-name]-plan.md
   - Read [task-name]-context.md
   - Read [task-name]-tasks.md

2. Update Tasks:
   - Mark completed tasks ✅
   - Add newly discovered tasks
   - Update progress summary
   - Record "Last Updated" timestamp

3. Update Context:
   - Add new files created/modified
   - Record new architectural decisions
   - Document issues encountered
   - Add next steps

4. Update Plan (if needed):
   - Adjust scope if changed
   - Update timelines if needed
   - Record "Last Updated" timestamp

Output:
- Summary of updates made
- Progress percentage
- Next steps summary

IMPORTANT: Be thorough - this is crucial for seamless continuation after compaction!
```

---

### 8.2 Quality & Review Commands

#### **/code-review**

**Purpose:** Launch architectural code review

**Prompt:**
```
Launch the code-architecture-reviewer agent to review recent changes.

Agent should:
- Read changed files from last session
- Check against relevant skills
- Verify architectural patterns
- Check for anti-patterns
- Generate detailed report

Focus areas:
- SKU-first architecture compliance
- Livewire patterns (trait composition, service injection)
- Frontend rules (no inline styles, no arbitrary Tailwind)
- Service layer patterns
- Error handling

Output: Detailed review report with ✅/⚠️/❌/💡 sections
```

---

#### **/build-and-fix**

**Purpose:** Run builds and fix all errors

**Prompt:**
```
Run build scripts and systematically fix all errors.

Steps:
1. Run composer phpstan (static analysis)
2. If errors found:
   - Launch build-error-resolver agent
   - Agent fixes errors systematically
   - Re-run build to verify
3. Run npm run build (Vite)
4. If errors found:
   - Fix frontend errors
   - Re-run build to verify

Output:
- Build status ✅/❌
- Errors fixed count
- Verification confirmation
```

---

### 8.3 Testing Commands

#### **/route-research-for-testing**

**Purpose:** Find affected routes and launch tests

**Prompt:**
```
Research routes affected by recent changes and test them.

Steps:
1. Analyze changed files
2. Identify affected routes (routes/web.php, routes/api.php)
3. For each route:
   - Check if authenticated
   - Check if API or web
4. Launch appropriate tests:
   - Web routes: frontend-verification skill
   - API routes: test with Postman/curl
   - Authenticated: use auth-route-tester agent

Output:
- Routes identified
- Test results
- Issues found (if any)
```

---

#### **/test-route**

**Purpose:** Test specific authenticated route

**Prompt:**
```
Test a specific authenticated route.

Steps:
1. Ask user for route URL
2. Check route definition (web.php/api.php)
3. Check middleware (auth, admin, role)
4. Use auth-route-tester agent:
   - Get auth token
   - Make authenticated request
   - Verify response
5. Report results

Output:
- Route info (method, middleware)
- Response status
- Response body (if error)
- Success/Failure
```

---

### 8.4 PrestaShop Commands

#### **/prestashop-sync**

**Purpose:** Sync product/category to PrestaShop

**Prompt:**
```
Sync a product or category to PrestaShop shops.

Steps:
1. Ask user for:
   - Type (product/category)
   - SKU (for product) or ID (for category)
   - Target shops (all or specific)
2. Launch prestashop-sync-manager agent:
   - Verify mappings
   - Execute sync
   - Handle conflicts
   - Update sync logs
3. Report results

Output:
- Sync status per shop
- Conflicts (if any)
- Sync log IDs
- PrestaShop URLs
```

---

#### **/prestashop-debug**

**Purpose:** Debug PrestaShop sync failure

**Prompt:**
```
Debug a PrestaShop sync failure.

Steps:
1. Ask user for:
   - Sync job ID or product SKU
2. Launch prestashop-sync-debugger agent:
   - Read sync logs
   - Identify failure type
   - Check API response
   - Verify mappings
   - Suggest fix
3. Apply fix if approved
4. Re-sync to verify

Output:
- Failure diagnosis
- Root cause
- Fix applied
- Re-sync status
```

---

## 9. IMPLEMENTATION ROADMAP

### Phase 1: Core Skills (Week 1-2)

**Priority: CRITICAL**

- [ ] **laravel-dev-guidelines** (SKILL.md + 10 resources)
- [ ] **livewire-dev-guidelines** (SKILL.md + 10 resources)
- [ ] **frontend-dev-guidelines** (SKILL.md + 5 resources)
- [ ] **skill-rules.json** (configuration file)

**Deliverables:**
- 3 skills with full resources
- skill-rules.json with triggers
- Documentation in _DOCS/SKILLS_SYSTEM.md

---

### Phase 2: Domain Skills (Week 3-4)

**Priority: HIGH**

- [ ] **prestashop-sync-manager** (SKILL.md + 6 resources + 1 script)
- [ ] **sku-architecture-validator** (SKILL.md + 3 resources + 1 script)
- [ ] **multi-store-data-manager** (SKILL.md + 4 resources)

**Deliverables:**
- 3 domain skills
- 2 validation scripts
- Updated skill-rules.json

---

### Phase 3: Hooks System (Week 5)

**Priority: CRITICAL**

- [ ] **UserPromptSubmit Hook** (.claude/hooks/user-prompt-submit.ts)
- [ ] **Stop Event Hook** (.claude/hooks/stop-event.ts)
- [ ] **Post-Tool-Use Hook** (.claude/hooks/post-tool-use.ts)
- [ ] **Build Checker Hook** (.claude/hooks/build-checker.ts)
- [ ] **Error Handling Reminder Hook** (.claude/hooks/error-handling-reminder.ts)

**Deliverables:**
- 5 TypeScript hooks
- Hook configuration (.claude/hooks/config.json)
- Documentation in _DOCS/HOOKS_SYSTEM.md

---

### Phase 4: Dev Docs System (Week 6)

**Priority: HIGH**

- [ ] **Directory Structure** (dev/active/, dev/completed/, dev/templates/)
- [ ] **Templates** (plan, context, tasks)
- [ ] **Slash Commands** (/dev-docs, /update-dev-docs)
- [ ] **Workflow Documentation**

**Deliverables:**
- dev/ directory structure
- 3 templates
- 2 slash commands
- Documentation in _DOCS/DEV_DOCS_SYSTEM.md

---

### Phase 5: Specialized Agents (Week 7-8)

**Priority: MEDIUM**

**Quality Control:**
- [ ] code-architecture-reviewer
- [ ] build-error-resolver

**Testing & Debugging:**
- [ ] frontend-error-fixer
- [ ] prestashop-sync-debugger

**Planning & Strategy:**
- [ ] strategic-plan-architect
- [ ] plan-reviewer

**Domain Agents:**
- [ ] prestashop-sync-manager
- [ ] sku-validator
- [ ] variant-generator

**Deliverables:**
- 9 specialized agents
- Agent documentation in _DOCS/AGENTS_SYSTEM.md

---

### Phase 6: Remaining Skills (Week 9)

**Priority: LOW**

- [ ] **variant-system-manager** (SKILL.md + 4 resources)
- [ ] **database-migration-manager** (SKILL.md + 4 resources + 1 script)

**Deliverables:**
- 2 remaining skills
- Updated skill-rules.json

---

### Phase 7: Slash Commands (Week 10)

**Priority: MEDIUM**

**Planning & Docs:**
- [x] /dev-docs (already planned)
- [x] /update-dev-docs (already planned)

**Quality & Review:**
- [ ] /code-review
- [ ] /build-and-fix

**Testing:**
- [ ] /route-research-for-testing
- [ ] /test-route

**PrestaShop:**
- [ ] /prestashop-sync
- [ ] /prestashop-debug

**Deliverables:**
- 6 slash commands
- Documentation in _DOCS/SLASH_COMMANDS.md

---

### Phase 8: Utility Scripts (Week 11)

**Priority: LOW**

- [ ] **test-prestashop-sync.php** (attach to prestashop-sync-manager)
- [ ] **validate-sku-usage.php** (attach to sku-architecture-validator)
- [ ] **migration-safety-check.php** (attach to database-migration-manager)
- [ ] **variant-bulk-generator.php** (standalone utility)

**Deliverables:**
- 4 utility scripts
- Scripts attached to relevant skills
- Documentation in _DOCS/UTILITY_SCRIPTS.md

---

### Phase 9: CLAUDE.md Refactoring (Week 12)

**Priority: CRITICAL**

- [ ] **Refactor CLAUDE.md** (consolidate, reference skills)
- [ ] **Update C:\Users\kamil\.claude\CLAUDE.md** (global)
- [ ] **Create PROJECT_KNOWLEDGE.md** (architecture overview)
- [ ] **Create TROUBLESHOOTING.md** (common issues)

**Deliverables:**
- Refactored CLAUDE.md (< 200 lines)
- PROJECT_KNOWLEDGE.md
- TROUBLESHOOTING.md
- Updated global CLAUDE.md

---

### Phase 10: Testing & Refinement (Week 13-14)

**Priority: HIGH**

- [ ] Test all skills with real scenarios
- [ ] Test hooks with various prompts
- [ ] Test agents with different tasks
- [ ] Refine triggers in skill-rules.json
- [ ] Update documentation based on feedback

**Deliverables:**
- Test results document
- Refined skill-rules.json
- Updated documentation

---

## 10. SUCCESS METRICS

### Quantitative Metrics

1. **Skill Activation Rate**
   - Target: 90% of relevant prompts trigger correct skills
   - Measurement: Hook logs analysis

2. **Error Detection Rate**
   - Target: 100% of build errors caught by hooks
   - Measurement: Build checker logs

3. **Anti-Pattern Detection Rate**
   - Target: 95% of anti-patterns caught (inline styles, hard-coded IDs)
   - Measurement: Stop event hook logs

4. **Dev Docs Usage**
   - Target: 100% of large tasks use dev docs
   - Measurement: dev/active/ directory tracking

5. **Agent Success Rate**
   - Target: 80% of agent tasks completed without human intervention
   - Measurement: Agent reports analysis

---

### Qualitative Metrics

1. **Code Consistency**
   - Measure: Code review findings (fewer pattern violations)

2. **Context Retention**
   - Measure: Fewer instances of "Claude lost the plot"

3. **Developer Experience**
   - Measure: Subjective assessment (faster implementation, less fixing)

4. **Documentation Quality**
   - Measure: Dev docs completeness, clarity

---

## 11. APPENDIX

### A. skill-rules.json Full Example

[See section 4 above]

---

### B. Hook Implementation Examples

[TypeScript code will be provided in separate files during implementation]

---

### C. Template Files

[See section 6.3 above]

---

### D. Related Documentation

- `_DOCS/CLAUDE.md` - Main project instructions (to be refactored)
- `_DOCS/SKU_ARCHITECTURE_GUIDE.md` - SKU-first patterns
- `_DOCS/FRONTEND_VERIFICATION_GUIDE.md` - UI verification
- `_DOCS/CSS_STYLING_GUIDE.md` - CSS rules
- `_DOCS/LIVEWIRE_TROUBLESHOOTING.md` - 9 known Livewire issues
- `_DOCS/AGENT_USAGE_GUIDE.md` - Agent patterns
- `_DOCS/SKILLS_USAGE_GUIDE.md` - Skills usage (to be created)

---

**Dokument stworzony:** 2025-11-04
**Autor:** Claude Code (Sonnet 4.5) + Kamil Wiliński
**Status:** Design Document - Ready for Implementation
**Szacowany czas implementacji:** 14 tygodni (Phase 1-10)
**Priorytet:** CRITICAL - Foundation for improved Claude Code experience

---

## END OF DOCUMENT
