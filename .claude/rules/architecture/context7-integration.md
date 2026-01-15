# Architecture: Context7 Integration (MANDATORY)

## Critical Rule
**BEFORE** implementing any Laravel/Livewire/PrestaShop feature, ALWAYS verify patterns with Context7!

## Library IDs
| Technology | Context7 Library ID | Snippets |
|------------|---------------------|----------|
| Laravel 12.x | `/websites/laravel_12_x` | 4927 |
| Livewire 3.x | `/livewire/livewire` | 867 |
| Alpine.js | `/alpinejs/alpine` | 364 |
| PrestaShop | `/prestashop/docs` | 3289 |

## Usage Pattern
```
1. BEFORE implementing: mcp__context7__get-library-docs
2. Verify current patterns from official sources
3. Implement according to documentation
4. Reference docs in code comments
```

## Example
```javascript
// Before implementing Livewire component
mcp__context7__get-library-docs({
  context7CompatibleLibraryID: "/livewire/livewire",
  topic: "component lifecycle"
})
```

## Do NOT Rely On
- Memory from training data (may be outdated)
- Assumptions about API (verify documentation)
- Old examples (check current version)

## Always Reference
In responses, reference official documentation:
```markdown
According to Livewire 3.x documentation (Context7: /livewire/livewire):
- Use `$this->dispatch()` instead of `$this->emit()`
- Properties must be public or have getters/setters
- Wire:key is MANDATORY in loops
```

## Common Mistakes Prevented
- `$this->emit()` instead of `$this->dispatch()` (Livewire 3.x)
- Old Laravel syntax
- Deprecated patterns
