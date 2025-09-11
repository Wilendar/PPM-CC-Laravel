{{-- Flash Messages Component --}}
<div class="fixed top-4 right-4 z-50 space-y-2" x-data="flashMessages()" x-init="init()">
    {{-- Success Messages --}}
    @if(session('success'))
        <div x-data="{ show: true }" 
             x-show="show" 
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 scale-95 translate-x-full"
             x-transition:enter-end="opacity-100 scale-100 translate-x-0"
             x-transition:leave="transition ease-in duration-300 transform"
             x-transition:leave-start="opacity-100 scale-100 translate-x-0"
             x-transition:leave-end="opacity-0 scale-95 translate-x-full"
             x-init="setTimeout(() => show = false, 5000)"
             class="max-w-sm w-full bg-white dark:bg-gray-800 shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden">
            <div class="p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-3 w-0 flex-1 pt-0.5">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            Sukces!
                        </p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ session('success') }}
                        </p>
                    </div>
                    <div class="ml-4 flex-shrink-0 flex">
                        <button @click="show = false"
                                class="bg-white dark:bg-gray-800 rounded-md inline-flex text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <span class="sr-only">Close</span>
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            {{-- Progress bar --}}
            <div class="bg-green-50 dark:bg-green-900/20 px-4 py-2">
                <div class="bg-green-200 dark:bg-green-600 rounded-full h-1">
                    <div class="bg-green-400 dark:bg-green-500 h-1 rounded-full transition-all duration-5000 ease-linear" 
                         x-init="setTimeout(() => $el.style.width = '0%', 100)"
                         style="width: 100%"></div>
                </div>
            </div>
        </div>
    @endif

    {{-- Error Messages --}}
    @if(session('error'))
        <div x-data="{ show: true }" 
             x-show="show" 
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 scale-95 translate-x-full"
             x-transition:enter-end="opacity-100 scale-100 translate-x-0"
             x-transition:leave="transition ease-in duration-300 transform"
             x-transition:leave-start="opacity-100 scale-100 translate-x-0"
             x-transition:leave-end="opacity-0 scale-95 translate-x-full"
             x-init="setTimeout(() => show = false, 8000)"
             class="max-w-sm w-full bg-white dark:bg-gray-800 shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden">
            <div class="p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <div class="ml-3 w-0 flex-1 pt-0.5">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            Błąd
                        </p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ session('error') }}
                        </p>
                    </div>
                    <div class="ml-4 flex-shrink-0 flex">
                        <button @click="show = false"
                                class="bg-white dark:bg-gray-800 rounded-md inline-flex text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            <span class="sr-only">Close</span>
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            {{-- Progress bar --}}
            <div class="bg-red-50 dark:bg-red-900/20 px-4 py-2">
                <div class="bg-red-200 dark:bg-red-600 rounded-full h-1">
                    <div class="bg-red-400 dark:bg-red-500 h-1 rounded-full transition-all duration-8000 ease-linear" 
                         x-init="setTimeout(() => $el.style.width = '0%', 100)"
                         style="width: 100%"></div>
                </div>
            </div>
        </div>
    @endif

    {{-- Warning Messages --}}
    @if(session('warning'))
        <div x-data="{ show: true }" 
             x-show="show" 
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 scale-95 translate-x-full"
             x-transition:enter-end="opacity-100 scale-100 translate-x-0"
             x-transition:leave="transition ease-in duration-300 transform"
             x-transition:leave-start="opacity-100 scale-100 translate-x-0"
             x-transition:leave-end="opacity-0 scale-95 translate-x-full"
             x-init="setTimeout(() => show = false, 6000)"
             class="max-w-sm w-full bg-white dark:bg-gray-800 shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden">
            <div class="p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <div class="ml-3 w-0 flex-1 pt-0.5">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            Ostrzeżenie
                        </p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ session('warning') }}
                        </p>
                    </div>
                    <div class="ml-4 flex-shrink-0 flex">
                        <button @click="show = false"
                                class="bg-white dark:bg-gray-800 rounded-md inline-flex text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                            <span class="sr-only">Close</span>
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            {{-- Progress bar --}}
            <div class="bg-yellow-50 dark:bg-yellow-900/20 px-4 py-2">
                <div class="bg-yellow-200 dark:bg-yellow-600 rounded-full h-1">
                    <div class="bg-yellow-400 dark:bg-yellow-500 h-1 rounded-full transition-all duration-6000 ease-linear" 
                         x-init="setTimeout(() => $el.style.width = '0%', 100)"
                         style="width: 100%"></div>
                </div>
            </div>
        </div>
    @endif

    {{-- Info Messages --}}
    @if(session('info'))
        <div x-data="{ show: true }" 
             x-show="show" 
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 scale-95 translate-x-full"
             x-transition:enter-end="opacity-100 scale-100 translate-x-0"
             x-transition:leave="transition ease-in duration-300 transform"
             x-transition:leave-start="opacity-100 scale-100 translate-x-0"
             x-transition:leave-end="opacity-0 scale-95 translate-x-full"
             x-init="setTimeout(() => show = false, 5000)"
             class="max-w-sm w-full bg-white dark:bg-gray-800 shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden">
            <div class="p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-3 w-0 flex-1 pt-0.5">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            Informacja
                        </p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ session('info') }}
                        </p>
                    </div>
                    <div class="ml-4 flex-shrink-0 flex">
                        <button @click="show = false"
                                class="bg-white dark:bg-gray-800 rounded-md inline-flex text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <span class="sr-only">Close</span>
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            {{-- Progress bar --}}
            <div class="bg-blue-50 dark:bg-blue-900/20 px-4 py-2">
                <div class="bg-blue-200 dark:bg-blue-600 rounded-full h-1">
                    <div class="bg-blue-400 dark:bg-blue-500 h-1 rounded-full transition-all duration-5000 ease-linear" 
                         x-init="setTimeout(() => $el.style.width = '0%', 100)"
                         style="width: 100%"></div>
                </div>
            </div>
        </div>
    @endif
