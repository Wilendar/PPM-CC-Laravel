<?php $__env->startSection('title', 'Unified Visual Editor'); ?>

<?php $__env->startSection('content'); ?>
    <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('products.visual-description.unified-visual-editor', ['product' => $product,'shop' => $shop]);

$__html = app('livewire')->mount($__name, $__params, 'lw-2009641508-0', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Skrypty\PPM-CC-Laravel\resources\views\admin\visual-editor\unified-editor.blade.php ENDPATH**/ ?>