<?php use contextvariableset\Daterange; ?>
<?php $daterange = new DateRange('daterange'); ?>
<?php $daterange->display(); ?>
<?php if ($hasJars) : ?>
    <?php ContextVariableSet::get('jar')->display(); ?>
    <?php if ($hasSuperjars) : ?><?php ContextVariableSet::get('superjar')->display(); ?><?php endif ?>
<?php endif ?>

<div class="navset">
    <i class="icon icon--gray icon--times delete-selected"></i>
</div>

<?php $showas->display(); ?>

<div class="navset">
    <?php $repeater->display(); ?>
    <?php if ($repeater->period): ?>
        <?php if (count($linetypes) == 1): ?>
            <i class="icon icon--gray icon--plus trigger-bulk-add" data-type="<?= $linetypes[0]->name ?>"></i>
        <?php elseif (count($linetypes) > 1): ?>
            <div class="inline-rel">
                <div class="inline-modal">
                    <nav>
                        <?php foreach ($linetypes as $linetype): ?><a href="#"><i class="icon icon--gray icon--<?= $linetype->icon ?> trigger-bulk-add" data-type="<?= $linetype->name ?>"></i></a><?php endforeach ?>
                    </nav>
                </div>
                <i class="inline-modal-trigger icon icon--gray icon--plus"></i>
            </div>
        <?php endif ?>
    <?php endif ?>
</div>
