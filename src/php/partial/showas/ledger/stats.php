<?php

if (!function_exists('get_stats')) {
    function get_stats(array $arr, bool $sample = true): array {
        $count = count($arr);
        if ($count === 0) {
            return [];
        }

        // Min and Max
        $min = min($arr);
        $max = max($arr);

        // Mean
        $mean = array_sum($arr) / $count;

        // Median
        sort($arr);
        $middle = floor($count / 2);
        if ($count % 2 === 0) {
            $median = ($arr[$middle - 1] + $arr[$middle]) / 2;
        } else {
            $median = $arr[$middle];
        }

        // Standard Deviation
        $variance = 0.0;
        foreach ($arr as $val) {
            $variance += pow(($val - $mean), 2);
        }
        
        // Degrees of freedom: n - 1 for sample, n for population
        $denom = $sample ? ($count - 1) : $count;
        $stdDev = $denom > 0 ? sqrt($variance / $denom) : 0.0;

        return [
            'count' => $count,
            'min' => $min,
            'max' => $max,
            'mean' => $mean,
            'median' => $median,
            'stdDev' => $stdDev
        ];
    }
}

foreach ($fields as $field) {
    foreach ($field->summary as $fs) {
        $stats = get_stats(array_map(fn ($line) => $line->{$fs->alias}, $lines));

        ?><table class="easy-table"><?php
            ?><tbody><?php
                ?><tr><?php
                    ?><th>Field</th><?php
                    ?><td><?= $fs->alias ?><?php
                ?></tr><?php
                ?><tr><?php
                    ?><th>Count</th><?php
                    ?><td><?= $stats['count'] ?><?php
                ?></tr><?php
                ?><tr><?php
                    ?><th>Min</th><?php
                    ?><td><?= $stats['min'] ?><?php
                ?></tr><?php
                ?><tr><?php
                    ?><th>Max</th><?php
                    ?><td><?= $stats['max'] ?><?php
                ?></tr><?php
                ?><tr><?php
                    ?><th>Mean</th><?php
                    ?><td><?= $stats['mean'] ?><?php
                ?></tr><?php
                ?><tr><?php
                    ?><th>Median</th><?php
                    ?><td><?= $stats['median'] ?><?php
                ?></tr><?php
                ?><tr><?php
                    ?><th>Std. Dev.</th><?php
                    ?><td><?= $stats['stdDev'] ?><?php
                ?></tr><?php
            ?></tbody><?php
        ?></table><?php
    }
}

