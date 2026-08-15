<?php

?><br><?php

if (!$ledger->hideTitle()) {
    ?><h3><?= $ledger->title() ?></h3><?php
    ?><br><?php
}

if ($lines === null) {
    ss_require("src/php/partial/showas/ledger/error.php", compact('error'));

    return;
}

ss_require("src/php/partial/showas/ledger/$showas.php", $viewdata);
