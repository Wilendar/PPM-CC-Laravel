
<div id="flash-messages-container" class="fixed top-4 right-4 z-50 space-y-2">

    
    <?php if(session('success') || session('message')): ?>
        <div x-data="{ show: true }" 
             x-show="show" 
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 scale-95 translate-x-full"
             x-transition:enter-end="opacity-100 scale-100 translate-x-0"
             x-transition:leave="transition ease-in duration-300 transform"
             x-transition:leave-start="opacity-100 scale-100 translate-x-0"
             x-transition:leave-end="opacity-0 scale-95 translate-x-full"
             x-init="setTimeout(() => show = false, 5000)"
             class="max-w-md w-full bg-gray-800 shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden">
            <div class="p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-3 w-0 flex-1 pt-0.5">
                        <p class="text-sm font-medium text-white">
                            Sukces!
                        </p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            <?php echo e(session('success') ?? session('message')); ?>

                        </p>
                    </div>
                    <div class="ml-4 flex-shrink-0 flex">
                        <button @click="show = false"
                                class="bg-gray-800 rounded-md inline-flex text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <span class="sr-only">Close</span>
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="bg-green-50 dark:bg-green-900/20 px-4 py-2">
                <div class="bg-green-200 dark:bg-green-600 rounded-full h-1">
                    <div class="bg-green-400 dark:bg-green-500 h-1 rounded-full transition-all duration-5000 ease-linear" 
                         x-init="setTimeout(() => $el.style.width = '0%', 100)"
                         style="width: 100%"></div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    
    <?php if(session('error')): ?>
        <div x-data="{ show: true }" 
             x-show="show" 
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 scale-95 translate-x-full"
             x-transition:enter-end="opacity-100 scale-100 translate-x-0"
             x-transition:leave="transition ease-in duration-300 transform"
             x-transition:leave-start="opacity-100 scale-100 translate-x-0"
             x-transition:leave-end="opacity-0 scale-95 translate-x-full"
             x-init="setTimeout(() => show = false, 8000)"
             class="max-w-md w-full bg-gray-800 shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden">
            <div class="p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <div class="ml-3 w-0 flex-1 pt-0.5">
                        <p class="text-sm font-medium text-white">
                            Błąd
                        </p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            <?php echo e(session('error')); ?>

                        </p>
                    </div>
                    <div class="ml-4 flex-shrink-0 flex">
                        <button @click="show = false"
                                class="bg-gray-800 rounded-md inline-flex text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            <span class="sr-only">Close</span>
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="bg-red-50 dark:bg-red-900/20 px-4 py-2">
                <div class="bg-red-200 dark:bg-red-600 rounded-full h-1">
                    <div class="bg-red-400 dark:bg-red-500 h-1 rounded-full transition-all duration-8000 ease-linear" 
                         x-init="setTimeout(() => $el.style.width = '0%', 100)"
                         style="width: 100%"></div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    
    <?php if(session('warning')): ?>
        <div x-data="{ show: true }" 
             x-show="show" 
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 scale-95 translate-x-full"
             x-transition:enter-end="opacity-100 scale-100 translate-x-0"
             x-transition:leave="transition ease-in duration-300 transform"
             x-transition:leave-start="opacity-100 scale-100 translate-x-0"
             x-transition:leave-end="opacity-0 scale-95 translate-x-full"
             x-init="setTimeout(() => show = false, 6000)"
             class="max-w-md w-full bg-gray-800 shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden">
            <div class="p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <div class="ml-3 w-0 flex-1 pt-0.5">
                        <p class="text-sm font-medium text-white">
                            Ostrzeżenie
                        </p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            <?php echo e(session('warning')); ?>

                        </p>
                    </div>
                    <div class="ml-4 flex-shrink-0 flex">
                        <button @click="show = false"
                                class="bg-gray-800 rounded-md inline-flex text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                            <span class="sr-only">Close</span>
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="bg-yellow-50 dark:bg-yellow-900/20 px-4 py-2">
                <div class="bg-yellow-200 dark:bg-yellow-600 rounded-full h-1">
                    <div class="bg-yellow-400 dark:bg-yellow-500 h-1 rounded-full transition-all duration-6000 ease-linear" 
                         x-init="setTimeout(() => $el.style.width = '0%', 100)"
                         style="width: 100%"></div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    
    <?php if(session('info')): ?>
        <div x-data="{ show: true }" 
             x-show="show" 
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 scale-95 translate-x-full"
             x-transition:enter-end="opacity-100 scale-100 translate-x-0"
             x-transition:leave="transition ease-in duration-300 transform"
             x-transition:leave-start="opacity-100 scale-100 translate-x-0"
             x-transition:leave-end="opacity-0 scale-95 translate-x-full"
             x-init="setTimeout(() => show = false, 5000)"
             class="max-w-md w-full bg-gray-800 shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden">
            <div class="p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-3 w-0 flex-1 pt-0.5">
                        <p class="text-sm font-medium text-white">
                            Informacja
                        </p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            <?php echo e(session('info')); ?>

                        </p>
                    </div>
                    <div class="ml-4 flex-shrink-0 flex">
                        <button @click="show = false"
                                class="bg-gray-800 rounded-md inline-flex text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <span class="sr-only">Close</span>
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="bg-blue-50 dark:bg-blue-900/20 px-4 py-2">
                <div class="bg-blue-200 dark:bg-blue-600 rounded-full h-1">
                    <div class="bg-blue-400 dark:bg-blue-500 h-1 rounded-full transition-all duration-5000 ease-linear" 
                         x-init="setTimeout(() => $el.style.width = '0%', 100)"
                         style="width: 100%"></div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>


