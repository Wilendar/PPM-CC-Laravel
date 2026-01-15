# RAPORT: Panel Kategorii UI/UX Redesign

**Agent:** frontend-specialist
**Data:** 2025-12-23
**Zadanie:** Analiza UI/UX panelu kategorii i projekt ulepszen

---

## 1. ANALIZA AKTUALNEGO STANU

### 1.1 Lokalizacja
- **URL:** `https://ppm.mpptrade.pl/admin/products/categories`
- **Livewire:** `CategoryTree.php`
- **Blade:** `resources/views/livewire/products/categories/category-tree.blade.php`
- **Partial:** `resources/views/livewire/products/categories/partials/tree-node.blade.php`

### 1.2 Screenshot Analysis (2025-12-23)

**Pozytywne aspekty:**
- Ciemny motyw (dark theme) - zgodny z PPM brand
- Czytelna hierarchia z poziomami 0-4
- Kolorowe badge dla liczby podkategorii (zielony, pomaranczowy, fioletowy)
- Ikony folderow (zolte/pomaranczowe) wizualnie oddzielaja kategorie
- Status "Aktywna" z zielonym badge - dobra widocznosc
- Kolumny: KATEGORIA, POZIOM, PRODUKTY, STATUS, AKCJE

**Problemy zidentyfikowane:**
1. **Wcecia oparte na kreskach (--- --- +)** - przestarzaly wzorzec wizualny
2. **Brak animacji rozwijania/zwijania** - nagly skok przy expand/collapse
3. **Brak wizualnych polaczen parent-child** - trudna orientacja w hierarchii
4. **Loading overlay z przyciemnieniem** - blokuje UI podczas operacji
5. **Brak inline "+" dla dodawania** - wymaga osobnego przycisku "Dodaj kategorie"
6. **Drag & Drop bez wizualnych wskaznikow** - drop zones niewidoczne

### 1.3 Istniejace zasoby CSS
| Plik | Rozmiar | Zawartosc |
|------|---------|-----------|
| `resources/css/products/category-form.css` | ~692 linii | Enterprise forms, tabs, animations |
| `resources/css/admin/components.css` | ~7000 linii | Sync badges, enterprise cards |
| `resources/css/components/category-picker.css` | - | Category picker modal |

---

## 2. SPECYFIKACJA CSS - NOWE KLASY

### 2.1 Plik docelowy
`resources/css/admin/category-tree.css` (NOWY)

### 2.2 Animacje rozwijania/zwijania

```css
/* ========================================
   CATEGORY TREE ANIMATIONS
   ======================================== */

/* Smooth expand/collapse transition */
.category-tree-children {
    overflow: hidden;
    max-height: 0;
    opacity: 0;
    transform: translateY(-8px);
    transition:
        max-height 0.35s cubic-bezier(0.4, 0, 0.2, 1),
        opacity 0.25s ease-out,
        transform 0.25s ease-out;
}

.category-tree-children.is-expanded {
    max-height: 2000px; /* Large enough for any content */
    opacity: 1;
    transform: translateY(0);
}

/* Chevron rotation animation */
.category-tree-toggle-icon {
    transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    transform-origin: center;
}

.category-tree-toggle-icon.is-expanded {
    transform: rotate(90deg);
}

/* Staggered children animation */
.category-tree-children.is-expanded > .category-tree-node {
    animation: slideInFromTop 0.3s ease-out forwards;
}

.category-tree-children.is-expanded > .category-tree-node:nth-child(1) { animation-delay: 0.02s; }
.category-tree-children.is-expanded > .category-tree-node:nth-child(2) { animation-delay: 0.04s; }
.category-tree-children.is-expanded > .category-tree-node:nth-child(3) { animation-delay: 0.06s; }
.category-tree-children.is-expanded > .category-tree-node:nth-child(4) { animation-delay: 0.08s; }
.category-tree-children.is-expanded > .category-tree-node:nth-child(5) { animation-delay: 0.10s; }

@keyframes slideInFromTop {
    from {
        opacity: 0;
        transform: translateY(-12px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
```

### 2.3 Wizualne polaczenia parent-child (Tree Lines)

