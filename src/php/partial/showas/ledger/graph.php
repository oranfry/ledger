<?php

use subsimple\Period;

if (!$daterange = ContextVariableSet::get('daterange')) {
    return;
}

$from = $daterange->from;
$to = $daterange->to;
$graphfields ??= ['amount'];

$graphfrom = @$from ?? @array_keys($summaries)[1];
$graphto = @$to ?? @array_keys($summaries)[count($summaries) - 1];
$current_period = Period::load($daterange->period);

if (!@$summaries) {
    echo 'cant show a graph';
    return;
}

$max = 0;
$min = 0;
$rmin = INF;
$rmax = -INF;

foreach ($graphfields as $graphfield) {
    foreach ($summaries as $summary) {
        $max = max($max, $summary->$graphfield ?? 0);
        $min = min($min, $summary->$graphfield ?? INF);
        $rmax = max($rmax, $summary->$graphfield ?? 0);
        $rmin = min($rmin, $summary->$graphfield ?? INF);
    }
}

if (!$range = $max - $min) {
    echo 'cant show a graph';
    return;
}

for ($date = $graphfrom, $graphtotal_days = 0; strcmp($graphto, $date) >= 0; $date = date_shift($date, '+1 day'), $graphtotal_days++);

$graphdiv = $daterange->rawto ? '1 month' : @$current_period->graphdiv;
$graphdivff = $daterange->rawto ? false : @$current_period->graphdivff;

$series = [];
$colors = ['333333'];

if (count($graphfields) > 1) {
    $colors = ['b02323', '245881', '24813C', '815624', '672481'];
}

foreach ($graphfields as $i => $graphfield) {
    // TODO: decide whether to go this way
    // $min = $rmin; $max = $rmax;

    $day = 1;
    $graphtoday = null;
    $date = $graphfrom;
    $summary = $summaries['initial'];
    $points = [[0, (($summary->$graphfield ?? 0) - $min) / $range]];

    if ($graphdiv) {
        $divs = [];

        if ($graphdivff) {
            $nextdiv = ff($date);
        } else {
            $nextdiv = date_shift($date, '+' . $graphdiv);
        }
    }

    while (strcmp($graphto, $date) >= 0) {
        if (isset($summaries[$date])) {
            $summary = $summaries[$date];
        }

        $final = $summary->$graphfield ?? 0;

        $points[] = [$day / $graphtotal_days, (($summary->$graphfield ?? 0) - $min) / $range];

        if ($date == date('Y-m-d')) {
            $graphtoday = $day / $graphtotal_days;
        }

        if ($graphdiv && !strcmp($date, $nextdiv)) {
            $divs[] = $day / $graphtotal_days;
            $nextdiv = date_shift($nextdiv, '+' . $graphdiv);
        }

        $date = date_shift($date, '+1 day');
        $day++;
    }

    $series[$graphfield] = (object) [
        'points' => $points,
        'color' => $colors[$i] ?? str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT),
    ];
}

$xAxis = -$min / $range;
$dollarDeltaTop = 50;
$rminLabelTop = round(100 * (1 - $rmin / $range), 2);
$rmaxLabelTop = round(100 * ($max - $rmax) / $range, 2);
$zeroTop = round(100 * $max / $range, 2);

$showRmin = abs($min - $rmin) / $range > 0.03 && abs($max - $rmin) / $range > 0.03;
$showRmax = abs($max - $rmax) / $range > 0.03 && abs($min - $rmax) / $range > 0.03;
$showZero = $min && $max && abs($rminLabelTop - $zeroTop) > 5;

if ($showRmin && abs($rminLabelTop - $dollarDeltaTop) < 5 || $showRmax && abs($rmaxLabelTop - $dollarDeltaTop) < 5) {
    $dollarDeltaTop += 10;
}
?>

<div id="bg-container" style="position: relative; margin: 0 auto; font-size: 0.8em;">
    <canvas id="bg" width="960" height="540"></canvas>
    <div class="graph-label graph-label--bump-left graph-label--bump-up" style="top: 0; right: 100%; transform: rotate(270deg);"><?= $max ?></div>
    <div class="graph-label graph-label--bump-left graph-label--bump-up" style="top: 100%; right: 100%; transform: rotate(270deg);"><?= $min ?></div>
    <div class="graph-label graph-label--bump-down" style="top: 100%; left: 0"><?= date_shift($graphfrom, '-1 day') ?></div>
    <div class="graph-label graph-label--bump-down" style="top: 100%; right: 0"><?= $graphto ?></div>
    <div class="graph-label graph-label--bump-left" style="top: <?= $dollarDeltaTop ?>%; right: 100%; margin-top: -50px; transform: rotate(270deg); text-align: center;">Δ&nbsp;<?= number_format($final - (@$summaries['initial']->{$graphfield} ?: 0), 2, '.', '') ?></div>
    <div class="graph-label graph-label--bump-down" style="top: 100%; left: 50%; width: 100px; margin-left: -50px; text-align: center;">Δ <?= (new DateTime($graphfrom))->diff(new DateTime($graphto))->format('%r%a days'); ?></div>

    <?php if ($showZero): ?>
        <div class="graph-label graph-label--bump-left graph-label--bump-up" style="top: <?= $zeroTop ?>%; right: 100%; transform: rotate(270deg);">0</div>
    <?php endif ?>

    <?php if ($showRmin): ?>
        <div class="graph-label graph-label--bump-left" style="top: <?= $rminLabelTop ?>%; right: 100%"><?= $rmin ?></div>
    <?php endif ?>

    <?php if ($showRmax): ?>
        <div class="graph-label graph-label--bump-left" style="top: <?= $rmaxLabelTop ?>%; right: 100%"><?= $rmax ?></div>
    <?php endif ?>

    <?php if (count($series) > 1): ?>
        <div class="graph-label graph-label--bump-down2" style="top: 100%; left: 50%; width: 300px; margin-left: -150px; text-align: center;">
            <?php foreach ($series as $graphfield => $oneseries): ?>
                <span style="color: #<?= $oneseries->color; ?>"><?= $graphfield ?> &mdash;</span>
                &nbsp;&nbsp;&nbsp;
            <?php endforeach ?>
        </div>
    <?php endif ?>
</div>

<script>
    var graphSeries = <?= json_encode($series); ?>;
    var xAxisProp = <?= $xAxis ?>;
    var highlight = '<?= adjustBrightness(defined('HIGHLIGHT') ? HIGHLIGHT : REFCOL, -30); ?>';
    var today = <?= $graphtoday ?: 'null' ?>;
    <?php if (@$graphdiv): ?>
        var divs = <?= json_encode($divs); ?>;
    <?php endif ?>
</script>

<br><br>
