<?php

use OranFry\ContextVariableSets\ContextVariableSet;

$vars = array_filter([...$ledger->variables(), ContextVariableSet::get('showas')]);

foreach ($vars as $var) {
    ?><div id="cvs-<?= $var->prefix ?>" style="margin: 1em 0 1em"><?php
        if (!$var->invisible()) {
            ?><h4 style="margin-bottom: 0.2em"><?= $var->prefix ?></h4><?php
        }

        $var->display();
    ?></div><?php
}

