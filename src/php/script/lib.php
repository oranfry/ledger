<?php

function get_stats(array $arr, bool $sample = true): array
{
    $count = count($arr);

    if ($count === 0) {
        return ['count' => 0];
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

    return compact('count','min','max','mean','median','stdDev');
}