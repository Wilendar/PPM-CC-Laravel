{{--
    Column Mode - Spreadsheet-like entry with dynamic columns and editable rows.
    Supports clipboard paste from Excel (tabs/semicolons/newlines).
    Supports targeted paste: click cell in column + Ctrl+V fills that column only.
    Supports copy-down: fills all rows with value from row 1.
    Supports drag-fill: drag cell corner to fill range.

    Passed from render():
    - $availableColumns (array) - all possible columns with metadata
    - $columnDefinitions (array) - active columns with labels/types

    Livewire properties:
    - $rows, $activeColumns, $priceDisplayMode
    - $productTypes, $suppliers, $manufacturers, $importers
--}}
<div x-data="{
        showColumnPicker: false,
        focusedColumn: null,
        dragSource: null,
        isDragging: false,
        dragTargetRow: null,
        pasteHandler(event) {
            const text = (event.clipboardData || window.clipboardData).getData('text');
            if (!text || !text.trim()) return;

            const activeEl = document.activeElement;
            const isInTable = activeEl && activeEl.closest('[data-import-table]');
            if (!isInTable) return;

            const cell = activeEl.closest('td');
            const colKey = cell?.dataset?.colKey || null;
            const lines = text.split(/\r?\n/).filter(l => l.trim());
            const firstLine = lines[0] || '';
            const isSingleColumn = !firstLine.includes('\t') && !firstLine.includes(';');

            if (isSingleColumn && colKey) {
                event.preventDefault();
                $wire.pasteToColumn(colKey, text);
            } else if (lines.length > 0) {
                event.preventDefault();
                $wire.pasteFromClipboard(text);
            }
        },
        startDragFill(event, rowIndex, colKey) {
            this.dragSource = { row: rowIndex, col: colKey };
            this.isDragging = true;
            this.dragTargetRow = rowIndex;

            const onMove = (e) => {
                const tr = document.elementFromPoint(e.clientX, e.clientY)?.closest('tr');
                if (tr?.dataset?.rowIndex !== undefined) {
                    this.dragTargetRow = parseInt(tr.dataset.rowIndex);
                }
            };
            const onUp = () => {
                if (this.dragSource && this.dragTargetRow !== null) {
                    const start = Math.min(this.dragSource.row, this.dragTargetRow);
                    const end = Math.max(this.dragSource.row, this.dragTargetRow);
                    if (start !== end) {
                        $wire.fillColumnRange(this.dragSource.col, this.dragSource.row, start, end);
                    }
                }
                this.isDragging = false;
                this.dragSource = null;
                this.dragTargetRow = null;
                document.removeEventListener('mousemove', onMove);
                document.removeEventListener('mouseup', onUp);
            };
            document.addEventListener('mousemove', onMove);
            document.addEventListener('mouseup', onUp);
        }
    }"
     x-on:paste.window="pasteHandler($event)">

    {{-- ================================================================
         COLUMN PICKER
         ================================================================ --}}
    <div class="flex items-center gap-2 mb-4 flex-wrap">
        <span class="text-xs font-medium text-gray-400 uppercase tracking-wider mr-1">Kolumny:</span>

        {{-- Fixed columns (SKU, Nazwa) - not removable --}}
        <span class="px-2.5 py-1 bg-gray-700 rounded-md text-xs text-gray-300 font-medium border border-gray-600">
            SKU *
        </span>
        <span class="px-2.5 py-1 bg-gray-700 rounded-md text-xs text-gray-300 font-medium border border-gray-600">
            Nazwa *
        </span>

        {{-- Active optional column badges with remove button --}}
        @foreach($activeColumns as $colKey)
            @php
                $colConfig = $availableColumns[$colKey] ?? null;
                $colLabel = $colConfig['label'] ?? $colKey;
                $colType = $colConfig['type'] ?? 'input';
            @endphp
            <span class="px-2.5 py-1 {{ $colType === 'price' ? 'bg-blue-900/40 border-blue-700/50 text-blue-300' : 'bg-amber-900/40 border-amber-700/50 text-amber-300' }} rounded-md text-xs font-medium border flex items-center gap-1.5">
                {{ $colLabel }}
                @if($colType === 'price')
                    <span class="text-[9px] opacity-60">({{ $priceDisplayMode === 'net' ? 'netto' : 'brutto' }})</span>
                @endif
                <button wire:click="removeColumn('{{ $colKey }}')"
                        class="{{ $colType === 'price' ? 'text-blue-400 hover:text-red-400' : 'text-amber-400 hover:text-red-400' }} transition-colors"
                        title="Usun kolumne {{ $colLabel }}">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </span>
        @endforeach

        {{-- Add column button + dropdown --}}
        <div class="relative">
            <button x-on:click="showColumnPicker = !showColumnPicker"
                    class="px-2.5 py-1 bg-green-700 hover:bg-green-600 rounded-md text-xs text-white font-medium transition-colors flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Dodaj kolumne
            </button>

            {{-- Dropdown with available columns --}}
            <div x-show="showColumnPicker"
                 x-cloak
                 x-on:click.away="showColumnPicker = false"
                 x-transition:enter="transition ease-out duration-100"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-75"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="absolute z-20 mt-1 left-0 bg-gray-700 rounded-lg shadow-xl border border-gray-600 min-w-[220px] max-h-64 overflow-y-auto">

                {{-- Separator: Base columns --}}
                @php
                    $hasBaseAvailable = false;
                    $hasPriceAvailable = false;
                    foreach ($availableColumns as $key => $col) {
                        if (in_array($key, $activeColumns)) continue;
                        if (($col['type'] ?? '') === 'price') $hasPriceAvailable = true;
                        else $hasBaseAvailable = true;
                    }
                @endphp

                @if($hasBaseAvailable)
                    <div class="px-3 py-1.5 text-[10px] font-bold text-gray-500 uppercase tracking-wider bg-gray-750">Dane produktu</div>
                @endif
                @foreach($availableColumns as $key => $col)
                    @if(!in_array($key, $activeColumns) && ($col['type'] ?? '') !== 'price')
                        <button wire:click.stop="addColumn('{{ $key }}')"
                                x-on:click="setTimeout(() => showColumnPicker = false, 100)"
                                class="block w-full text-left px-4 py-2.5 text-sm text-gray-300 hover:bg-gray-600 hover:text-white transition-colors">
                            {{ $col['label'] ?? $key }}
                            @if(isset($col['type']) && $col['type'] === 'dropdown')
                                <span class="text-gray-500 text-xs ml-1">(lista)</span>
                            @endif
                        </button>
                    @endif
                @endforeach

                @if($hasPriceAvailable)
                    <div class="px-3 py-1.5 text-[10px] font-bold text-blue-400 uppercase tracking-wider bg-gray-750 border-t border-gray-600">Grupy cenowe</div>
                @endif
                @foreach($availableColumns as $key => $col)
                    @if(!in_array($key, $activeColumns) && ($col['type'] ?? '') === 'price')
                        <button wire:click.stop="addColumn('{{ $key }}')"
                                x-on:click="setTimeout(() => showColumnPicker = false, 100)"
                                class="block w-full text-left px-4 py-2.5 text-sm text-blue-300 hover:bg-gray-600 hover:text-blue-200 transition-colors">
                            {{ $col['label'] ?? $key }}
                            <span class="text-blue-500 text-xs ml-1">(cena)</span>
                        </button>
                    @endif
                @endforeach

                @if(!$hasBaseAvailable && !$hasPriceAvailable)
                    <div class="px-4 py-3 text-sm text-gray-500">
                        Wszystkie kolumny sa juz aktywne
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ================================================================
         DATA TABLE
         ================================================================ --}}
    <div class="overflow-x-auto rounded-lg border border-gray-700/50" data-import-table>
        <table class="w-full text-sm">
            <thead class="bg-gray-900/50 sticky top-0">
                <tr class="border-b border-gray-700">
                    <th class="px-2 py-2.5 text-left text-xs font-medium text-gray-400 w-10">#</th>
                    <th class="px-2 py-2.5 text-left text-xs font-medium text-gray-400 min-w-[130px]">
                        SKU <span class="text-red-400">*</span>
                    </th>
                    <th class="px-2 py-2.5 text-left text-xs font-medium text-gray-400 min-w-[200px]">
                        Nazwa <span class="text-red-400">*</span>
                    </th>
                    @foreach($activeColumns as $colKey)
                        @php
                            $colConfig = $availableColumns[$colKey] ?? null;
                            $colLabel = $colConfig['label'] ?? $colKey;
                            $colType = $colConfig['type'] ?? 'input';
                        @endphp
                        <th class="px-2 py-2.5 text-left text-xs font-medium text-gray-400 min-w-[150px]">
                            <div class="flex items-center gap-1">
                                <span class="truncate">{{ $colLabel }}</span>

                                {{-- Price column: netto/brutto switch --}}
                                @if($colType === 'price')
                                    <button type="button" wire:click="togglePriceDisplayMode"
                                            class="px-1.5 py-0.5 rounded text-[10px] font-bold transition-colors
                                            {{ $priceDisplayMode === 'net'
                                                ? 'bg-blue-900/50 text-blue-300 border border-blue-600'
                                                : 'bg-orange-900/50 text-orange-300 border border-orange-600' }}"
                                            title="Przelacz netto/brutto">
                                        {{ $priceDisplayMode === 'net' ? 'NETTO' : 'BRUTTO' }}
                                    </button>
                                @endif

                                {{-- Copy-down button for dropdown and price columns --}}
                                @if(in_array($colType, ['dropdown', 'price']))
                                    <button wire:click="copyDownColumn('{{ $colKey }}')"
                                            class="p-0.5 text-gray-600 hover:text-amber-400 transition-colors"
                                            title="Kopiuj wartosc z wiersza 1 na wszystkie">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        </th>
                    @endforeach
                    <th class="px-2 py-2.5 text-gray-400 w-10"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700/30">
                @foreach($rows as $rowIndex => $row)
                    <tr class="hover:bg-gray-700/20 transition-colors group"
                        :class="{
                            'import-drag-fill-row-highlight': isDragging && dragSource && dragTargetRow !== null && (
                                {{ $rowIndex }} >= Math.min(dragSource.row, dragTargetRow) &&
                                {{ $rowIndex }} <= Math.max(dragSource.row, dragTargetRow)
                            )
                        }"
                        wire:key="import-row-{{ $rowIndex }}"
                        data-row-index="{{ $rowIndex }}">
                        {{-- Row number --}}
                        <td class="px-2 py-1.5 text-gray-500 text-xs font-mono">{{ $rowIndex + 1 }}</td>

                        {{-- SKU input --}}
                        <td class="px-2 py-1.5 relative group/cell" data-col-key="sku"
                            :class="{
                                'import-drag-fill-highlight': isDragging && dragSource?.col === 'sku' && dragTargetRow !== null && (
                                    {{ $rowIndex }} >= Math.min(dragSource.row, dragTargetRow) &&
                                    {{ $rowIndex }} <= Math.max(dragSource.row, dragTargetRow)
                                ),
                                'import-drag-fill-source': isDragging && dragSource?.row === {{ $rowIndex }} && dragSource?.col === 'sku'
                            }">
                            <input type="text"
                                   wire:model.blur="rows.{{ $rowIndex }}.sku"
                                   class="form-input-dark-sm w-full font-mono"
                                   placeholder="SKU"
                                   autocomplete="off">
                            <div class="import-drag-handle"
                                 x-on:mousedown.prevent="startDragFill($event, {{ $rowIndex }}, 'sku')"></div>
                        </td>

                        {{-- Name input --}}
                        <td class="px-2 py-1.5 relative group/cell" data-col-key="name"
                            :class="{
                                'import-drag-fill-highlight': isDragging && dragSource?.col === 'name' && dragTargetRow !== null && (
                                    {{ $rowIndex }} >= Math.min(dragSource.row, dragTargetRow) &&
                                    {{ $rowIndex }} <= Math.max(dragSource.row, dragTargetRow)
                                ),
                                'import-drag-fill-source': isDragging && dragSource?.row === {{ $rowIndex }} && dragSource?.col === 'name'
                            }">
                            <input type="text"
                                   wire:model.blur="rows.{{ $rowIndex }}.name"
                                   class="form-input-dark-sm w-full"
                                   placeholder="Nazwa produktu"
                                   autocomplete="off">
                            <div class="import-drag-handle"
                                 x-on:mousedown.prevent="startDragFill($event, {{ $rowIndex }}, 'name')"></div>
                        </td>

                        {{-- Dynamic columns --}}
                        @foreach($activeColumns as $colKey)
                            @php
                                $colConfig = $availableColumns[$colKey] ?? null;
                                $colType = $colConfig['type'] ?? 'input';
                            @endphp
                            <td class="px-2 py-1.5 relative group/cell" data-col-key="{{ $colKey }}"
                                :class="{
                                    'import-drag-fill-highlight': isDragging && dragSource?.col === '{{ $colKey }}' && dragTargetRow !== null && (
                                        {{ $rowIndex }} >= Math.min(dragSource.row, dragTargetRow) &&
                                        {{ $rowIndex }} <= Math.max(dragSource.row, dragTargetRow)
                                    ),
                                    'import-drag-fill-source': isDragging && dragSource?.row === {{ $rowIndex }} && dragSource?.col === '{{ $colKey }}'
                                }">
                                @if($colType === 'dropdown' && $colKey === 'product_type_id')
                                    <select wire:model.blur="rows.{{ $rowIndex }}.{{ $colKey }}"
                                            class="form-select-dark-sm w-full">
                                        <option value="">-- Wybierz --</option>
                                        @foreach(($productTypes ?? []) as $type)
                                            <option value="{{ $type['id'] }}">{{ $type['name'] }}</option>
                                        @endforeach
                                    </select>
                                @elseif($colType === 'dropdown' && $colKey === 'supplier_id')
                                    <select wire:model.blur="rows.{{ $rowIndex }}.{{ $colKey }}"
                                            class="form-select-dark-sm w-full">
                                        <option value="">-- Wybierz --</option>
                                        @foreach(($suppliers ?? []) as $supplier)
                                            <option value="{{ $supplier['id'] }}">{{ $supplier['name'] }}</option>
                                        @endforeach
                                    </select>
                                @elseif($colType === 'dropdown' && $colKey === 'manufacturer_id')
                                    <select wire:model.blur="rows.{{ $rowIndex }}.{{ $colKey }}"
                                            class="form-select-dark-sm w-full">
                                        <option value="">-- Wybierz --</option>
                                        @foreach(($manufacturers ?? []) as $manufacturer)
                                            <option value="{{ $manufacturer['id'] }}">{{ $manufacturer['name'] }}</option>
                                        @endforeach
                                    </select>
                                @elseif($colType === 'dropdown' && $colKey === 'importer_id')
                                    <select wire:model.blur="rows.{{ $rowIndex }}.{{ $colKey }}"
                                            class="form-select-dark-sm w-full">
                                        <option value="">-- Wybierz --</option>
                                        @foreach(($importers ?? []) as $importer)
                                            <option value="{{ $importer['id'] }}">{{ $importer['name'] }}</option>
                                        @endforeach
                                    </select>
                                @elseif($colType === 'price')
                                    <input type="number"
                                           wire:model.blur="rows.{{ $rowIndex }}.{{ $colKey }}"
                                           class="form-input-dark-sm w-full font-mono text-right"
                                           step="0.01" min="0"
                                           placeholder="{{ $priceDisplayMode === 'net' ? 'Netto' : 'Brutto' }}"
                                           autocomplete="off">
                                @else
                                    <input type="text"
                                           wire:model.blur="rows.{{ $rowIndex }}.{{ $colKey }}"
                                           class="form-input-dark-sm w-full"
                                           placeholder="{{ $colConfig['label'] ?? '' }}"
                                           autocomplete="off">
                                @endif
                                <div class="import-drag-handle"
                                     x-on:mousedown.prevent="startDragFill($event, {{ $rowIndex }}, '{{ $colKey }}')"></div>
                            </td>
                        @endforeach

                        {{-- Remove row button --}}
                        <td class="px-2 py-1.5 text-center">
                            @if(count($rows) > 1)
                                <button wire:click="removeRow({{ $rowIndex }})"
                                        class="p-1 text-gray-600 hover:text-red-400 transition-colors opacity-0 group-hover:opacity-100"
                                        title="Usun wiersz">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- ================================================================
         ADD ROW + HINTS
         ================================================================ --}}
    <div class="flex items-center justify-between mt-3">
        <button wire:click="addEmptyRow"
                class="px-3 py-1.5 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm text-gray-300 transition-colors flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Dodaj wiersz
        </button>

        <p class="text-xs text-gray-500 flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>Wklej dane z Excel (<kbd class="px-1 py-0.5 bg-gray-700 rounded text-xs">Ctrl+V</kbd>) - kliknij komorke aby wkleic do jednej kolumny.</span>
        </p>
    </div>

</div>
