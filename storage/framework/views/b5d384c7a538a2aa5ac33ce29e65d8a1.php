

<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['category', 'level' => 0]));

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

foreach (array_filter((['category', 'level' => 0]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>

<?php
    // Ensure category data exists
    $categoryId = $category['prestashop_id'] ?? 0;
    $ppmId = $category['ppm_id'] ?? null; // PPM ID for manual categories
    $isManual = $category['is_manual'] ?? false; // Flag for manual (non-PrestaShop) categories
    $categoryName = $category['name'] ?? 'Unknown';
    $levelDepth = $category['level_depth'] ?? 0;
    $isActive = $category['active'] ?? $category['is_active'] ?? true;
    $idParent = $category['id_parent'] ?? 0;
    $children = $category['children'] ?? [];
    $existsInPpm = $category['exists_in_ppm'] ?? false;

    // Unique ID for scroll target (use ppm_id for manual, prestashop_id for imports)
    $uniqueId = $isManual && $ppmId ? "category-ppm-{$ppmId}" : "category-ps-{$categoryId}";

    // Visual indentation based on level (24px per level)
    $paddingLeft = $level * 24;

    // Icon based on exists_in_ppm flag
    if ($existsInPpm) {
        $icon = '✅'; // Already exists
        $iconClass = 'text-green-400';
        $textClass = 'text-gray-500';
        $disabled = true;
    } else {
        $icon = match($levelDepth) {
            0 => '📁', // Root level
            1 => '📂', // Second level
            default => '📄' // Leaf categories
        };
        $iconClass = 'text-brand-400';
        $textClass = 'text-white';
        $disabled = false;
    }
?>

<div class="category-tree-item-compact" id="<?php echo e($uniqueId); ?>" data-category-id="<?php echo e($categoryId); ?>" data-ppm-id="<?php echo e($ppmId ?? ''); ?>" data-is-manual="<?php echo e($isManual ? '1' : '0'); ?>">
    <div class="flex items-center gap-2 py-1.5 px-2 rounded-lg hover:bg-gray-700/30 transition-colors duration-150 <?php echo e($disabled ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer'); ?> group">
        <!-- Visual Hierarchy Indicators (horizontal bars like in Categories panel) -->
        <?php if($level > 0): ?>
            <div class="flex items-center flex-shrink-0">
                <?php for($i = 0; $i < $level; $i++): ?>
                    <span class="text-gray-600 text-sm">—</span>
                <?php endfor; ?>
            </div>
        <?php endif; ?>

        <!-- Checkbox -->
        <label class="flex items-center gap-2 flex-1 cursor-pointer">
        <!-- Checkbox - CRITICAL FIX: wire:model doesn't work in nested components, use Alpine.js + Livewire -->
        
        <?php
            $checkboxId = $isManual && $ppmId ? $ppmId : $categoryId;
        ?>
        <input type="checkbox"
               x-data
               @click="$wire.toggleCategory(<?php echo e($checkboxId); ?>)"
               <?php echo e($disabled ? 'disabled' : ''); ?>

               <?php if(in_array($checkboxId, $this->selectedCategoryIds)): echo 'checked'; endif; ?>
               class="w-4 h-4 rounded border-gray-600 text-brand-600 focus:ring-brand-500 focus:ring-offset-gray-900 flex-shrink-0 <?php echo e($disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'); ?>"
               style="accent-color: #e0ac7e;">

        <!-- Icon -->
        <span class="flex-shrink-0 text-base <?php echo e($iconClass); ?>">
            <?php echo e($icon); ?>

        </span>

        <!-- Category Info - COMPACT -->
        <div class="flex-1 min-w-0 flex items-center gap-2 flex-wrap">
            <span class="text-sm font-medium <?php echo e($textClass); ?> truncate">
                <?php echo e($categoryName); ?>

            </span>

            <!-- Badges - COMPACT -->
            <span class="px-1.5 py-0.5 text-xs font-semibold rounded bg-gray-700/50 text-gray-400 whitespace-nowrap">
                L<?php echo e($levelDepth); ?>

            </span>

            <?php if($existsInPpm): ?>
                <span class="px-1.5 py-0.5 text-xs font-semibold rounded bg-green-900/30 text-green-400 whitespace-nowrap">
                    Istnieje
                </span>
            <?php else: ?>
                <span class="px-1.5 py-0.5 text-xs font-semibold rounded bg-blue-900/30 text-blue-400 whitespace-nowrap">
                    Nowa
                </span>
            <?php endif; ?>

            <?php if(!$isActive): ?>
                <span class="px-1.5 py-0.5 text-xs font-semibold rounded bg-gray-700 text-gray-500 whitespace-nowrap">
                    Nieaktywna
                </span>
            <?php endif; ?>

            <!-- PrestaShop ID - COMPACT -->
            <span class="text-xs text-gray-500">
                PS:<?php echo e($categoryId); ?>

            </span>
        </div>
        </label>
    </div>

    
    <?php if(!empty($children)): ?>
        <div class="category-children">
            <?php $__currentLoopData = $children; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $child): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if (isset($component)) { $__componentOriginalcee44d877031a2157ce75746f346bf92 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcee44d877031a2157ce75746f346bf92 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.category-tree-item','data' => ['category' => $child,'level' => $level + 1,'wire:key' => 'cat-child-'.e($child['prestashop_id'] ?? 'unknown').'-'.e($categoryId).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('category-tree-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['category' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($child),'level' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($level + 1),'wire:key' => 'cat-child-'.e($child['prestashop_id'] ?? 'unknown').'-'.e($categoryId).'']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalcee44d877031a2157ce75746f346bf92)): ?>
<?php $attributes = $__attributesOriginalcee44d877031a2157ce75746f346bf92; ?>
<?php unset($__attributesOriginalcee44d877031a2157ce75746f346bf92); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalcee44d877031a2157ce75746f346bf92)): ?>
<?php $component = $__componentOriginalcee44d877031a2157ce75746f346bf92; ?>
<?php unset($__componentOriginalcee44d877031a2157ce75746f346bf92); ?>
<?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php endif; ?>
</div>
<?php /**PATH D:\Skrypty\PPM-CC-Laravel\resources\views\components\category-tree-item.blade.php ENDPATH**/ ?>