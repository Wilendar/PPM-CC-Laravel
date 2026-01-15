<?php

namespace App\Http\Livewire\Products\VisualDescription\Traits;

use Illuminate\Support\Str;

/**
 * UVE Element Editing Trait - ETAP_07f_P5 FAZA 3
 *
 * Operacje na elementach wewnatrz odmrozonwego bloku:
 * - Dodawanie, usuwanie, duplikowanie elementow
 * - Edycja wlasciwosci, stylow, klas CSS
 * - Przenoszenie elementow w hierarchii
 */
trait UVE_ElementEditing
{
    // =====================
    // ELEMENT CRUD
    // =====================

    /**
     * Add new element to block
     */
    public function addElement(string $type, ?string $parentId = null, ?int $position = null): void
    {
        if ($this->editingBlockIndex === null) {
            return;
        }

        $this->pushHistory();

        $element = $this->createElementFromType($type);

        if ($parentId === null) {
            // Add to root children
            $this->addElementToRoot($element, $position);
        } else {
            // Add to specific parent
            $this->addElementToParent($element, $parentId, $position);
        }

        $this->selectedElementId = $element['id'];
        $this->isDirty = true;

        $this->dispatch('element-added', elementId: $element['id'], type: $type);
        $this->dispatch('notify', type: 'success', message: 'Element dodany');
    }

    /**
     * Remove element from block
     */
    public function removeElement(string $elementId): void
    {
        if ($this->editingBlockIndex === null) {
            return;
        }

        // Cannot remove root element
        $root = $this->blocks[$this->editingBlockIndex]['document']['root'] ?? null;
        if ($root && ($root['id'] ?? '') === $elementId) {
            $this->dispatch('notify', type: 'warning', message: 'Nie mozna usunac elementu glownego');
            return;
        }

        $this->pushHistory();

        $this->removeElementFromTree($elementId);

        if ($this->selectedElementId === $elementId) {
            $this->selectedElementId = null;
        }

        $this->isDirty = true;
        $this->dispatch('element-removed', elementId: $elementId);
        $this->dispatch('notify', type: 'info', message: 'Element usuniety');
    }

    /**
     * Duplicate element
     */
    public function duplicateElement(string $elementId): void
    {
        if ($this->editingBlockIndex === null) {
            return;
        }

        $element = $this->findElementById(
            $this->blocks[$this->editingBlockIndex]['document']['root'],
            $elementId
        );

        if (!$element) {
            return;
        }

        $this->pushHistory();

        // Clone with new IDs
        $duplicate = $this->cloneElement($element);

        // Find parent and insert after original
        $this->insertElementAfter($elementId, $duplicate);

        $this->selectedElementId = $duplicate['id'];
        $this->isDirty = true;

        $this->dispatch('element-duplicated', originalId: $elementId, newId: $duplicate['id']);
        $this->dispatch('notify', type: 'success', message: 'Element zduplikowany');
    }

    /**
     * Move element to new parent/position
     */
    public function moveElement(string $elementId, string $newParentId, int $position): void
    {
        if ($this->editingBlockIndex === null) {
            return;
        }

        // Cannot move root
        $root = $this->blocks[$this->editingBlockIndex]['document']['root'] ?? null;
        if ($root && ($root['id'] ?? '') === $elementId) {
            return;
        }

        // Cannot move into itself
        if ($elementId === $newParentId) {
            return;
        }

        $element = $this->findElementById($root, $elementId);
        if (!$element) {
            return;
        }

        $this->pushHistory();

        // Remove from current location
        $this->removeElementFromTree($elementId);

        // Add to new location
        $this->addElementToParent($element, $newParentId, $position);

        $this->isDirty = true;
        $this->dispatch('element-moved', elementId: $elementId, newParentId: $newParentId);
    }