<script>
(function() {
    const ICONS = {
        success: '<svg class="h-6 w-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
        error: '<svg class="h-6 w-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z"></path></svg>',
        warning: '<svg class="h-6 w-6 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z"></path></svg>',
        info: '<svg class="h-6 w-6 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
    };

    const TITLES = {
        success: 'Sukces!',
        error: 'Blad',
        warning: 'Ostrzezenie',
        info: 'Informacja'
    };

    const DURATIONS = {
        success: 5000,
        error: 8000,
        warning: 6000,
        info: 5000
    };

    function showFlashMessage(type, message) {
        console.log('[FLASH] showFlashMessage:', type, message);
        const container = document.getElementById('flash-messages-container');
        if (!container) {
            console.error('[FLASH] Container not found!');
            return;
        }

        const id = 'flash-' + Date.now();
        const duration = DURATIONS[type] || 5000;

        const notification = document.createElement('div');
        notification.id = id;
        notification.className = 'max-w-xl w-full bg-gray-800 shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden transform translate-x-full opacity-0 transition-all duration-300';
        notification.innerHTML = `
            <div class="p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">${ICONS[type] || ICONS.info}</div>
                    <div class="ml-3 flex-1 min-w-0 pt-0.5">
                        <p class="text-sm font-medium text-white">${TITLES[type] || TITLES.info}</p>
                        <p class="mt-1 text-sm text-gray-400 break-words whitespace-pre-wrap">${message}</p>
                    </div>
                    <div class="ml-4 flex-shrink-0 flex">
                        <button class="bg-gray-800 rounded-md inline-flex text-gray-400 hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" onclick="window.flash.close('${id}')">
                            <span class="sr-only">Close</span>
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        `;

        container.insertBefore(notification, container.firstChild);

        // Animate in
        requestAnimationFrame(() => {
            notification.classList.remove('translate-x-full', 'opacity-0');
            notification.classList.add('translate-x-0', 'opacity-100');
        });

        // Auto-remove after duration
        setTimeout(() => window.flash.close(id), duration);
    }

    function closeFlashMessage(id) {
        const notification = document.getElementById(id);
        if (notification) {
            notification.classList.remove('translate-x-0', 'opacity-100');
            notification.classList.add('translate-x-full', 'opacity-0');
            setTimeout(() => notification.remove(), 300);
        }
    }

    // Global flash helper
    window.flash = {
        show: showFlashMessage,
        close: closeFlashMessage,
        success: (msg) => showFlashMessage('success', msg),
        error: (msg) => showFlashMessage('error', msg),
        warning: (msg) => showFlashMessage('warning', msg),
        info: (msg) => showFlashMessage('info', msg)
    };

    // Register Livewire listener
    document.addEventListener('livewire:init', () => {
        console.log('[FLASH] Livewire:init - registering flash-message listener');

        Livewire.on('flash-message', (params) => {
            console.log('[FLASH] Received flash-message event:', params);

            // Livewire 3.x passes params as array with single object
            const data = Array.isArray(params) ? params[0] : params;
            const type = data?.type || 'info';
            const message = data?.message || '';

            console.log('[FLASH] Calling showFlashMessage:', type, message);
            showFlashMessage(type, message);
        });
    });
})();
</script><?php /**PATH D:\Skrypty\PPM-CC-Laravel\resources\views/components/flash-messages.blade.php ENDPATH**/ ?>