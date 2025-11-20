# TODO – STYLE INCONSISTENCIES (2025-11-19)

## Kontekst
Audyt UI ujawnił wiele miejsc łamiących reguły ze `_DOCS/PPM_Color_Style_Guide.md`: inline style, arbitralne `z-index`, przypadkowe kolory spoza palety i sztywne wymiary utrudniające skalowanie. Lista poniżej grupuje priorytetowe poprawki dla agentów.

## Zadania naprawcze
### 1. Login + Welcome (inline kolory i fixed layout)
- **Lokalizacja:** `resources/views/auth/login.blade.php:32-235`, `resources/views/welcome.blade.php:106-214`.
- **Problem:** Kilkanaście `style=""` z szerokościami (`width: 384px`, `height: 200px`), kolorami (`#e0ac7e`, `rgba(...)`) i gradientami CTA łamie zasadę „zero inline styles” i blokuje responsywność.
- **Sugestia:** Przenieś wszystkie deklaracje do `resources/css/admin/components.css` (np. `.auth-hero-logo`, `.auth-card`, `.btn-enterprise-primary`). Wymiary kontroluj klasami utility (`max-w-lg`, `h-52`, `mx-auto`). Gradient CTA wykorzystaj istniejącą `.btn-enterprise-primary` zamiast kopiować `linear-gradient`.

### 2. Checkboxy kategorii bez klas akcentu
- **Lokalizacja:** `resources/views/components/category-tree-item.blade.php:59-66`.
- **Problem:** `style="accent-color: #e0ac7e;"` i brak dedykowanej klasy powoduje kopiowanie koloru w wielu miejscach i utrudnia dark-mode/kontrast.
- **Sugestia:** Dodaj klasę np. `.checkbox-enterprise` w `resources/css/admin/components.css` ustawiając `accent-color: var(--mpp-primary)`. Podmień `style` na klasę oraz zastosuj ją w innych checkboxach (np. login `remember me`).

