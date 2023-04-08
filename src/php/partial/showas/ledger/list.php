<?php $lastgroup = 'initial'; ?>
<?php $daterange = ContextVariableSet::get('daterange'); ?>
<?php $num_visible_cols = count($fields) - count($mask_fields); ?>
<?php $seen_today = !@$daterange || !@$currentgroup || strcmp($currentgroup, $daterange->from) < 0 || strcmp($currentgroup, $daterange->to) > 0; ?>
<div style="float: left">
    <table class="easy-table">
        <thead>
            <tr>
                <th class="select-column printhide"><i class="icon icon--gray icon--smalldot-o selectall"></i></td></th>
                <?php foreach ($fields as $field): ?>
                    <th class="<?= $field->type == 'number' ? 'right' : '' ?>" <?php if (in_array($field->name, $mask_fields)): ?>style="display: none"<?php endif ?>><?= !@$field->supress_header && @$field->type != 'icon' ? $field->name : '' ?></th>
                <?php endforeach ?>
            </tr>
        </thead>
        <tbody>
            <?php for ($i = 0; $i <= count($lines); $i++): ?>
                <?php
                    $skip = false;
                    unset($line);

                    if ($i == count($lines)) {
                        if (!$seen_today) {
                            $line = (object) ['date' => $currentgroup];
                        } else {
                            $line = (object) ['date' => null];
                        }

                        $skip = true;
                    } else {
                        $line = $lines[$i];
                    }
                ?>

                <?php if (@$summaries[@$lastgroup] && ($i == count($lines) || $line->date != $lastgroup)): ?>
                    <?php $summary = $summaries[$lastgroup]; ?>
                    <tr>
                        <td class="select-column printhide"></td>
                        <?php foreach ($fields as $field): ?>
                            <td class="<?= $field->type == 'number' ? 'right' : '' ?>" <?php if (in_array($field->name, $mask_fields)): ?>style="display: none"<?php endif ?>>
                                <?php if (@$summary->{$field->name}): ?>
                                    <strong><?= @$field->prefix . $summary->{$field->name} ?></strong>
                                <?php endif ?>
                            </td>
                        <?php endforeach ?>
                    </tr>
                <?php endif ?>

                <?php if ($i == count($lines) || $line->date != $lastgroup): ?>
                    <?php
                        if (!$seen_today && strcmp($currentgroup, @$line->date) < 0) {
                            unset($line);

                            $line = (object) ['date' => $currentgroup];
                            $i--;
                            $skip = true;
                        }
                    ?>

                    <?php if ($i > 0): ?>
                        </tbody>
                        <tbody>
                    <?php endif ?>

                    <?php if (@$line->date) : ?>
                        <tr class="<?= strcmp($line->date, $currentgroup ?? '') ? '' : 'today' ?>">
                            <td class="select-column printhide"><i class="icon icon--gray icon--smalldot-o selectall"></i></td>
                            <?php $grouphref = strtok($_SERVER['REQUEST_URI'], '?') . '?' . ($daterange ? $daterange->constructQuery(['period' => 'day', 'rawrawfrom' => $line->date]) . '&' : '') . 'back=' . base64_encode($_SERVER['REQUEST_URI']); ?>
                            <?php $grouptitle = "<a class=\"incog\" href=\"{$grouphref}\">" . $line->date . "</a>"; ?>
                            <td colspan="<?= $num_visible_cols ?>" style="line-height: 2em; font-weight: bold">
                                <?= $grouptitle ?>
                                <div style="float: right" class="inline-rel">
                                    <?php if (count($addable) > 1): ?>
                                        <div class="inline-modal inline-modal--right"><nav><?php foreach ($addable as $linetype): ?><?php if ($linetype instanceof \ledger\linetype\Transferout): ?><?php continue; ?><?php endif ?><a href="#" class="trigger-add-line" data-type="<?= $linetype->name ?>" data-date="<?= $line->date ?>"><i class="icon icon--gray icon--<?= $linetype instanceof \ledger\linetype\Transferin ? 'arrowleftright' : $linetype->icon ?>"></i></a><?php endforeach ?></nav></div>
                                        <a class="inline-modal-trigger"><i class="icon icon--gray icon--plus"></i></a>
                                    <?php elseif (count($addable) == 1): ?>
                                        <a href="#" class="trigger-add-line" data-type="<?= $addable[0]->name ?>" data-date="<?= $line->date ?>"><i class="icon icon--gray icon--plus"></i></a>
                                    <?php endif ?>
                                </div>
                            </td>
                        </tr>
                    <?php endif ?>
                <?php endif ?>

                <?php if (!@$skip): ?>
                    <tr
                        <?= @$parent ? "data-parent=\"{$parent}\"" : '' ?>
                        data-group="<?= $line->date ?>"
                        class="linerow <?= @$line->broken ? 'broken' : null ?>"
                        data-id="<?= $line->id ?>"
                        data-type="<?= $line->type ?>"
                    >
                        <td class="select-column printhide"><input type="checkbox"></td>
                        <?php foreach ($fields as $field): ?>
                            <?php $value = @$field->value ? computed_field_value($line, $field->value) : @$line->{$field->name}; ?>
                            <td data-name="<?= $field->name ?>" data-value="<?= htmlspecialchars($value ?? '') ?>" style="<?php if ($field->type == 'number'): ?>text-align: right;<?php endif ?><?php if (in_array($field->name, $mask_fields)): ?>display: none;<?php endif ?>"><?php
                                if ($field->type == 'icon') {
                                    ?><i class="icon icon--gray icon--<?= @$field->translate->{$value} ?? $value ?>"></i><?php
                                } elseif ($field->type == 'color') {
                                    ?><span style="display: inline-block; height: 1em; width: 1em; background-color: #<?= $value ?>;">&nbsp;</span><?php
                                } elseif ($field->type == 'file' && @$line->{"{$field->name}_path"}) {
                                   ?><a href="/api/download/<?= $line->{"{$field->name}_path"} ?>" download><i class="icon icon--gray icon--<?= @$field->translate[$field->icon] ?? $field->icon ?>"></i></a><?php
                                } else {
                                    echo htmlspecialchars($value ?? '');
                                }
                            ?></td>
                        <?php endforeach ?>
                    </tr>
                <?php endif ?>

                <?php $lastgroup = @$line->date; ?>
                <?php $seen_today = $seen_today || ($lastgroup ?? '') == $currentgroup; ?>
            <?php endfor ?>
        </tbody>
    </table>

    <nav>
        <?php foreach ($addable as $linetype): ?>
            <?php if ($linetype instanceof \ledger\linetype\Transferout): ?><?php continue; ?><?php endif ?>
            <a href="#" class="trigger-add-line" data-type="<?= $linetype->name ?>" data-date="<?= $defaultgroup ?>"><i class="icon icon--gray icon--plus"></i> <i class="icon icon--gray icon--<?= $linetype instanceof \ledger\linetype\Transferin ? 'arrowleftright' : $linetype->icon ?>"></i></a>
        <?php endforeach ?>
    </nav>
    <br><br>
