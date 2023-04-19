<?php foreach ($variables as $var): ?>
    <?php $var->display(); ?>
<?php endforeach ?>

<div class="navset">
    <a class="delete-selected disabled" href="#"><i class="icon icon--gray icon--bin"></i></a>
</div>

<?php $showas->display(); ?>