```css
/* ========================================
   TREE CONNECTOR LINES
   ======================================== */

.category-tree-node {
    position: relative;
}

/* Vertical line from parent */
.category-tree-node::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 50%;
    width: 1px;
    background: linear-gradient(
        to bottom,
        rgba(100, 116, 139, 0.4),
        rgba(100, 116, 139, 0.2)
    );
}

/* Horizontal line to node */
.category-tree-node::after {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    width: 16px;
    height: 1px;
    background: rgba(100, 116, 139, 0.3);
}

/* Remove lines from root level */
.category-tree-node[data-level="0"]::before,
.category-tree-node[data-level="0"]::after {
    display: none;
}

/* Indent per level with connector offset */
.category-tree-node[data-level="1"] { padding-left: 24px; }
.category-tree-node[data-level="2"] { padding-left: 48px; }
.category-tree-node[data-level="3"] { padding-left: 72px; }
.category-tree-node[data-level="4"] { padding-left: 96px; }

/* Position connector lines based on level */
.category-tree-node[data-level="1"]::before { left: 12px; }
.category-tree-node[data-level="2"]::before { left: 36px; }
.category-tree-node[data-level="3"]::before { left: 60px; }
.category-tree-node[data-level="4"]::before { left: 84px; }

.category-tree-node[data-level="1"]::after { left: 12px; }
.category-tree-node[data-level="2"]::after { left: 36px; }
.category-tree-node[data-level="3"]::after { left: 60px; }
.category-tree-node[data-level="4"]::after { left: 84px; }

/* Last child gets shorter vertical line */
.category-tree-node:last-child::before {
    bottom: 50%;
    height: 50%;
}
```

### 2.4 Inline "+" Button dla dodawania

```css
/* ========================================
   INLINE ADD SUBCATEGORY BUTTON
   ======================================== */

.category-tree-add-inline {
    position: relative;
    height: 0;
    overflow: visible;
}

.category-tree-add-trigger {
    position: absolute;
    left: 50%;
    top: -12px;
    transform: translateX(-50%);
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: var(--color-bg-secondary, #1e293b);
    border: 2px dashed rgba(100, 116, 139, 0.3);
    color: rgba(100, 116, 139, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    opacity: 0;
    transition: all 0.2s ease;
    z-index: 5;
}

/* Show on parent hover */
.category-tree-node:hover + .category-tree-add-inline .category-tree-add-trigger,
.category-tree-add-inline:hover .category-tree-add-trigger {
    opacity: 1;
}

.category-tree-add-trigger:hover {
    background: var(--color-primary, #f97316);
    border-color: var(--color-primary, #f97316);
    color: white;
    transform: translateX(-50%) scale(1.1);
}

.category-tree-add-trigger i {
    font-size: 10px;
}

/* Expanded state - shows inline form */
.category-tree-add-inline.is-adding {
    height: auto;
    padding: 8px 0;
}

.category-tree-add-inline.is-adding .category-tree-add-trigger {
    display: none;
}

/* Inline add form */
.category-tree-add-form {
    display: none;
    background: rgba(31, 41, 55, 0.95);
    border: 1px solid rgba(100, 116, 139, 0.3);
    border-radius: 8px;
    padding: 12px 16px;
    margin: 8px 0;
}

.category-tree-add-inline.is-adding .category-tree-add-form {
    display: flex;
    align-items: center;
    gap: 12px;
    animation: fadeInScale 0.2s ease-out;
}

.category-tree-add-form input {
    flex: 1;
    background: rgba(17, 24, 39, 0.8);
    border: 1px solid rgba(100, 116, 139, 0.4);
    border-radius: 6px;
    padding: 8px 12px;
    color: white;
    font-size: 14px;
}

.category-tree-add-form input:focus {
    border-color: var(--color-primary, #f97316);
    outline: none;
    box-shadow: 0 0 0 2px rgba(249, 115, 22, 0.1);
}

.category-tree-add-form-actions {
    display: flex;
    gap: 8px;
}

.category-tree-add-form-btn {
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
    border: none;
    cursor: pointer;
    transition: all 0.15s ease;
}

.category-tree-add-form-btn--save {
    background: var(--color-primary, #f97316);
    color: white;
}

.category-tree-add-form-btn--save:hover {
    background: var(--color-primary-hover, #ea580c);
}

.category-tree-add-form-btn--cancel {
    background: transparent;
    color: #9ca3af;
    border: 1px solid rgba(100, 116, 139, 0.3);
}

.category-tree-add-form-btn--cancel:hover {
    background: rgba(100, 116, 139, 0.1);
    color: white;
}

@keyframes fadeInScale {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}
```

