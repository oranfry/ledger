<?php $daterange = ContextVariableSet::get('daterange'); ?>
<?php $seen_today = !@$daterange || !@$currentgroup || strcmp($currentgroup, $daterange->chunk->start()) < 0 || strcmp($currentgroup, $daterange->chunk->end()) > 0; ?>
<?php $groups = array_keys($summaries); ?>
<?php if (!empty($summaries)): ?>
    <table class="easy-table">
        <thead>
            <tr>
                <th>date</th>
                <?php foreach ($fields as $field): ?>
                    <?php if (@$field->summary != 'sum'): ?>
                        <?php continue; ?>
                    <?php endif ?>
                    <th <?= $field->type == 'number' ? 'class="right"' : '' ?>><?= !@$field->supress_header && @$field->type != 'icon' ? $field->name : '' ?></th>
                <?php endforeach ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($groups as $i => $group): ?>
                <?php $summary = $summaries[$group]; ?>
                <?php if ($is_current = !$seen_today && strcmp($currentgroup, $groups[$i + 1] ?? null) < 0): ?>
                    <?php $seen_today = true; ?>
                <?php endif ?>

                <tr<?php if ($is_current) echo ' class="today"' ?>>
                    <td><?= $group ?></td>
                    <?php foreach ($fields as $field): ?>
                        <?php if (@$field->summary != 'sum'): ?>
                            <?php continue; ?>
                        <?php endif ?>
                        <td <?= $field->type == 'number' ? 'class="right"' : '' ?>><strong><?= @$summary->{$field->name} ?: '0.00' ?></strong></td>
                    <?php endforeach ?>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
<?php endif ?>

