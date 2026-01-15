<?php

declare(strict_types=1);

namespace App\Http\Livewire\Products\VisualDescription\Traits;

/**
 * Trait EditorUndoRedo.
 *
 * Handles undo/redo functionality with state stack.
 */
trait EditorUndoRedo
{
    /**
     * Undo stack - previous states.
     */
    public array $undoStack = [];

    /**
     * Redo stack - undone states.
     */
    public array $redoStack = [];

    /**
     * Maximum undo/redo history size.
     */
    protected int $maxHistorySize = 50;

    /**
     * Push current state to undo stack.
     */
    protected function pushUndoState(): void
    {
        $state = [
            'blocks' => $this->blocks,
            'selectedBlockIndex' => $this->selectedBlockIndex,
            'timestamp' => now()->timestamp,
        ];

        $this->undoStack[] = $state;

        // Limit history size
        if (count($this->undoStack) > $this->maxHistorySize) {
            array_shift($this->undoStack);
        }

        // Clear redo stack on new action
        $this->redoStack = [];
    }

    /**
     * Undo last action.
     */
    public function undo(): void
    {
        if (empty($this->undoStack)) {
            return;
        }

        // Push current state to redo
        $this->redoStack[] = [
            'blocks' => $this->blocks,
            'selectedBlockIndex' => $this->selectedBlockIndex,
            'timestamp' => now()->timestamp,
        ];

        // Pop and restore previous state
        $previousState = array_pop($this->undoStack);
        $this->blocks = $previousState['blocks'];
        $this->selectedBlockIndex = $previousState['selectedBlockIndex'];
        $this->isDirty = true;

        $this->dispatch('state-restored', action: 'undo');
    }

    /**
     * Redo last undone action.
     */
    public function redo(): void
    {
        if (empty($this->redoStack)) {
            return;
        }

        // Push current state to undo
        $this->undoStack[] = [
            'blocks' => $this->blocks,
            'selectedBlockIndex' => $this->selectedBlockIndex,
            'timestamp' => now()->timestamp,
        ];

        // Pop and restore redo state
        $redoState = array_pop($this->redoStack);
        $this->blocks = $redoState['blocks'];
        $this->selectedBlockIndex = $redoState['selectedBlockIndex'];
        $this->isDirty = true;

        $this->dispatch('state-restored', action: 'redo');
    }

    /**
     * Check if undo is available.
     */
    public function getCanUndoProperty(): bool
    {
        return !empty($this->undoStack);
    }

    /**
     * Check if redo is available.
     */
    public function getCanRedoProperty(): bool
    {
        return !empty($this->redoStack);
    }

    /**
     * Get undo stack size.
     */
    public function getUndoCountProperty(): int
    {
        return count($this->undoStack);
    }

    /**
     * Get redo stack size.
     */
    public function getRedoCountProperty(): int
    {
        return count($this->redoStack);
    }

    /**
     * Clear undo/redo history.
     */
    public function clearHistory(): void
    {
        $this->undoStack = [];
        $this->redoStack = [];
    }
}
