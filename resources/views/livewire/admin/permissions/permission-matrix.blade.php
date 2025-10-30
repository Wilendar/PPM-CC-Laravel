<div x-data="permissionMatrix()" class="max-w-7xl mx-auto">
    <!-- Header z role selector -->
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white">
                Macierz Uprawnień
            </h1>
            <p class="text-gray-400 mt-1">
                Zarządzaj uprawnieniami dla 7 poziomów użytkowników PPM
            </p>
        </div>
        
        <!-- Action buttons -->
        <div class="flex space-x-2">
            @if($hasChanges)
                <button wire:click="saveChanges" 
                        wire:loading.attr="disabled"
                        class="btn btn-success">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Zapisz zmiany
                </button>
                
                <button wire:click="discardChanges" class="btn btn-secondary">
                    Odrzuć
                </button>
            @endif
            
            <button @click="showTemplates = true" class="btn btn-outline-primary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Szablony
            </button>
        </div>
    </div>

    <!-- Role selector i stats -->
    <div class="bg-gray-800 rounded-lg shadow-sm border border-gray-700 p-6 mb-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Role selector -->
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">
                    Wybierz rolę:
                </label>
                <select wire:model="selectedRole" 
                        class="w-full px-3 py-2 border border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                    <option value="">-- Wybierz rolę --</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" 
                                class="flex items-center">
                            {{ $role->name }}
                            ({{ $role->users_count ?? 0 }} użytkowników)
                        </option>
                    @endforeach
                </select>
            </div>
            
            <!-- Role info -->
            @if($selectedRoleData)
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">
                    Informacje o roli:
                </label>
                <div class="space-y-1 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-400">Nazwa:</span>
                        <span class="font-medium">{{ $selectedRoleData->name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Użytkowników:</span>
                        <span class="font-medium">{{ $selectedRoleData->users->count() }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Uprawnień:</span>
                        <span class="font-medium">{{ $selectedRoleData->permissions->count() }}/{{ array_sum(array_column($moduleStats, 'total')) }}</span>
                    </div>
                </div>
            </div>
            @endif
            
            <!-- Quick actions -->
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">
                    Szybkie akcje:
                </label>
                <div class="flex flex-wrap gap-2">
                    <button wire:click="applyTemplate('read_only')" 
                            class="px-3 py-1 text-xs bg-blue-100 text-blue-800 rounded-full hover:bg-blue-200 transition-colors">
                        Tylko odczyt
                    </button>
                    <button wire:click="applyTemplate('manager_level')" 
                            class="px-3 py-1 text-xs bg-green-100 text-green-800 rounded-full hover:bg-green-200 transition-colors">
                        Poziom Menadżer
                    </button>
                    <button wire:click="applyTemplate('full_access')" 
                            class="px-3 py-1 text-xs bg-red-100 text-red-800 rounded-full hover:bg-red-200 transition-colors">
                        Pełny dostęp
                    </button>
                </div>
            </div>
        </div>
        
        @if($hasChanges)
        <div class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-yellow-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"></path>
                </svg>
                <span class="text-yellow-800 dark:text-yellow-200 font-medium">
                    Masz niezapisane zmiany uprawnień
                </span>
            </div>
        </div>
        @endif
    </div>

    @if($selectedRole && !empty($permissionsByModule))
    <!-- Bulk operations toolbar -->
    <div class="bg-gray-800 rounded-lg shadow-sm border border-gray-700 p-4 mb-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex items-center space-x-4">
                @if(!$bulkSelectMode)
                    <button wire:click="enableBulkSelect" class="btn btn-outline-secondary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Operacje masowe
                    </button>
                @else
                    <button wire:click="disableBulkSelect" class="btn btn-secondary">
                        Anuluj
                    </button>
                    
                    <div class="flex items-center space-x-2">
                        <button wire:click="selectAllPermissions" class="text-sm text-blue-600 hover:text-blue-800">
                            Zaznacz wszystkie
                        </button>
                        <span class="text-gray-400">|</span>
                        <button wire:click="deselectAllPermissions" class="text-sm text-blue-600 hover:text-blue-800">
                            Odznacz wszystkie
                        </button>
                    </div>
                @endif
            </div>
            
            @if($bulkSelectMode && !empty($selectedPermissions))
            <div class="flex items-center space-x-2">
                <span class="text-sm text-gray-400">
                    Wybrano: {{ count($selectedPermissions) }}
                </span>
                
                <select wire:model="bulkAction" 
                        class="px-3 py-1 text-sm border border-gray-600 rounded focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                    <option value="">-- Akcja --</option>
                    <option value="enable">Włącz</option>
                    <option value="disable">Wyłącz</option>
                </select>
                
                <button wire:click="executeBulkAction" 
                        :disabled="!$wire.bulkAction"
                        class="btn btn-primary btn-sm">
                    Wykonaj
                </button>
            </div>
            @endif
        </div>
    </div>

    <!-- Permission matrix -->
    <div class="bg-gray-800 rounded-lg shadow-sm border border-gray-700">
        @foreach($permissionsByModule as $moduleName => $permissions)
        <div class="border-b border-gray-700 last:border-b-0">
            <!-- Module header -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <button wire:click="toggleModuleExpansion('{{ $moduleName }}')" 
                                class="p-1 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                            <svg class="w-4 h-4 transform transition-transform {{ $expandedModules[$moduleName] ?? true ? 'rotate-90' : '' }}" 
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                        
                        <h3 class="text-lg font-semibold text-white">
                            {{ $moduleName }}
                        </h3>
                        
                        <button wire:click="toggleModule('{{ $moduleName }}')" 
                                class="px-3 py-1 text-xs bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-full transition-colors">
                            {{ $moduleStats[$moduleName]['enabled'] ?? 0 === $moduleStats[$moduleName]['total'] ?? 0 ? 'Wyłącz wszystkie' : 'Włącz wszystkie' }}
                        </button>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <!-- Progress bar -->
                        <div class="flex items-center space-x-2">
                            <div class="w-24 h-2 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-green-400 to-green-600 transition-all duration-300"
                                     style="width: {{ $moduleStats[$moduleName]['percentage'] ?? 0 }}%"></div>
                            </div>
                            <span class="text-sm font-medium text-gray-300">
                                {{ $moduleStats[$moduleName]['enabled'] ?? 0 }}/{{ $moduleStats[$moduleName]['total'] ?? 0 }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Permissions list -->
            @if($expandedModules[$moduleName] ?? true)
            <div class="px-6 py-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($permissions as $permission)
                    <div class="flex items-center justify-between p-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-700/50 transition-colors">
                        <div class="flex items-center space-x-3">
                            @if($bulkSelectMode)
                                <input type="checkbox" 
                                       wire:click="togglePermissionSelection({{ $permission->id }})"
                                       :checked="$wire.selectedPermissions.includes({{ $permission->id }})"
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            @endif
                            
                            <div>
                                <div class="font-medium text-sm text-white">
                                    {{ str_replace('.', ' › ', $permission->name) }}
                                </div>
                                @if($permission->description)
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {{ $permission->description }}
                                </div>
                                @endif
                            </div>
                        </div>
                        
                        <div class="flex items-center space-x-2">
                            <!-- Permission status indicator -->
                            <div class="flex items-center space-x-1">
                                @if($permissionMatrix[$permission->id] ?? false)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        Włączone
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                        Wyłączone
                                    </span>
                                @endif
                            </div>
                            
                            <!-- Toggle switch -->
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" 
                                       wire:click="togglePermission({{ $permission->id }})"
                                       :checked="{{ $permissionMatrix[$permission->id] ?? false ? 'true' : 'false' }}"
                                       class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-gray-800 after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                            </label>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @endforeach
    </div>
    @else
    <!-- Empty state -->
    <div class="bg-gray-800 rounded-lg shadow-sm border border-gray-700 p-12 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-1a2 2 0 00-2-2H6a2 2 0 00-2 2v1a2 2 0 002 2zM12 15V9m0 0l3 3m-3-3l-3 3"></path>
        </svg>
        <h3 class="mt-4 text-sm font-medium text-white">Wybierz rolę</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Wybierz rolę z listy powyżej, aby zarządzać jej uprawnieniami.
        </p>
    </div>
    @endif

    <!-- Templates Modal -->
    <div x-show="showTemplates" 
         x-transition:enter="transition ease-out duration-300" 
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100"
         class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showTemplates = false"></div>
            
            <div class="inline-block align-bottom bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-white mb-4">
                        Szablony uprawnień
                    </h3>
                    
                    <!-- Predefined templates -->
                    <div class="space-y-2 mb-6">
                        <h4 class="text-sm font-medium text-gray-300">Predefiniowane szablony:</h4>
                        
                        <button wire:click="applyTemplate('read_only')" 
                                @click="showTemplates = false"
                                class="w-full text-left p-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-700 transition-colors">
                            <div class="font-medium">Tylko odczyt</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Podstawowe uprawnienia do przeglądania</div>
                        </button>
                        
                        <button wire:click="applyTemplate('editor_level')" 
                                @click="showTemplates = false"
                                class="w-full text-left p-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-700 transition-colors">
                            <div class="font-medium">Poziom Redaktor</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Edycja opisów, zdjęć, kategorii</div>
                        </button>
                        
                        <button wire:click="applyTemplate('manager_level')" 
                                @click="showTemplates = false"
                                class="w-full text-left p-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-700 transition-colors">
                            <div class="font-medium">Poziom Menadżer</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">CRUD produktów + import/export</div>
                        </button>
                        
                        <button wire:click="applyTemplate('full_access')" 
                                @click="showTemplates = false"
                                class="w-full text-left p-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-700 transition-colors">
                            <div class="font-medium">Pełny dostęp</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Wszystkie uprawnienia systemowe</div>
                        </button>
                    </div>
                    
                    <!-- Custom templates -->
                    @if(!empty($customTemplates))
                    <div class="space-y-2">
                        <h4 class="text-sm font-medium text-gray-300">Niestandardowe szablony:</h4>
                        
                        @foreach($customTemplates as $templateName => $template)
                        <div class="flex items-center justify-between p-3 rounded-lg border border-gray-200 dark:border-gray-600">
                            <div>
                                <div class="font-medium">{{ $template['name'] }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ count($template['permissions']) }} uprawnień
                                    • {{ $template['created_by'] }}
                                    • {{ \Carbon\Carbon::parse($template['created_at'])->format('d.m.Y') }}
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <button wire:click="applyTemplate('{{ $templateName }}')" 
                                        @click="showTemplates = false"
                                        class="text-blue-600 hover:text-blue-800 text-sm">
                                    Zastosuj
                                </button>
                                <button wire:click="deleteCustomTemplate('{{ $templateName }}')" 
                                        class="text-red-600 hover:text-red-800 text-sm">
                                    Usuń
                                </button>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
                
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button @click="showSaveTemplate = true" 
                            class="btn btn-primary mb-2 sm:mb-0 sm:ml-2">
                        Zapisz jako szablon
                    </button>
                    <button @click="showTemplates = false" 
                            class="btn btn-secondary">
                        Zamknij
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading overlay -->
    <div wire:loading wire:target="saveChanges" 
         class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-gray-800 rounded-lg p-6 flex items-center space-x-3">
            <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-gray-300">Zapisywanie uprawnień...</span>
        </div>
    </div>
</div>

<script>
function permissionMatrix() {
    return {
        showTemplates: false,
        showSaveTemplate: false,
        
        init() {
            // Initialize component
        }
    }
}
</script>