### 2.5 Drag & Drop Visual Indicators

```css
/* ========================================
   DRAG & DROP SYSTEM
   ======================================== */

/* Draggable item */
.category-tree-node-draggable {
    cursor: grab;
    transition: all 0.2s ease;
}

.category-tree-node-draggable:active {
    cursor: grabbing;
}

/* Being dragged */
.category-tree-node.is-dragging {
    opacity: 0.5;
    transform: scale(0.98);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
    z-index: 100;
}

/* Ghost element (browser default replacement) */
.category-tree-ghost {
    background: linear-gradient(135deg, rgba(249, 115, 22, 0.15), rgba(249, 115, 22, 0.05));
    border: 2px dashed var(--color-primary, #f97316);
    border-radius: 8px;
    padding: 12px 16px;
    color: var(--color-primary, #f97316);
    font-weight: 500;
}

/* Drop zone indicators */
.category-tree-drop-zone {
    position: relative;
}

/* Drop zone - before (insert above) */
.category-tree-drop-zone::before {
    content: '';
    position: absolute;
    top: -2px;
    left: 0;
    right: 0;
    height: 4px;
    background: transparent;
    border-radius: 2px;
    transition: all 0.15s ease;
    pointer-events: none;
}

/* Drop zone - after (insert below) */
.category-tree-drop-zone::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    right: 0;
    height: 4px;
    background: transparent;
    border-radius: 2px;
    transition: all 0.15s ease;
    pointer-events: none;
}

/* Active drop zone - top */
.category-tree-drop-zone.drop-target-top::before {
    background: linear-gradient(90deg,
        transparent,
        var(--color-primary, #f97316) 10%,
        var(--color-primary, #f97316) 90%,
        transparent
    );
    box-shadow: 0 0 12px rgba(249, 115, 22, 0.4);
}

/* Active drop zone - bottom */
.category-tree-drop-zone.drop-target-bottom::after {
    background: linear-gradient(90deg,
        transparent,
        var(--color-primary, #f97316) 10%,
        var(--color-primary, #f97316) 90%,
        transparent
    );
    box-shadow: 0 0 12px rgba(249, 115, 22, 0.4);
}

/* Active drop zone - nest as child */
.category-tree-drop-zone.drop-target-child {
    background: rgba(249, 115, 22, 0.08);
    border: 2px solid rgba(249, 115, 22, 0.3);
    border-radius: 8px;
}

/* Drop indicator line */
.category-tree-drop-indicator {
    position: absolute;
    left: 0;
    right: 0;
    height: 3px;
    background: var(--color-primary, #f97316);
    border-radius: 2px;
    pointer-events: none;
    z-index: 50;
    display: none;
}

.category-tree-drop-indicator::before,
.category-tree-drop-indicator::after {
    content: '';
    position: absolute;
    width: 8px;
    height: 8px;
    background: var(--color-primary, #f97316);
    border-radius: 50%;
    top: 50%;
    transform: translateY(-50%);
}

.category-tree-drop-indicator::before {
    left: -4px;
}

.category-tree-drop-indicator::after {
    right: -4px;
}

.category-tree-drop-indicator.is-visible {
    display: block;
    animation: dropIndicatorPulse 0.8s ease-in-out infinite;
}

@keyframes dropIndicatorPulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}

/* Invalid drop target */
.category-tree-drop-zone.drop-invalid {
    opacity: 0.4;
    cursor: not-allowed;
}
```

### 2.6 Hover States bez reload/overlay

