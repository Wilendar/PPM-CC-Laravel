<?php

namespace App\Http\Livewire\Admin\Scan\Traits;

use App\Models\ProductScanSession;
use App\Services\Scan\ChunkedScanEngine;
use App\Services\Scan\ProductScanService;
use Illuminate\Support\Facades\Log;

/**
 * ChunkedScanTrait
 *
 * Obsluguje chunked scanning produktow w trybie AJAX.
 * Skanowanie jest podzielone na chunki (~500 produktow) wywolywane sekwencyjnie przez Alpine.
 * Wspiera: start, processNextChunk, finalize, cancel, resume.
 *
 * @package App\Http\Livewire\Admin\Scan\Traits
 */
trait ChunkedScanTrait
{
    /** ID aktywnej sesji skanowania */
    public ?int $activeScanSessionId = null;

    /**
     * Faza skanowania.
     * Mozliwe wartosci: idle | prefetching | scanning | finalizing
     */
    public string $scanPhase = 'idle';

    /** Postep skanowania 0-100 */
    public int $scanProgress = 0;

    /** Liczba przetworzonych chunkow */
    public int $processedChunks = 0;

    /** Laczna liczba chunkow */
    public int $totalChunks = 0;

    /** Szacowany czas pozostaly (np. "2 min 30 sek") */
    public ?string $estimatedTimeRemaining = null;

    /** @var array Biezace statystyki skanowania */
    public array $scanStats = [];

    /** @var float Znacznik czasu startu chunka - do szacowania czasu */
    protected float $chunkStartTime = 0.0;

    /**
     * Uruchamia chunked scan.
     * Etapy: prefetch zrodel -> zapisz do cache -> skanuj chunk po chunku.
     *
     * @return void
     */
    public function startChunkedScan(): void
    {
        /** @var ProductScanService $scanService */
        $scanService = app(ProductScanService::class);

        $session = $scanService->createScanSession(
            ProductScanSession::SCAN_LINKS,
            'cross_source',
            null,
            auth()->id()
        );

        $this->activeScanSessionId = $session->id;
        $this->scanPhase           = 'prefetching';
        $this->scanProgress        = 0;
        $this->processedChunks     = 0;
        $this->scanStats           = [];

        $sourceConfigs = array_map(fn (array $source): array => [
            'type' => $source['type'],
            'id'   => $source['id'],
        ], $this->sources);

        try {
            /** @var ChunkedScanEngine $engine */
            $engine = app(ChunkedScanEngine::class);
            $prefetchResult = $engine->prefetchSourceData($session, $sourceConfigs);

            $this->totalChunks = $prefetchResult['total_chunks'];
            $this->scanPhase   = 'scanning';
            $this->scanStats   = [
                'total_products'  => $prefetchResult['total_products'],
                'sources_cached'  => $prefetchResult['sources_cached'],
                'conflicts_found' => 0,
                'processed'       => 0,
            ];

            Log::info('ChunkedScanTrait: scan started', [
                'session_id'   => $session->id,
                'total_chunks' => $this->totalChunks,
            ]);
        } catch (\Exception $e) {
            Log::error('ChunkedScanTrait: prefetch failed', ['error' => $e->getMessage()]);
            $this->scanPhase           = 'idle';
            $this->activeScanSessionId = null;
            session()->flash('error', 'Blad podczas prefetchowania danych: ' . $e->getMessage());
        }
    }

