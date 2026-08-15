<?php

use OranFry\Ledger\FieldFilters;

$showValue = function ($field, $value): void {
    if ($callback = $field->transform ?? null) {
        $value = $callback($value);
    }

    if ($field->type == 'icon') {
        ?><i class="icon icon--gray icon--<?= $value ?>"></i><?php
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
                $fieldName = explode('|', $field->name, 2)[0];
                $alias = $field->alias ?? $fieldName;

                ?><th<?php

                if ($field->type == 'number') {
                    echo ' class="right"';
                }

                if ($alias !== $fieldName) {
                    echo ' title="' . $fieldName . '"';
                }

                ?>><?php

                if (!@$field->supress_header && @$field->type != 'icon') {
                    echo $alias;
                }

                ?></th><?php
            }

            if (!$ledger->groupingInfo()) {
                ?><th class="right"><?php
                ss_require('src/php/partial/snippets/addable.php', compact('ledger'));
                ?></th><?php
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
                            $fieldName = explode('|', $field->name, 2)[0];

                            ?><td<?= $field->type == 'number' ? ' class="right"' : '' ?>><?php
                                if ($fs = @$field->summary[0]) {
                                    $alias = $fs->alias;

                                    if ($correct = @$verified->$fieldName) {
                                        if (@$summary->{$fs->alias} == $correct) {
                                            $icon = 'tick';
                                            $color = 'green';
                                        } else {
                                            $icon = 'times';
                                            $color = 'red';
                                        }

                                        ?><i<?php
                                        ?> class="icon icon--<?= $color ?> icon--<?= $icon ?>"<?php

                                        if (@$summary->$fieldName != $correct) {
                                            $delta = bcsub($correct, $summary->$fieldName ?? 0, 2);
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

                        if (!$ledger->groupingInfo()) {
                            ?><td></td><?php
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

                        ?><td colspan="<?= $num_visible_cols - 1 ?>" style="line-height: 2em; font-weight: bold"><?php
                            echo $grouptitle;
                        ?></td><?php
                        ?><td class="right"><?php
                            ss_require('src/php/partial/snippets/addable.php', compact('ledger'));
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
                        @[$fieldName, $pipeline] = explode('|', $field->name, 2);

                        $value = FieldFilters::apply(@$line->$fieldName, $pipeline);

                        ?><td data-name="<?= $fieldName ?>" data-value="<?= htmlspecialchars($value ?? '') ?>" style="<?= $field->type == 'number' ? 'text-align: right;' : null ?>"><?php
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

                    if (!$ledger->groupingInfo()) {
                        ?><td></td><?php
                    }
                ?></tr><?php
            }

            $lastgroup = @$line->_grouping;
            $seen_today = $seen_today || ($lastgroup ?? '') == $groupingInfo->currentGrouping;
        }
    ?></tbody><?php
?></table><?php

if ($underTableItems = $ledger->underTableItems($viewdata)) {
    ?><div class="under-table-items snap-pad"><?php
        echo implode('&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;', array_map(function ($uti) {
            $output = '';

            if (@$uti->href) {
                $output .= "<a href=\"$uti->href\">";
            }

            $output .= $uti->text;

            if (@$uti->href) {
                $output .= '</a>';
            }

            return $output;
        }, $underTableItems));
    ?></div><?php
}

?><br><br><?php

?><div id="line-container"></div><?php