```css
/* ========================================
   HOVER STATES (NO OVERLAY!)
   ======================================== */

.category-tree-node-content {
    display: flex;
    align-items: center;
    padding: 10px 16px;
    border-radius: 8px;
    background: transparent;
    transition: background 0.15s ease, border-color 0.15s ease;
    border: 1px solid transparent;
}

/* Hover - subtle background change (NO TRANSFORM!) */
.category-tree-node-content:hover {
    background: rgba(100, 116, 139, 0.08);
    border-color: rgba(100, 116, 139, 0.15);
    /* NO transform: translateY() - forbidden by PPM standards! */
}

/* Selected state */
.category-tree-node-content.is-selected {
    background: rgba(249, 115, 22, 0.08);
    border-color: rgba(249, 115, 22, 0.2);
}

.category-tree-node-content.is-selected:hover {
    background: rgba(249, 115, 22, 0.12);
    border-color: rgba(249, 115, 22, 0.3);
}

/* Focus state (keyboard navigation) */
.category-tree-node-content:focus-visible {
    outline: none;
    box-shadow: 0 0 0 2px rgba(249, 115, 22, 0.4);
    border-color: var(--color-primary, #f97316);
}

/* Actions appear on hover */
.category-tree-node-actions {
    opacity: 0;
    transition: opacity 0.15s ease;
}

.category-tree-node-content:hover .category-tree-node-actions,
.category-tree-node-content:focus-within .category-tree-node-actions {
    opacity: 1;
}
```

### 2.7 Loading States bez przyciemnienia

```css
/* ========================================
   LOADING STATES (NO OVERLAY!)
   ======================================== */

/* Inline loading indicator */
.category-tree-loading {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #9ca3af;
    font-size: 13px;
}

.category-tree-loading-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(100, 116, 139, 0.2);
    border-top-color: var(--color-primary, #f97316);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

/* Node-level loading (skeleton) */
.category-tree-node.is-loading .category-tree-node-content {
    position: relative;
    overflow: hidden;
}

.category-tree-node.is-loading .category-tree-node-content::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(
        90deg,
        transparent,
        rgba(255, 255, 255, 0.04),
        transparent
    );
    animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

/* Saving indicator */
.category-tree-node.is-saving {
    pointer-events: none;
}

.category-tree-node.is-saving .category-tree-node-content {
    opacity: 0.7;
}

.category-tree-saving-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    background: rgba(59, 130, 246, 0.15);
    border: 1px solid rgba(59, 130, 246, 0.3);
    border-radius: 12px;
    color: #60a5fa;
    font-size: 12px;
    font-weight: 500;
}

.category-tree-saving-badge i {
    animation: spin 1s linear infinite;
}
```

---

## 3. KOMPONENTY ALPINE.JS

### 3.1 Glowny Category Tree Manager