    /**
     * Przetwarza nastepny chunk produktow.
     * Wywolywan przez Alpine po zakonczeniu poprzedniego chunka.
     * Jezeli wszystkie chunki przetworzone - wywoluje finalizeScanSession().
     *
     * @return void
     */
    public function processNextChunk(): void
    {
        if ($this->activeScanSessionId === null) {
            return;
        }

        if ($this->processedChunks >= $this->totalChunks) {
            $this->finalizeScanSession();
            return;
        }

        $session = ProductScanSession::find($this->activeScanSessionId);

        if (!$session || $session->status === ProductScanSession::STATUS_CANCELLED) {
            $this->resetScanState();
            return;
        }

        $this->chunkStartTime = microtime(true);

        try {
            /** @var ChunkedScanEngine $engine */
            $engine = app(ChunkedScanEngine::class);
            $result = $engine->processChunk($session, $this->processedChunks);

            $this->processedChunks++;

            // Aktualizuj statystyki
            $this->scanStats['conflicts_found'] = ($this->scanStats['conflicts_found'] ?? 0)
                + ($result['conflicts_found'] ?? 0);
            $this->scanStats['processed'] = ($this->scanStats['processed'] ?? 0)
                + ($result['processed'] ?? 0);

            // Oblicz postep
            $this->scanProgress = $this->totalChunks > 0
                ? (int) round(($this->processedChunks / $this->totalChunks) * 100)
                : 100;

            // Szacuj pozostaly czas na podstawie sredniej dlugosci chunka
            $chunkTime = microtime(true) - $this->chunkStartTime;
            $remaining = $this->totalChunks - $this->processedChunks;
            $this->estimatedTimeRemaining = $this->formatTimeRemaining((int) ($remaining * $chunkTime));

        } catch (\Exception $e) {
            Log::error('ChunkedScanTrait: chunk processing failed', [
                'chunk_index' => $this->processedChunks,
                'error'       => $e->getMessage(),
            ]);
            // Kontynuuj mimo bledu chunka
            $this->processedChunks++;
        }
    }

    /**
     * Finalizuje sesje skanowania po przetworzeniu wszystkich chunkow.
     *
     * @return void
     */
    public function finalizeScanSession(): void
    {
        if ($this->activeScanSessionId === null) {
            return;
        }

        $session = ProductScanSession::find($this->activeScanSessionId);

        if (!$session) {
            $this->resetScanState();
            return;
        }

        $this->scanPhase = 'finalizing';

        try {
            /** @var ChunkedScanEngine $engine */
            $engine = app(ChunkedScanEngine::class);
            $engine->finalizeScan($session);

            $conflictsFound = $this->scanStats['conflicts_found'] ?? 0;
            session()->flash('success', 'Skan zakonczony. Wykryto konfliktow: ' . $conflictsFound . '.');
        } catch (\Exception $e) {
            Log::error('ChunkedScanTrait: finalize failed', ['error' => $e->getMessage()]);
            session()->flash('error', 'Blad podczas finalizacji skanu.');
        }

        $this->resetScanState();
        $this->refreshMatrix();
    }

    /**
     * Anuluje aktywne skanowanie.
     *
     * @return void
     */
    public function cancelScan(): void
    {
        if ($this->activeScanSessionId === null) {
            return;
        }

        $session = ProductScanSession::find($this->activeScanSessionId);

        if ($session) {
            /** @var ChunkedScanEngine $engine */
            $engine = app(ChunkedScanEngine::class);
            $engine->cancelScan($session);
        }

        $this->resetScanState();
        session()->flash('info', 'Skan zostal anulowany.');
    }

    /**
     * Wznawia przerwan sesje skanowania.
     *
     * @return void
     */
    public function resumeScan(): void
    {
        if ($this->activeScanSessionId === null) {
            return;
        }

        $session = ProductScanSession::find($this->activeScanSessionId);

        if (!$session) {
            session()->flash('error', 'Sesja skanowania nie zostala znaleziona.');
            return;
        }

        try {
            /** @var ChunkedScanEngine $engine */
            $engine = app(ChunkedScanEngine::class);
            $resumeData = $engine->resumeScan($session);

            if ($resumeData !== null) {
                $this->processedChunks       = $resumeData['processed_chunks'] ?? $this->processedChunks;
                $this->totalChunks           = $resumeData['total_chunks'] ?? $this->totalChunks;
                $this->scanPhase             = 'scanning';
                $this->estimatedTimeRemaining = null;

                Log::info('ChunkedScanTrait: scan resumed', [
                    'session_id'       => $session->id,
                    'processedChunks'  => $this->processedChunks,
                ]);
            } else {
                session()->flash('error', 'Nie mozna wznowic skanu - dane sesji niedostepne.');
            }
        } catch (\Exception $e) {
            Log::error('ChunkedScanTrait: resume failed', ['error' => $e->getMessage()]);
            session()->flash('error', 'Blad podczas wznawiania skanu.');
        }
    }

