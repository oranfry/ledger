<?php

foreach ($fields as $field) {
    foreach ($field->summary as $fs) {
        $stats = get_stats(array_map(fn ($line) => $line->{$fs->alias}, $summaries));

        ss_require('src/php/partial/showas/ledger/snippets/stats-table.php', compact('stats', 'fs'));
    }
}

