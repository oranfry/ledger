<?php

?><table class="easy-table"><?php
    ?><tbody><?php
        ?><tr><?php
            ?><th>Field</th><?php
            ?><td><?= $fs->alias ?><?php
        ?></tr><?php
        ?><tr><?php
            ?><th>Count</th><?php
            ?><td><?= $stats['count'] ?><?php
        ?></tr><?php

        if ($stats['count']) {
            ?><tr><?php
                ?><th>Min</th><?php
                ?><td><?= $stats['min'] ?><?php
            ?></tr><?php
            ?><tr><?php
                ?><th>Max</th><?php
                ?><td><?= $stats['max'] ?><?php
            ?></tr><?php
            ?><tr><?php
                ?><th>Mean</th><?php
                ?><td><?= $stats['mean'] ?><?php
            ?></tr><?php
            ?><tr><?php
                ?><th>Median</th><?php
                ?><td><?= $stats['median'] ?><?php
            ?></tr><?php
            ?><tr><?php
                ?><th>Std. Dev.</th><?php
                ?><td><?= $stats['stdDev'] ?><?php
            ?></tr><?php
        }
    ?></tbody><?php
?></table><?php