</div>

<?php foreach ($linetypes as $linetype): ?>
    <div data-type="<?php echo $linetype->name ?>" class="line floatline edit-form" style="display: none">
        <div class="lineclose">close</div>
        <h3><?= ucfirst($linetype->name) ?></h3>
        <form method="post">
            <?php
                foreach ($linetype->fields as $field) {
                    if ($field->type == 'file') {
                        $value = @$line->{"{$field->name}_path"};
                    } else {
                        $value = @$line->{$field->name} ?: @$_GET[$field->name] ?: @$field->default;
                    }

                    $options = @$field->options;

                    if ($value && $options && !in_array($value, $options)) {
                        array_unshift($options, $value);
                    } ?>
                    <div class="form-row">
                        <div class="form-row__label"><?= @$field->label ?? $field->name ?></div>
                        <div class="form-row__value">
                            <?php ss_require("src/php/partial/fieldtype/{$field->type}.php", compact('field', 'value', 'options')); ?>
                        </div>
                        <div style="clear: both"></div>
                    </div>
                    <?php
                }
            ?>

            <div class="form-row">
                <div class="form-row__label">&nbsp;</div>
                <div class="form-row__value">
                    <button class="saveline button button--main" type="button">Save</button>
                    <button class="bulkadd button button--main" type="button" style="display: none">Bulk Add</button>
                </div>
                <div style="clear: both"></div>
            </div>
        </form>
    </div>
<?php endforeach ?>

<div data-type="generic" class="line floatline bulk-edit-form" style="display: none">
    <div class="lineclose">close</div>
    <h3>Multiple Selections</h3>
    <form>
        <?php foreach ($fields as $field): ?>
            <?php if (!($field_inc = search_plugins("src/php/partial/fieldtype/{$field->type}.php")) || !file_exists($field_inc)) : ?>
                <?php continue; ?>
            <?php endif ?>

            <div class="form-row">
                <div class="form-row__label"><?= $field->name ?></div>
                <div class="form-row__value">
                    <?php if ($field->type != 'file'): ?>
                        <div style="position: absolute; left: 0;">
                            <input type="checkbox" data-for="<?= $field->name ?>">
                        </div>
                    <?php endif ?>
                    <?php $options = @$field->options; ?>
                    <?php $bulk = true; require $field_inc; unset($bulk); ?>
                </div>
                <div style="clear: both"></div>
            </div>
        <?php endforeach ?>

        <div class="form-row">
            <div class="form-row__label">&nbsp;</div>
            <div class="form-row__value">
                <button type="button" class="bulksave button button--main">Save</button>
            </div>
            <div style="clear: both"></div>
        </div>
    </form>
</div>
