<?php

namespace App\Http\Livewire\Admin\Parameters;

use App\Services\Compatibility\AiScoringConfig;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

/**
 * AiScoringConfigPanel - Sub-tab "Konfiguracja AI" w SmartMatchingPanel.
 *
 * Umozliwia konfiguracje wag i progow algorytmu AI scoring
 * uzywajac AiScoringConfig service jako backend.
 *
 * Kazde pole ma: suwak (range) + input numeryczny + domyslna wartosc + opis.
 */
class AiScoringConfigPanel extends Component
{
    /** @var array<string, float|int> Aktualne wartosci konfiguracji */
    public array $values = [];

    /** @var array<string, float|int> Domyslne wartosci do porownania */
    public array $defaults = [];

    /** @var array<string, array> Definicje grup i pol do renderowania UI */
    public array $fieldDefinitions = [];

    /** @var bool Flaga: czy sa niezapisane zmiany */
    public bool $hasChanges = false;

    /** @var array Dynamiczna lista typow pojazdow */
    public array $vehicleTypes = [];

    public function mount(): void
    {
        $this->authorize('parameters.read');
        $this->loadConfig();
    }

    /**
     * Zaladuj konfiguracje z serwisu.
     */
    protected function loadConfig(): void
    {
        $config = app(AiScoringConfig::class);
        $this->values = $config->all();
        $this->defaults = $config->getDefaults();
        $this->fieldDefinitions = $config->getFieldDefinitions();
        $this->vehicleTypes = $config->getVehicleTypes();
    }

    /**
     * Reaguj na zmiane dowolnej wartosci w tablicy values.
     * Sprawdza czy cos sie zmienilo wzgledem domyslnych.
     */
    public function updatedValues(): void
    {
        $this->detectChanges();
    }

    /**
     * Zapisz konfiguracje do bazy przez AiScoringConfig service.
     */
    public function saveConfig(): void
    {
        $config = app(AiScoringConfig::class);

        foreach ($this->values as $configKey => $value) {
            $default = $this->defaults[$configKey] ?? 0;
            $typedValue = is_float($default) ? (float) $value : (int) $value;
            $config->set($configKey, $typedValue);
        }

        // Save vehicle types
        $config->setVehicleTypes($this->vehicleTypes);

        $this->hasChanges = false;
        Log::info('AI Scoring config saved', [
            'changed_keys' => $this->getChangedKeys(),
            'vehicle_types_count' => count($this->vehicleTypes),
        ]);

        session()->flash('ai-config-success', 'Konfiguracja AI zapisana.');
    }

    /**
     * Przywroc domyslne wartosci (usun nadpisania z bazy).
     */
    public function resetToDefaults(): void
    {
        $config = app(AiScoringConfig::class);
        $config->resetToDefaults();
        $config->resetVehicleTypesToDefaults();

        $this->values = $config->getDefaults();
        $this->vehicleTypes = $config->getDefaultVehicleTypes();
        $this->hasChanges = false;

        Log::info('AI Scoring config reset to defaults');

        session()->flash('ai-config-success', 'Przywrocono domyslne wartosci.');
    }

    /**
     * Dodaj nowy typ pojazdu.
     */
    public function addVehicleType(string $key, string $label, string $prefix, string $keywordsRaw): void
    {
        $key = trim($key);
        $label = trim($label);
        $prefix = mb_strtolower(trim($prefix));
        $keywordsRaw = trim($keywordsRaw);

        if (empty($key) || empty($label) || empty($prefix) || empty($keywordsRaw)) {
            session()->flash('ai-config-error', 'Wypelnij wszystkie pola nowego typu.');
            return;
        }

        foreach ($this->vehicleTypes as $type) {
            if ($type['key'] === $key) {
                session()->flash('ai-config-error', "Typ o kluczu '{$key}' juz istnieje.");
                return;
            }
        }

        $keywords = array_map('trim', explode(',', $keywordsRaw));
        $keywords = array_values(array_filter($keywords, fn($k) => !empty($k)));
        $keywords = array_map('mb_strtolower', $keywords);

        $this->vehicleTypes[] = [
            'key' => $key,
            'label' => $label,
            'prefix' => $prefix,
            'keywords' => $keywords,
        ];

        $this->hasChanges = true;
        session()->flash('ai-config-success', "Typ '{$label}' dodany. Kliknij 'Zapisz konfiguracje' aby zachowac zmiany.");
    }

