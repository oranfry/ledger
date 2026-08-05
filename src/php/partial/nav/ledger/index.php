<?php

foreach ($variables as $var) {
    if (!$var->invisible()) {
        ?><h4 style="margin-bottom: 0.5em;"><?= $var->prefix ?></h4><?php
    }

    $var->display();
}

if (count($showas->options) > 1) {
    $showas->display();
}