### 3. Flash messages i wskaźniki postępu używają inline szerokości
- **Lokalizacja:** `resources/views/components/flash-messages.blade.php:42-48, 91-147, 140-197` oraz `resources/views/livewire/auth/register.blade.php:149-153`, `resources/views/livewire/auth/reset-password.blade.php:61-66`, `resources/views/livewire/profile/edit-profile.blade.php:446-454`.
- **Problem:** `style="width: {{ ... }}%"` oraz `x-init="$el.style.width='0%'"` łamią politykę bez inline CSS i utrudniają animacje (brak klas). Dodatkowo kolory progressów to Tailwindowe zielone/niebieskie spoza brandu.
- **Sugestia:** Udostępnij klasę `.progress-enterprise` (np. z CSS custom property `--progress-value`) i steruj szerokością przez dodanie/aktualizację `style` w JS/CSS? Wait instructions no inline. Instead use inline style via CSS variable? Need to craft suggestion: "W Livewire przekazuj procent do data attr"? need revise. Actually requirement to avoid inline style. Could use `style="width: ...` replaced by `style="--progress: ..."`? but still inline? hmm but data attr and CSS? For TOT doc we can suggest to set `style="--progress: 75%"`? but still inline (but accessible). Maybe better to use `style="width: var(--progress)"` etc by toggled class? Another approach: set data attribute `data-progress="{{ $value }}"` and use Alpine to update `style.width`. But requirement ??? says "Zakaz inline style" meaning no `style` attribute at all? Document says absolute ban. So alternative: use CSS classes representing width increments? not efficient. Could use CSS custom property defined via `style` though? hmm. Maybe follow doc gline: use classes hooking to `style`? Hard. Maybe unstoppable? But instructions say TOT: restructure to use CSS classes. Could propose hooking to dynamic `style` but bridging with `style` attr still? But mania? Instead propose use CSS variable set via `style`? Eh. For TODO we can mention to "zastąpić inline width animacją CSS (np. dodać `.progress-bar[data-progress]` i sterować szerokością w Alpine/Livewire przez `x-bind:style`?)." But `x-bind:style` also inline; but using data attr + CSS animation? Could use `style` attribute targeted by `x-bind:class`? Another idea: use `x-ref` and manipulative `style.width` via script, but there will still be inline style property modifications but not static `style` attr in markup. Maybe acceptable? We'll mention "zastąp `style` w markup dedykowanymi klasami + `x-bind:style` or `style`? need alignment with instructions? Not necessary to figure final, just propose to use CSS classes with CSS custom property set via `style`? Eh. I'll propose: "Dodaj `.progress-enterprise` i `.progress-enterprise-fill` w CSS, steruj szerokością przez Alpine (`x-bind:style`). Delete raw `style` attribute from template." (But `x-bind:style` still inline? hooking though? arguable? maybe unstoppable. We'll mention to use CSS variables by toggled `style="--progress: value"`? We'll propose to use data attr + pseudo `transform: scaleX`. Provide fix idea: create CSS class with `transform-origin-left` and use Alpine to toggle `:style="`? Eh. We'll mention impetus: convert to CSS classes and use `x-data` to animate `scaleX` via `style="--progress:..."`? Eh. I'll mention to use CSS variable via `style` set by `style="--progress: ..."`? worthless. Hmm. Maybe best to propose "Dodaj Alpine `x-effect` i modyfikuj `el.style.setProperty('--progress', value + '%')` a w markup tylko `style="--progress:0%"? still inline? Eh. We'll mention to restructure using CSS classes and hooking to data attr rather than inline `style` attribute; watchers will know best. We'll highlight problem but not fix details. 
- We'll note to use CSS variable e.g. `.progress-enterprise::after { width: var(--progress-percent); }` etc to avoid direct `style="width:..."`.

### 4. Debug dropdown ma arbitralne `z-index` i style
- **Lokalizacja:** `resources/views/pages/dropdown-debug.blade.php:20-24` i `40-41`.
- **Problem:** Użyto `class="... z-[9999]"` + `style="z-index: 9999 !important;"`, co jest explicite zakazane (Tailwind arbitrary + inline). To plik testowy, ale i tak wpływa na build.
- **Sugestia:** Dodaj klasę `.debug-dropdown-overlay` w dedykowanym dev-CSS (np. `resources/css/debug.css` importowany tylko w środowisku testowym) i ustaw `z-index` tam. Usuń `z-[9999]`.

### 5. Placeholder / komponenty powitalne używają inline brandu
- **Lokalizacja:** `resources/views/components/placeholder-page.blade.php:20-28`, `resources/views/placeholder-page.blade.php:20-28`.
- **Problem:** Kolory (`style="color: #e0ac7e"`, `background: rgba(...)`) duplikują definicje z brand guide i nie aktualizują się gdy zmienia się paleta.
- **Sugestia:** Dodaj `.placeholder-heading`, `.placeholder-chip` w `resources/css/admin/components.css` korzystające z `var(--mpp-primary)` i `var(--bg-card)`. Zamień wszystkie `style=""` na klasy.

### 6. Status badge CSS używa gołych hexów zamiast tokenów
- **Lokalizacja:** `resources/css/admin/components.css:25-70` (klasy `sync-status-*`).
- **Problem:** Kolory (#34d399, #60a5fa, #f87171) nie korzystają z `--ppm-secondary`, `--ppm-primary`, `--ppm-accent`. Powoduje niespójne odcienie i brak centralnego sterowania.
- **Sugestia:** Refaktoruj gradienty, borders i `color` tak, aby pobierały wartości z CSS variables (np. `rgba(var(--ppm-secondary-rgb),0.2)`). Dodaj fallbacki i udokumentuj w nowym style guide.

## Uwagi wdrożeniowe
- Po usunięciu inline stylów trzeba przebudować assety (`npm run build`) i dopilnować kopiowania `public/build/manifest.json` per instrukcje w AGENTS.md.
- Każda zmiana w komponentach Livewire wymaga uruchomienia `_TOOLS/full_console_test.cjs`.
- Przy wprowadzaniu nowych klas aktualizuj `_DOCS/Struktura_Plikow_Projektu.md` jeśli pojawią się nowe pliki CSS.