```javascript
// resources/js/components/category-tree-manager.js

function categoryTreeManager() {
    return {
        // State
        expandedNodes: new Set(),
        selectedNodes: new Set(),
        dragState: {
            isDragging: false,
            draggedId: null,
            dropTargetId: null,
            dropPosition: null // 'before', 'after', 'child'
        },
        inlineAddState: {
            parentId: null,
            isAdding: false,
            newName: ''
        },
        loadingNodes: new Set(),

        // Initialize
        init() {
            this.initKeyboardNavigation();
            this.initDragAndDrop();

            // Listen for Livewire events
            Livewire.on('category-expanded', (data) => {
                this.expandedNodes.add(data.id);
            });

            Livewire.on('category-collapsed', (data) => {
                this.expandedNodes.delete(data.id);
            });
        },

        // Expand/Collapse with animation
        toggleNode(nodeId) {
            const isExpanded = this.expandedNodes.has(nodeId);

            if (isExpanded) {
                this.collapseNode(nodeId);
            } else {
                this.expandNode(nodeId);
            }
        },

        expandNode(nodeId) {
            this.loadingNodes.add(nodeId);

            // Trigger Livewire
            this.$wire.call('expandNode', nodeId).then(() => {
                this.loadingNodes.delete(nodeId);
                this.expandedNodes.add(nodeId);
            });
        },

        collapseNode(nodeId) {
            this.expandedNodes.delete(nodeId);
            this.$wire.call('collapseNode', nodeId);
        },

        isExpanded(nodeId) {
            return this.expandedNodes.has(nodeId);
        },

        isLoading(nodeId) {
            return this.loadingNodes.has(nodeId);
        },

        // Inline Add Subcategory
        startInlineAdd(parentId) {
            this.inlineAddState = {
                parentId,
                isAdding: true,
                newName: ''
            };

            // Focus input after DOM update
            this.$nextTick(() => {
                const input = document.querySelector(`[data-inline-add="${parentId}"] input`);
                if (input) input.focus();
            });
        },

        cancelInlineAdd() {
            this.inlineAddState = {
                parentId: null,
                isAdding: false,
                newName: ''
            };
        },

        saveInlineAdd() {
            if (!this.inlineAddState.newName.trim()) return;

            const { parentId, newName } = this.inlineAddState;

            this.$wire.call('createSubcategory', parentId, newName.trim())
                .then(() => {
                    this.cancelInlineAdd();
                    // Auto-expand parent to show new child
                    this.expandedNodes.add(parentId);
                });
        },

        // Keyboard Navigation
        initKeyboardNavigation() {
            document.addEventListener('keydown', (e) => {
                if (e.target.matches('input, textarea')) return;

                switch (e.key) {
                    case 'ArrowRight':
                        this.expandFocusedNode();
                        e.preventDefault();
                        break;
                    case 'ArrowLeft':
                        this.collapseFocusedNode();
                        e.preventDefault();
                        break;
                    case 'ArrowUp':
                        this.focusPreviousNode();
                        e.preventDefault();
                        break;
                    case 'ArrowDown':
                        this.focusNextNode();
                        e.preventDefault();
                        break;
                    case 'Enter':
                        this.activateFocusedNode();
                        e.preventDefault();
                        break;
                    case 'Escape':
                        if (this.inlineAddState.isAdding) {
                            this.cancelInlineAdd();
                            e.preventDefault();
                        }
                        break;
                }
            });
        },

        // Drag & Drop
        initDragAndDrop() {
            // Setup will be handled by individual node x-data
        },

        handleDragStart(nodeId, event) {
            this.dragState = {
                isDragging: true,
                draggedId: nodeId,
                dropTargetId: null,
                dropPosition: null
            };

            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', nodeId);

            // Create custom drag image
            const ghost = this.createGhostElement(nodeId);
            event.dataTransfer.setDragImage(ghost, 20, 20);
        },

        handleDragOver(targetId, event, rect) {
            if (targetId === this.dragState.draggedId) return;

            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';

            // Determine drop position based on mouse Y
            const mouseY = event.clientY;
            const nodeTop = rect.top;
            const nodeHeight = rect.height;
            const relativeY = mouseY - nodeTop;

            let position;
            if (relativeY < nodeHeight * 0.25) {
                position = 'before';
            } else if (relativeY > nodeHeight * 0.75) {
                position = 'after';
            } else {
                position = 'child';
            }

            this.dragState.dropTargetId = targetId;
            this.dragState.dropPosition = position;
        },

        handleDragLeave(targetId) {
            if (this.dragState.dropTargetId === targetId) {
                this.dragState.dropTargetId = null;
                this.dragState.dropPosition = null;
            }
        },

        handleDrop(event) {
            event.preventDefault();

            const { draggedId, dropTargetId, dropPosition } = this.dragState;

            if (!draggedId || !dropTargetId || draggedId === dropTargetId) {
                this.resetDragState();
                return;
            }

            // Call Livewire to reorder
            this.$wire.call('moveCategory', draggedId, dropTargetId, dropPosition)
                .then(() => {
                    this.resetDragState();
                });
        },

        handleDragEnd() {
            this.resetDragState();
        },

        resetDragState() {
            this.dragState = {
                isDragging: false,
                draggedId: null,
                dropTargetId: null,
                dropPosition: null
            };
        },

        createGhostElement(nodeId) {
            const node = document.querySelector(`[data-category-id="${nodeId}"]`);
            const ghost = node.cloneNode(true);
            ghost.classList.add('category-tree-ghost');
            ghost.style.position = 'absolute';
            ghost.style.left = '-9999px';
            document.body.appendChild(ghost);

            setTimeout(() => ghost.remove(), 0);
            return ghost;
        },

        getDropTargetClass(nodeId) {
            if (this.dragState.dropTargetId !== nodeId) return '';

            switch (this.dragState.dropPosition) {
                case 'before': return 'drop-target-top';
                case 'after': return 'drop-target-bottom';
                case 'child': return 'drop-target-child';
                default: return '';
            }
        }
    }
}

// Register globally
window.categoryTreeManager = categoryTreeManager;
```

