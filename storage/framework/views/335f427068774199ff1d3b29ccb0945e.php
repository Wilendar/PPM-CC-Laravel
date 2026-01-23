
<div class="space-y-1">
    
    <a href="<?php echo e(route('dashboard')); ?>" 
       class="group flex items-center px-2 py-2 text-sm font-medium rounded-md 
              <?php echo e(request()->routeIs('dashboard') 
                  ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200' 
                  : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'); ?>">
        <svg class="mr-3 h-5 w-5 <?php echo e(request()->routeIs('dashboard') ? 'text-blue-500' : 'text-gray-400'); ?>" 
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6a2 2 0 01-2 2H10a2 2 0 01-2-2V5z"></path>
        </svg>
        Dashboard
    </a>

    
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('products.read')): ?>
    <a href="<?php echo e(route('products.index')); ?>" 
       class="group flex items-center px-2 py-2 text-sm font-medium rounded-md
              <?php echo e(request()->routeIs('products.*') 
                  ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200' 
                  : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'); ?>">
        <svg class="mr-3 h-5 w-5 <?php echo e(request()->routeIs('products.*') ? 'text-blue-500' : 'text-gray-400'); ?>" 
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
        </svg>
        Produkty
        
        <span class="ml-auto inline-block py-0.5 px-2 text-xs rounded-full bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
            12.5k
        </span>
    </a>
    <?php endif; ?>

    
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('categories.read')): ?>
    <a href="<?php echo e(route('categories.index')); ?>" 
       class="group flex items-center px-2 py-2 text-sm font-medium rounded-md
              <?php echo e(request()->routeIs('categories.*') 
                  ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200' 
                  : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'); ?>">
        <svg class="mr-3 h-5 w-5 <?php echo e(request()->routeIs('categories.*') ? 'text-blue-500' : 'text-gray-400'); ?>" 
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
        </svg>
        Kategorie
    </a>
    <?php endif; ?>

    
    <hr class="my-4 border-gray-200 dark:border-gray-700">

    
    <?php if (\Illuminate\Support\Facades\Blade::check('hasanyrole', 'Admin|Manager')): ?>
    <div class="space-y-1">
        <h3 class="px-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
            Zarządzanie
        </h3>
        
        
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('products.import')): ?>
        <a href="<?php echo e(route('import.index')); ?>"
           class="group flex items-center px-2 py-2 text-sm font-medium rounded-md
                  <?php echo e(request()->routeIs('import.*')
                      ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-200'
                      : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'); ?>">
            <svg class="mr-3 h-5 w-5 <?php echo e(request()->routeIs('import.*') ? 'text-green-500' : 'text-gray-400'); ?>"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
            </svg>
            Import/Export
        </a>
        <?php endif; ?>

        
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('products.import')): ?>
        <a href="<?php echo e(route('csv.import')); ?>"
           class="group flex items-center px-2 py-2 text-sm font-medium rounded-md
                  <?php echo e(request()->routeIs('csv.*')
                      ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-200'
                      : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'); ?>">
            <svg class="mr-3 h-5 w-5 <?php echo e(request()->routeIs('csv.*') ? 'text-green-500' : 'text-gray-400'); ?>"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            CSV Import/Export
            <span class="ml-auto inline-block py-0.5 px-2 text-xs rounded-full bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                Nowy
            </span>
        </a>
        <?php endif; ?>

        
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('products.manage')): ?>
        <a href="<?php echo e(route('compatibility.index')); ?>"
           class="group flex items-center px-2 py-2 text-sm font-medium rounded-md
                  <?php echo e(request()->routeIs('compatibility.*')
                      ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200'
                      : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'); ?>">
            <svg class="mr-3 h-5 w-5 <?php echo e(request()->routeIs('compatibility.*') ? 'text-blue-500' : 'text-gray-400'); ?>"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
            </svg>
            Dopasowania Części
        </a>
        <?php endif; ?>

        
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('integrations.sync')): ?>
        <a href="<?php echo e(route('sync.index')); ?>"
           class="group flex items-center px-2 py-2 text-sm font-medium rounded-md
                  <?php echo e(request()->routeIs('sync.*')
                      ? 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-200'
                      : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'); ?>">
            <svg class="mr-3 h-5 w-5 <?php echo e(request()->routeIs('sync.*') ? 'text-purple-500' : 'text-gray-400'); ?>"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            Synchronizacja
        </a>
        <?php endif; ?>
    </div>
    
    <hr class="my-4 border-gray-200 dark:border-gray-700">
    <?php endif; ?>

    
    
    
    <?php if (\Illuminate\Support\Facades\Blade::check('role', 'Admin')): ?>
    <div class="space-y-1">
        <h3 class="px-2 text-xs font-semibold text-red-500 dark:text-red-400 uppercase tracking-wider">
            Administracja
        </h3>
        
        <a href="<?php echo e(route('admin.users')); ?>" 
           class="group flex items-center px-2 py-2 text-sm font-medium rounded-md
                  <?php echo e(request()->routeIs('admin.users*') 
                      ? 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-200' 
                      : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'); ?>">
            <svg class="mr-3 h-5 w-5 <?php echo e(request()->routeIs('admin.users*') ? 'text-red-500' : 'text-gray-400'); ?>" 
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
            </svg>
            Użytkownicy
        </a>

        <a href="<?php echo e(route('admin.system')); ?>" 
           class="group flex items-center px-2 py-2 text-sm font-medium rounded-md
                  <?php echo e(request()->routeIs('admin.system*') 
                      ? 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-200' 
                      : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'); ?>">
            <svg class="mr-3 h-5 w-5 <?php echo e(request()->routeIs('admin.system*') ? 'text-red-500' : 'text-gray-400'); ?>" 
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            Ustawienia systemu
        </a>

        <a href="<?php echo e(route('admin.logs')); ?>" 
           class="group flex items-center px-2 py-2 text-sm font-medium rounded-md
                  <?php echo e(request()->routeIs('admin.logs*') 
                      ? 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-200' 
                      : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'); ?>">
            <svg class="mr-3 h-5 w-5 <?php echo e(request()->routeIs('admin.logs*') ? 'text-red-500' : 'text-gray-400'); ?>" 
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Logi systemowe
        </a>
    </div>
    
    <hr class="my-4 border-gray-200 dark:border-gray-700">
    <?php endif; ?>

    
    <?php if (\Illuminate\Support\Facades\Blade::check('hasrole', 'Warehouseman')): ?>
    <div class="space-y-1">
        <h3 class="px-2 text-xs font-semibold text-blue-500 dark:text-blue-400 uppercase tracking-wider">
            Magazyn
        </h3>
        
        <a href="<?php echo e(route('warehouse.deliveries')); ?>" 
           class="group flex items-center px-2 py-2 text-sm font-medium rounded-md
                  <?php echo e(request()->routeIs('warehouse.*') 
                      ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200' 
                      : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'); ?>">
            <svg class="mr-3 h-5 w-5 <?php echo e(request()->routeIs('warehouse.*') ? 'text-blue-500' : 'text-gray-400'); ?>" 
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
            </svg>
            Dostawy
        </a>
    </div>
    
    <hr class="my-4 border-gray-200 dark:border-gray-700">
    <?php endif; ?>

    
    <?php if (\Illuminate\Support\Facades\Blade::check('hasrole', 'Salesperson')): ?>
    <div class="space-y-1">
        <h3 class="px-2 text-xs font-semibold text-purple-500 dark:text-purple-400 uppercase tracking-wider">
            Sprzedaż
        </h3>
        
        <a href="<?php echo e(route('sales.orders')); ?>" 
           class="group flex items-center px-2 py-2 text-sm font-medium rounded-md
                  <?php echo e(request()->routeIs('sales.*') 
                      ? 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-200' 
                      : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'); ?>">
            <svg class="mr-3 h-5 w-5 <?php echo e(request()->routeIs('sales.*') ? 'text-purple-500' : 'text-gray-400'); ?>" 
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M8 11v6h8v-6M8 11h8"></path>
            </svg>
            Zamówienia
        </a>
    </div>
    
    <hr class="my-4 border-gray-200 dark:border-gray-700">
    <?php endif; ?>

    
    <?php if (\Illuminate\Support\Facades\Blade::check('hasrole', 'Claims')): ?>
    <div class="space-y-1">
        <h3 class="px-2 text-xs font-semibold text-teal-500 dark:text-teal-400 uppercase tracking-wider">
            Reklamacje
        </h3>
        
        <a href="<?php echo e(route('claims.index')); ?>" 
           class="group flex items-center px-2 py-2 text-sm font-medium rounded-md
                  <?php echo e(request()->routeIs('claims.*') 
                      ? 'bg-teal-100 text-teal-700 dark:bg-teal-900 dark:text-teal-200' 
                      : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'); ?>">
            <svg class="mr-3 h-5 w-5 <?php echo e(request()->routeIs('claims.*') ? 'text-teal-500' : 'text-gray-400'); ?>" 
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
            Panel reklamacji
        </a>
    </div>
    
    <hr class="my-4 border-gray-200 dark:border-gray-700">
    <?php endif; ?>

    
    <div class="space-y-1">
        <h3 class="px-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
            Konto
        </h3>
        
        
        <a href="<?php echo e(route('profile.edit')); ?>" 
           class="group flex items-center px-2 py-2 text-sm font-medium rounded-md
                  <?php echo e(request()->routeIs('profile.*') 
                      ? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200' 
                      : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'); ?>">
            <svg class="mr-3 h-5 w-5 <?php echo e(request()->routeIs('profile.*') ? 'text-gray-500' : 'text-gray-400'); ?>" 
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
            </svg>
            Profil użytkownika
        </a>

        
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('products.read')): ?>
        <a href="<?php echo e(route('search')); ?>" 
           class="group flex items-center px-2 py-2 text-sm font-medium rounded-md
                  <?php echo e(request()->routeIs('search*') 
                      ? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200' 
                      : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'); ?>">
            <svg class="mr-3 h-5 w-5 <?php echo e(request()->routeIs('search*') ? 'text-gray-500' : 'text-gray-400'); ?>" 
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            Wyszukiwarka
            <kbd class="ml-auto text-xs text-gray-500 dark:text-gray-400">Ctrl+K</kbd>
        </a>
        <?php endif; ?>
    </div>
</div>


<div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-200 dark:border-gray-700">
    <div class="text-xs text-gray-500 dark:text-gray-400 text-center">
        <p>PPM v1.0</p>
        <p class="mt-1">© <?php echo e(date('Y')); ?> MPP TRADE</p>
    </div>
</div><?php /**PATH D:\Skrypty\PPM-CC-Laravel\resources\views/layouts/navigation.blade.php ENDPATH**/ ?>