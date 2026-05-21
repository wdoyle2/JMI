<?php $__env->startSection('content'); ?>
  <h1>Page not found</h1>
  <p>
    <?php echo e($exception->getMessage() ?: 'This is not the page you were looking for.'); ?>

  </p>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/errors/404.blade.php ENDPATH**/ ?>