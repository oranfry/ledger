<?php

use OranFry\ContextVariableSets\ContextVariableSet;

foreach ($variables as $var) {
    if (!$var->invisible()) {
        ?><h4 style="margin-bottom: 0.5em;"><?= $var->prefix ?></h4><?php
    }

    $var->display();
}

if ($showasCvs = ContextVariableSet::get('showas')) {
    $showasCvs->display();
}
