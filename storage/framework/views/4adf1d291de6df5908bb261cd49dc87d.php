

<?php $__env->startSection('title', 'Dodaj nowego uzytkownika - Admin PPM'); ?>

<?php $__env->startSection('content'); ?>
    <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('admin.users.user-form', []);

$__html = app('livewire')->mount($__name, $__params, 'lw-1915875331-0', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Skrypty\PPM-CC-Laravel\resources\views\admin\users\create.blade.php ENDPATH**/ ?>