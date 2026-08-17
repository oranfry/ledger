<?php

if (!$ledger->hideTitle()) {
    ?><div class="snap-pad"><?php
        ?><h3 class="ledger-title easy-table-title"><?= $ledger->title() ?></h3><?php
    ?></div><?php
}

if ($lines === null) {
    ss_require("src/php/partial/showas/ledger/error.php", compact('error'));

    return;
}

ss_require("src/php/partial/showas/ledger/$showas.php", $viewdata);
