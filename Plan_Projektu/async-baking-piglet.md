# PLAN: Naprawa Sessions Management UI - Integracja z PPM Layout

**Data**: 2026-01-21
**Status**: Do zatwierdzenia
**Priorytet**: KRYTYCZNY - UI niezgodne z założeniami PPM

---

## PROBLEM

Strona `/admin/sessions` NIE ma standardowego layoutu PPM:
- Brak left sidepanel z nawigacją
- Własny jasny header zamiast dark theme
- Niezgodność wizualna z resztą panelu admin

### Przyczyna:
1. `Sessions.php` używa `layouts.app` zamiast `layouts.admin`
2. `sessions.blade.php` definiuje własny kompletny layout z headerem

---

## PLIKI DO MODYFIKACJI

| Plik | Zmiana |
|------|--------|
| `app/Http/Livewire/Admin/Sessions.php` | Zmiana layoutu na `layouts.admin` |
| `resources/views/livewire/admin/sessions.blade.php` | Przeprojektowanie struktury na dark theme |

### Pliki referencyjne (wzorce):
- `resources/views/livewire/admin/erp/erp-manager.blade.php` - wzorzec struktury
- `resources/views/layouts/admin.blade.php` - layout docelowy

---

## PLAN IMPLEMENTACJI

### KROK 1: Sessions.php - Zmiana layoutu

**Plik:** `app/Http/Livewire/Admin/Sessions.php`
**Linie:** 684-693

**PRZED:**
```php
public function render()
{
    return view('livewire.admin.sessions', [
        'sessions' => $this->sessions,
        'users' => $this->users,
        'deviceTypes' => $this->deviceTypes,
    ])->layout('layouts.app', [
        'title' => 'Zarządzanie Sesjami - Admin PPM'
    ]);
}
```

**PO:**
```php
public function render()
{
    return view('livewire.admin.sessions', [
        'sessions' => $this->sessions,
        'users' => $this->users,
        'deviceTypes' => $this->deviceTypes,
    ])->layout('layouts.admin', [
        'title' => 'Monitor Sesji - Admin PPM',
        'breadcrumb' => 'Monitor Sesji'
    ]);
}
```

---

### KROK 2: sessions.blade.php - Przeprojektowanie struktury

**Plik:** `resources/views/livewire/admin/sessions.blade.php`

#### 2.1 Usunąć:
- Linia 2: `<div class="min-h-screen bg-gray-100 dark:bg-gray-900">` (własny wrapper)
- Linie 4-34: Własny Page Header z białym tłem
- `max-w-7xl mx-auto` constraints (layout admin je zapewnia)

#### 2.2 Nowa struktura (wzorowana na erp-manager.blade.php):

```blade
{{-- ETAP_04 FAZA A: Session Monitor View --}}
<div class="bg-gray-900 min-h-screen">
    {{-- Compact Header with View Mode Tabs --}}
    <div class="border-b border-gray-800 bg-gray-900/95 backdrop-blur-sm sticky top-0 z-40">
        <div class="flex items-center justify-between px-4 py-3">
            <div>
                <h1 class="text-lg font-semibold text-white">Monitor Sesji</h1>
                <p class="text-xs text-gray-400 mt-0.5">Zarządzanie aktywnymi sesjami użytkowników</p>
            </div>
            {{-- View Mode Tabs (orange instead of blue) --}}
            <div class="inline-flex rounded-md shadow-sm">
                ... (tabs z bg-orange-500 dla active state)
            </div>
        </div>

        {{-- Compact Stats Bar --}}
        <div class="flex items-center justify-between px-4 py-2 bg-gray-800/50">
            ... (stats inline zamiast 4 dużych cards)
        </div>
    </div>

    {{-- Main Content --}}
    <div class="p-4">
        ... (filtry, tabela, analytics, security - dark theme classes)
    </div>
</div>
```

#### 2.3 Zmiany klas CSS (jasny → ciemny theme):

| Element | PRZED (jasny) | PO (ciemny) |
|---------|---------------|-------------|
| Wrapper | `bg-gray-100 dark:bg-gray-900` | `bg-gray-900` |
| Cards | `bg-white dark:bg-gray-800` | `bg-gray-800/50` |
| Borders | `border-gray-300 dark:border-gray-600` | `border-gray-700` |
| Text primary | `text-gray-900 dark:text-white` | `text-white` |
| Text secondary | `text-gray-500 dark:text-gray-400` | `text-gray-400` |
| Active tab | `bg-blue-600` | `bg-orange-500` |
| Inputs | Light bg | `bg-gray-700 border-gray-600` |

---

## ZACHOWANA FUNKCJONALNOŚĆ

Wszystkie features pozostają bez zmian:
- 3 widoki (Active, Analytics, Security)
- Filtrowanie i wyszukiwanie
- Bulk actions (force logout, mark suspicious)
- Session details modal
- Pagination
- Flash messages

---

## WERYFIKACJA

### Po implementacji:
1. Deploy do produkcji (pscp + cache clear)
2. Nawiguj do https://ppm.mpptrade.pl/admin/sessions
3. Zweryfikuj Chrome DevTools:
   - [ ] Left sidepanel widoczny (nawigacja admin)
   - [ ] Dark theme (bg-gray-900)
   - [ ] Sticky header z tabs (orange active state)
   - [ ] Compact stats bar
   - [ ] Filtry działają
   - [ ] Tabele w dark theme
   - [ ] Modals poprawnie wyświetlane
   - [ ] Brak błędów w Console

### Screenshot verification:
```javascript
mcp__claude-in-chrome__tabs_context_mcp({ createIfEmpty: true })
mcp__claude-in-chrome__navigate({ tabId: TAB_ID, url: "https://ppm.mpptrade.pl/admin/sessions" })
mcp__claude-in-chrome__computer({ tabId: TAB_ID, action: "screenshot" })
```

---

## OCZEKIWANY REZULTAT

Po implementacji strona `/admin/sessions` będzie:
1. Mieć standardowy PPM admin layout z left sidepanel
2. Używać dark theme zgodnego z resztą panelu
3. Mieć compact header ze sticky behavior
4. Używać orange (brand color) zamiast blue dla active states
5. Być w pełni spójna wizualnie z innymi stronami admin (ERP Manager, Backup, etc.)

---

## ESTYMACJA

- Modyfikacja Sessions.php: ~5 min
- Przeprojektowanie sessions.blade.php: ~30 min
- Deploy i weryfikacja: ~10 min
- **TOTAL: ~45 min**
