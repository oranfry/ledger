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

if (!$groupings) {
    echo "Can't show a graph - could not determine groupings";
    return;
}

$trueScale = $groupingInfo->trueScale ?? true;
$style = $groupingInfo->graphStyle ?? 'line';

foreach ($groupingInfo->divs ?? $groupings as $key => $div) {
    if (is_string($div)) {
        $groupingInfo->divs[$key] = (object) [
            'grouping' => $div,
        ];
    }
}

$numGroupings = count($groupings);
$colors = ['#b02323', '#245881', '#24813C', '#815624', '#672481'];

$collinear = fn (array $a, array $b, array $c): bool => $a[0] * ($b[1] - $c[1]) + $b[0] * ($c[1] - $a[1]) + $c[0] * ($a[1] - $b[1]) == 0;

// sift fields into common scales

$scales = [];

foreach ($graphfields as $graphfield) {
    $scales[$graphfield->unit ?? '']['graphfields'][] = $graphfield;
}

$series = [];

if (count($scales) > 2) {
    echo "Can't show a graph - " . count($scales) . ' scales found, but the limit is 2';
    return;
}

foreach ($scales as $unit => $scale) {
    $max = 0;
    $min = 0;
    $rmin = INF;
    $rmax = -INF;

    $maxFieldMax = null;
    $minFieldMin = null;
    $maxFieldNearest = null;

    foreach ($scale['graphfields'] as $graphfield) {
        if (null !== $graphfield->max ?? null) {
            $maxFieldMax = max($maxFieldMax ?? -INF, $graphfield->max);
        }

        if (null !== $graphfield->min ?? null) {
            $minFieldMin = min($minFieldMin ?? INF, $graphfield->min);
        }

        if ($graphfield->nearest ?? null) {
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

    if (!$trueScale) {
        $min = $rmin;
        $max = $rmax;
    }

    if (null !== $maxFieldMax) {
        $max = $maxFieldMax;
    }

    if (null !== $minFieldMin) {
        $min = $minFieldMin;
    }

    if ($nearest = $maxFieldNearest ?? $groupingInfo->nearest ?? null) {
        $max = ceil($max / $nearest) * $nearest;
        $min = floor($min / $nearest) * $nearest;
    }

    while (!$range = $max - $min) {
        $max = 1;
        $min = -1;
    }

    $scales[$unit] += compact('max', 'min', 'rmax', 'rmin', 'range', 'nearest', 'unit');
}

$scales = array_values($scales);

$offset = (int) (
    array_reduce($scales, function ($carry, $scale) use ($summaries) {
        return $carry || array_reduce($scale['graphfields'], function ($carry, $graphfield) use ($summaries) {
            return $carry || ($summaries['initial']->{$graphfield->alias} ?? null);
        }, false);
    }, false)
);

foreach ($scales as $scaleKey => $scale) {
    foreach ($scale['graphfields'] as $graphfield) {
        $alias = $graphfield->alias;
        $bridge = $graphfield->bridge ?? true;

        $points = [];

        if ($initial = $summaries['initial']->$alias ?? null) {
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
                ($groupNum + $offset) / ($numGroupings + (int) ($style === 'bar') + $offset - 1),
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
        $scales[$scaleKey]['guides'] = array_merge($scales[$scaleKey]['guides'] ?? [], $graphfield->guides ?? []);

        $series[$alias] = (object) [
            'color' => $graphfield->color,
            'label' => $graphfield->alias,
            'points' => $points,
            'unit' => $graphfield->unit,
        ];
    }

    $xAxis = -$scale['min'] / $scale['range'];
    $rminLabelTop = round(100 * (1 - $scale['rmin'] / $scale['range']), 2);
    $rmaxLabelTop = round(100 * ($scale['max'] - $scale['rmax']) / $scale['range'], 2);
    $zeroTop = round(100 * $scale['max'] / $scale['range'], 2);

    $showRmin = $trueScale && abs($scale['min'] - $scale['rmin']) / $scale['range'] > 0.03 && abs($scale['max'] - $scale['rmin']) / $scale['range'] > 0.03;
    $showRmax = $trueScale && abs($scale['max'] - $scale['rmax']) / $scale['range'] > 0.03 && abs($scale['min'] - $scale['rmax']) / $scale['range'] > 0.03;
    $showZero = $trueScale && $scale['min'] && $scale['max'] && abs($rminLabelTop - $zeroTop) > 5;

    $scales[$scaleKey] += compact('rminLabelTop', 'rmaxLabelTop', 'zeroTop', 'showRmin', 'showRmax', 'showZero');
}

foreach ($scales as $scaleKey => $scale) {
    $scales[$scaleKey] += ['color' => count($scale['colors']) === 1 ? reset($scale['colors']) : null];
    unset($scales[$scaleKey]['colors']);

    foreach ($scale['guides'] as $guideKey => $guide) {
        if (is_string($guide) && preg_match('/^([1-9][0-9]*(?:\.[0-9]+)?)%$/', $guide, $matches)) {
            $scales[$scaleKey]['guides'][$guideKey] = $matches[1] * $scale['range'] / 100 + $scale['min'];
        }
    }
}

$divs = [];

foreach ($groupings as $groupingNum => $grouping) {
    $div = array_values(array_filter($groupingInfo->divs ?? [], fn ($div) => $div->grouping === $grouping))[0] ?? null;

    if ($div) {
        $divs[] = (object) (
            ['x' => ($groupingNum + $offset) / ($numGroupings + $offset + (int) ($style === 'bar') - 1)]
            + (array) $div
            + ['label' => $grouping]
        );
    }

    if ($grouping === ($groupingInfo->currentgroup ?? null)) {
        $graphtoday = [
            ($groupingNum + $offset) / ($numGroupings + $offset - 1),
            ($groupingNum + $offset + 1) / ($numGroupings + $offset - 1),
        ];
    }
}

$guides = [];

foreach ($scales as $scaleKey => $scale) {
    foreach ($scale['guides'] as $guideValue) {
        $y = bcdiv(bcsub($guideValue, $scale['min'], 4), $scale['range'], 4);

        if (isset($guides[$y])) {
            $guides[$y]->color = '#efefef';
            $guides[$y]->labels[$scaleKey] = $guideValue;
        } else {
            $guides[$y] = (object) (compact('y') + [
                'color' => $scale['color'],
                'labels' => [
                    $scaleKey => $guideValue,
                ]
            ]);
        }
    }
}

$guides = array_values($guides);

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

    if (!$divs) {
        ?><div class="graph-label graph-label--x graph-label--bump-down" style="left: 0"><?= reset($groupings) ?></div><?php
        ?><div class="graph-label graph-label--x graph-label--bump-down" style="left: 100%"><?= end($groupings) ?></div><?php
    }

    // X-axis divisions labels

    foreach ($divs as $div) {
        ?><div class="graph-label graph-label--x graph-label--bump-down" style="left: <?= ($div->x + ($style === 'bar' ? 0.5 / count($groupings) : 0)) * 100 ?>%" title="<?= $div->grouping ?>"><?= $div->label ?></div><?php
    }

    // Y-axis guide labels

    foreach ($guides as $guide) {
        foreach ($scales as $scaleKey => $scale) {
            if ($label = $guide->labels[$scaleKey] ?? null) {
                $position = ($scaleKey ? 'left' : 'right');
                $bump = 'graph-label--bump-' . ($scaleKey ? 'right' : 'left');

                ?><div class="graph-label graph-label--y <?= $bump ?>" style="top: <?= (1 - $guide->y) * 100 ?>%; <?= $position ?>: 100%; color: <?= $scale['color'] ?>; margin-top: -0.5em"><?= $label ?></div><?php
            }
        }
    }

    foreach ($scales as $scaleKey => $scale) {
        $color = $scale['color'] ? 'color: ' . $scale['color'] : null;
        $position = ($scaleKey ? 'left' : 'right');
        $otherPosition = ($scaleKey ? 'right' : 'left');
        $bump = 'graph-label--bump-' . ($scaleKey ? 'right' : 'left');

        // Y Max
        ?><div class="graph-label <?= $bump ?>" style="top: 0; <?= $position ?>: 100%; <?= $color ?>; margin-top: -0.5em"><?= $scale['max'] ?></div><?php

        // Y Min
        ?><div class="graph-label <?= $bump ?> <?= $scale['min'] ? null : 'graph-label--bump-up' ?>" style="top: 100%; <?= $position ?>: 100%; <?= $color ?>; margin-top: -0.5em"><?= $scale['min'] ?></div><?php

        // Unit
        ?><div class="graph-label <?= $bump ?>" style="top: 50%; <?= $position ?>: 100%; <?= $color ?>"><strong><?= $scale['unit'] ?></strong></div><?php

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
    ?>var numSeries = <?= count($series); ?>;<?php
    ?>var xAxisProp = <?= $xAxis ?>;<?php
    ?>var highlight = '<?= adjustBrightness(defined('HIGHLIGHT') ? HIGHLIGHT : REFCOL, 60); ?>';<?php
    ?>var today = <?= json_encode($graphtoday ?? null) ?>;<?php
    ?>var divs = <?= json_encode(array_map(fn ($div) => $div->x, $divs)); ?>;<?php
    ?>var guides = <?= json_encode($guides); ?>;<?php
    ?>var style = '<?= $style; ?>';<?php
?>
</script><?php

?><br><br><?php
