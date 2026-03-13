<div>
    <div class="mb-4 flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold text-white">Wybor pol do eksportu</h2>
            <p class="mt-1 text-sm text-gray-400">Wybrane pola: <span class="font-medium text-[#e0ac7e]">{{ $this->getSelectedCount() }}</span></p>
        </div>
        <div class="flex gap-3">
            <button wire:click="selectAllFields" class="text-sm font-medium text-[#e0ac7e] transition-colors hover:text-[#c9956a]">
                Zaznacz wszystkie
            </button>
            <span class="text-gray-600">|</span>
            <button wire:click="deselectAllFields" class="text-sm text-gray-400 transition-colors hover:text-white">
                Odznacz wszystkie
            </button>
        </div>
    </div>

    @error('selectedFields')
        <div class="mb-4 rounded-lg border border-red-700 bg-red-900/30 px-4 py-3 text-sm text-red-300">
            {{ $message }}
        </div>
    @enderror

    <div class="space-y-5">
        @foreach($availableFieldGroups as $groupKey => $group)
            <div wire:key="field-group-{{ $groupKey }}" class="rounded-lg border border-gray-700 bg-gray-800/30 p-4">
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-white">
                        {{ $group['label'] }}
                        <span class="ml-2 text-xs font-normal text-gray-400">
                            ({{ $this->getGroupSelectedCount($groupKey) }}/{{ count($group['fields'] ?? []) }})
                        </span>
                    </h3>
                    <div class="flex gap-3">
                        <button wire:click="selectAllInGroup('{{ $groupKey }}')"
                                class="text-xs text-gray-400 transition-colors hover:text-white">Wszystkie</button>
                        <button wire:click="deselectAllInGroup('{{ $groupKey }}')"
                                class="text-xs text-gray-400 transition-colors hover:text-white">Zadne</button>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach($group['fields'] as $fieldKey => $field)
                        <label wire:key="field-{{ $groupKey }}-{{ $fieldKey }}"
                               class="flex cursor-pointer items-center gap-2 rounded bg-gray-700/50 px-3 py-2 transition-colors hover:bg-gray-700">
                            <input type="checkbox"
                                   wire:click="toggleField('{{ $fieldKey }}')"
                                   {{ $this->isFieldSelected($fieldKey) ? 'checked' : '' }}
                                   class="rounded border-gray-500 bg-gray-600 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                            <span class="text-sm text-gray-300">{{ $field['label'] }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
