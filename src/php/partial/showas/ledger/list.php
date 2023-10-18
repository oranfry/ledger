<?php

$lastgroup = 'initial';

$num_visible_cols = count($fields);
$seen_today = !$dateinfo || !@$currentgroup || strcmp($currentgroup, $dateinfo->start ?? '0000-00-00') < 0 || strcmp($currentgroup, $dateinfo->end ?? '9999-12-31') > 0;

?><table class="easy-table"><?php
    ?><thead><?php
        ?><tr><?php
            foreach ($fields as $field):
                ?><th<?= $field->type == 'number' ? ' class="right"' : '' ?>><?= !@$field->supress_header && @$field->type != 'icon' ? $field->name : '' ?></th><?php
            endforeach;
        ?></tr><?php
    ?></thead><?php
    ?><tbody><?php
        for ($i = 0; $i <= count($lines); $i++):
            $skip = false;
            unset($line);

            if ($i == count($lines)) :
                if (!$seen_today) :
                    $line = (object) ['date' => $currentgroup];
                else :
                    $line = (object) ['date' => null];
                endif;

                $skip = true;
            else :
                $line = $lines[$i];
            endif;

            if ($dateinfo && @$summaries[@$lastgroup] && ($i == count($lines) || @$line->{$dateinfo->field} != $lastgroup)):
                $summary = $summaries[$lastgroup];

                ?><tr><?php
                    foreach ($fields as $field):
                        ?><td<?= $field->type == 'number' ? ' class="right"' : '' ?>><?php
                            if (@$summary->{$field->name}):
                                ?><strong><?= @$field->prefix . $summary->{$field->name} ?></strong><?php
                            endif;
                        ?></td><?php
                    endforeach;
                ?></tr><?php
            endif;

            if ($i == count($lines) || $dateinfo && $line->{$dateinfo->field} != $lastgroup):
                if (!$seen_today && strcmp($currentgroup, @$line->{$dateinfo->field}) < 0) :
                    unset($line);
                    $line = (object) [$dateinfo->field => $currentgroup];
                    $i--;
                    $skip = true;
                endif;

                if ($i > 0):
                    ?></tbody><?php
                    ?><tbody><?php
                endif;

                if (@$line->{$dateinfo->field}):
                    ?><tr class="<?= strcmp($line->{$dateinfo->field}, $currentgroup ?? '') ? '' : 'today' ?>"><?php
                        $grouptitle = $line->{$dateinfo->field};

                        if (@$dateinfo->daylink):
                            $grouphref = strtok($_SERVER['REQUEST_URI'], '?') . '?' . ($dateinfo->daylink)($line->{$dateinfo->field}) . '&back=' . base64_encode($_SERVER['REQUEST_URI']);
                            $grouptitle = "<a class=\"incog\" href=\"$grouphref\">$grouptitle</a>";
                        endif;

                        ?><td colspan="<?= $num_visible_cols ?>" style="line-height: 2em; font-weight: bold"><?php
                            echo $grouptitle;
                            ?><div style="float: right" class="inline-rel"><?php
                                if (count($addable) > 1):
                                    ?><div class="inline-modal inline-modal--right"><?php
                                        ?><nav><?php
                                            foreach ($addable as $linetype):
                                                ?><a href="#" class="trigger-add-line" data-type="<?= $linetype->name ?>" data-date="<?= $line->{$dateinfo->field} ?>"><i class="icon icon--gray icon--<?= $linetype->icon ?? 'doc' ?>"></i></a><?php
                                            endforeach;
                                        ?></nav><?php
                                    ?></div><?php
                                    ?><a class="inline-modal-trigger"><i class="icon icon--gray icon--plus"></i></a><?php
                                elseif (count($addable) == 1):
                                    ?><a href="#" class="trigger-add-line" data-type="<?= $addable[0]->name ?>" data-date="<?= $line->{$dateinfo->field} ?>"><i class="icon icon--gray icon--plus"></i></a><?php
                                endif;
                            ?></div><?php
                        ?></td><?php
                    ?></tr><?php
                endif;
            endif;

            if (!@$skip):
                ?><tr<?php
                    echo @$parent ? " data-parent=\"$parent\"" : '';

                    ?> data-group="<?= @$line->{$dateinfo->field} ?>"<?php
                    ?> class="linerow <?= @$line->broken ? 'broken' : null ?>"<?php
                    ?> data-id="<?= $line->id ?>"<?php
                    ?> data-type="<?= $line->type ?>"<?php
                ?>><?php
                    foreach ($fields as $fi => $field):
                        $value = @$field->value ? computed_field_value($line, $field->value) : @$line->{$field->name};

                        ?><td data-name="<?= $field->name ?>" data-value="<?= htmlspecialchars($value ?? '') ?>" style="<?= $field->type == 'number' ? 'text-align: right;' : null ?>"><?php
                            if (!$fi):
                                ?><div class="select-column"><input style="display: none" type="checkbox"></div><?php
                            endif;

                            if ($field->type == 'icon'):
                                ?><i class="icon icon--gray icon--<?= @$field->translate->{$value} ?? $value ?>"></i><?php
                            elseif ($field->type == 'color'):
                                ?><span style="display: inline-block; height: 1em; width: 1em; background-color: #<?= $value ?>;">&nbsp;</span><?php
                            elseif ($field->type == 'number' && @$field->dp !== null):
                                echo htmlspecialchars(bcadd('0', $value ?? '0', $field->dp));
                            elseif ($field->type == 'number'):
                                echo htmlspecialchars($value ?? '0');
                            else:
                                echo htmlspecialchars($value ?? '');
                            endif;
                        ?></td><?php
                    endforeach;
                ?></tr><?php
            endif;

            $lastgroup = @$line->{$dateinfo->field};
            $seen_today = $seen_today || ($lastgroup ?? '') == $currentgroup;
        endfor;
    ?></tbody><?php
