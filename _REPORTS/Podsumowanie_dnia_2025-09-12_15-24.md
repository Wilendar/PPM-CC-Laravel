# Podsumowanie dnia — 2025-09-12 15:24

## Status ogólny
- Prod działa: / (200), /up (200), /admin (200)
- Wdrożone poprawki UI (dropdowny) oraz bezpieczne usprawnienia deployu
- W AGENTS.md dopisana polityka „minimalnej ingerencji” i „When to use”

## Co zrobiono (chronologicznie i rzeczowo)
- Deploy/infra:
  - Naprawiono „DryRun” w `_TOOLS/hostido_deploy.ps1` – nie wykonuje uploadu/komend.
  - Dodano wykluczenia uploadu: `vendor/*`, `storage/*`, `bootstrap/cache/*` (runtime i vendor nietykane).
  - Dodano `-NoDelete` do przyrostowego uploadu (brak kasowania po stronie serwera).
  - Zmieniono kolejność: custom `-Command` (np. `composer install`) przed komendami post-deploy; po `-Command` ponowny post-deploy (cache).
  - Dodano narzędzie `_TOOLS/hostido_quick_push.ps1` do wysyłki tylko wskazanych plików + opcjonalny `-PostCommand`.
- UI/Admin (/admin):
  - Zdiagnozowano przyczynę dropdownów chowających się pod panele: stacking context (backdrop-blur/transform) + brak odpowiedniej warstwy rodzica.
  - Naprawiono błędy markupu (brakujące `>` na buttonach w headerze layoutu admina).
  - Podniesiono warstwę `<nav>`: `overflow: visible; z-index: 10000` (dla spójności).
  - Zaimplementowano teleport dropdownu profilu do `<body>` (Alpine `x-teleport`) z pozycjonowaniem `fixed` wg. `getBoundingClientRect()` i aktualizacją na scroll/resize.
  - Podniesiono warstwę nagłówka wewnątrz `resources/views/livewire/dashboard/admin-dashboard.blade.php` (z-index: 10000), by wygrać z warstwą kart.
  - Weryfikacja: dropdowny renderują się nad panelami (potwierdzone, problem rozwiązany).
- Incydent i naprawa:
  - W trakcie jednego z wcześniejszych uploadów z `-delete` usunięto runtime/vendor – przywrócono: `composer install`, odtworzenie katalogów `storage/**` i `bootstrap/cache`, odświeżenie cache. Dodane wykluczenia i `-NoDelete` eliminują ryzyko powtórki.

## Zmodyfikowane/utworzone pliki (kluczowe)
- _TOOLS/hostido_deploy.ps1 — `-NoDelete`, wykluczenia runtime/vendor, poprawa DryRun, reorder post-deploy
- _TOOLS/hostido_quick_push.ps1 — nowy skrypt Quick Push
- resources/views/layouts/admin.blade.php — fix markup buttonów, teleport dropdownu profilu, `z-index`/overflow dla nav
- resources/views/livewire/dashboard/admin-dashboard.blade.php — `z-index` dla nagłówka
- AGENTS.md — „When to use” + twarda zasada: brak pełnego deploy/build bez konieczności

## Rekomendacje na kolejną zmianę (od czego zacząć)
- UI/Dropdowns:
  - Jeśli wystąpi podobny problem z dzwonkiem (powiadomienia) – zastosować identyczny teleport do `<body>` (wzorować się na profilu).
- Testy/Automation:
  - Dodać prosty smoke script w `_TOOLS` (np. `tools_smoke.ps1`) sprawdzający: `/up` 200, `/admin` 200 oraz selektory dropdownu (widoczność po kliknięciu).
  - Rozważyć E2E (Playwright) dla krytycznych interakcji (dropdown, quick-actions).
- Deploy workflow:
  - Drobne zmiany: preferuj `_TOOLS/hostido_quick_push.ps1` + `php artisan view:clear`.
  - Większe, bez zależności: `_TOOLS/hostido_deploy.ps1 -UploadOnly -NoDelete`.
  - Zmiany composer/migracje: pełny deploy z `-Command` i backupem.
- Projektowe (kontynuacja):
  - Baselinker (FAZA w toku) — uzgodnić najbliższe API endpoints i zakres danych.
  - Przejrzeć inne widoki z backdrop/transform — prewencyjnie podnieść z-index nagłówków lub wprowadzić teleport dla overlayów.

## Szybkie komendy (przydatne)
- Quick Push widoku + odświeżenie cache:
  `_TOOLS/hostido_quick_push.ps1 -Files @('resources/views/layouts/admin.blade.php') -PostCommand "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear"`
- UploadOnly bez kasowania:
  `_TOOLS/hostido_deploy.ps1 -UploadOnly -NoDelete -Verbose`
- Pełny deploy z composerem i cache:
  `_TOOLS/hostido_deploy.ps1 -Verbose -Command "cd domains/ppm.mpptrade.pl/public_html && composer install --no-dev && php artisan migrate --force && php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache"`

## Uwagi końcowe
- System działa stabilnie po poprawkach; dropdowny sprawdzane na /admin.
- Nowe zasady w AGENTS.md obowiązują: minimalna ingerencja, brak pełnego deploy bez potrzeby.