    /**
     * Wykrywa aktywny chunked scan przy ladowaniu komponentu.
     * Zapewnia persistence stanu po odswiezeniu strony.
     *
     * @return void
     */
    public function detectActiveChunkedScan(): void
    {
        $session = ProductScanSession::where('status', ProductScanSession::STATUS_RUNNING)
            ->where('scan_mode', 'chunked')
            ->latest()
            ->first();

        if (!$session) {
            return;
        }

        $this->activeScanSessionId = $session->id;
        $this->processedChunks     = $session->processed_chunks ?? 0;
        $this->totalChunks         = $session->total_chunks ?? 0;
        $this->scanPhase           = 'scanning';
        $this->scanProgress        = $this->totalChunks > 0
            ? (int) round(($this->processedChunks / $this->totalChunks) * 100)
            : 0;

        Log::info('ChunkedScanTrait: detected active scan on mount', [
            'session_id'      => $session->id,
            'processedChunks' => $this->processedChunks,
            'totalChunks'     => $this->totalChunks,
        ]);
    }

    /**
     * Sprawdza aktualny status skanowania - uzywane przez wire:poll.
     *
     * @return void
     */
    public function checkScanStatus(): void
    {
        if ($this->activeScanSessionId === null) {
            return;
        }

        $session = ProductScanSession::find($this->activeScanSessionId);

        if (!$session) {
            $this->resetScanState();
            return;
        }

        if ($session->status === ProductScanSession::STATUS_COMPLETED) {
            $this->resetScanState();
            $this->refreshMatrix();
        } elseif ($session->status === ProductScanSession::STATUS_FAILED) {
            $this->resetScanState();
            session()->flash('error', 'Skan zakonczyl sie bledem: ' . ($session->error_message ?? 'Nieznany blad.'));
        } elseif ($session->status === ProductScanSession::STATUS_CANCELLED) {
            $this->resetScanState();
        }
    }

    /**
     * Zwraca dane postepu skanowania dla Alpine.js.
     *
     * @return array{phase: string, progress: int, processedChunks: int, totalChunks: int, estimatedTime: string|null, stats: array}
     */
    public function getScanProgressData(): array
    {
        return [
            'phase'           => $this->scanPhase,
            'progress'        => $this->scanProgress,
            'processedChunks' => $this->processedChunks,
            'totalChunks'     => $this->totalChunks,
            'estimatedTime'   => $this->estimatedTimeRemaining,
            'stats'           => $this->scanStats,
        ];
    }

    /**
     * Resetuje wszystkie properties stanu skanowania do wartosci poczatkowych.
     *
     * @return void
     */
    protected function resetScanState(): void
    {
        $this->activeScanSessionId    = null;
        $this->scanPhase              = 'idle';
        $this->scanProgress           = 0;
        $this->processedChunks        = 0;
        $this->totalChunks            = 0;
        $this->estimatedTimeRemaining = null;
        $this->scanStats              = [];
    }

    /**
     * Formatuje sekundy do czytelnego ciagu czasu.
     *
     * @param  int $seconds Liczba sekund
     * @return string Np. "2 min 30 sek" lub "45 sek"
     */
    protected function formatTimeRemaining(int $seconds): string
    {
        if ($seconds <= 0) {
            return 'konczy...';
        }

        if ($seconds < 60) {
            return $seconds . ' sek';
        }

        $minutes = (int) floor($seconds / 60);
        $secs    = $seconds % 60;

        return $secs > 0
            ? $minutes . ' min ' . $secs . ' sek'
            : $minutes . ' min';
    }
}