?></table><?php

?><nav><?php
    foreach ($addable as $linetype):
        ?><a href="#" class="trigger-add-line" data-type="<?= $linetype->name ?>" data-date="<?= $defaultgroup ?>"><i class="icon icon--gray icon--plus"></i> <i class="icon icon--gray icon--<?= $linetype instanceof \ledger\linetype\Transferin ? 'arrowleftright' : $linetype->icon ?? 'doc' ?>"></i></a><?php
    endforeach;
?></nav><?php
?><br><br><?php

foreach ($linetypes as $linetype):
    ?><div data-type="<?php echo $linetype->name ?>" class="line floatline edit-form" style="display: none"><?php
        ?><div class="lineclose">close</div><?php
        ?><h3><?= ucfirst($linetype->name) ?></h3><?php
        ?><form method="post"><?php
            foreach ($linetype->fields as $field):
                if ($field->type == 'file') :
                    $value = @$line->{"{$field->name}_path"};
                else :
                    $value = @$line->{$field->name} ?: @$_GET[$field->name] ?: @$field->default;
                endif;

                $options = @$field->options;

                if ($value && $options && !in_array($value, $options)) :
                    array_unshift($options, $value);
                endif;

                ?><div class="form-row"><?php
                    ?><div class="form-row__label"><?= @$field->label ?? $field->name ?></div><?php
                    ?><div class="form-row__value <?= @$field->readonly ? 'noedit' : null ?>"><?php
                        ss_require("src/php/partial/fieldtype/{$field->type}.php", compact('field', 'value', 'options'));
                    ?></div><?php
                    ?><div style="clear: both"></div><?php
                ?></div><?php
             endforeach;

            ?><div class="form-row"><?php
                ?><div class="form-row__label">&nbsp;</div><?php
                ?><div class="form-row__value"><?php
                    ?><button class="saveline button button--main" type="button">Save</button><?php
                    ?><button class="bulkadd button button--main" type="button" style="display: none">Bulk Add</button><?php
                ?></div><?php
                ?><div style="clear: both"></div><?php
            ?></div><?php
        ?></form><?php
    ?></div><?php
endforeach;
