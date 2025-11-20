# RAPORT HOTFIX: Button Placement Correction

**Data**: 2025-11-17
**Agent**: refactoring-specialist
**Zadanie**: Korekcja umiejscowienia przycisków per-shop w ProductForm
**Priorytet**: CRITICAL (user-reported bug)

## Problem

User zgłosił błędną implementację ETAP_13 - przyciski per-shop ("Aktualizuj aktualny sklep", "Wczytaj z aktualnego sklepu") zostały umieszczone w złym miejscu:

- **BŁĘDNIE**: Footer Shop Tab (linie 1714-1746)
- **POPRAWNIE**: Panel Synchronizacji - rozwijany element "Szczegóły synchronizacji" (linie 502-544)

## Wykonane Prace

### 1. Footer Buttons - Przywrócenie oryginału (linie 1688-1762)

**USUNIĘTE:**
```blade
{{-- Aktualizuj aktualny sklep --}}
<button wire:click="syncShop({{ $activeShopId }})">...</button>

{{-- Wczytaj z aktualnego sklepu --}}
<button wire:click="pullShopData({{ $activeShopId }})">...</button>
```

**PRZYWRÓCONE:**
```blade
{{-- Sync to Shops (context-aware) --}}
<button wire:click="syncToShops" class="btn-enterprise-warning">
    @if($activeShopId === null)
        Aktualizuj na wszystkich sklepach
    @else
        Zaktualizuj na sklepie
    @endif
</button>

{{-- Save All Changes --}}
<button wire:click="saveAllPendingChanges" class="btn-enterprise-primary">
    Zapisz wszystkie zmiany
</button>
```

### 2. Panel Synchronizacji - Wymiana przycisków (linie 502-530)

**ZMIENIONE:**
- "Aktualizuj sklep" → "**Aktualizuj aktualny sklep**"
- "Pobierz dane" → "**Wczytaj z aktualnego sklepu**"

**USUNIĘTE:**
- Przycisk "Zobacz w PS" (lines 531-543) - zgodnie z user request

## Deployment

### Pliki zmodyfikowane:
- `resources/views/livewire/products/management/product-form.blade.php` (144 kB)

### Deployment steps:
1. ✅ Upload hotfixed Blade file via pscp
2. ✅ Clear cache: `php artisan view:clear && cache:clear`

### Verification:
- Cache cleared successfully
- File uploaded (144 kB)
- Ready for manual testing

## Następne Kroki

**MANUAL TESTING** (user):
1. Otworzyć ProductForm z aktywnym sklepem
2. Sprawdzić footer buttons - powinny być:
   - Anuluj
   - Przywróć domyślne (jeśli hasUnsavedChanges)
   - Aktualizuj na wszystkich sklepach / Zaktualizuj na sklepie (context-aware)
   - Zapisz wszystkie zmiany
   - Zapisz i Zamknij
3. Rozwinąć "Szczegóły synchronizacji" w zakładce Shop
4. Zweryfikować przyciski per-shop:
   - "Aktualizuj aktualny sklep" (btn-compact-primary)
   - "Wczytaj z aktualnego sklepu" (btn-compact-secondary)
   - Brak przycisku "Zobacz w PS"

## Pliki

- `resources/views/livewire/products/management/product-form.blade.php` - Footer restoration + Panel buttons swap
- `_AGENT_REPORTS/HOTFIX_button_placement_correction_2025-11-17_REPORT.md` - Ten raport

---

**Status**: ✅ DEPLOYED - Awaiting user acceptance testing
