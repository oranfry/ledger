<?php

if (!$groupingInfo) {
    echo "Can't show a graph - no grouping info";
    return;
}

if (!@$summaries) {
    echo "Can't show a graph - no data";
    return;
}

if (!@$graphfields) {
    echo "Can't show a graph - no fields to graph";
    return;
}

if (!($groupings = $groupingInfo->groupings ?? null)) {
    echo "Can't show a graph - could not determine groupings";
    return;
}

$numGroupings = count($groupings);
$colors = ['b02323', '245881', '24813C', '815624', '672481'];

$collinear = fn (array $a, array $b, array $c): bool => $a[0] * ($b[1] - $c[1]) + $b[0] * ($c[1] - $a[1]) + $c[0] * ($a[1] - $b[1]) == 0;

// sift fields into common scales

$scales = [];

foreach ($graphfields as $graphfield) {
    $scales[$graphfield->unit ?? '']['graphfields'][] = $graphfield;
}

$scales = array_values($scales);

$series = [];

if (count($scales) > 2) {
    echo "Can't show a graph - " . count($scales) . ' scales found, but the limit is 2';
    return;
}

foreach ($scales as $scaleKey => $scale) {
    $max = 0;
    $min = 0;
    $rmin = INF;
    $rmax = -INF;

    $maxFieldNearest = null;

    foreach ($scale['graphfields'] as $graphfield) {
        if ($graphfield->nearest) {
            $maxFieldNearest = max($maxFieldNearest ?? 0, $graphfield->nearest);
        }

        foreach ($summaries as $summary) {
            $alias = $graphfield->alias;
            $max = max($max, $summary->$alias ?? 0);
            $min = min($min, $summary->$alias ?? INF);
            $rmax = max($rmax, $summary->$alias ?? 0);
            $rmin = min($rmin, $summary->$alias ?? INF);
        }
    }

    if (!($groupingInfo->trueScale ?? true)) {
        $min = $rmin;
        $max = $rmax;
    }

    if ($nearest = $maxFieldNearest ?? $groupingInfo->nearest ?? null) {
        $max = ceil($max / $nearest) * $nearest;
        $min = floor($min / $nearest) * $nearest;
    }

    while (!$range = $max - $min) {
        $max = 1;
        $min = -1;
    }

    $scales[$scaleKey] += compact('max', 'min', 'rmax', 'rmin', 'range', 'nearest') + ['unit' => reset($scale['graphfields'])->unit];
}

$divs = [];

foreach ($groupings as $groupNum => $grouping) {
    if (@$groupingInfo->divs && in_array($grouping, $groupingInfo->divs)) {
        $divs[] = (object) [
            'label' => $grouping,
            'x' => $groupNum / $numGroupings,
        ];
    }

    if ($grouping === $groupingInfo->currentgroup) {
        $graphtoday = $groupNum / $numGroupings;
    }
}

foreach ($scales as $scaleKey => $scale) {
    foreach ($scale['graphfields'] as $graphfield) {
        $alias = $graphfield->alias;
        $bridge = $graphfield->bridge ?? true;

        $points = [];
        $offset = 0;

        if ($initial = $summaries['initial']->$alias ?? null) {
            $offset = 1;
            $points[] = [0, ($initial - $scale['min']) / $scale['range']];
        }

        foreach ($groupings as $groupNum => $grouping) {
            if (isset($summaries[$grouping])) {
                $summary = $summaries[$grouping];
                $initial ??= $summary->$alias;
                $final = $summary->$alias;
            } elseif (!$bridge) {
                continue;
            }

            $point = [
                ($groupNum + $offset) / ($numGroupings + $offset),
                (($summary->$alias ?? 0) - $scale['min']) / $scale['range'],
            ];

            // remove redundant intermediate points on a straight line

            if ($bridge && count($points) >= 2) {
                $prev1 = $points[count($points) - 1];
                $prev2 = $points[count($points) - 2];

                if ($collinear($prev2, $prev1, $point)) {
                    array_pop($points);
                }
            }

            $points[] = $point;
        }

        $graphfield->color ??= array_shift($colors) ?? str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);

        $scales[$scaleKey]['colors'][] = $graphfield->color;

        $series[$alias] = (object) [
            'color' => $graphfield->color,
            'label' => $graphfield->alias,
            'points' => $points,
            'unit' => $graphfield->unit,
        ];
    }

    $xAxis = -$scale['min'] / $scale['range'];
    $dollarDeltaTop = 50;
    $rminLabelTop = round(100 * (1 - $scale['rmin'] / $scale['range']), 2);
    $rmaxLabelTop = round(100 * ($scale['max'] - $scale['rmax']) / $scale['range'], 2);
    $zeroTop = round(100 * $scale['max'] / $scale['range'], 2);

    $showRmin = !$scale['nearest'] && abs($scale['min'] - $scale['rmin']) / $scale['range'] > 0.03 && abs($scale['max'] - $scale['rmin']) / $scale['range'] > 0.03;
    $showRmax = !$scale['nearest'] && abs($scale['max'] - $scale['rmax']) / $scale['range'] > 0.03 && abs($scale['min'] - $scale['rmax']) / $scale['range'] > 0.03;
    $showZero = !$scale['nearest'] && $scale['min'] && $scale['max'] && abs($rminLabelTop - $zeroTop) > 5;

    if ($showRmin && abs($rminLabelTop - $dollarDeltaTop) < 5 || $showRmax && abs($rmaxLabelTop - $dollarDeltaTop) < 5) {
        $dollarDeltaTop += 10;
    }

    $scales[$scaleKey] += compact('rminLabelTop', 'rmaxLabelTop', 'zeroTop', 'showRmin', 'showRmax', 'showZero', 'dollarDeltaTop');
}

