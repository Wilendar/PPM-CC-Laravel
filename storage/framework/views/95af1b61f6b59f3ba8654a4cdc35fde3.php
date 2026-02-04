<?php $__env->startSection('title', 'Parametry Produktow'); ?>

<?php $__env->startSection('content'); ?>
    <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('admin.parameters.product-parameters-manager', []);

$__html = app('livewire')->mount($__name, $__params, 'lw-1991376213-0', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Skrypty\PPM-CC-Laravel\resources\views\admin\product-parameters.blade.php ENDPATH**/ ?>