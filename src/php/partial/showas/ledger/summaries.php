<?php

use ContextVariableSets\ContextVariableSet;

$daterange = ContextVariableSet::get('daterange');
$seen_today = !@$daterange || !@$daterange->chunk || !@$currentgroup || strcmp($currentgroup, $daterange->chunk->start()) < 0 || strcmp($currentgroup, $daterange->chunk->end()) > 0;
$groups = array_keys($summaries);

if (empty($summaries)):
    return;
endif;

?><table class="easy-table"><?php
    ?><thead><?php
        ?><tr><?php
            ?><th>date</th><?php

            foreach ($fields as $field):
                if (@$field->summary != 'sum'):
                    continue;
                endif;

                ?><th <?= $field->type == 'number' ? 'class="right"' : '' ?>><?= !@$field->supress_header && @$field->type != 'icon' ? $field->name : '' ?></th><?php
            endforeach;
        ?></tr><?php
    ?></thead><?php

    ?><tbody><?php
        foreach ($groups as $i => $group):
            $summary = $summaries[$group];
            $seen_today = ($is_current = !$seen_today && strcmp($currentgroup, $groups[$i + 1] ?? null) < 0) || $seen_today;

            ?><tr<?php if ($is_current) echo ' class="today"' ?>><?php
                ?><td><?= $group ?></td><?php
                foreach ($fields as $field):
                    if (@$field->summary != 'sum'):
                        continue;
                    endif;

                    ?><td <?= $field->type == 'number' ? 'class="right"' : '' ?>><strong><?= @$summary->{$field->name} ?: '0.00' ?></strong></td><?php
                endforeach;
            ?></tr><?php
        endforeach;
    ?></tbody><?php
?></table><?php
