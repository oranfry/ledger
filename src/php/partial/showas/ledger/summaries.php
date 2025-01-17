<?php

use ContextVariableSets\ContextVariableSet;


if (!$groupingInfo) {
    echo "Can't show a summaries - no grouping info";
    return;
}

if (!@$summaries) {
    echo "Can't show a summaries - no data";
    return;
}

$seen_today = !$groupingInfo
    || !@$groupingInfo->currentgroup
    || !@$groupingInfo->start
    || strcmp($groupingInfo->currentgroup, $groupingInfo->start) < 0
    || !$groupingInfo->end
    || strcmp($groupingInfo->currentgroup, $groupingInfo->end) > 0;

?><table class="easy-table"><?php
    ?><thead><?php
        ?><tr><?php
            ?><th>date</th><?php

            foreach ($graphfields as $graphfield) {
                ?><th class="right"><?= $graphfield->alias ?></th><?php
            }
        ?></tr><?php
    ?></thead><?php

    ?><tbody><?php
        foreach ($groupingInfo->groupings as $i => $group) {
            if (!$summary = $summaries[$group] ?? null) {
                continue;
            }

            $seen_today = ($is_current = !$seen_today && strcmp($currentgroup, $groups[$i + 1] ?? null) < 0) || $seen_today;

            ?><tr<?php if ($is_current) echo ' class="today"' ?>><?php
                ?><td><?= $group ?></td><?php
                foreach ($graphfields as $graphfield) {
                    ?><td class="right"><strong><?= $summary->{$graphfield->alias} ?></strong></td><?php
                }
            ?></tr><?php
        }
    ?></tbody><?php
?></table><?php
