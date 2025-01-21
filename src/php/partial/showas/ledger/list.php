<?php

$showValue = function ($field, $value): void {
    if ($field->type == 'icon') {
        ?><i class="icon icon--gray icon--<?= @$field->translate->{$value} ?? $value ?>"></i><?php
    } elseif ($field->type == 'color') {
        ?><span style="display: inline-block; height: 1em; width: 1em; background-color: #<?= $value ?>;">&nbsp;</span><?php
    } elseif ($field->type == 'number' && @$field->dp !== null) {
        echo htmlspecialchars(bcadd('0', $value ?? '0', $field->dp));
    } elseif ($field->type == 'number') {
        echo htmlspecialchars($value ?? '0');
    } else {
        echo htmlspecialchars($value ?? '');
    }
};

$lastgroup = 'initial';

$num_visible_cols = count($fields);

$seen_today = !$groupingInfo
    || !@$groupingInfo->currentGrouping
    || !in_array($groupingInfo->currentGrouping, $groupings);

$hasSummaries = false;

foreach ($fields as $field) {
    foreach ($field->summary as $fs) {
        $hasSummaries = true;
        break 2;
    }
}

?><table class="easy-table"><?php
    ?><thead><?php
        ?><tr><?php
            foreach ($fields as $field) {
                ?><th<?= $field->type == 'number' ? ' class="right"' : '' ?>><?= !@$field->supress_header && @$field->type != 'icon' ? $field->name : '' ?></th><?php
            }
        ?></tr><?php
    ?></thead><?php
    ?><tbody><?php
        for ($i = 0; $i <= count($lines); $i++) {
            unset($line);

            if ($i == count($lines)) {
                if ($groupingInfo) {
                    $line = (object) [
                        '_grouping' => $seen_today ? null : $groupingInfo->currentGrouping,
                    ];
                }

                $skip = true;
            } else {
                $line = $lines[$i];
                $skip = (bool) @$line->_skip;
            }

            if (
                @$summaries[@$lastgroup]
                && ($i == count($lines) || @$line->_grouping != $lastgroup)
            ) {
                $summary = $summaries[$lastgroup];
                $verified = @$verified_data[$lastgroup];

                if ($hasSummaries) {
                    ?><tr><?php
                        foreach ($fields as $field) {
                            ?><td<?= $field->type == 'number' ? ' class="right"' : '' ?>><?php
                                if ($fs = @$field->summary[0]) {
                                    $alias = $fs->alias;

                                    if ($correct = @$verified->{$field->name}) {
                                        if (@$summary->{$fs->alias} == $correct) {
                                            $icon = 'tick';
                                            $color = 'green';
                                        } else {
                                            $icon = 'times';
                                            $color = 'red';
                                        }

                                        ?><i<?php
                                        ?> class="icon icon--<?= $color ?> icon--<?= $icon ?>"<?php

                                        if (@$summary->{$field->name} != $correct) {
                                            $delta = $correct - $summary->{$field->name} ?? 0;
                                            ?> title="<?= $correct ?>    [Δ<?= $delta ?>]"<?php
                                        }

                                        ?>></i> <?php
                                    }

                                    ?><strong<?php

                                    if (count($field->summary) > 1) {
                                        ?> title="<?php

                                        foreach ($field->summary as $fsi => $fs) {
                                            if ($fsi) {
                                                echo "\n";
                                            }

                                            echo $fs->alias . ': ' . @$field->prefix . $summary->{$fs->alias};
                                        }

                                        ?>"<?php
                                    }

                                    ?>><?php

                                    echo @$field->prefix . $summary->$alias;

                                    ?></strong><?php
                                }
                            ?></td><?php
                        }
                    ?></tr><?php
                }
            }

            if (
                $groupingInfo &&
                ($i == count($lines) || $line->_grouping != $lastgroup)
            ) {
                if (!$seen_today && strcmp($groupingInfo->currentGrouping, $line->_grouping) < 0) {
                    unset($line);
                    $line = (object) ['_grouping' => $groupingInfo->currentGrouping];
                    $i--;
                    $skip = true;
                }

                if ($i > 0) {
                    ?></tbody><?php
                    ?><tbody><?php
                }

                if (@$line->_grouping) {
                    ?><tr class="<?= strcmp($line->_grouping, $groupingInfo->currentGrouping ?? '') ? '' : 'today' ?>"><?php
                        $grouptitle = $line->_grouping;

                        if (@$groupingInfo->daylink) {
                            $grouphref = strtok($_SERVER['REQUEST_URI'], '?') . '?' . ($groupingInfo->daylink)($line->_grouping) . '&back=' . base64_encode($_SERVER['REQUEST_URI']);
                            $grouptitle = "<a class=\"incog\" href=\"$grouphref\">$grouptitle</a>";
                        }

                        ?><td colspan="<?= $num_visible_cols ?>" style="line-height: 2em; font-weight: bold"><?php
                            echo $grouptitle;

                            ?><div style="float: right" class="inline-rel"><?php
                                if (count($addable) > 1) {
                                    ?><div class="inline-modal inline-modal--right"><?php
                                        ?><nav><?php
                                            foreach ($addable as $linetype) {
                                                ?><a href="#" class="trigger-add-line" data-type="<?= $linetype->name ?>" data-date="<?= $line->_grouping ?>"><i class="icon icon--gray icon--<?= $linetype->icon ?? 'doc' ?>"></i></a><?php
                                            }
                                        ?></nav><?php
                                    ?></div><?php
                                    ?><a class="inline-modal-trigger"><i class="icon icon--gray icon--plus"></i></a><?php
                                } elseif (count($addable) == 1) {
                                    ?><a href="#" class="trigger-add-line" data-type="<?= $addable[0]->name ?>" data-date="<?= $line->_grouping ?>"><i class="icon icon--gray icon--plus"></i></a><?php
                                }
                            ?></div><?php
                        ?></td><?php
                    ?></tr><?php
                }
            }

            if (!@$skip) {
                ?><tr<?php
                    echo @$parent ? " data-parent=\"$parent\"" : '';

                    ?> data-group="<?= @$line->_grouping ?>"<?php
                    ?> class="linerow <?= @$line->broken ? 'broken' : null ?>"<?php
                    ?> data-id="<?= $line->id ?>"<?php
                    ?> data-type="<?= $line->type ?>"<?php
                ?>><?php
                    foreach ($fields as $field) {
                        $value = @$field->value ? computed_field_value($line, $field->value) : @$line->{$field->name};

                        ?><td data-name="<?= $field->name ?>" data-value="<?= htmlspecialchars($value ?? '') ?>" style="<?= $field->type == 'number' ? 'text-align: right;' : null ?>"><?php
                            if ($value && $limit = @$field->width_limit) {
                                ?><div class="only-sub1200" style="overflow: hidden; white-space: nowrap; text-overflow: ellipsis; max-width: <?= $field->width_limit ?>;"><?php
                                $showValue($field, $value);
                                ?></div><?php
                                ?><div class="only-super1200"><?php
                            }

                            $showValue($field, $value);

                            if ($value && $limit = @$field->width_limit) {
                                ?></div><?php
                            }
                        ?></td><?php
                    }
                ?></tr><?php
            }

            $lastgroup = @$line->_grouping;
            $seen_today = $seen_today || ($lastgroup ?? '') == $groupingInfo->currentGrouping;
        }
    ?></tbody><?php
?></table><?php

?><nav><?php
    foreach ($addable as $linetype) {
        ?><a href="#" class="trigger-add-line" data-type="<?= $linetype->name ?>" data-date="<?= $groupingInfo->defaultGrouping ?? null ?>"><i class="icon icon--gray icon--plus"></i> <i class="icon icon--gray icon--<?= $linetype instanceof \ledger\linetype\Transferin ? 'arrowleftright' : $linetype->icon ?? 'doc' ?>"></i></a><?php
    }
?></nav><?php
?><br><br><?php

?><div id="line-container"></div><?php
