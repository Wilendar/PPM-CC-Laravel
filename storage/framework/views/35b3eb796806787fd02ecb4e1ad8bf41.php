
<div class="min-h-screen bg-gray-100 dark:bg-gray-900">
    
    <div class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
                        Dashboard Bezpieczenstwa
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Monitoring bezpieczenstwa systemu i aktywnosci uzytkownikow
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <button wire:click="refreshStats"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                        <svg class="w-4 h-4 mr-2" wire:loading.class="animate-spin" wire:target="refreshStats" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Odswiez
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
            
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-green-100 dark:bg-green-900 rounded-lg p-3">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Aktywne sesje</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white"><?php echo e($activeSessionsCount); ?></p>
                    </div>
                </div>
            </div>

            
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-red-100 dark:bg-red-900 rounded-lg p-3">
                        <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Nieudane logowania</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white"><?php echo e($failedLoginsToday); ?></p>
                    </div>
                </div>
            </div>

            
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-yellow-100 dark:bg-yellow-900 rounded-lg p-3">
                        <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Podejrzane</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white"><?php echo e($suspiciousActivities); ?></p>
                    </div>
                </div>
            </div>

            
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-orange-100 dark:bg-orange-900 rounded-lg p-3">
                        <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Zablokowane</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white"><?php echo e($lockedAccounts); ?></p>
                    </div>
                </div>
            </div>

            
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-purple-100 dark:bg-purple-900 rounded-lg p-3">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Wygasajace hasla</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white"><?php echo e($expiringPasswords); ?></p>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
            <div class="border-b border-gray-200 dark:border-gray-700">
                <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                    <button wire:click="setTab('overview')"
                        class="py-4 px-1 border-b-2 font-medium text-sm <?php echo e($activeTab === 'overview' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400'); ?>">
                        Przeglad
                    </button>
                    <button wire:click="setTab('alerts')"
                        class="py-4 px-1 border-b-2 font-medium text-sm <?php echo e($activeTab === 'alerts' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400'); ?>">
                        Alerty
                        <!--[if BLOCK]><![endif]--><?php if($recentAlerts->count() > 0): ?>
                            <span class="ml-2 px-2 py-0.5 text-xs rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                <?php echo e($recentAlerts->count()); ?>

                            </span>
                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                    </button>
                    <button wire:click="setTab('logins')"
                        class="py-4 px-1 border-b-2 font-medium text-sm <?php echo e($activeTab === 'logins' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400'); ?>">
                        Historia logowan
                    </button>
                    <button wire:click="setTab('policies')"
                        class="py-4 px-1 border-b-2 font-medium text-sm <?php echo e($activeTab === 'policies' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400'); ?>">
                        Polityki hasel
                    </button>
                </nav>
            </div>

            <div class="p-6">
                <!--[if BLOCK]><![endif]--><?php if($activeTab === 'overview'): ?>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Ostatnie alerty</h3>
                                <!--[if BLOCK]><![endif]--><?php if($recentAlerts->count() > 0): ?>
                                    <button wire:click="acknowledgeAllAlerts"
                                        class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                        Potwierdz wszystkie
                                    </button>
                                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                            </div>

                            <!--[if BLOCK]><![endif]--><?php if($recentAlerts->count() > 0): ?>
                                <div class="space-y-3">
                                    <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $recentAlerts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $alert): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div class="flex items-start justify-between bg-white dark:bg-gray-800 rounded-lg p-3">
                                            <div class="flex items-start">
                                                <?php
                                                    $badge = $alert->getSeverityBadge();
                                                ?>
                                                <span class="flex-shrink-0 h-8 w-8 rounded-full flex items-center justify-center
                                                    <?php echo e($badge['color'] === 'red' ? 'bg-red-100 dark:bg-red-900' : ''); ?>

                                                    <?php echo e($badge['color'] === 'orange' ? 'bg-orange-100 dark:bg-orange-900' : ''); ?>

                                                    <?php echo e($badge['color'] === 'yellow' ? 'bg-yellow-100 dark:bg-yellow-900' : ''); ?>

                                                    <?php echo e($badge['color'] === 'blue' ? 'bg-blue-100 dark:bg-blue-900' : ''); ?>">
                                                    <svg class="w-4 h-4
                                                        <?php echo e($badge['color'] === 'red' ? 'text-red-600 dark:text-red-400' : ''); ?>

                                                        <?php echo e($badge['color'] === 'orange' ? 'text-orange-600 dark:text-orange-400' : ''); ?>

                                                        <?php echo e($badge['color'] === 'yellow' ? 'text-yellow-600 dark:text-yellow-400' : ''); ?>

                                                        <?php echo e($badge['color'] === 'blue' ? 'text-blue-600 dark:text-blue-400' : ''); ?>"
                                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                                    </svg>
                                                </span>
                                                <div class="ml-3">
                                                    <p class="text-sm font-medium text-gray-900 dark:text-white"><?php echo e($alert->title); ?></p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400"><?php echo e($alert->created_at->diffForHumans()); ?></p>
                                                </div>
                                            </div>
                                            <button wire:click="dismissAlert(<?php echo e($alert->id); ?>)"
                                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                                </div>
                            <?php else: ?>
                                <p class="text-center text-gray-500 dark:text-gray-400 py-8">Brak aktywnych alertow</p>
                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                        </div>

                        
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Zablokowane konta</h3>
                                <!--[if BLOCK]><![endif]--><?php if($lockedOutUsers->count() > 0): ?>
                                    <button wire:click="unlockAllUsers"
                                        class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                        Odblokuj wszystkie
                                    </button>
                                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                            </div>

                            <!--[if BLOCK]><![endif]--><?php if($lockedOutUsers->count() > 0): ?>
                                <div class="space-y-3">
                                    <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $lockedOutUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div class="flex items-center justify-between bg-white dark:bg-gray-800 rounded-lg p-3">
                                            <div class="flex items-center">
                                                <div class="h-8 w-8 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                                                    <span class="text-xs font-medium text-gray-600 dark:text-gray-300">
                                                        <?php echo e(strtoupper(substr($user->first_name ?? $user->email, 0, 2))); ?>

                                                    </span>
                                                </div>
                                                <div class="ml-3">
                                                    <p class="text-sm font-medium text-gray-900 dark:text-white"><?php echo e($user->full_name ?? $user->email); ?></p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                                        Do: <?php echo e($user->locked_until->format('H:i')); ?>

                                                    </p>
                                                </div>
                                            </div>
                                            <button wire:click="unlockUser(<?php echo e($user->id); ?>)"
                                                class="text-sm text-green-600 hover:text-green-800 dark:text-green-400">
                                                Odblokuj
                                            </button>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                                </div>
                            <?php else: ?>
                                <p class="text-center text-gray-500 dark:text-gray-400 py-8">Brak zablokowanych kont</p>
                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                        </div>

                        
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Podejrzane adresy IP</h3>

                            <!--[if BLOCK]><![endif]--><?php if($topAttackingIPs->count() > 0): ?>
                                <div class="space-y-3">
                                    <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $topAttackingIPs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ipData): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div class="flex items-center justify-between bg-white dark:bg-gray-800 rounded-lg p-3">
                                            <div>
                                                <p class="text-sm font-medium text-gray-900 dark:text-white"><?php echo e($ipData->ip_address); ?></p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400"><?php echo e($ipData->attempt_count); ?> nieudanych prob</p>
                                            </div>
                                            <button wire:click="blockIp('<?php echo e($ipData->ip_address); ?>')"
                                                class="text-sm text-red-600 hover:text-red-800 dark:text-red-400">
                                                Zablokuj IP
                                            </button>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                                </div>
                            <?php else: ?>
                                <p class="text-center text-gray-500 dark:text-gray-400 py-8">Brak podejrzanych adresow IP</p>
                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                        </div>

                        
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Wygasajace hasla</h3>

                            <!--[if BLOCK]><![endif]--><?php if($usersWithExpiringPasswords->count() > 0): ?>
                                <div class="space-y-3">
                                    <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $usersWithExpiringPasswords; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div class="flex items-center justify-between bg-white dark:bg-gray-800 rounded-lg p-3">
                                            <div class="flex items-center">
                                                <div class="h-8 w-8 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                                                    <span class="text-xs font-medium text-gray-600 dark:text-gray-300">
                                                        <?php echo e(strtoupper(substr($user->first_name ?? $user->email, 0, 2))); ?>

                                                    </span>
                                                </div>
                                                <div class="ml-3">
                                                    <p class="text-sm font-medium text-gray-900 dark:text-white"><?php echo e($user->full_name ?? $user->email); ?></p>
                                                </div>
                                            </div>
                                            <button wire:click="forcePasswordChange(<?php echo e($user->id); ?>)"
                                                class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                                Wymus zmiane
                                            </button>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                                </div>
                            <?php else: ?>
                                <p class="text-center text-gray-500 dark:text-gray-400 py-8">Brak wygasajacych hasel</p>
                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                        </div>
                    </div>
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                <!--[if BLOCK]><![endif]--><?php if($activeTab === 'alerts'): ?>
                    
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-4">
                                <select wire:model.live="alertFilter"
                                    class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="all">Wszystkie alerty</option>
                                    <option value="unacknowledged">Niepotwierdzone</option>
                                    <option value="critical">Krytyczne</option>
                                </select>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Typ</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tytul</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Uzytkownik</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Data</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Akcje</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <!--[if BLOCK]><![endif]--><?php $__empty_1 = true; $__currentLoopData = $alerts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $alert): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php
                                                    $badge = $alert->getSeverityBadge();
                                                ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                    <?php echo e($badge['color'] === 'red' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : ''); ?>

                                                    <?php echo e($badge['color'] === 'orange' ? 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200' : ''); ?>

                                                    <?php echo e($badge['color'] === 'yellow' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : ''); ?>

                                                    <?php echo e($badge['color'] === 'blue' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : ''); ?>">
                                                    <?php echo e($badge['label']); ?>

                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm text-gray-900 dark:text-white"><?php echo e($alert->title); ?></div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400"><?php echo e($alert->getTypeLabel()); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                <?php echo e($alert->relatedUser?->full_name ?? '-'); ?>

                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                <?php echo e($alert->created_at->format('Y-m-d H:i')); ?>

                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <!--[if BLOCK]><![endif]--><?php if($alert->resolved): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                        Rozwiazany
                                                    </span>
                                                <?php elseif($alert->acknowledged): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                        Potwierdzony
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                                        Nowy
                                                    </span>
                                                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <!--[if BLOCK]><![endif]--><?php if(!$alert->acknowledged): ?>
                                                    <button wire:click="acknowledgeAlert(<?php echo e($alert->id); ?>)"
                                                        class="text-blue-600 hover:text-blue-900 dark:text-blue-400 mr-3">
                                                        Potwierdz
                                                    </button>
                                                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                                <!--[if BLOCK]><![endif]--><?php if(!$alert->resolved): ?>
                                                    <button wire:click="resolveAlert(<?php echo e($alert->id); ?>)"
                                                        class="text-green-600 hover:text-green-900 dark:text-green-400">
                                                        Rozwiaz
                                                    </button>
                                                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                        <tr>
                                            <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                                Brak alertow do wyswietlenia.
                                            </td>
                                        </tr>
                                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            <?php echo e($alerts->links()); ?>

                        </div>
                    </div>
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                <!--[if BLOCK]><![endif]--><?php if($activeTab === 'logins'): ?>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">IP</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Powod</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Data</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <!--[if BLOCK]><![endif]--><?php $__empty_1 = true; $__currentLoopData = $loginAttempts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $attempt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr class="<?php echo e(!$attempt->success ? 'bg-red-50 dark:bg-red-900/10' : ''); ?>">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            <?php echo e($attempt->email); ?>

                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <?php echo e($attempt->ip_address); ?>

                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <!--[if BLOCK]><![endif]--><?php if($attempt->success): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                    Sukces
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                    Blad
                                                </span>
                                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <?php echo e($attempt->getFailureReasonLabel() ?? '-'); ?>

                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <?php echo e($attempt->attempted_at->format('Y-m-d H:i:s')); ?>

                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                            Brak historii logowan.
                                        </td>
                                    </tr>
                                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                <!--[if BLOCK]><![endif]--><?php if($activeTab === 'policies'): ?>
                    
                    <div class="space-y-4">
                        <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $passwordPolicies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $policy): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center">
                                        <h4 class="text-lg font-medium text-gray-900 dark:text-white"><?php echo e($policy->name); ?></h4>
                                        <!--[if BLOCK]><![endif]--><?php if($policy->is_default): ?>
                                            <span class="ml-2 px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                Domyslna
                                            </span>
                                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                    </div>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">
                                        <?php echo e($policy->users_count); ?> uzytkownikow
                                    </span>
                                </div>

                                <!--[if BLOCK]><![endif]--><?php if($policy->description): ?>
                                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-3"><?php echo e($policy->description); ?></p>
                                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Min. dlugosc:</span>
                                        <span class="font-medium text-gray-900 dark:text-white ml-1"><?php echo e($policy->min_length); ?></span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Wielkie litery:</span>
                                        <span class="font-medium text-gray-900 dark:text-white ml-1"><?php echo e($policy->require_uppercase ? 'Tak' : 'Nie'); ?></span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Cyfry:</span>
                                        <span class="font-medium text-gray-900 dark:text-white ml-1"><?php echo e($policy->require_numbers ? 'Tak' : 'Nie'); ?></span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Znaki specjalne:</span>
                                        <span class="font-medium text-gray-900 dark:text-white ml-1"><?php echo e($policy->require_symbols ? 'Tak' : 'Nie'); ?></span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Wygasa po:</span>
                                        <span class="font-medium text-gray-900 dark:text-white ml-1">
                                            <?php echo e($policy->expire_days > 0 ? $policy->expire_days . ' dni' : 'Nigdy'); ?>

                                        </span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Historia hasel:</span>
                                        <span class="font-medium text-gray-900 dark:text-white ml-1"><?php echo e($policy->history_count); ?></span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Blokada po:</span>
                                        <span class="font-medium text-gray-900 dark:text-white ml-1"><?php echo e($policy->lockout_attempts); ?> probach</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Czas blokady:</span>
                                        <span class="font-medium text-gray-900 dark:text-white ml-1"><?php echo e($policy->lockout_duration_minutes); ?> min</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                    </div>
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
            </div>
        </div>
    </div>

    
    <!--[if BLOCK]><![endif]--><?php if(session()->has('success')): ?>
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
            class="fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">
            <?php echo e(session('success')); ?>

        </div>
    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

    <?php if(session()->has('error')): ?>
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
            class="fixed bottom-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">
            <?php echo e(session('error')); ?>

        </div>
    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

    <?php if(session()->has('info')): ?>
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
            class="fixed bottom-4 right-4 bg-blue-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">
            <?php echo e(session('info')); ?>

        </div>
    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
</div>
<?php /**PATH D:\Skrypty\PPM-CC-Laravel\resources\views/livewire/admin/security/security-dashboard.blade.php ENDPATH**/ ?>