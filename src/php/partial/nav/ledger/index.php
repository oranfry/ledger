<?php

foreach ($variables as $var):
    $var->display();
endforeach;

?>
<div class="navset">
    <a class="delete-selected disabled" href="#"><i class="icon icon--gray icon--bin"></i></a>
</div>
<?php

if (count($showas->options) > 1):
    $showas->display();
endif;
