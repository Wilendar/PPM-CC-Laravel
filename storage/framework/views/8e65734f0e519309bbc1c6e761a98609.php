
<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['node', 'level' => 0]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['node', 'level' => 0]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>

<?php
    $status = $node['status'] ?? 'both';
    $hasChildren = !empty($node['children']);

    // Status-based styling (ProductForm-inspired)
    $statusTextColors = [
        'both' => 'text-green-400',
        'prestashop_only' => 'text-orange-400',
        'ppm_only' => 'text-red-400',
    ];

    $statusBgColors = [
        'both' => 'bg-green-500/10 border-green-500/30',
        'prestashop_only' => 'bg-orange-500/10 border-orange-500/30',
        'ppm_only' => 'bg-red-500/10 border-red-500/30',
    ];

    $statusLabels = [
        'both' => 'Zsynchronizowana',
        'prestashop_only' => 'Do dodania',
        'ppm_only' => 'Tylko w PPM',
    ];

    $statusBadgeClasses = [
        'both' => 'bg-green-500/20 text-green-400 border-green-500/30',
        'prestashop_only' => 'bg-orange-500/20 text-orange-400 border-orange-500/30',
        'ppm_only' => 'bg-red-500/20 text-red-400 border-red-500/30',
    ];
?>


<div x-data="{ collapsed: <?php echo e($level < 2 ? 'false' : 'true'); ?> }"
     wire:key="comparison-<?php echo e($node['prestashop_id'] ?? $node['id'] ?? uniqid()); ?>"
     class="comparison-tree-node">

    
    <div class="category-tree-row flex items-center space-x-2 py-1.5 px-2 rounded-lg transition-all duration-150 hover:bg-gray-700/20 <?php echo e($statusBgColors[$status] ?? ''); ?> border"
         style="padding-left: <?php echo e($level * 1.5); ?>rem;">

        
        <?php if($hasChildren): ?>
            <button type="button"
                    @click="collapsed = !collapsed"
                    class="text-gray-500 hover:text-gray-300 transition-transform duration-200 p-0.5"
                    :class="collapsed ? 'rotate-0' : 'rotate-90'">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                </svg>
            </button>
        <?php else: ?>
            
            <span class="w-5"></span>
        <?php endif; ?>

        
        <span class="flex-shrink-0">
            <?php if($status === 'both'): ?>
                
                <svg class="w-4 h-4 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
            <?php elseif($status === 'prestashop_only'): ?>
                
                <svg class="w-4 h-4 text-orange-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"/>
                </svg>
            <?php else: ?>
                
                <svg class="w-4 h-4 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
            <?php endif; ?>
        </span>

        
        <?php if($level > 0): ?>
            <span class="category-tree-icon text-gray-500 text-sm">└─</span>
        <?php endif; ?>

        
        <span class="flex-1 text-sm font-medium <?php echo e($statusTextColors[$status] ?? 'text-gray-300'); ?>">
            <?php echo e($node['name']); ?>

        </span>

        
        <span class="px-2 py-0.5 rounded text-xs font-semibold border <?php echo e($statusBadgeClasses[$status] ?? 'bg-gray-500/20 text-gray-400'); ?>">
            <?php echo e($statusLabels[$status] ?? 'Nieznany'); ?>

        </span>

        
        <?php if(isset($node['product_count_ppm']) && $node['product_count_ppm'] > 0): ?>
            <span class="px-2 py-0.5 rounded text-xs bg-gray-700/50 text-gray-400 border border-gray-600/30">
                <?php echo e($node['product_count_ppm']); ?> prod.
            </span>
        <?php endif; ?>

        
        <span class="text-xs text-gray-600 tabular-nums">
            <?php if($node['id']): ?>
                <span class="mr-1">PPM:<?php echo e($node['id']); ?></span>
            <?php endif; ?>
            <?php if($node['prestashop_id']): ?>
                <span>PS:<?php echo e($node['prestashop_id']); ?></span>
            <?php endif; ?>
        </span>
    </div>

    
    <?php if($hasChildren): ?>
        <div x-show="!collapsed"
             x-transition.opacity.duration.100ms
             class="mt-0.5">
            <?php $__currentLoopData = $node['children']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $child): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if (isset($component)) { $__componentOriginal81f92f9bbc683e01ff4a747f358484ee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal81f92f9bbc683e01ff4a747f358484ee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.comparison-tree-node','data' => ['node' => $child,'level' => $level + 1]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('comparison-tree-node'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['node' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($child),'level' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($level + 1)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal81f92f9bbc683e01ff4a747f358484ee)): ?>
<?php $attributes = $__attributesOriginal81f92f9bbc683e01ff4a747f358484ee; ?>
<?php unset($__attributesOriginal81f92f9bbc683e01ff4a747f358484ee); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal81f92f9bbc683e01ff4a747f358484ee)): ?>
<?php $component = $__componentOriginal81f92f9bbc683e01ff4a747f358484ee; ?>
<?php unset($__componentOriginal81f92f9bbc683e01ff4a747f358484ee); ?>
<?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php endif; ?>
</div>
<?php /**PATH D:\Skrypty\PPM-CC-Laravel\resources\views\components\comparison-tree-node.blade.php ENDPATH**/ ?>