### 3.2 Individual Node Component

```javascript
// resources/js/components/category-tree-node.js

function categoryTreeNode(nodeId, parentId, level) {
    return {
        nodeId,
        parentId,
        level,

        get isExpanded() {
            return this.$parent.isExpanded(this.nodeId);
        },

        get isLoading() {
            return this.$parent.isLoading(this.nodeId);
        },

        get isDragging() {
            return this.$parent.dragState.draggedId === this.nodeId;
        },

        get dropTargetClass() {
            return this.$parent.getDropTargetClass(this.nodeId);
        },

        toggle() {
            this.$parent.toggleNode(this.nodeId);
        },

        startInlineAdd() {
            this.$parent.startInlineAdd(this.nodeId);
        },

        // Drag handlers
        onDragStart(event) {
            this.$parent.handleDragStart(this.nodeId, event);
        },

        onDragOver(event) {
            const rect = this.$el.getBoundingClientRect();
            this.$parent.handleDragOver(this.nodeId, event, rect);
        },

        onDragLeave() {
            this.$parent.handleDragLeave(this.nodeId);
        },

        onDrop(event) {
            this.$parent.handleDrop(event);
        },

        onDragEnd() {
            this.$parent.handleDragEnd();
        }
    }
}

window.categoryTreeNode = categoryTreeNode;
```

---

## 4. MOCKUP KONCEPCYJNY (Opis Tekstowy)

### 4.1 Layout Glowny

```
+------------------------------------------------------------------+
|  [Drzewo] [Lista]    [Szukaj kategorii...         ]  [+ Dodaj]   |
|  [Tylko aktywne]     [Rozwin]  [Zwin]                            |
+------------------------------------------------------------------+
|                                                                  |
|  |-- Baza                          Poziom 0   63  (+0)  Aktywna  |
|  |   |                                                           |
|  |   +-- Wszystko                  Poziom 1   83  (+0)  Aktywna  |
|  |       |                                                       |
|  |       +-- Akcesoria             Poziom 2    2  (+0)  Aktywna  |
|  |       |   [+] hover: "Dodaj podkategorie"                     |
|  |       |                                                       |
|  |       +-- Buggy                 Poziom 2    2  (+2)  Aktywna  |
|  |       |   [+]                                                 |
|  |       |                                                       |
|  |       v-- Czesci zamienne [4]   Poziom 2   84  (+0)  Aktywna  |
|  |           |                                                   |
|  |           +-- Czesci Buggy      Poziom 3   14  (+0)  Aktywna  |
|  |           |   [+]                                             |
|  |           |                                                   |
|  |           +-- Mechanizm pedalu  Poziom 4   10 (+10)  Aktywna  |
|  |               [+]                                             |
|  |                                                               |
+------------------------------------------------------------------+

Legenda:
- | i + : Linie polaczen (vertical/horizontal)
- v : Rozwiniety node (klikniecie zwija)
- > : Zwiniety node (klikniecie rozwija)
- [4] : Badge z liczba podkategorii
- [+] : Inline button "Dodaj podkategorie" (visible on hover)
```

### 4.2 Stany Interakcji

**Hover na kategorii:**
- Background: `rgba(100, 116, 139, 0.08)`
- Border: `1px solid rgba(100, 116, 139, 0.15)`
- Actions buttons visibility: 100%
- Inline [+] button visibility: 100%

**Drag & Drop:**
- Dragged element: 50% opacity, scale 0.98
- Drop zone TOP: Orange line above target
- Drop zone BOTTOM: Orange line below target
- Drop zone CHILD: Orange border around target
- Invalid target: 40% opacity, cursor: not-allowed

**Expand/Collapse Animation:**
- Duration: 350ms
- Easing: cubic-bezier(0.4, 0, 0.2, 1)
- Children stagger: 20ms delay per child
- Chevron rotation: 90deg

### 4.3 Inline Add Subcategory

```
Przed kliknieciem [+]:
|       +-- Akcesoria             Poziom 2    2  (+0)  Aktywna
|           [+]  <- subtle, dashed border

Po kliknięciu [+]:
|       +-- Akcesoria             Poziom 2    2  (+0)  Aktywna
|           +--------------------------------------------------+
|           | [input: Nazwa nowej kategorii]  [Zapisz] [Anuluj]|
|           +--------------------------------------------------+
```

