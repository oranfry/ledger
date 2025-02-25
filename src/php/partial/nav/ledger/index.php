<?php

foreach ($variables as $var):
    $var->display();
endforeach;

if (count($showas->options) > 1):
    $showas->display();
endif;
