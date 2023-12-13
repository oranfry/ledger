<br><?php

?><h3><?=
    $title
?></h3><?php

?><br><?php

if ($lines === null) {
    ss_require("src/php/partial/showas/ledger/error.php", compact('error'));

    return;
}

ss_require("src/php/partial/showas/ledger/$showas->value.php", $viewdata);
