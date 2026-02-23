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

    public function mount(): void
    {
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
            // Rzutuj na odpowiedni typ (float/int) na podstawie defaultu
            $default = $this->defaults[$configKey] ?? 0;
            $typedValue = is_float($default) ? (float) $value : (int) $value;

            $config->set($configKey, $typedValue);
        }

        $this->hasChanges = false;
        Log::info('AI Scoring config saved', [
            'changed_keys' => $this->getChangedKeys(),
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

        $this->values = $config->getDefaults();
        $this->hasChanges = false;

        Log::info('AI Scoring config reset to defaults');

        session()->flash('ai-config-success', 'Przywrocono domyslne wartosci.');
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

        foreach ($this->values as $configKey => $value) {
            $savedValue = $saved[$configKey] ?? null;

            // Porownuj jako float/int
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