    /**
     * Move element relative to target (before/after)
     */
    public function moveElementRelative(string $elementId, string $targetId, string $position): void
    {
        if ($this->editingBlockIndex === null) {
            return;
        }

        // Cannot move root
        $root = $this->blocks[$this->editingBlockIndex]['document']['root'] ?? null;
        if ($root && ($root['id'] ?? '') === $elementId) {
            $this->dispatch('notify', type: 'warning', message: 'Nie mozna przesunac elementu glownego');
            return;
        }

        // Cannot move to itself
        if ($elementId === $targetId) {
            return;
        }

        // Check if trying to move into descendant (would create loop)
        if ($this->isDescendantOf($root, $targetId, $elementId)) {
            $this->dispatch('notify', type: 'warning', message: 'Nie mozna przesunac elementu do swojego potomka');
            return;
        }

        $element = $this->findElementById($root, $elementId);
        if (!$element) {
            return;
        }

        // Find target's parent
        $targetParent = $this->findParentOfElement($root, $targetId);
        if (!$targetParent) {
            return;
        }

        $this->pushHistory();

        // Remove element from current location
        $this->removeElementFromTree($elementId);

        // Find target's index in parent
        $targetIndex = $this->findChildIndex($targetParent, $targetId);
        if ($targetIndex === -1) {
            return;
        }

        // Calculate new position
        $newPosition = $position === 'before' ? $targetIndex : $targetIndex + 1;

        // Insert at new position
        $parentId = $targetParent['id'] ?? null;
        if ($parentId) {
            $this->addElementToParent($element, $parentId, $newPosition);
        } else {
            // Target is direct child of root
            $this->addElementToRoot($element, $newPosition);
        }

        $this->isDirty = true;
        $this->dispatch('element-moved', elementId: $elementId, position: $position);
        $this->dispatch('notify', type: 'success', message: 'Element przesuniety');
    }

