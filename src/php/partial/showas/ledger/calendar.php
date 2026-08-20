<?php
$daterange = ContextVariableSet::get('daterange');
$from = $daterange->from;
$to = $daterange->to;

function record_title($record, $fields)
{
    foreach ($fields as $field) {
        if (in_array($field->name, ['icon'])) {
            continue;
        }

        echo is_callable(@$field->prefix) ? ($field->prefix)($record) : @$field->prefix;
        echo($field->type == 'fake' ? $field->value : $record->{$field->name});
        echo is_callable(@$field->suffix) ? ($field->suffix)($record) : @$field->suffix;
        echo ' ';
    }
}

$c = 0;
for ($backtrack = 0; date('D', strtotime(date_shift($from, "-{$backtrack} day"))) != 'Mon'; $backtrack++);
?>

<div class="calendar-month">
    <div class="row dowrow"><div class="cell dowcell">Mon</div><div class="cell dowcell">Tue</div><div class="cell dowcell">Wed</div><div class="cell dowcell">Thu</div><div class="cell dowcell">Fri</div><div class="cell dowcell">Sat</div><div class="cell dowcell">Sun</div></div>
    <?php $prevMonthLabel = date('M', strtotime($from)); ?>
    <?php for ($i = 0; ; $i += 7): ?>
        <div class="row daterow"><?php
            require __DIR__ . '/../calendar-daterow.php';
            $prevMonthLabel = $monthLabel;
        ?></div>
        <div class="row eventrow"><?php
            require __DIR__ . '/../calendar-eventrow.php';
        ?></div>
        <?php if (strcmp($date, $to) >= 0) {
            break;
        } ?>
    <?php endfor ?>
</div>
