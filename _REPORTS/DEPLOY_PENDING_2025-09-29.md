# ⚠️ DEPLOY PENDING: ETAP_04_CategoryForm_Layout_Fix
**Data**: 2025-09-29 00:00
**Powod**: Nieudany Quick Push (`_TOOLS/hostido_quick_push.ps1`) – skrypt zakonczyl sie kodem 1. Prawdopodobne problemy z polaczeniem/kluczem SSH lub ograniczenia srodowiska.

## Zmiany oczekujace na wdrozenie
- resources/views/livewire/products/categories/category-form.blade.php – korekta struktury kolumn (md:w-3/4 + md:w-1/4 jako rodzenstwo), usuniecie inline style, porzadki w CSS.

## Zalecane czynnosci po stronie prod
1) `_TOOLS/hostido_quick_push.ps1 -Files @('resources/views/livewire/products/categories/category-form.blade.php') -PostCommand "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear"`
2) Health-check `/up`, smoke-test `/admin` i formularza kategorii (uklad kolumn na md+).

