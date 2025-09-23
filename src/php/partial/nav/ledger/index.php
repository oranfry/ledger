<?php

foreach ($variables as $var) {
    $var->display();
}

if (count($showas->options) > 1) {
    $showas->display();
}