    /**
     * Check if elementId is a descendant of targetId
     */
    protected function isDescendantOf(array $element, string $targetId, string $elementId): bool
    {
        if (($element['id'] ?? '') === $elementId) {
            return false; // Found the element we're moving
        }

        if (($element['id'] ?? '') === $targetId) {
            // We're at target, check if elementId is in its descendants
            return $this->containsElement($element, $elementId);
        }

        if (!empty($element['children'])) {
            foreach ($element['children'] as $child) {
                if ($this->isDescendantOf($child, $targetId, $elementId)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if element tree contains specific elementId
     */
    protected function containsElement(array $element, string $elementId): bool
    {
        if (!empty($element['children'])) {
            foreach ($element['children'] as $child) {
                if (($child['id'] ?? '') === $elementId) {
                    return true;
                }
                if ($this->containsElement($child, $elementId)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Find parent element of given child
     */
    protected function findParentOfElement(array $element, string $childId): ?array
    {
        if (!empty($element['children'])) {
            foreach ($element['children'] as $child) {
                if (($child['id'] ?? '') === $childId) {
                    return $element;
                }
                $found = $this->findParentOfElement($child, $childId);
                if ($found) {
                    return $found;
                }
            }
        }
        return null;
    }

    /**
     * Find index of child in parent's children array
     */
    protected function findChildIndex(array $parent, string $childId): int
    {
        if (empty($parent['children'])) {
            return -1;
        }

        foreach ($parent['children'] as $index => $child) {
            if (($child['id'] ?? '') === $childId) {
                return $index;
            }
        }

        return -1;
    }

    // =====================
    // PROPERTY EDITING
    // =====================

    /**
     * Update element property
     */
    public function updateElementProperty(string $elementId, string $property, mixed $value): void
    {
        if ($this->editingBlockIndex === null) {
            return;
        }

        $updated = $this->updateElementInTree($elementId, function ($element) use ($property, $value) {
            $element[$property] = $value;
            return $element;
        });

        if ($updated) {
            $this->isDirty = true;

            // Recompile block HTML and notify iframe
            $this->compileBlockHtml($this->editingBlockIndex);
            $this->dispatch('uve-preview-refresh');
        }
    }

    /**
     * Update element content (text)
     */
    public function updateElementContent(string $elementId, string $content): void
    {
        $this->updateElementProperty($elementId, 'content', $content);
    }

    /**
     * Update element styles
     */
    public function updateElementStyles(string $elementId, array $styles): void
    {
        if ($this->editingBlockIndex === null) {
            return;
        }

        $updated = $this->updateElementInTree($elementId, function ($element) use ($styles) {
            $element['styles'] = array_merge($element['styles'] ?? [], $styles);
            // Remove empty values
            $element['styles'] = array_filter($element['styles'], fn($v) => $v !== '' && $v !== null);
            return $element;
        });

        if ($updated) {
            $this->isDirty = true;

            // Recompile block HTML and notify iframe
            $this->compileBlockHtml($this->editingBlockIndex);
            $this->dispatch('uve-preview-refresh');
        }
    }

    /**
     * Update single style property
     */
    public function updateElementStyle(string $elementId, string $property, ?string $value): void
    {
        $this->updateElementStyles($elementId, [$property => $value]);
    }

    /**
     * Update element CSS classes
     */
    public function updateElementClasses(string $elementId, array $classes): void
    {
        if ($this->editingBlockIndex === null) {
            return;
        }

        $updated = $this->updateElementInTree($elementId, function ($element) use ($classes) {
            $element['classes'] = array_values(array_unique($classes));
            return $element;
        });

        if ($updated) {
            $this->isDirty = true;
        }
    }

    /**
     * Toggle CSS class on element
     */
    public function toggleElementClass(string $elementId, string $class): void
    {
        if ($this->editingBlockIndex === null) {
            return;
        }

        $this->updateElementInTree($elementId, function ($element) use ($class) {
            $classes = $element['classes'] ?? [];

            if (in_array($class, $classes)) {
                $classes = array_values(array_diff($classes, [$class]));
            } else {
                $classes[] = $class;
            }

            $element['classes'] = $classes;
            return $element;
        });

        $this->isDirty = true;
    }

    // =====================
    // IFRAME SYNCHRONIZATION (FAZA 4.5.4)
    // =====================

    /**
     * Update element content from iframe inline editing
     * Called via postMessage when user edits text in contenteditable
     */
    public function updateElementContentFromIframe(string $elementId, string $content): void
    {
        if ($this->editingBlockIndex === null) {
            return;
        }

        $updated = $this->updateElementInTree($elementId, function ($element) use ($content) {
            $element['content'] = $content;
            return $element;
        });

        if ($updated) {
            $this->isDirty = true;
            // Note: No iframe refresh here - content came FROM iframe
            // Property panel will update via Livewire re-render
        }
    }

    /**
     * Explicitly refresh iframe content after property panel changes
     * Recompiles block HTML and triggers iframe update
     */
    public function refreshIframeContent(): void
    {
        if ($this->editingBlockIndex === null) {
            return;
        }

        $this->compileBlockHtml($this->editingBlockIndex);
        $this->dispatch('uve-preview-refresh');
    }

    /**
     * Update element visibility
     */
    public function toggleElementVisibility(string $elementId): void
    {
        $this->updateElementInTree($elementId, function ($element) {
            $element['visible'] = !($element['visible'] ?? true);
            return $element;
        });

        $this->isDirty = true;
    }

    /**
     * Lock/unlock element
     */
    public function toggleElementLock(string $elementId): void
    {
        $this->updateElementInTree($elementId, function ($element) {
            $element['locked'] = !($element['locked'] ?? false);
            return $element;
        });

        $this->isDirty = true;
    }

    // =====================
    // ELEMENT FACTORY
    // =====================

    /**
     * Create new element from type
     */
    protected function createElementFromType(string $type): array
    {
        $id = 'el_' . Str::random(8);

        $base = [
            'id' => $id,
            'type' => $type,
            'visible' => true,
            'locked' => false,
            'classes' => [],
            'styles' => [],
            'children' => [],
        ];

        return match ($type) {
            'heading' => array_merge($base, [
                'tag' => 'h2',
                'content' => 'Naglowek',
                'styles' => ['marginBottom' => '1rem'],
            ]),
            'text', 'paragraph' => array_merge($base, [
                'tag' => 'p',
                'content' => 'Tekst paragrafu...',
                'styles' => ['marginBottom' => '1rem'],
            ]),
            'image' => array_merge($base, [
                'tag' => 'img',
                'src' => '',
                'alt' => '',
                'styles' => ['maxWidth' => '100%', 'height' => 'auto'],
            ]),
            'button' => array_merge($base, [
                'tag' => 'a',
                'content' => 'Przycisk',
                'href' => '#',
                'classes' => ['btn', 'btn-primary'],
            ]),
            'container', 'div' => array_merge($base, [
                'tag' => 'div',
                'content' => '',
                'styles' => ['padding' => '1rem'],
            ]),
            'row' => array_merge($base, [
                'tag' => 'div',
                'content' => '',
                'classes' => ['row'],
                'styles' => ['display' => 'flex', 'gap' => '1rem'],
            ]),
            'column' => array_merge($base, [
                'tag' => 'div',
                'content' => '',
                'classes' => ['col'],
                'styles' => ['flex' => '1'],
            ]),
            'list' => array_merge($base, [
                'tag' => 'ul',
                'content' => '',
                'styles' => ['paddingLeft' => '1.5rem'],
            ]),
            'list-item' => array_merge($base, [
                'tag' => 'li',
                'content' => 'Element listy',
            ]),
            'icon' => array_merge($base, [
                'tag' => 'span',
                'icon' => 'check',
                'classes' => ['icon'],
            ]),
            'spacer' => array_merge($base, [
                'tag' => 'div',
                'content' => '',
                'styles' => ['height' => '2rem'],
            ]),
            'divider' => array_merge($base, [
                'tag' => 'hr',
                'styles' => ['border' => 'none', 'borderTop' => '1px solid #e5e7eb', 'margin' => '1rem 0'],
            ]),
            default => $base,
        };
    }

    /**
     * Clone element with new IDs
     */
    protected function cloneElement(array $element): array
    {
        $clone = $element;
        $clone['id'] = 'el_' . Str::random(8);

        if (!empty($clone['children'])) {
            $clone['children'] = array_map(
                fn($child) => $this->cloneElement($child),
                $clone['children']
            );
        }

        return $clone;
    }

    // =====================
    // TREE OPERATIONS
    // =====================

    /**
     * Add element to root's children
     */
    protected function addElementToRoot(array $element, ?int $position = null): void
    {
        if (!isset($this->blocks[$this->editingBlockIndex]['document']['root']['children'])) {
            $this->blocks[$this->editingBlockIndex]['document']['root']['children'] = [];
        }

        $children = &$this->blocks[$this->editingBlockIndex]['document']['root']['children'];

        if ($position === null || $position >= count($children)) {
            $children[] = $element;
        } else {
            array_splice($children, $position, 0, [$element]);
        }
    }

    /**
     * Add element to specific parent
     */
    protected function addElementToParent(array $element, string $parentId, ?int $position = null): void
    {
        $this->updateElementInTree($parentId, function ($parent) use ($element, $position) {
            if (!isset($parent['children'])) {
                $parent['children'] = [];
            }

            if ($position === null || $position >= count($parent['children'])) {
                $parent['children'][] = $element;
            } else {
                array_splice($parent['children'], $position, 0, [$element]);
            }

            return $parent;
        });
    }

    /**
     * Remove element from tree
     */
    protected function removeElementFromTree(string $elementId): bool
    {
        if (!isset($this->blocks[$this->editingBlockIndex]['document']['root'])) {
            return false;
        }

        $this->blocks[$this->editingBlockIndex]['document']['root'] = $this->removeElementRecursive(
            $this->blocks[$this->editingBlockIndex]['document']['root'],
            $elementId
        );

        return true;
    }

    /**
     * Recursively remove element from tree
     */
    protected function removeElementRecursive(array $element, string $targetId): array
    {
        if (!empty($element['children'])) {
            $element['children'] = array_values(array_filter(
                $element['children'],
                fn($child) => ($child['id'] ?? '') !== $targetId
            ));

            $element['children'] = array_map(
                fn($child) => $this->removeElementRecursive($child, $targetId),
                $element['children']
            );
        }

        return $element;
    }

    /**
     * Insert element after target element
     */
    protected function insertElementAfter(string $targetId, array $element): void
    {
        if (!isset($this->blocks[$this->editingBlockIndex]['document']['root'])) {
            return;
        }

        $this->blocks[$this->editingBlockIndex]['document']['root'] = $this->insertAfterRecursive(
            $this->blocks[$this->editingBlockIndex]['document']['root'],
            $targetId,
            $element
        );
    }

    /**
     * Recursively find target and insert element after it
     */
    protected function insertAfterRecursive(array $parent, string $targetId, array $element): array
    {
        if (!empty($parent['children'])) {
            $newChildren = [];
            foreach ($parent['children'] as $child) {
                $newChildren[] = $this->insertAfterRecursive($child, $targetId, $element);
                if (($child['id'] ?? '') === $targetId) {
                    $newChildren[] = $element;
                }
            }
            $parent['children'] = $newChildren;
        }

        return $parent;
    }

    /**
     * Update element in tree by ID
     */
    protected function updateElementInTree(string $elementId, callable $callback): bool
    {
        if (!isset($this->blocks[$this->editingBlockIndex]['document']['root'])) {
            return false;
        }

        $result = $this->updateElementRecursive(
            $this->blocks[$this->editingBlockIndex]['document']['root'],
            $elementId,
            $callback
        );

        if ($result['found']) {
            $this->blocks[$this->editingBlockIndex]['document']['root'] = $result['element'];
            return true;
        }

        return false;
    }

    /**
     * Recursively find and update element
     */
    protected function updateElementRecursive(array $element, string $targetId, callable $callback): array
    {
        \Log::debug('[UVE] updateElementRecursive checking', [
            'element_id' => $element['id'] ?? 'NO_ID',
            'targetId' => $targetId,
            'match' => ($element['id'] ?? '') === $targetId,
        ]);

        if (($element['id'] ?? '') === $targetId) {
            return ['found' => true, 'element' => $callback($element)];
        }

        if (!empty($element['children'])) {
            foreach ($element['children'] as $i => $child) {
                $result = $this->updateElementRecursive($child, $targetId, $callback);
                if ($result['found']) {
                    $element['children'][$i] = $result['element'];
                    return ['found' => true, 'element' => $element];
                }
            }
        }

        return ['found' => false, 'element' => $element];
    }

    // =====================
    // ELEMENT PALETTE DATA
    // =====================

    /**
     * Get available element types for palette
     */
    public function getElementPaletteProperty(): array
    {
        return [
            [
                'category' => 'Tekst',
                'elements' => [
                    ['type' => 'heading', 'label' => 'Naglowek', 'icon' => 'heading'],
                    ['type' => 'text', 'label' => 'Paragraf', 'icon' => 'align-left'],
                    ['type' => 'list', 'label' => 'Lista', 'icon' => 'list'],
                ],
            ],
            [
                'category' => 'Media',
                'elements' => [
                    ['type' => 'image', 'label' => 'Obraz', 'icon' => 'image'],
                    ['type' => 'icon', 'label' => 'Ikona', 'icon' => 'emoji-happy'],
                ],
            ],
            [
                'category' => 'Layout',
                'elements' => [
                    ['type' => 'container', 'label' => 'Kontener', 'icon' => 'square'],
                    ['type' => 'row', 'label' => 'Wiersz', 'icon' => 'view-columns'],
                    ['type' => 'column', 'label' => 'Kolumna', 'icon' => 'view-boards'],
                    ['type' => 'spacer', 'label' => 'Odstep', 'icon' => 'arrows-expand'],
                    ['type' => 'divider', 'label' => 'Linia', 'icon' => 'minus'],
                ],
            ],
            [
                'category' => 'Akcje',
                'elements' => [
                    ['type' => 'button', 'label' => 'Przycisk', 'icon' => 'cursor-click'],
                ],
            ],
        ];
    }
}