</div>

{{-- JavaScript for dynamic flash messages --}}
<script>
function flashMessages() {
    return {
        messages: [],
        
        init() {
            // Listen for Livewire flash messages
            window.addEventListener('livewire:init', () => {
                Livewire.on('flash-message', (data) => {
                    this.showMessage(data.type, data.message);
                });
            });
        },

        showMessage(type, message, duration = 5000) {
            const id = Date.now();
            const messageObj = {
                id: id,
                type: type,
                message: message,
                show: true
            };
            
            this.messages.push(messageObj);
            
            // Auto-remove message after duration
            setTimeout(() => {
                this.removeMessage(id);
            }, duration);
        },

        removeMessage(id) {
            const index = this.messages.findIndex(msg => msg.id === id);
            if (index > -1) {
                this.messages[index].show = false;
                // Remove from array after animation
                setTimeout(() => {
                    this.messages.splice(index, 1);
                }, 300);
            }
        },

        // Public methods for manual flash messages
        success(message) {
            this.showMessage('success', message);
        },

        error(message) {
            this.showMessage('error', message, 8000);
        },

        warning(message) {
            this.showMessage('warning', message, 6000);
        },

        info(message) {
            this.showMessage('info', message);
        }
    }
}

// Global flash message functions
window.flash = {
    success: (message) => Alpine.store('flash').success(message),
    error: (message) => Alpine.store('flash').error(message),
    warning: (message) => Alpine.store('flash').warning(message),
    info: (message) => Alpine.store('flash').info(message)
};

// Make flash messages available globally via Alpine store
document.addEventListener('alpine:init', () => {
    Alpine.store('flash', {
        messages: [],
        
        success(message) {
            this.add('success', message);
        },
        
        error(message) {
            this.add('error', message, 8000);
        },
        
        warning(message) {
            this.add('warning', message, 6000);
        },
        
        info(message) {
            this.add('info', message);
        },
        
        add(type, message, duration = 5000) {
            const id = Date.now() + Math.random();
            this.messages.push({
                id: id,
                type: type,
                message: message,
                show: true
            });
            
            setTimeout(() => {
                this.remove(id);
            }, duration);
        },
        
        remove(id) {
            const index = this.messages.findIndex(msg => msg.id === id);
            if (index > -1) {
                this.messages.splice(index, 1);
            }
        }
    });
});
</script>