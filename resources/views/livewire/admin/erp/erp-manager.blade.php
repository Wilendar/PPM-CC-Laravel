<div class="bg-gray-900 min-h-screen">
    <!-- Compact Header -->
    <div class="border-b border-gray-800 bg-gray-900/95 backdrop-blur-sm sticky top-0 z-40">
        <div class="flex items-center justify-between px-4 py-3">
            <div>
                <h1 class="text-lg font-semibold text-white">ERP Integrations</h1>
                <p class="text-xs text-gray-400 mt-0.5">Manage external system connections</p>
            </div>
            <div class="flex items-center gap-2">
                <button wire:click="$refresh"
                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-300 hover:text-white
                               bg-gray-800 hover:bg-gray-700 border border-gray-700 hover:border-gray-600
                               rounded-md transition-colors duration-150">
                    <svg class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Refresh
                </button>
                <button wire:click="startWizard"
                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white
                               rounded-md transition-colors duration-150 btn-enterprise-primary">
                    <svg class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Add Connection
                </button>
            </div>
        </div>

        <!-- Compact Stats Bar -->
        <div class="flex items-center justify-between px-4 py-2 bg-gray-800/50">
            <div class="flex items-center gap-6 text-xs">
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                    <span class="text-gray-400">Total:</span>
                    <span class="text-white font-medium">{{ $stats['total'] ?? 0 }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                    <span class="text-gray-400">Active:</span>
                    <span class="text-white font-medium">{{ $stats['active'] ?? 0 }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 bg-emerald-500 rounded-full"></div>
                    <span class="text-gray-400">Connected:</span>
                    <span class="text-white font-medium">{{ $stats['connected'] ?? 0 }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                    <span class="text-gray-400">Issues:</span>
                    <span class="text-white font-medium">{{ $stats['issues'] ?? 0 }}</span>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <input wire:model.live.debounce.300ms="search"
                       type="text"
                       class="px-2 py-1 text-xs bg-gray-700 border border-gray-600 rounded text-white
                              placeholder-gray-400 focus:outline-none focus:border-orange-400 w-48"
                       placeholder="Search connections...">
                <select wire:model.live="erpTypeFilter"
                        class="px-2 py-1 text-xs bg-gray-700 border border-gray-600 rounded text-white focus:outline-none focus:border-orange-400">
                    <option value="all">All Types</option>
                    <option value="baselinker">BaseLinker</option>
                    <option value="subiekt_gt">Subiekt GT</option>
                    <option value="dynamics">Dynamics</option>
                </select>
                <select wire:model.live="statusFilter"
                        class="px-2 py-1 text-xs bg-gray-700 border border-gray-600 rounded text-white focus:outline-none focus:border-orange-400">
                    <option value="all">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="connected">Connected</option>
                    <option value="issues">Has Issues</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
    @if(session()->has('success'))
        <div class="mx-4 mt-4 p-3 bg-green-900/50 border border-green-700 rounded-md text-green-300 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if(session()->has('error'))
        <div class="mx-4 mt-4 p-3 bg-red-900/50 border border-red-700 rounded-md text-red-300 text-sm">
            {{ session('error') }}
        </div>
    @endif

    <!-- Main Content -->
    <div class="p-4">
        @if($connections->isEmpty())
            <!-- Compact Empty State -->
            <div class="text-center py-8 bg-gray-800/30 rounded-lg border border-gray-700">
                <svg class="w-16 h-16 mx-auto text-gray-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                <h3 class="text-sm font-medium text-white mb-2">No ERP connections</h3>
                <p class="text-xs text-gray-400 mb-4">Add your first integration to get started</p>
                <button wire:click="startWizard"
                        class="inline-flex items-center px-4 py-2 text-xs font-medium text-white rounded-md btn-enterprise-primary">
                    <svg class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Add Connection
                </button>
            </div>
        @else
            <!-- Compact Connections Table -->
            <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
                <table class="w-full text-xs">
                    <thead class="bg-gray-700/50">
                        <tr class="border-b border-gray-600">
                            <th class="text-left py-2 px-3 text-gray-300 font-medium cursor-pointer" wire:click="sortBy('instance_name')">
                                Connection
                                @if($sortBy === 'instance_name')
                                    <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </th>
                            <th class="text-left py-2 px-3 text-gray-300 font-medium cursor-pointer" wire:click="sortBy('erp_type')">
                                Type
                                @if($sortBy === 'erp_type')
                                    <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </th>
                            <th class="text-left py-2 px-3 text-gray-300 font-medium">Status</th>
                            <th class="text-left py-2 px-3 text-gray-300 font-medium cursor-pointer" wire:click="sortBy('last_sync_at')">
                                Last Sync
                                @if($sortBy === 'last_sync_at')
                                    <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </th>
                            <th class="text-left py-2 px-3 text-gray-300 font-medium">Success Rate</th>
                            <th class="text-left py-2 px-3 text-gray-300 font-medium cursor-pointer" wire:click="sortBy('priority')">
                                Priority
                                @if($sortBy === 'priority')
                                    <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </th>
                            <th class="text-right py-2 px-3 text-gray-300 font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($connections as $connection)
                            <tr wire:key="connection-{{ $connection->id }}" class="border-b border-gray-700/50 hover:bg-gray-700/30 transition-colors duration-150">
                                <!-- Connection Name -->
                                <td class="py-2 px-3">
                                    <div class="flex items-center gap-2">
                                        @if(!$connection->is_active)
                                            <span class="w-2 h-2 bg-gray-500 rounded-full" title="Inactive"></span>
                                        @endif
                                        <div>
                                            <div class="font-medium text-white">{{ $connection->instance_name }}</div>
                                            <div class="text-gray-400 text-xs">{{ Str::limit($connection->description, 40) ?: 'No description' }}</div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Type -->
                                <td class="py-2 px-3">
                                    @php
                                        $typeColors = [
                                            'baselinker' => 'bg-blue-600/20 text-blue-400 border-blue-500/30',
                                            'subiekt_gt' => 'bg-purple-600/20 text-purple-400 border-purple-500/30',
                                            'dynamics' => 'bg-green-600/20 text-green-400 border-green-500/30',
                                        ];
                                        $typeLabels = [
                                            'baselinker' => 'BaseLinker',
                                            'subiekt_gt' => 'Subiekt GT',
                                            'dynamics' => 'Dynamics',
                                        ];
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium border {{ $typeColors[$connection->erp_type] ?? 'bg-gray-700 text-gray-300' }}">
                                        {{ $typeLabels[$connection->erp_type] ?? $connection->erp_type }}
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="py-2 px-3">
                                    <div class="flex items-center gap-2">
                                        @php
                                            $statusColors = [
                                                'connected' => 'bg-green-500',
                                                'disconnected' => 'bg-gray-500',
                                                'error' => 'bg-red-500',
                                                'maintenance' => 'bg-yellow-500',
                                                'rate_limited' => 'bg-orange-500',
                                            ];
                                            $statusLabels = [
                                                'connected' => 'Connected',
                                                'disconnected' => 'Disconnected',
                                                'error' => 'Error',
                                                'maintenance' => 'Maintenance',
                                                'rate_limited' => 'Rate Limited',
                                            ];
                                        @endphp
                                        <div class="w-2 h-2 rounded-full {{ $statusColors[$connection->connection_status] ?? 'bg-gray-500' }}"
                                             wire:loading.class="animate-pulse"
                                             wire:target="testConnection({{ $connection->id }})">
                                        </div>
                                        <span class="text-white text-xs">
                                            {{ $statusLabels[$connection->connection_status] ?? 'Unknown' }}
                                        </span>
                                        @if($connection->last_error_message)
                                            <span class="text-red-400 text-xs" title="{{ $connection->last_error_message }}">
                                                ⚠
                                            </span>
                                        @endif
                                    </div>
                                    <!-- Auth Status -->
                                    <div class="text-xs text-gray-400 mt-0.5">
                                        Auth:
                                        <span class="{{ $connection->auth_status === 'authenticated' ? 'text-green-400' : 'text-yellow-400' }}">
                                            {{ ucfirst($connection->auth_status ?? 'pending') }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Last Sync -->
                                <td class="py-2 px-3 text-gray-300">
                                    @if($connection->last_sync_at)
                                        <div>{{ $connection->last_sync_at->diffForHumans() }}</div>
                                        <div class="text-xs text-gray-500">{{ $connection->last_sync_at->format('Y-m-d H:i') }}</div>
                                    @else
                                        <span class="text-gray-500">Never</span>
                                    @endif
                                </td>

                                <!-- Success Rate -->
                                <td class="py-2 px-3">
                                    @php
                                        $rate = $connection->sync_success_rate;
                                    @endphp
                                    @if($connection->sync_success_count + $connection->sync_error_count > 0)
                                        <span class="text-xs font-medium
                                                   {{ $rate >= 95 ? 'text-green-400' : ($rate >= 80 ? 'text-yellow-400' : 'text-red-400') }}">
                                            {{ number_format($rate, 1) }}%
                                        </span>
                                        <div class="text-xs text-gray-500">
                                            {{ $connection->sync_success_count }}/{{ $connection->sync_success_count + $connection->sync_error_count }}
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-xs">N/A</span>
                                    @endif
                                </td>

                                <!-- Priority -->
                                <td class="py-2 px-3">
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-medium
                                               {{ $connection->priority <= 3 ? 'bg-red-600/20 text-red-400' : ($connection->priority <= 5 ? 'bg-yellow-600/20 text-yellow-400' : 'bg-gray-600/20 text-gray-400') }}">
                                        {{ $connection->priority }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="py-2 px-3">
                                    <div class="flex items-center justify-end gap-1">
                                        <!-- Test Connection -->
                                        <button wire:click="testConnection({{ $connection->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="testConnection({{ $connection->id }})"
                                                class="inline-flex items-center px-2 py-1 text-xs font-medium
                                                       text-blue-400 hover:text-blue-300 hover:bg-blue-500/10
                                                       rounded transition-colors duration-150 disabled:opacity-50"
                                                title="Test Connection">
                                            <span wire:loading.remove wire:target="testConnection({{ $connection->id }})">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>
                                                </svg>
                                            </span>
                                            <span wire:loading wire:target="testConnection({{ $connection->id }})">
                                                <svg class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                            </span>
                                        </button>

                                        <!-- Sync Now -->
                                        <button wire:click="syncERP({{ $connection->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="syncERP({{ $connection->id }})"
                                                class="inline-flex items-center px-2 py-1 text-xs font-medium
                                                       text-green-400 hover:text-green-300 hover:bg-green-500/10
                                                       rounded transition-colors duration-150
                                                       {{ $connection->connection_status !== 'connected' ? 'opacity-50 cursor-not-allowed' : '' }}"
                                                {{ $connection->connection_status !== 'connected' ? 'disabled' : '' }}
                                                title="Sync Now">
                                            <span wire:loading.remove wire:target="syncERP({{ $connection->id }})">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                </svg>
                                            </span>
                                            <span wire:loading wire:target="syncERP({{ $connection->id }})">
                                                <svg class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                            </span>
                                        </button>

                                        <!-- Toggle Active -->
                                        <button wire:click="toggleConnectionStatus({{ $connection->id }})"
                                                class="inline-flex items-center px-2 py-1 text-xs font-medium
                                                       {{ $connection->is_active ? 'text-yellow-400 hover:text-yellow-300 hover:bg-yellow-500/10' : 'text-gray-400 hover:text-gray-300 hover:bg-gray-500/10' }}
                                                       rounded transition-colors duration-150"
                                                title="{{ $connection->is_active ? 'Deactivate' : 'Activate' }}">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                @if($connection->is_active)
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                                @else
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                @endif
                                            </svg>
                                        </button>

                                        <!-- Details -->
                                        <button wire:click="showDetails({{ $connection->id }})"
                                                class="inline-flex items-center px-2 py-1 text-xs font-medium
                                                       text-gray-400 hover:text-gray-300 hover:bg-gray-500/10
                                                       rounded transition-colors duration-150"
                                                title="View Details">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                        </button>

                                        <!-- Delete -->
                                        <button wire:click="deleteConnection({{ $connection->id }})"
                                                wire:confirm="Czy na pewno chcesz usunac polaczenie '{{ $connection->instance_name }}'?"
                                                class="inline-flex items-center px-2 py-1 text-xs font-medium
                                                       text-red-400 hover:text-red-300 hover:bg-red-500/10
                                                       rounded transition-colors duration-150"
                                                title="Delete">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-4">
                {{ $connections->links() }}
            </div>
        @endif
    </div>

    <!-- Add Connection Modal (Wizard) -->
    @if($showAddConnection)
        <div class="fixed inset-0 z-50 flex items-start justify-center pt-16 bg-black/60"
             x-data
             @keydown.escape.window="$wire.closeWizard()">
            <div class="bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[80vh] overflow-y-auto">
                <!-- Header -->
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-700">
                    <h2 class="text-lg font-semibold text-white">
                        Add ERP Connection - Step {{ $wizardStep }}/4
                    </h2>
                    <button wire:click="closeWizard" class="p-1 text-gray-400 hover:text-white hover:bg-gray-700 rounded">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Progress Bar -->
                <div class="px-4 py-2 bg-gray-700/30">
                    <div class="flex items-center justify-between text-xs text-gray-400 mb-1">
                        <span>Progress</span>
                        <span>{{ ($wizardStep / 4) * 100 }}%</span>
                    </div>
                    <div class="w-full bg-gray-700 rounded-full h-1">
                        <div class="bg-orange-500 h-1 rounded-full transition-all duration-300" style="width: {{ ($wizardStep / 4) * 100 }}%"></div>
                    </div>
                </div>

                <!-- Form Content -->
                <div class="p-4">
                    @if($wizardStep === 1)
                        <!-- Step 1: Basic Info -->
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-300 mb-1">ERP System Type *</label>
                                <select wire:model.live="connectionForm.erp_type"
                                        class="w-full px-3 py-2 text-sm bg-gray-700 border border-gray-600 rounded text-white focus:outline-none focus:border-orange-400">
                                    <option value="baselinker">BaseLinker</option>
                                    <option value="subiekt_gt">Subiekt GT (REST API)</option>
                                    <option value="dynamics">Microsoft Dynamics (Not implemented)</option>
                                </select>
                                @error('connectionForm.erp_type') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-300 mb-1">Connection Name *</label>
                                <input wire:model.blur="connectionForm.instance_name"
                                       type="text"
                                       class="w-full px-3 py-2 text-sm bg-gray-700 border border-gray-600 rounded text-white placeholder-gray-400 focus:outline-none focus:border-orange-400"
                                       placeholder="e.g., Main BaseLinker Account">
                                @error('connectionForm.instance_name') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-300 mb-1">Description</label>
                                <textarea wire:model.blur="connectionForm.description"
                                          rows="2"
                                          class="w-full px-3 py-2 text-sm bg-gray-700 border border-gray-600 rounded text-white placeholder-gray-400 focus:outline-none focus:border-orange-400"
                                          placeholder="Optional description..."></textarea>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-300 mb-1">Priority (1 = highest)</label>
                                <input wire:model.blur="connectionForm.priority"
                                       type="number"
                                       min="1"
                                       max="100"
                                       class="w-full px-3 py-2 text-sm bg-gray-700 border border-gray-600 rounded text-white focus:outline-none focus:border-orange-400">
                                @error('connectionForm.priority') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    @elseif($wizardStep === 2)
                        <!-- Step 2: ERP-specific Configuration -->
                        @if($connectionForm['erp_type'] === 'baselinker')
                            <div class="space-y-4">
                                <div class="p-3 bg-blue-900/30 border border-blue-700/50 rounded-md">
                                    <h4 class="text-sm font-medium text-blue-300 mb-1">BaseLinker Configuration</h4>
                                    <p class="text-xs text-blue-200/70">Get your API token from BaseLinker panel: Settings → API</p>
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-300 mb-1">API Token *</label>
                                    <input wire:model.blur="baselinkerConfig.api_token"
                                           type="password"
                                           class="w-full px-3 py-2 text-sm bg-gray-700 border border-gray-600 rounded text-white placeholder-gray-400 focus:outline-none focus:border-orange-400"
                                           placeholder="Your BaseLinker API token">
                                    @error('baselinkerConfig.api_token') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-300 mb-1">Inventory ID *</label>
                                    <input wire:model.blur="baselinkerConfig.inventory_id"
                                           type="number"
                                           class="w-full px-3 py-2 text-sm bg-gray-700 border border-gray-600 rounded text-white placeholder-gray-400 focus:outline-none focus:border-orange-400"
                                           placeholder="e.g., 123456">
                                    @error('baselinkerConfig.inventory_id') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                                    <p class="text-xs text-gray-500 mt-1">Find this in BaseLinker: Inventory → select inventory → URL contains inventory_id</p>
                                </div>
                            </div>
                        @elseif($connectionForm['erp_type'] === 'subiekt_gt')
                            <div class="space-y-4">
                                <div class="p-3 bg-purple-900/30 border border-purple-700/50 rounded-md">
                                    <h4 class="text-sm font-medium text-purple-300 mb-1">Subiekt GT - REST API</h4>
                                    <p class="text-xs text-purple-200/70">
                                        Polaczenie przez REST API Wrapper na serwerze Windows (sapi.mpptrade.pl).
                                        API obsluguje produkty, stany magazynowe, ceny i kontrahentow.
                                    </p>
                                </div>

                                {{-- REST API URL --}}
                                <div>
                                    <label class="block text-xs font-medium text-gray-300 mb-1">REST API URL *</label>
                                    <input wire:model.blur="subiektConfig.rest_api_url"
                                           type="url"
                                           class="w-full px-3 py-2 text-sm bg-gray-700 border border-gray-600 rounded text-white placeholder-gray-400 focus:outline-none focus:border-orange-400"
                                           placeholder="https://sapi.mpptrade.pl">
                                    @error('subiektConfig.rest_api_url') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                                    <p class="text-xs text-gray-500 mt-1">URL do REST API Subiekt GT na serwerze Windows</p>
                                </div>

                                {{-- API Key --}}
                                <div>
                                    <label class="block text-xs font-medium text-gray-300 mb-1">API Key *</label>
                                    <input wire:model="subiektConfig.rest_api_key"
                                           type="text"
                                           class="w-full px-3 py-2 text-sm bg-gray-700 border border-gray-600 rounded text-white placeholder-gray-400 focus:outline-none focus:border-orange-400"
                                           placeholder="Twoj klucz API">
                                    @error('subiektConfig.rest_api_key') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                                    <p class="text-xs text-gray-500 mt-1">Klucz API do autoryzacji (header X-API-Key)</p>
                                </div>

                                {{-- Timeout --}}
                                <div>
                                    <label class="block text-xs font-medium text-gray-300 mb-1">Timeout (sekundy)</label>
                                    <input wire:model.blur="subiektConfig.rest_api_timeout"
                                           type="number"
                                           min="5"
                                           max="120"
                                           class="w-full px-3 py-2 text-sm bg-gray-700 border border-gray-600 rounded text-white placeholder-gray-400 focus:outline-none focus:border-orange-400"
                                           placeholder="30">
                                    @error('subiektConfig.rest_api_timeout') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                                    <p class="text-xs text-gray-500 mt-1">Maksymalny czas oczekiwania na odpowiedz (5-120s)</p>
                                </div>

                                {{-- Success info --}}
                                <div class="p-3 bg-green-900/30 border border-green-700/50 rounded-md">
                                    <div class="flex items-start gap-2">
                                        <svg class="w-4 h-4 text-green-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <div>
                                            <p class="text-xs text-green-300 font-medium">REST API Ready</p>
                                            <p class="text-xs text-green-200/70 mt-0.5">
                                                API dziala na HTTPS przez IIS. Dostepne endpointy: /api/health, /api/products, /api/warehouses, /api/price-levels
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @elseif($connectionForm['erp_type'] === 'dynamics')
                            <div class="p-4 bg-yellow-900/30 border border-yellow-700/50 rounded-md">
                                <h4 class="text-sm font-medium text-yellow-300 mb-2">Microsoft Dynamics - Not Implemented</h4>
                                <p class="text-xs text-yellow-200/70">Dynamics integration requires OAuth2 and OData API. This will be implemented in a future update.</p>
                            </div>
                        @endif
                    @elseif($wizardStep === 3)
                        <!-- Step 3: Authentication Test -->
                        <div class="space-y-4">
                            <div class="p-3 bg-gray-700/50 rounded-md">
                                <h4 class="text-sm font-medium text-white mb-2">Authentication Test</h4>
                                <p class="text-xs text-gray-400">Testing connection to {{ $connectionForm['erp_type'] }}...</p>
                            </div>

                            @if($authTestResult)
                                <div class="p-4 rounded-md {{ $authTestResult['success'] ? 'bg-green-900/30 border border-green-700' : 'bg-red-900/30 border border-red-700' }}">
                                    <div class="flex items-center gap-2 mb-2">
                                        @if($authTestResult['success'])
                                            <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            <span class="text-green-300 font-medium">Connection Successful!</span>
                                        @else
                                            <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                            <span class="text-red-300 font-medium">Connection Failed</span>
                                        @endif
                                    </div>
                                    <p class="text-xs {{ $authTestResult['success'] ? 'text-green-200/70' : 'text-red-200/70' }}">
                                        {{ $authTestResult['message'] }}
                                    </p>

                                    @if($authTestResult['success'] && !empty($authTestResult['supported_features']))
                                        <div class="mt-3 pt-3 border-t border-green-700/50">
                                            <p class="text-xs text-green-300 mb-1">Supported Features:</p>
                                            <div class="flex flex-wrap gap-1">
                                                @foreach($authTestResult['supported_features'] as $featureName => $enabled)
                                                    @if($enabled === true)
                                                        <span class="px-2 py-0.5 bg-green-800/50 text-green-300 text-xs rounded">{{ ucfirst(str_replace('_', ' ', $featureName)) }}</span>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    @if($authTestResult['success'] && !empty($authTestResult['details']['inventories']))
                                        <div class="mt-3 pt-3 border-t border-green-700/50">
                                            <p class="text-xs text-green-300 mb-1">Available Inventories: {{ count($authTestResult['details']['inventories']) }}</p>
                                        </div>
                                    @endif

                                    {{-- Subiekt GT specific results --}}
                                    @if($authTestResult['success'] && $connectionForm['erp_type'] === 'subiekt_gt')
                                        <div class="mt-3 pt-3 border-t border-green-700/50 space-y-3">
                                            {{-- Database Stats --}}
                                            @if(!empty($authTestResult['details']['database_stats']))
                                                <div>
                                                    <p class="text-xs text-green-300 mb-1">Database Statistics:</p>
                                                    <div class="grid grid-cols-2 gap-2 text-xs">
                                                        <div class="bg-green-800/30 px-2 py-1 rounded">
                                                            <span class="text-gray-400">Products:</span>
                                                            <span class="text-white font-medium ml-1">{{ number_format($authTestResult['details']['database_stats']['product_count'] ?? 0) }}</span>
                                                        </div>
                                                        <div class="bg-green-800/30 px-2 py-1 rounded">
                                                            <span class="text-gray-400">Active:</span>
                                                            <span class="text-white font-medium ml-1">{{ number_format($authTestResult['details']['database_stats']['active_products'] ?? 0) }}</span>
                                                        </div>
                                                        @if(!empty($authTestResult['details']['database_stats']['contractor_count']))
                                                        <div class="bg-green-800/30 px-2 py-1 rounded">
                                                            <span class="text-gray-400">Contractors:</span>
                                                            <span class="text-white font-medium ml-1">{{ number_format($authTestResult['details']['database_stats']['contractor_count']) }}</span>
                                                        </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif

                                            {{-- Warehouses --}}
                                            @if(!empty($authTestResult['details']['warehouses']))
                                                <div>
                                                    <p class="text-xs text-green-300 mb-1">Available Warehouses ({{ count($authTestResult['details']['warehouses']) }}):</p>
                                                    <div class="flex flex-wrap gap-1">
                                                        @foreach($authTestResult['details']['warehouses'] as $warehouse)
                                                            <span class="px-2 py-0.5 bg-purple-800/50 text-purple-300 text-xs rounded">
                                                                {{ $warehouse['mag_Nazwa'] ?? $warehouse['name'] ?? 'ID: ' . ($warehouse['mag_Id'] ?? $warehouse['id'] ?? '?') }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif

                                            {{-- Price Types --}}
                                            @if(!empty($authTestResult['details']['price_types']))
                                                <div>
                                                    <p class="text-xs text-green-300 mb-1">Price Types ({{ count($authTestResult['details']['price_types']) }}):</p>
                                                    <div class="flex flex-wrap gap-1">
                                                        @foreach($authTestResult['details']['price_types'] as $priceType)
                                                            <span class="px-2 py-0.5 bg-blue-800/50 text-blue-300 text-xs rounded">
                                                                {{ $priceType['rc_Nazwa'] ?? $priceType['name'] ?? 'ID: ' . ($priceType['rc_Id'] ?? $priceType['id'] ?? '?') }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif

                                            {{-- Connection Mode Info --}}
                                            <div class="p-2 bg-purple-900/30 rounded">
                                                <p class="text-xs text-purple-300">
                                                    <span class="font-medium">Mode:</span>
                                                    @if($subiektConfig['connection_mode'] === 'sql_direct')
                                                        SQL Direct (Read-Only) - Full pull support, no push operations
                                                    @elseif($subiektConfig['connection_mode'] === 'rest_api')
                                                        REST API - Full read/write via Windows wrapper
                                                    @else
                                                        Sfera API - Full read/write via COM/OLE
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @elseif($testingConnection)
                                <div class="flex items-center justify-center py-8">
                                    <svg class="animate-spin w-8 h-8 text-orange-500" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                            @endif

                            <button wire:click="testAuthentication"
                                    wire:loading.attr="disabled"
                                    class="w-full px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md transition-colors">
                                <span wire:loading.remove wire:target="testAuthentication">Test Connection Again</span>
                                <span wire:loading wire:target="testAuthentication">Testing...</span>
                            </button>
                        </div>
                    @elseif($wizardStep === 4)
                        <!-- Step 4: Sync Settings -->
                        <div class="space-y-4">
                            <div class="p-3 bg-gray-700/50 rounded-md">
                                <h4 class="text-sm font-medium text-white mb-1">Sync Settings</h4>
                                <p class="text-xs text-gray-400">Configure what should be synchronized automatically.</p>
                            </div>

                            {{-- Subiekt GT: Warehouse & Price Type Mapping --}}
                            @if($connectionForm['erp_type'] === 'subiekt_gt' && $authTestResult && $authTestResult['success'])
                                <div class="p-3 bg-purple-900/30 border border-purple-700/50 rounded-md space-y-3">
                                    <h4 class="text-sm font-medium text-purple-300">Subiekt GT Mappings</h4>

                                    <div class="grid grid-cols-2 gap-4">
                                        {{-- Default Warehouse --}}
                                        <div>
                                            <label class="block text-xs font-medium text-gray-300 mb-1">Default Warehouse</label>
                                            <select wire:model="subiektConfig.default_warehouse_id"
                                                    class="w-full px-3 py-2 text-sm bg-gray-700 border border-gray-600 rounded text-white focus:outline-none focus:border-orange-400">
                                                @if(!empty($authTestResult['details']['warehouses']))
                                                    @foreach($authTestResult['details']['warehouses'] as $warehouse)
                                                        <option value="{{ $warehouse['mag_Id'] ?? $warehouse['id'] ?? 1 }}">
                                                            {{ $warehouse['mag_Nazwa'] ?? $warehouse['name'] ?? 'Warehouse ' . ($warehouse['mag_Id'] ?? '?') }}
                                                        </option>
                                                    @endforeach
                                                @else
                                                    <option value="1">Default (ID: 1)</option>
                                                @endif
                                            </select>
                                            <p class="text-xs text-gray-500 mt-1">Stock levels will be synced from this warehouse</p>
                                        </div>

                                        {{-- Default Price Type --}}
                                        <div>
                                            <label class="block text-xs font-medium text-gray-300 mb-1">Default Price Type</label>
                                            <select wire:model="subiektConfig.default_price_type_id"
                                                    class="w-full px-3 py-2 text-sm bg-gray-700 border border-gray-600 rounded text-white focus:outline-none focus:border-orange-400">
                                                @if(!empty($authTestResult['details']['price_types']))
                                                    @foreach($authTestResult['details']['price_types'] as $priceType)
                                                        <option value="{{ $priceType['rc_Id'] ?? $priceType['id'] ?? 1 }}">
                                                            {{ $priceType['rc_Nazwa'] ?? $priceType['name'] ?? 'Price Type ' . ($priceType['rc_Id'] ?? '?') }}
                                                        </option>
                                                    @endforeach
                                                @else
                                                    <option value="1">Default (ID: 1)</option>
                                                @endif
                                            </select>
                                            <p class="text-xs text-gray-500 mt-1">Prices will be synced from this price list</p>
                                        </div>
                                    </div>

                                    {{-- Create Missing Products Option --}}
                                    <div>
                                        <label class="flex items-center gap-2 text-sm text-gray-300">
                                            <input type="checkbox"
                                                   wire:model="subiektConfig.create_missing_products"
                                                   class="rounded border-gray-600 bg-gray-700 text-orange-500 focus:ring-orange-500">
                                            Create missing products in PPM during sync
                                        </label>
                                        <p class="text-xs text-gray-500 ml-6">If enabled, new products from Subiekt GT will be automatically created in PPM</p>
                                    </div>

                                    {{-- Sync Mode Note for SQL Direct --}}
                                    @if($subiektConfig['connection_mode'] === 'sql_direct')
                                        <div class="p-2 bg-yellow-900/30 border border-yellow-700/50 rounded text-xs text-yellow-300">
                                            <strong>Note:</strong> SQL Direct mode is read-only. Product push to Subiekt GT requires REST API or Sfera API mode.
                                        </div>
                                    @endif
                                </div>
                            @endif

                            <div>
                                <label class="block text-xs font-medium text-gray-300 mb-1">Sync Mode</label>
                                <select wire:model="connectionForm.sync_mode"
                                        class="w-full px-3 py-2 text-sm bg-gray-700 border border-gray-600 rounded text-white focus:outline-none focus:border-orange-400">
                                    @if($connectionForm['erp_type'] === 'subiekt_gt' && ($subiektConfig['connection_mode'] ?? 'sql_direct') === 'sql_direct')
                                        <option value="pull_only">Pull Only (Subiekt GT → PPM) - SQL Direct Mode</option>
                                        <option value="disabled">Disabled</option>
                                    @else
                                        <option value="bidirectional">Bidirectional (Push & Pull)</option>
                                        <option value="push_only">Push Only (PPM → ERP)</option>
                                        <option value="pull_only">Pull Only (ERP → PPM)</option>
                                        <option value="disabled">Disabled</option>
                                    @endif
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <label class="flex items-center gap-2 text-sm text-gray-300">
                                    <input type="checkbox" wire:model="connectionForm.auto_sync_products" class="rounded border-gray-600 bg-gray-700 text-orange-500 focus:ring-orange-500">
                                    Auto-sync Products
                                </label>
                                <label class="flex items-center gap-2 text-sm text-gray-300">
                                    <input type="checkbox" wire:model="connectionForm.auto_sync_stock" class="rounded border-gray-600 bg-gray-700 text-orange-500 focus:ring-orange-500">
                                    Auto-sync Stock
                                </label>
                                <label class="flex items-center gap-2 text-sm text-gray-300">
                                    <input type="checkbox" wire:model="connectionForm.auto_sync_prices" class="rounded border-gray-600 bg-gray-700 text-orange-500 focus:ring-orange-500">
                                    Auto-sync Prices
                                </label>
                                <label class="flex items-center gap-2 text-sm text-gray-300"
                                       @if($connectionForm['erp_type'] === 'subiekt_gt') title="Orders sync not supported for Subiekt GT" @endif>
                                    <input type="checkbox"
                                           wire:model="connectionForm.auto_sync_orders"
                                           class="rounded border-gray-600 bg-gray-700 text-orange-500 focus:ring-orange-500"
                                           @if($connectionForm['erp_type'] === 'subiekt_gt') disabled @endif>
                                    Auto-sync Orders
                                    @if($connectionForm['erp_type'] === 'subiekt_gt')
                                        <span class="text-xs text-gray-500">(N/A)</span>
                                    @endif
                                </label>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <label class="flex items-center gap-2 text-sm text-gray-300">
                                    <input type="checkbox" wire:model="connectionForm.notify_on_errors" class="rounded border-gray-600 bg-gray-700 text-orange-500 focus:ring-orange-500">
                                    Notify on Errors
                                </label>
                                <label class="flex items-center gap-2 text-sm text-gray-300">
                                    <input type="checkbox" wire:model="connectionForm.notify_on_auth_expire" class="rounded border-gray-600 bg-gray-700 text-orange-500 focus:ring-orange-500">
                                    Notify on Auth Expire
                                </label>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Footer -->
                <div class="flex items-center justify-between px-4 py-3 bg-gray-700/30 border-t border-gray-700">
                    <button wire:click="closeWizard" class="px-3 py-1.5 text-xs font-medium text-gray-300 hover:text-white border border-gray-600 hover:border-gray-500 rounded">
                        Cancel
                    </button>

                    <div class="flex items-center gap-2">
                        @if($wizardStep > 1)
                            <button wire:click="previousWizardStep" class="px-3 py-1.5 text-xs font-medium text-gray-300 hover:text-white border border-gray-600 hover:border-gray-500 rounded">
                                Previous
                            </button>
                        @endif

                        @if($wizardStep < 4)
                            <button wire:click="nextWizardStep" class="px-4 py-1.5 text-xs font-medium text-white btn-enterprise-primary rounded">
                                Next
                            </button>
                        @else
                            <button wire:click="completeWizard"
                                    class="px-4 py-1.5 text-xs font-medium text-white btn-enterprise-primary rounded"
                                    {{ !$authTestResult || !$authTestResult['success'] ? 'disabled' : '' }}>
                                Create Connection
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Connection Details Modal -->
    @if($showConnectionDetails && $selectedConnection)
        <div class="fixed inset-0 z-50 flex items-start justify-center pt-16 bg-black/60"
             x-data
             @keydown.escape.window="$wire.closeDetails()">
            <div class="bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[80vh] overflow-y-auto">
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-700">
                    <h2 class="text-lg font-semibold text-white">{{ $selectedConnection->instance_name }}</h2>
                    <button wire:click="closeDetails" class="p-1 text-gray-400 hover:text-white hover:bg-gray-700 rounded">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="p-4 space-y-4">
                    <!-- Basic Info -->
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-400">Type:</span>
                            <span class="text-white ml-2">{{ ucfirst($selectedConnection->erp_type) }}</span>
                        </div>
                        <div>
                            <span class="text-gray-400">Status:</span>
                            <span class="text-white ml-2">{{ ucfirst($selectedConnection->connection_status) }}</span>
                        </div>
                        <div>
                            <span class="text-gray-400">Auth Status:</span>
                            <span class="text-white ml-2">{{ ucfirst($selectedConnection->auth_status) }}</span>
                        </div>
                        <div>
                            <span class="text-gray-400">Priority:</span>
                            <span class="text-white ml-2">{{ $selectedConnection->priority }}</span>
                        </div>
                        <div>
                            <span class="text-gray-400">Sync Mode:</span>
                            <span class="text-white ml-2">{{ ucfirst(str_replace('_', ' ', $selectedConnection->sync_mode)) }}</span>
                        </div>
                        <div>
                            <span class="text-gray-400">Rate Limit:</span>
                            <span class="text-white ml-2">{{ $selectedConnection->rate_limit_per_minute }}/min</span>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="p-3 bg-gray-700/50 rounded-md">
                        <h4 class="text-sm font-medium text-white mb-2">Sync Statistics</h4>
                        <div class="grid grid-cols-3 gap-4 text-sm">
                            <div>
                                <span class="text-gray-400">Success:</span>
                                <span class="text-green-400 ml-2">{{ $selectedConnection->sync_success_count }}</span>
                            </div>
                            <div>
                                <span class="text-gray-400">Errors:</span>
                                <span class="text-red-400 ml-2">{{ $selectedConnection->sync_error_count }}</span>
                            </div>
                            <div>
                                <span class="text-gray-400">Records:</span>
                                <span class="text-white ml-2">{{ number_format($selectedConnection->records_synced_total) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Sync Jobs -->
                    @if($selectedConnection->syncJobs && $selectedConnection->syncJobs->count() > 0)
                        <div class="p-3 bg-gray-700/50 rounded-md">
                            <h4 class="text-sm font-medium text-white mb-2">Recent Sync Jobs</h4>
                            <div class="space-y-2">
                                @foreach($selectedConnection->syncJobs as $job)
                                    <div class="flex items-center justify-between text-xs py-1 border-b border-gray-600 last:border-0">
                                        <span class="text-gray-300">{{ $job->job_name }}</span>
                                        <span class="{{ $job->status === 'completed' ? 'text-green-400' : ($job->status === 'failed' ? 'text-red-400' : 'text-yellow-400') }}">
                                            {{ ucfirst($job->status) }}
                                        </span>
                                        <span class="text-gray-500">{{ $job->created_at->diffForHumans() }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Last Error -->
                    @if($selectedConnection->last_error_message)
                        <div class="p-3 bg-red-900/30 border border-red-700 rounded-md">
                            <h4 class="text-sm font-medium text-red-300 mb-1">Last Error</h4>
                            <p class="text-xs text-red-200/70">{{ $selectedConnection->last_error_message }}</p>
                        </div>
                    @endif
                </div>

                <div class="flex items-center justify-end px-4 py-3 bg-gray-700/30 border-t border-gray-700">
                    <button wire:click="closeDetails" class="px-4 py-1.5 text-xs font-medium text-gray-300 hover:text-white border border-gray-600 hover:border-gray-500 rounded">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
