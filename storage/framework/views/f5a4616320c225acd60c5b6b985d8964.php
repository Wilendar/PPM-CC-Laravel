



<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['category', 'context' => 'default', 'selectedCategories' => []]));

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

foreach (array_filter((['category', 'context' => 'default', 'selectedCategories' => []]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>

<?php
    // Use level from category data (calculated by CategoryPicker backend)
    $level = $category['level'] ?? 0;
?>

<div class="category-picker-node"
     x-data="{
         expanded: <?php echo e($category['has_children'] ? 'false' : 'true'); ?>,
         categoryId: <?php echo e($category['id']); ?>,
         hasChildren: <?php echo e($category['has_children'] ? 'true' : 'false'); ?>,
         // Check if this category is selected (from parent Alpine state)
         get isSelected() {
             return selectedCategories.includes(this.categoryId);
         }
     }">

    <!-- Node Row -->
    <div class="category-picker-node-row"
         :class="{ 'category-picker-node-expanded': expanded }">

        <!-- Indentation Spacer (NO inline styles) -->
        <?php if($level > 0): ?>
            <div class="category-indent-spacer category-indent-spacer-<?php echo e(min($level, 5)); ?>"
                 data-level="<?php echo e($level); ?>"
                 data-debug="Level:<?php echo e($level); ?> Class:category-indent-spacer-<?php echo e(min($level, 5)); ?>"
                 title="Indent Level: <?php echo e($level); ?>"></div>
        <?php endif; ?>

        <!-- Expand/Collapse Button -->
        <button <?php if($category['has_children']): ?>
                    @click="expanded = !expanded"
                <?php endif; ?>
                class="category-picker-node-toggle"
                :class="{ 'invisible': !hasChildren }">
            <svg x-show="!expanded"
                 x-cloak
                 class="w-4 h-4 text-gray-400"
                 fill="currentColor"
                 viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
            </svg>
            <svg x-show="expanded"
                 x-cloak
                 class="w-4 h-4 text-gray-400"
                 fill="currentColor"
                 viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
            </svg>
        </button>

        <!-- Folder Icon -->
        <div class="category-picker-node-icon">
            <?php if($category['has_children']): ?>
                <svg x-show="!expanded"
                     x-cloak
                     class="w-5 h-5 text-brand-400"
                     fill="currentColor"
                     viewBox="0 0 20 20">
                    <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path>
                </svg>
                <svg x-show="expanded"
                     x-cloak
                     class="w-5 h-5 text-brand-400"
                     fill="currentColor"
                     viewBox="0 0 20 20">
                    <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" fill-opacity="0.5"></path>
                    <path d="M2 10h16v6a2 2 0 01-2 2H4a2 2 0 01-2-2v-6z"></path>
                </svg>
            <?php else: ?>
                <svg class="w-5 h-5 text-gray-500"
                     fill="currentColor"
                     viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path>
                </svg>
            <?php endif; ?>
        </div>

        <!-- Checkbox -->
        <div class="category-picker-node-checkbox">
            <input type="checkbox"
                   id="category-<?php echo e($context); ?>-<?php echo e($category['id']); ?>"
                   @click="$wire.toggleCategory(<?php echo e($category['id']); ?>)"
                   :checked="isSelected"
                   class="category-picker-checkbox">
        </div>

        <!-- Category Label -->
        <label for="category-<?php echo e($context); ?>-<?php echo e($category['id']); ?>"
               class="category-picker-node-label">
            <span class="category-picker-node-name"><?php echo e($category['name']); ?></span>
            <?php if($category['has_children']): ?>
                <span class="category-picker-node-count">
                    (<?php echo e(count($category['children'])); ?>)
                </span>
            <?php endif; ?>
        </label>
    </div>

    <!-- Children (Recursive) -->
    <?php if($category['has_children']): ?>
        <div x-show="expanded"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 transform -translate-y-1"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 transform translate-y-0"
             x-transition:leave-end="opacity-0 transform -translate-y-1"
             class="category-picker-node-children">
            <?php $__currentLoopData = $category['children']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $child): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    // CRITICAL FIX: Ensure child has level set (backend should provide this)
                    if (!isset($child['level'])) {
                        $child['level'] = $level + 1;
                        \Log::warning('CategoryPicker: Child missing level, calculated', [
                            'parent_id' => $category['id'],
                            'child_id' => $child['id'],
                            'parent_level' => $level,
                            'calculated_level' => $child['level'],
                        ]);
                    }
                ?>
                <?php if (isset($component)) { $__componentOriginaleb3a98156ed915263458b6395ced6295 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaleb3a98156ed915263458b6395ced6295 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.category-picker-node','data' => ['category' => $child,'context' => $context,'selectedCategories' => $selectedCategories]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('category-picker-node'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['category' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($child),'context' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($context),'selected-categories' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($selectedCategories)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaleb3a98156ed915263458b6395ced6295)): ?>
<?php $attributes = $__attributesOriginaleb3a98156ed915263458b6395ced6295; ?>
<?php unset($__attributesOriginaleb3a98156ed915263458b6395ced6295); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaleb3a98156ed915263458b6395ced6295)): ?>
<?php $component = $__componentOriginaleb3a98156ed915263458b6395ced6295; ?>
<?php unset($__componentOriginaleb3a98156ed915263458b6395ced6295); ?>
<?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php endif; ?>
</div>
<?php /**PATH D:\Skrypty\PPM-CC-Laravel\resources\views\components\category-picker-node.blade.php ENDPATH**/ ?>