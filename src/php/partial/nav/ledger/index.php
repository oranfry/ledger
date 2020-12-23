<?php if ($hasJars) : ?>
    <?php ContextVariableSet::get('jar')->display(); ?>
    <?php if ($hasSuperjars) : ?><?php ContextVariableSet::get('superjar')->display(); ?><?php endif ?>

    <div class="navset">
        <i class="icon icon--gray icon--times delete-selected"></i>
    </div>
<?php endif ?>