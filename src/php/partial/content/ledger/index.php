<br>
<h3><?= $title ?></h3>
<br>
<?php

if ($lines === null):
    ss_require("src/php/partial/showas/ledger/error.php", compact('error'));

    return; 
endif;

ss_require("src/php/partial/showas/ledger/$showas->value.php", $viewdata);
