<?php $lastgroup = 'initial'; ?>
<?php $daterange = ContextVariableSet::get('daterange'); ?>
<?php $num_visible_cols = count($fields); ?>
<?php $seen_today = !$has_date || !@$currentgroup || strcmp($currentgroup, $daterange->from ?? '0000-00-00') < 0 || strcmp($currentgroup, $daterange->to ?? '9999-12-31') > 0; ?>
<table class="easy-table">
    <thead>
        <tr>
            <?php foreach ($fields as $field): ?>
                <th class="<?= $field->type == 'number' ? 'right' : '' ?>"><?= !@$field->supress_header && @$field->type != 'icon' ? $field->name : '' ?></th>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
        <?php for ($i = 0; $i <= count($lines); $i++): ?>
            <?php $skip = false; ?>
            <?php unset($line); ?>
            <?php if ($i == count($lines)) : ?>
                <?php if (!$seen_today) : ?>
                    <?php $line = (object) ['date' => $currentgroup]; ?>
                <?php else : ?>
                    <?php $line = (object) ['date' => null]; ?>
                <?php endif; ?>
                <?php $skip = true; ?>
            <?php else : ?>
                <?php $line = $lines[$i]; ?>
            <?php endif; ?>
            <?php if ($has_date && @$summaries[@$lastgroup] && ($i == count($lines) || @$line->date != $lastgroup)): ?>
                <?php $summary = $summaries[$lastgroup]; ?>
                <tr>
                    <?php foreach ($fields as $field): ?>
                        <td class="<?= $field->type == 'number' ? 'right' : '' ?>">
                            <?php if (@$summary->{$field->name}): ?>
                                <strong><?= @$field->prefix . $summary->{$field->name} ?></strong>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endif; ?>
            <?php if ($i == count($lines) || @$line->date != $lastgroup): ?>
                <?php if (!$seen_today && strcmp($currentgroup, @$line->date) < 0) : ?>
                    <?php unset($line); ?>
                    <?php $line = (object) ['date' => $currentgroup]; ?>
                    <?php $i--; ?>
                    <?php $skip = true; ?>
                <?php endif; ?>
                <?php if ($i > 0): ?>
                    </tbody>
                    <tbody>
                <?php endif; ?>
                <?php if (@$line->date) : ?>
                    <tr class="<?= strcmp($line->date, $currentgroup ?? '') ? '' : 'today' ?>">
                        <?php $grouphref = strtok($_SERVER['REQUEST_URI'], '?') . '?' . ($daterange ? $daterange->constructQuery(['period' => 'day', 'rawrawfrom' => $line->date]) . '&' : '') . 'back=' . base64_encode($_SERVER['REQUEST_URI']); ?>
                        <?php $grouptitle = "<a class=\"incog\" href=\"{$grouphref}\">" . $line->date . "</a>"; ?>
                        <td colspan="<?= $num_visible_cols ?>" style="line-height: 2em; font-weight: bold">
                            <?= $grouptitle ?>
                            <div style="float: right" class="inline-rel">
                                <?php if (count($addable) > 1): ?>
                                    <div class="inline-modal inline-modal--right">
                                        <nav>
                                            <?php foreach ($addable as $linetype): ?>
                                                <a href="#" class="trigger-add-line" data-type="<?= $linetype->name ?>" data-date="<?= $line->date ?>"><i class="icon icon--gray icon--<?= $linetype->icon ?? 'doc' ?>"></i></a>
                                            <?php endforeach; ?>
                                        </nav>
                                    </div>
                                    <a class="inline-modal-trigger"><i class="icon icon--gray icon--plus"></i></a>
                                <?php elseif (count($addable) == 1): ?>
                                    <a href="#" class="trigger-add-line" data-type="<?= $addable[0]->name ?>" data-date="<?= $line->date ?>"><i class="icon icon--gray icon--plus"></i></a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endif ?>
            <?php endif ?>
            <?php if (!@$skip): ?>
                <tr
                    <?= @$parent ? "data-parent=\"{$parent}\"" : '' ?>
                    data-group="<?= @$line->date ?>"
                    class="linerow <?= @$line->broken ? 'broken' : null ?>"
                    data-id="<?= $line->id ?>"
                    data-type="<?= $line->type ?>"
                >
                    <?php foreach ($fields as $fi => $field): ?>
                        <?php $value = @$field->value ? computed_field_value($line, $field->value) : @$line->{$field->name}; ?>
                        <td data-name="<?= $field->name ?>" data-value="<?= htmlspecialchars($value ?? '') ?>" style="<?= $field->type == 'number' ? 'text-align: right;' : null ?>">
                            <?php if (!$fi) : ?><div class="select-column"><input style="display: none" type="checkbox"></div><?php endif; ?>
                            <?php if ($field->type == 'icon') : ?>
                                <i class="icon icon--gray icon--<?= @$field->translate->{$value} ?? $value ?>"></i>
                            <?php elseif ($field->type == 'color') : ?>
                                <span style="display: inline-block; height: 1em; width: 1em; background-color: #<?= $value ?>;">&nbsp;</span>
                            <?php elseif ($field->type == 'number' && @$field->dp !== null) : ?>
                                <?php echo htmlspecialchars(bcadd('0', $value ?? '0', $field->dp)); ?>
                            <?php elseif ($field->type == 'number') : ?>
                                <?php echo htmlspecialchars($value ?? '0'); ?>
                            <?php else : ?>
                                <?php echo htmlspecialchars($value ?? ''); ?>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endif; ?>
            <?php $lastgroup = @$line->date; ?>
            <?php $seen_today = $seen_today || ($lastgroup ?? '') == $currentgroup; ?>
        <?php endfor; ?>
    </tbody>
</table>
<nav>
    <?php foreach ($addable as $linetype): ?>
        <a href="#" class="trigger-add-line" data-type="<?= $linetype->name ?>" data-date="<?= $defaultgroup ?>"><i class="icon icon--gray icon--plus"></i> <i class="icon icon--gray icon--<?= $linetype instanceof \ledger\linetype\Transferin ? 'arrowleftright' : $linetype->icon ?? 'doc' ?>"></i></a>
    <?php endforeach; ?>
</nav>
<br><br>
<?php foreach ($linetypes as $linetype): ?>
    <div data-type="<?php echo $linetype->name ?>" class="line floatline edit-form" style="display: none">
        <div class="lineclose">close</div>
        <h3><?= ucfirst($linetype->name) ?></h3>
        <form method="post">
            <?php foreach ($linetype->fields as $field) : ?>
                <?php if ($field->type == 'file') : ?>
                    <?php $value = @$line->{"{$field->name}_path"}; ?>
                <?php else : ?>
                    <?php $value = @$line->{$field->name} ?: @$_GET[$field->name] ?: @$field->default; ?>
                <?php endif; ?>
                <?php $options = @$field->options; ?>
                <?php if ($value && $options && !in_array($value, $options)) : ?>
                    <?php array_unshift($options, $value); ?>
                <?php endif; ?>
                <div class="form-row">
                    <div class="form-row__label"><?= @$field->label ?? $field->name ?></div>
                    <div class="form-row__value <?= @$field->readonly ? 'noedit' : null ?>">
                        <?php ss_require("src/php/partial/fieldtype/{$field->type}.php", compact('field', 'value', 'options')); ?>
                    </div>
                    <div style="clear: both"></div>
                </div>
            <?php endforeach; ?>
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
<?php endforeach; ?>
