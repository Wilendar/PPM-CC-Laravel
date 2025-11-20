<?php

/**
 * DIAGNOZA: Bug z zapisem częstotliwości synchronizacji
 *
 * PROBLEM: Zmiana częstotliwości z "Co godzinę" na inną opcję NIE ZAPISUJE SIĘ
 *
 * ROOT CAUSE: wire:model.defer + wire:click = race condition
 *
 * ANALIZA:
 *
 * 1. BLADE (sync-controller.blade.php:343):
 *    <select wire:model.defer="autoSyncFrequency">
 *
 * 2. PRZYCISK (sync-controller.blade.php:603):
 *    <button wire:click="saveSyncConfiguration">
 *
 * 3. LIVEWIRE LIFECYCLE:
 *    - wire:model.defer: Synchronizacja DOPIERO przy submit/blur/następnym request
 *    - wire:click: Wywołuje metodę NATYCHMIAST (przed synchronizacją defer!)
 *
 * 4. KOLEJNOŚĆ ZDARZEŃ (BUG):
 *    a) User zmienia select: "hourly" → "daily"
 *    b) Livewire CZEKA z synchronizacją (defer)
 *    c) User klika "Zapisz"
 *    d) wire:click wywołuje saveSyncConfiguration() NATYCHMIAST
 *    e) Metoda czyta $this->autoSyncFrequency → WCIĄŻ "hourly"!
 *    f) Zapisuje "hourly" do bazy (overwrites zmianę)
 *    g) DOPIERO PO saveSyncConfiguration() Livewire zsynchronizuje defer
 *
 * FIX OPTIONS:
 *
 * OPCJA 1 (ZALECANA): Zmień wire:model.defer → wire:model.live
 *    - Synchronizacja NATYCHMIAST przy zmianie
 *    - Brak race condition
 *    - Prostsze
 *
 * OPCJA 2: Użyj <form wire:submit.prevent="saveSyncConfiguration">
 *    - defer synchronizuje przed submit
 *    - Wymaga przebudowy struktury (button type="submit")
 *
 * OPCJA 3: Dodaj $this->validateOnly() w update hook
 *    - Wymaga Livewire 3.x updated hook
 *    - Skomplikowane
 *
 * WERYFIKACJA:
 *
 * SELECT `key`, `value`, `type` FROM `system_settings` WHERE `key` = 'sync.schedule.frequency';
 *
 * Przed FIX: Zawsze wraca do "hourly"
 * Po FIX: Zapisuje wybraną wartość ("daily", "weekly")
 *
 * TESTY:
 * 1. Otwórz /admin/shops/sync
 * 2. Kliknij "Pokaż konfigurację"
 * 3. Zmień częstotliwość: "Co godzinę" → "Codziennie"
 * 4. Kliknij "Zapisz konfigurację"
 * 5. Odśwież stronę (F5)
 * 6. EXPECTED: "Codziennie" wciąż zaznaczone
 * 7. ACTUAL (przed FIX): Wraca do "Co godzinę"
 */

echo "DIAGNOZA ZAKOŃCZONA\n";
echo "ROOT CAUSE: wire:model.defer + wire:click race condition\n";
echo "FIX: Zmienić wire:model.defer na wire:model.live\n";
