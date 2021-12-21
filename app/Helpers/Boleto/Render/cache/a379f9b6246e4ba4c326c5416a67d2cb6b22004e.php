<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Boletos</title>
    <style type="text/css">
        <?php echo $css; ?>

    </style>
</head>
<body>

<div class="wrapper">
    <?php echo $__env->yieldContent('boleto'); ?>
</div>

<?php if(isset($imprimir_carregamento) && $imprimir_carregamento === true): ?>
    <script type="text/javascript">
        window.onload = function() { window.print(); }
    </script>
<?php endif; ?>
</body>
</html>
<?php /**PATH /var/www/html/vendor/eduardokum/laravel-boleto/src/Boleto/Render/view/layout.blade.php ENDPATH**/ ?>