?><div id="bg-container" style="position: relative; margin: 0 auto; font-size: 0.8em;"><?php
    ?><canvas id="bg" width="960" height="540"></canvas><?php

    // Legend

    if (count($scales) === 1) {
        ?><div class="graph-label graph-label--bump-down2" style="top: 100%; left: 50%; width: 300px; margin-left: -150px; text-align: center;"><?php
            foreach ($series as $oneseries) {
                ?><span style="color: <?= $oneseries->color; ?>"><?= $oneseries->label . ($oneseries->unit ? ' (' . $oneseries->unit . ')' : null); ?> &mdash;</span><?php
                ?>&nbsp;&nbsp;&nbsp;<?php
            }
        ?></div><?php
    }

    // X-axis extremes labels

    ?><div class="graph-label graph-label--bump-down" style="transform-origin: top left; transform: rotate(90deg); top: 100%; left: 0; margin-left: 0.5em"><?= reset($groupings) ?></div><?php
    ?><div class="graph-label graph-label--bump-down" style="transform-origin: top left; transform: rotate(90deg); top: 100%; left: 100%; <?= $groupingInfo->finalGroup ? 'margin-left: 0.5em' : null ?>"><?= $groupingInfo->finalGroup ?? end($groupings) ?></div><?php

    // X-axis divisions labels

    foreach ($divs as $div) {
        if ($div->label !== reset($groupings) && $div->label !== end($groupings)) {
            ?><div class="graph-label graph-label--bump-down" style="transform-origin: top left; transform: rotate(90deg); top: 100%; left: <?= $div->x * 100 ?>%; margin-left: 0.5em"><?= $div->label ?></div><?php
        }
    }

    foreach ($scales as $scaleKey => $scale) {
        $color = count($scale['colors']) === 1 ? reset($scale['colors']) : null;
        $color = $color ? 'color: ' . $color : null;
        $position = ($scaleKey ? 'left' : 'right');
        $otherPosition = ($scaleKey ? 'right' : 'left');
        $bump = 'graph-label--bump-' . ($scaleKey ? 'right' : 'left');

        // Y Max
        ?><div class="graph-label <?= $bump ?>" style="top: 0; <?= $position ?>: 100%; <?= $color ?>"><?= $scale['max'] ?></div><?php

        // Y Min
        ?><div class="graph-label <?= $bump ?> <?= $scale['min'] ? null : 'graph-label--bump-up' ?>" style="bottom: 0; <?= $position ?>: 100%; <?= $color ?>"><?= $scale['min'] ?></div><?php

        /*
        ?><div class="graph-label graph-label--bump-left" style="top: <?= $dollarDeltaTop ?>%; right: 100%; margin-top: -50px; transform: rotate(270deg); text-align: center;">Δ&nbsp;<?= number_format($final - $initial, 2, '.', '') ?></div><?php
        */

        // Unit
        ?><div class="graph-label <?= $bump ?>" style="top: <?= $scale['dollarDeltaTop'] ?>%; <?= $position ?>: 100%; <?= $color ?>"><strong><?= $scale['unit'] ?></strong></div><?php

        if ($scale['showZero']) {
            ?><div class="graph-label <?= $bump ?> graph-label--bump-up" style="top: <?= $scale['zeroTop'] ?>%; right: 100%; <?= $color ?>">0</div><?php
        }

        if ($scale['showRmin']) {
            ?><div class="graph-label <?= $bump ?>" style="top: <?= $scale['rminLabelTop'] ?>%; <?= $position ?>: 100%; <?= $color ?>"><?= $scale['rmin'] ?></div><?php
        }

        if ($scale['showRmax']) {
            ?><div class="graph-label <?= $bump ?>" style="top: <?= $scale['rmaxLabelTop'] ?>%; <?= $position ?>: 100%; <?= $color ?>"><?= $scale['rmax'] ?></div><?php
        }

        if (count($scales) > 1) {
            ?><div class="graph-label graph-label--bump-down2" style="top: 100%; <?= $otherPosition ?>: 0;"><?php
                foreach ($scale['graphfields'] as $i => $graphfield) {
                    if ($i) {
                        ?>&nbsp;&nbsp;&nbsp;<?php
                    }

                    ?><span style="color: <?= $graphfield->color; ?>"><?= $graphfield->alias ?> &mdash;</span><?php
                }
            ?></div><?php
        }
    }

?></div><?php

?><script><?php
    ?>var graphSeries = <?= json_encode($series); ?>;<?php
    ?>var xAxisProp = <?= $xAxis ?>;<?php
    ?>var highlight = '<?= adjustBrightness(defined('HIGHLIGHT') ? HIGHLIGHT : REFCOL, -30); ?>';<?php
    ?>var today = <?= $graphtoday ?: 'null' ?>;<?php
    ?>var divs = <?= json_encode(array_map(fn ($div) => $div->x, $divs)); ?>;<?php
?>
</script><?php

?><br><br><?php
