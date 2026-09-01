<?php

?><div class="snap-pad"><?php
    ?><form method="post"><?php
        ?><div><?php
            ?><textarea name="raw" class="raw"><?= json_encode($ledger->raw(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?></textarea><?php
            ?><br><?php
            ?><br><?php
            ?><button class="savelineraw button button--main" type="button">Save</button><?php
        ?></div><?php
    ?></form><?php
?></div><?php
