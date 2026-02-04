

<?php $__env->startSection('title', 'Visual Editor - Bloki'); ?>

<?php $__env->startSection('content'); ?>
    <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('admin.visual-editor.block-manager', []);

$__html = app('livewire')->mount($__name, $__params, 'lw-3964533978-0', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Skrypty\PPM-CC-Laravel\resources\views\admin\visual-editor\blocks.blade.php ENDPATH**/ ?>