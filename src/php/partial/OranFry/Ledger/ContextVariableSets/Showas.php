<?php

?><div class="nav-dropdown"><?php
    foreach ($this->options as $showas) {
        $current = $showas == $this->value ? 'current' : '';
        $icon = static::$icons[$showas];

        ?><a<?php
            ?> class="<?php
                echo implode(' ', array_filter([
                    !$this->disabled ? 'cv-manip' : null,
                    'showas-trigger',
                    $showas == $this->value ? 'current' : null,
                    $this->disabled ? 'disabled' : null,
                ]));
            ?>"<?php

            if (!$this->disabled) {
                ?> data-manips="<?= $this->prefix ?>__value=<?= $showas?>"<?php
            }
        ?>><?php
            ?><i class="icon icon--gray icon--<?= $icon ?>" alt="<?= $showas ?>"></i><?php
        ?></a><?php
    }
?></div><?php