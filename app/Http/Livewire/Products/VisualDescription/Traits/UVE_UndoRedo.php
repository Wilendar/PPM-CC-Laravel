<?php

namespace App\Http\Livewire\Products\VisualDescription\Traits;

use Livewire\Attributes\Computed;

/**
 * UVE Undo/Redo Trait - ETAP_07f_P5
 *
 * System historii zmian z undo/redo.
 * Maksymalnie 50 stanow w historii.
 */
trait UVE_UndoRedo
{
    /** @var array History stack (past states) */
    protected array $undoStack = [];

    /** @var array Redo stack (future states) */
    protected array $redoStack = [];

    /** @var int Maximum history size */
    protected int $maxHistorySize = 50;

    // =====================
    // INITIALIZATION
    // =====================

    /**
     * Initialize history with current state
     */
    protected function initHistory(): void
    {
        $this->undoStack = [];
        $this->redoStack = [];

        // Push initial state
        $this->undoStack[] = $this->captureState();
    }

    // =====================
    // STATE MANAGEMENT
    // =====================

    /**
     * Capture current state for history
     */
    protected function captureState(): array
    {
        return [
            'blocks' => $this->blocks,
            'selectedBlockIndex' => $this->selectedBlockIndex,
            'editingBlockIndex' => $this->editingBlockIndex,
            'selectedElementId' => $this->selectedElementId,
            'timestamp' => now()->timestamp,
        ];
    }

    /**
     * Restore state from history
     */
    protected function restoreState(array $state): void
    {
        $this->blocks = $state['blocks'] ?? [];
        $this->selectedBlockIndex = $state['selectedBlockIndex'] ?? null;
        $this->editingBlockIndex = $state['editingBlockIndex'] ?? null;
        $this->selectedElementId = $state['selectedElementId'] ?? null;
    }

    /**
     * Push current state to undo stack
     */
    protected function pushHistory(): void
    {
        // Capture current state before change
        $currentState = $this->captureState();

        // Clear redo stack (new branch in history)
        $this->redoStack = [];

        // Add to undo stack
        $this->undoStack[] = $currentState;

        // Limit history size
        if (count($this->undoStack) > $this->maxHistorySize) {
            array_shift($this->undoStack);
        }
    }

    // =====================
    // UNDO / REDO
    // =====================

    /**
     * Undo last action
     */
    public function undo(): void
    {
        if (!$this->canUndo) {
            return;
        }

        // Save current state to redo
        $this->redoStack[] = $this->captureState();

        // Pop from undo and restore
        array_pop($this->undoStack); // Remove current state
        $previousState = end($this->undoStack);

        if ($previousState) {
            $this->restoreState($previousState);
            $this->isDirty = true;
            $this->dispatch('state-restored', action: 'undo');
        }
    }

    /**
     * Redo last undone action
     */
    public function redo(): void
    {
        if (!$this->canRedo) {
            return;
        }

        // Pop from redo
        $nextState = array_pop($this->redoStack);

        if ($nextState) {
            // Push current to undo
            $this->undoStack[] = $this->captureState();

            $this->restoreState($nextState);
            $this->isDirty = true;
            $this->dispatch('state-restored', action: 'redo');
        }
    }

    /**
     * Clear all history
     */
    public function clearHistory(): void
    {
        $this->undoStack = [$this->captureState()];
        $this->redoStack = [];
    }

    // =====================
    // COMPUTED PROPERTIES
    // =====================

    /**
     * Check if undo is available
     */
    #[Computed]
    public function canUndo(): bool
    {
        return count($this->undoStack) > 1;
    }

    /**
     * Check if redo is available
     */
    #[Computed]
    public function canRedo(): bool
    {
        return count($this->redoStack) > 0;
    }

    /**
     * Get undo count
     */
    #[Computed]
    public function undoCount(): int
    {
        return max(0, count($this->undoStack) - 1);
    }

    /**
     * Get redo count
     */
    #[Computed]
    public function redoCount(): int
    {
        return count($this->redoStack);
    }
}
