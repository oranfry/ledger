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
    || !@$groupingInfo->currentGrouping
    || !in_array($groupingInfo->currentGrouping, $groupings);

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
        $summaryKeys = array_keys($summaries);

        foreach ($summaryKeys as $i => $grouping) {
            $summary = $summaries[$grouping];

            $is_current = !$seen_today
                && $groupingInfo->currentGrouping
                && strcmp($groupingInfo->currentGrouping, $summaryKeys[$i + 1] ?? '') < 0;

            $seen_today = $seen_today || $is_current;

            ?><tr<?php if ($is_current) echo ' class="today"' ?>><?php
                ?><td><?= $grouping ?></td><?php
                foreach ($graphfields as $graphfield) {
                    ?><td class="right"><strong><?= $summary->{$graphfield->alias} ?></strong></td><?php
                }
            ?></tr><?php
        }
    ?></tbody><?php
?></table><?php