    /**
     * Usun typ pojazdu po kluczu.
     */
    public function removeVehicleType(string $typeKey): void
    {
        $this->vehicleTypes = array_values(array_filter(
            $this->vehicleTypes,
            fn($t) => $t['key'] !== $typeKey
        ));
        $this->hasChanges = true;
        session()->flash('ai-config-success', "Typ usuniety. Kliknij 'Zapisz konfiguracje' aby zachowac zmiany.");
    }

    /**
     * Dodaj slowo kluczowe do istniejacego typu.
     */
    public function addKeywordToType(string $typeKey, string $keyword): void
    {
        $keyword = mb_strtolower(trim($keyword));
        if (empty($keyword)) {
            return;
        }

        foreach ($this->vehicleTypes as &$type) {
            if ($type['key'] === $typeKey) {
                if (!in_array($keyword, $type['keywords'])) {
                    $type['keywords'][] = $keyword;
                    $this->hasChanges = true;
                }
                break;
            }
        }
        unset($type);
    }

    /**
     * Usun slowo kluczowe z typu.
     */
    public function removeKeywordFromType(string $typeKey, string $keyword): void
    {
        foreach ($this->vehicleTypes as &$type) {
            if ($type['key'] === $typeKey) {
                $type['keywords'] = array_values(array_filter(
                    $type['keywords'],
                    fn($k) => $k !== $keyword
                ));
                $this->hasChanges = true;
                break;
            }
        }
        unset($type);
    }

    /**
     * Przywroc domyslna wartosc pojedynczego pola.
     */
    public function resetField(string $fieldKey): void
    {
        if (!isset($this->defaults[$fieldKey])) {
            return;
        }

        $this->values[$fieldKey] = $this->defaults[$fieldKey];
        $this->detectChanges();
    }

    /**
     * Sprawdz czy aktualne wartosci roznia sie od domyslnych lub zapisanych.
     */
    protected function detectChanges(): void
    {
        $this->hasChanges = false;

        $config = app(AiScoringConfig::class);
        $saved = $config->all();

        // Check scoring weight changes
        foreach ($this->values as $configKey => $value) {
            $savedValue = $saved[$configKey] ?? null;

            $default = $this->defaults[$configKey] ?? 0;
            if (is_float($default)) {
                if (abs((float) $value - (float) $savedValue) > 0.001) {
                    $this->hasChanges = true;
                    return;
                }
            } else {
                if ((int) $value !== (int) $savedValue) {
                    $this->hasChanges = true;
                    return;
                }
            }
        }

        // Check vehicle type changes
        $savedTypes = $config->getVehicleTypes();
        if (json_encode($this->vehicleTypes) !== json_encode($savedTypes)) {
            $this->hasChanges = true;
        }
    }

    /**
     * Zwraca klucze ktore roznia sie od zapisanych wartosci.
     *
     * @return array<string>
     */
    protected function getChangedKeys(): array
    {
        $changed = [];
        $config = app(AiScoringConfig::class);
        $saved = $config->all();

        foreach ($this->values as $configKey => $value) {
            $savedValue = $saved[$configKey] ?? null;
            $default = $this->defaults[$configKey] ?? 0;

            if (is_float($default)) {
                if (abs((float) $value - (float) $savedValue) > 0.001) {
                    $changed[] = $configKey;
                }
            } else {
                if ((int) $value !== (int) $savedValue) {
                    $changed[] = $configKey;
                }
            }
        }

        return $changed;
    }

    public function render()
    {
        return view('livewire.admin.parameters.ai-scoring-config-panel');
    }
}
