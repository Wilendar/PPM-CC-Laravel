<div class="bg-gray-900 min-h-screen">
    <!-- Compact Header -->
    <div class="border-b border-gray-800 bg-gray-900/95 backdrop-blur-sm sticky top-0 z-40">
        <div class="flex items-center justify-between px-4 py-3">
            <div>
                <h1 class="text-lg font-semibold text-white">ERP Integrations</h1>
                <p class="text-xs text-gray-400 mt-0.5">Manage external system connections</p>
            </div>
            <div class="flex items-center gap-2">
                <button wire:click="refreshConnections" 
                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-300 hover:text-white 
                               bg-gray-800 hover:bg-gray-700 border border-gray-700 hover:border-gray-600 
                               rounded-md transition-colors duration-150">
                    <svg class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Refresh
                </button>
                <button x-data @click="$dispatch('open-modal', 'add-erp-modal')"
                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white 
                               rounded-md transition-colors duration-150"
                        style="background-color: #e0ac7e; hover:background-color: #d1975a;">
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
                    <span class="text-white font-medium">{{ count($connections ?? []) }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                    <span class="text-gray-400">Active:</span>
                    <span class="text-white font-medium">{{ collect($connections ?? [])->where('status', 'connected')->count() }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                    <span class="text-gray-400">Failed:</span>
                    <span class="text-white font-medium">{{ collect($connections ?? [])->where('status', 'error')->count() }}</span>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <input wire:model="search" 
                       type="text" 
                       class="px-2 py-1 text-xs bg-gray-700 border border-gray-600 rounded text-white 
                              placeholder-gray-400 focus:outline-none focus:border-orange-400 w-48"
                       placeholder="Search connections...">
                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="p-4">
        @if(empty($connections))
            <!-- Compact Empty State -->
            <div class="text-center py-8 bg-gray-800/30 rounded-lg border border-gray-700">
                <svg class="w-16 h-16 mx-auto text-gray-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                <h3 class="text-sm font-medium text-white mb-2">No ERP connections</h3>
                <p class="text-xs text-gray-400 mb-4">Add your first integration to get started</p>
                <button x-data @click="$dispatch('open-modal', 'add-erp-modal')"
                        class="inline-flex items-center px-4 py-2 text-xs font-medium text-white rounded-md"
                        style="background-color: #e0ac7e;">
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
                            <th class="text-left py-2 px-3 text-gray-300 font-medium">Connection</th>
                            <th class="text-left py-2 px-3 text-gray-300 font-medium">Type</th>
                            <th class="text-left py-2 px-3 text-gray-300 font-medium">Status</th>
                            <th class="text-left py-2 px-3 text-gray-300 font-medium">Last Sync</th>
                            <th class="text-left py-2 px-3 text-gray-300 font-medium">Success Rate</th>
                            <th class="text-left py-2 px-3 text-gray-300 font-medium">Version</th>
                            <th class="text-right py-2 px-3 text-gray-300 font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($connections as $connection)
                            <tr class="border-b border-gray-700/50 hover:bg-gray-700/30 transition-colors duration-150">
                                <!-- Connection Name -->
                                <td class="py-2 px-3">
                                    <div class="font-medium text-white">{{ $connection['name'] ?? 'Unknown ERP' }}</div>
                                    <div class="text-gray-400 text-xs">{{ $connection['host'] ?? 'No host configured' }}</div>
                                </td>
                                
                                <!-- Type -->
                                <td class="py-2 px-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-700 text-gray-300">
                                        {{ $connection['type'] ?? 'Unknown' }}
                                    </span>
                                </td>
                                
                                <!-- Status -->
                                <td class="py-2 px-3">
                                    <div class="flex items-center">
                                        <div class="w-2 h-2 rounded-full mr-2
                                                   @if(($connection['status'] ?? '') === 'connected') bg-green-500
                                                   @elseif(($connection['status'] ?? '') === 'error') bg-red-500
                                                   @elseif(($connection['status'] ?? '') === 'testing') bg-yellow-500
                                                   @else bg-gray-500 @endif">
                                        </div>
                                        <span class="text-white text-xs">{{ ucfirst($connection['status'] ?? 'Unknown') }}</span>
                                    </div>
                                </td>
                                
                                <!-- Last Sync -->
                                <td class="py-2 px-3 text-gray-300">{{ $connection['last_sync'] ?? 'Never' }}</td>
                                
                                <!-- Success Rate -->
                                <td class="py-2 px-3">
                                    @php
                                        $rate = $connection['success_rate'] ?? 'N/A';
                                        $rateNum = is_numeric(str_replace('%', '', $rate)) ? (int)str_replace('%', '', $rate) : 0;
                                    @endphp
                                    @if($rate !== 'N/A')
                                        <span class="text-xs font-medium 
                                                   @if($rateNum >= 95) text-green-400
                                                   @elseif($rateNum >= 80) text-yellow-400
                                                   @else text-red-400 @endif">
                                            {{ $rate }}
                                        </span>
                                    @else
                                        <span class="text-gray-400 text-xs">{{ $rate }}</span>
                                    @endif
                                </td>
                                
                                <!-- API Version -->
                                <td class="py-2 px-3 text-gray-300">{{ $connection['api_version'] ?? 'Unknown' }}</td>
                                
                                <!-- Actions -->
                                <td class="py-2 px-3">
                                    <div class="flex items-center justify-end gap-1">
                                        <button wire:click="testConnection({{ $connection['id'] ?? 0 }})" 
                                                class="inline-flex items-center px-2 py-1 text-xs font-medium 
                                                       text-blue-400 hover:text-blue-300 hover:bg-blue-500/10 
                                                       rounded transition-colors duration-150
                                                       @if(($connection['status'] ?? '') === 'testing') opacity-50 cursor-not-allowed @endif"
                                                @if(($connection['status'] ?? '') === 'testing') disabled @endif
                                                title="Test Connection">
                                            @if(($connection['status'] ?? '') === 'testing')
                                                <svg class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                            @else
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                          d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>
                                                </svg>
                                            @endif
                                        </button>
                                        
                                        <button wire:click="syncConnection({{ $connection['id'] ?? 0 }})" 
                                                class="inline-flex items-center px-2 py-1 text-xs font-medium 
                                                       text-green-400 hover:text-green-300 hover:bg-green-500/10 
                                                       rounded transition-colors duration-150
                                                       @if(($connection['status'] ?? '') !== 'connected') opacity-50 cursor-not-allowed @endif"
                                                @if(($connection['status'] ?? '') !== 'connected') disabled @endif
                                                title="Sync Now">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                      d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                            </svg>
                                        </button>
                                        
                                        <button wire:click="editConnection({{ $connection['id'] ?? 0 }})" 
                                                class="inline-flex items-center px-2 py-1 text-xs font-medium 
                                                       text-gray-400 hover:text-gray-300 hover:bg-gray-500/10 
                                                       rounded transition-colors duration-150"
                                                title="Configure">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                      d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>


    <!-- Compact Add ERP Connection Modal -->
    <div x-data="{ open: false }" 
         @open-modal.window="if ($event.detail === 'add-erp-modal') open = true"
         x-show="open" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-start justify-center pt-16 bg-black/40"
         @click.self="open = false"
         @keydown.escape.window="open = false"
         style="display: none;">
         
        <div x-show="open"
             x-transition:enter="transition ease-out duration-200 delay-75"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[80vh] overflow-y-auto">
             
            <!-- Compact Header -->
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-700">
                <h2 class="text-lg font-semibold text-white">Add ERP Connection</h2>
                <button @click="open = false" 
                        class="p-1 text-gray-400 hover:text-white hover:bg-gray-700 rounded transition-colors duration-150">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <!-- Compact Form -->
            <div class="p-4">
                <form wire:submit.prevent="saveConnection" class="space-y-3">
                    <!-- Type & Name Row -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-300 mb-1">ERP System Type</label>
                            <select wire:model="newConnection.type" 
                                    class="w-full px-3 py-2 text-sm bg-gray-700 border border-gray-600 rounded 
                                           text-white focus:outline-none focus:border-orange-400 transition-colors">
                                <option value="">Select type...</option>
                                <option value="baselinker">BaseLinker</option>
                                <option value="subiekt_gt">Subiekt GT</option>
                                <option value="dynamics">Microsoft Dynamics</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-300 mb-1">Connection Name</label>
                            <input wire:model="newConnection.name" 
                                   type="text" 
                                   class="w-full px-3 py-2 text-sm bg-gray-700 border border-gray-600 rounded 
                                          text-white placeholder-gray-400 focus:outline-none focus:border-orange-400 transition-colors"
                                   placeholder="e.g., Main BaseLinker">
                        </div>
                    </div>
                    
                    <!-- API URL -->
                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-1">API URL / Host</label>
                        <input wire:model="newConnection.host" 
                               type="url" 
                               class="w-full px-3 py-2 text-sm bg-gray-700 border border-gray-600 rounded 
                                      text-white placeholder-gray-400 focus:outline-none focus:border-orange-400 transition-colors"
                               placeholder="https://api.baselinker.com">
                    </div>
                    
                    <!-- Credentials Row -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-300 mb-1">API Key / Username</label>
                            <input wire:model="newConnection.api_key" 
                                   type="text" 
                                   class="w-full px-3 py-2 text-sm bg-gray-700 border border-gray-600 rounded 
                                          text-white placeholder-gray-400 focus:outline-none focus:border-orange-400 transition-colors"
                                   placeholder="API key or username">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-300 mb-1">API Secret / Password</label>
                            <input wire:model="newConnection.api_secret" 
                                   type="password" 
                                   class="w-full px-3 py-2 text-sm bg-gray-700 border border-gray-600 rounded 
                                          text-white placeholder-gray-400 focus:outline-none focus:border-orange-400 transition-colors"
                                   placeholder="API secret or password">
                        </div>
                    </div>
                    
                    <!-- Priority & Auto-sync Row -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-end">
                        <div>
                            <label class="block text-xs font-medium text-gray-300 mb-1">Priority</label>
                            <select wire:model="newConnection.priority" 
                                    class="w-full px-3 py-2 text-sm bg-gray-700 border border-gray-600 rounded 
                                           text-white focus:outline-none focus:border-orange-400 transition-colors">
                                <option value="1">High (5 min)</option>
                                <option value="2" selected>Normal (15 min)</option>
                                <option value="3">Low (1 hour)</option>
                            </select>
                        </div>
                        <div class="flex items-center pb-2">
                            <input wire:model="newConnection.auto_sync" 
                                   type="checkbox" 
                                   id="autoSync"
                                   class="w-3 h-3 rounded border-gray-600 bg-gray-700 
                                          focus:ring-orange-500 focus:ring-1"
                                   style="accent-color: #e0ac7e;">
                            <label for="autoSync" class="ml-2 text-xs text-gray-300">
                                Auto-sync enabled
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Compact Footer -->
            <div class="flex items-center justify-end gap-2 px-4 py-3 bg-gray-700/30 border-t border-gray-700">
                <button @click="open = false" 
                        class="px-3 py-1.5 text-xs font-medium text-gray-300 hover:text-white 
                               border border-gray-600 hover:border-gray-500 rounded transition-colors duration-150">
                    Cancel
                </button>
                
                <button wire:click="testNewConnection" 
                        class="px-3 py-1.5 text-xs font-medium text-blue-300 hover:text-blue-200 
                               bg-blue-600/20 hover:bg-blue-600/30 border border-blue-500/30 
                               rounded transition-colors duration-150">
                    Test
                </button>
                
                <button wire:click="saveConnection" 
                        @click="open = false"
                        class="px-3 py-1.5 text-xs font-medium text-white rounded transition-colors duration-150"
                        style="background-color: #e0ac7e; hover:background-color: #d1975a;">
                    Save Connection
                </button>
            </div>
        </div>
    </div>
</div>