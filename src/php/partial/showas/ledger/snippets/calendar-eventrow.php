<?php

use OranFry\Obex\Obex;

for ($j = 0; $j < 7; $j++) {
    $date = date_shift($from, '+' . ($i + $j - $backtrack) . ' day');

    if (strcmp($date, $from) < 0 || strcmp($date, $to) > 0) {
        ?><div class="cell eventcell void">&nbsp;</div><?php
        continue;
    }

    ?><div class="cell eventcell"><?php

    for (; $c < count($records); $c++) {
        unset($record);

        $record = $records[$c];

        if ($record->date != $date) {
            break;
        }

        ?><a class="col" href="<?= editlink($record->id, $record->type) ?>" style="white-space: nowrap;" title="<?php record_title($record, $fields) ?>"><?php

        $firstfield = Obex::find($fields, 'type', 'notin', ['date', 'icon']);

        if ($iconfield = Obex::find($fields, 'type', 'is', 'icon')) {
            ?><i class="icon icon--gray icon--<?= $record->{$iconfield->name} ?>" alt=""></i><?php
        }

        if ($firstfield) {
            ?><span><?= $record->{$firstfield->name} ?></span><?php
        }

        ?></a><?php
    }

    ?></div><?php
}