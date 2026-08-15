<?php

use OranFry\ContextVariableSets\ContextVariableSet;

foreach ($ledger->variables() as $var) {
    ?><div id="cvs-<?= $var->prefix ?>"><?php
        if (!$var->invisible()) {
            ?><h4 style="margin: 0.75em 0 0.2em"><?= $var->prefix ?></h4><?php
        }

        $var->display();
    ?></div><?php
}

if ($showasCvs = ContextVariableSet::get('showas')) {
    $showasCvs->display();
}
