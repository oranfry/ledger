<?php use contextvariableset\Daterange; ?>
<?php $daterange = ContextVariableSet::get('daterange'); ?>
<?php $daterange->display(); ?>
<?php if ($hasJars) : ?>
    <?php ContextVariableSet::get('jar')->display(); ?>
    <?php if ($hasSuperjars) : ?><?php ContextVariableSet::get('superjar')->display(); ?><?php endif ?>
<?php endif ?>

<div class="navset">
    <a class="delete-selected disabled" href="#"><i class="icon icon--gray icon--bin"></i></a>
</div>

<?php $showas->display(); ?>