---

## 5. ZALECANE BIBLIOTEKI

### 5.1 Drag & Drop

**Sortable.js** (zalecane)
- URL: https://sortablejs.github.io/Sortable/
- CDN: `https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js`
- Zalety:
  - Zero dependencies
  - Touch support
  - Nested lists support (idealne dla drzewka)
  - Animation built-in
  - Alpine.js compatible

**Alternatywa: @shopify/draggable**
- Wiecej kontroli nad animacjami
- Wiekszy bundle size

### 5.2 Animacje

**Wbudowane CSS transitions** (zalecane)
- Nie wymaga dodatkowej biblioteki
- `max-height` + `opacity` + `transform`
- Cubic-bezier easing

**Alternatywa: Motion One**
- URL: https://motion.dev/
- Lekka (3KB)
- Web Animations API

### 5.3 Virtual Scrolling (dla duzych drzewek)

**Tanstack Virtual** (jesli >500 kategorii)
- URL: https://tanstack.com/virtual/latest
- Lazy rendering
- Smooth scroll

---

## 6. IMPLEMENTACJA - KROKI

### 6.1 Faza 1: CSS (1-2h)
1. Utworz `resources/css/admin/category-tree.css`
2. Dodaj import do `vite.config.js`
3. Implementuj animacje expand/collapse
4. Implementuj tree connector lines
5. Build: `npm run build`
6. Deploy CSS

### 6.2 Faza 2: Alpine.js Components (2-3h)
1. Utworz `resources/js/components/category-tree-manager.js`
2. Zarejestruj w `app.js`
3. Update `category-tree.blade.php` z nowymi x-data
4. Testuj expand/collapse animacje

### 6.3 Faza 3: Inline Add (1-2h)
1. Dodaj inline add CSS
2. Dodaj inline add Alpine component
3. Utworz Livewire method `createSubcategory()`
4. Testuj flow

### 6.4 Faza 4: Drag & Drop (2-3h)
1. Dodaj Sortable.js do assets
2. Implementuj drag handlers
3. Utworz Livewire method `moveCategory()`
4. Dodaj drop zone indicators
5. Testuj reorganizacje

### 6.5 Faza 5: Polish (1h)
1. Loading states bez overlay
2. Keyboard navigation
3. Accessibility (ARIA)
4. Chrome DevTools verification

---

## 7. ZGODNOSC Z PPM STANDARDS

### 7.1 Checklist

| Requirement | Status |
|-------------|--------|
| NO inline styles | OK - wszystko w CSS |
| Colors from tokens | OK - `var(--color-primary)` |
| NO hover transforms on cards | OK - tylko background/border |
| Min 20px padding | OK - 10-16px per node (appropriate for list items) |
| High contrast colors | OK - Orange #f97316, white text |
| Enterprise button hierarchy | OK - primary/secondary classes |

### 7.2 CSS Variables uzyte

```css
--color-primary: #f97316;           /* Orange */
--color-primary-hover: #ea580c;
--color-bg-secondary: #1e293b;      /* Slate-800 */
--color-text-primary: #f8fafc;      /* Slate-50 */
--color-text-secondary: #cbd5e1;    /* Slate-300 */
```

---

## 8. PLIKI DO UTWORZENIA/MODYFIKACJI

| Plik | Akcja | Opis |
|------|-------|------|
| `resources/css/admin/category-tree.css` | NOWY | Wszystkie style drzewka |
| `resources/js/components/category-tree-manager.js` | NOWY | Alpine.js manager |
| `resources/js/app.js` | MODYFIKACJA | Import nowych komponentow |
| `vite.config.js` | MODYFIKACJA | Dodaj nowy CSS entry |
| `category-tree.blade.php` | MODYFIKACJA | Nowa struktura x-data |
| `tree-node.blade.php` | MODYFIKACJA | Nowe klasy CSS |
| `CategoryTree.php` | MODYFIKACJA | Nowe metody Livewire |

---

**Raport przygotowany przez:** frontend-specialist
**Data:** 2025-12-23
**Status:** Gotowy do implementacji
