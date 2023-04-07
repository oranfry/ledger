<?php

use contextvariableset\Daterange;
use contextvariableset\Repeater;
use contextvariableset\Showas;
use contextvariableset\Value;
use obex\Obex;

$title = 'Ledger';
$icons = [
    'correction' => 'tick-o',
    'correctiongstsettlementgroup' => 'ird',
    'error' => 'times-o',
    'gstsettlementgroup' => 'ird',
    'transaction' => 'dollar',
];

if (preg_match('/^[a-f0-9]{64}$/', $_GET['version'])) {
    $version = $_GET['version'];
}

foreach ($linetypes = $jars->linetypes('ledger') as $linetype) {
    $linetype->icon ??= ($icons[$linetype->name] ?? 'doc');
    // foreach ($linetype->find_incoming_links(AUTH_TOKEN) as $incoming) {
    //     $parentaliasshort = $incoming->parent_link . '_' . $incoming->parent_linetype;

    //     $field = (object) ['name' => $parentaliasshort];
    //     $value = @$line->{$parentaliasshort} ?: @$_GET[$field->name] ?: @$field->default;

    //     $options = [];
    //     if (@$line->{$parentaliasshort}) {
    //         $options[] = $line->{$parentaliasshort};
    //     }
    // }
}

$daterange = new Daterange('daterange', [
    'periods' => ['gst'],
    'allow_custom' => false,
]);

ContextVariableSet::put('daterange', $daterange);
ContextVariableSet::put('repeater', $repeater = new Repeater('ledger_repeater'));

if ($repeater->period) {
      $filters[] = (object) [
        'cmp' => '*=',
        'field' => 'date',
        'value' => $repeater->render(),
    ];
}

$accounts = Obex::map(Obex::find($jars->group('lists', 'all', $version), 'name', 'is', 'accounts')->listitems, 'item');
sort($accounts);

$group = $daterange->to;

$lines = $jars->group('ledger', $group, $version);
$opening = '0.00';

foreach ($jars->group('ledgeropenings', 'all', $version) as $_group => $_opening) {
    $opening = $_opening->opening;

    if (strcmp($_group, $group) >= 0) {
        break;
    }
}

$_opening = $opening;
$clumped = [];

$fields = [
    (object) ['type' => 'icon', 'name' => 'icon'],
    (object) ['type' => 'string', 'name' => 'date'],
    (object) ['type' => 'string', 'name' => 'account'],
    (object) ['type' => 'string', 'name' => 'description'],
    (object) ['type' => 'number', 'name' => 'amount'],
];

$generic_builder = array_map(fn () => [], array_flip(Obex::map($fields, 'name')));

foreach ($lines as $line) {
    $line->icon ??= $icons[$line->type] ?? 'doc';

    foreach ($fields as $field) {
        $line->{$field->name} ??= null;

        if (count($generic_builder[$field->name]) < 2 && !in_array($line->{$field->name}, $generic_builder[$field->name])) {
            $generic_builder[$field->name][] = $line->{$field->name};
        }
    }

    // if (!isset($clumped[$line->date])) {
    //     $clumped[$line->date] = $clump = (object) [
    //         'lines' => [],
    //         'opening' => $_opening,
    //         'closing' => $_opening,
    //     ];
    // }

    // $clump->lines[] = $line;
    // $clump->closing = bcadd($clump->closing, $line->amount, 2);
    // $_opening = bcadd($_opening, $line->amount, 2);
}

$generic = (object) array_map(fn ($values) => count($values) == 1 ? reset($values) : null, $generic_builder);
// $highlight = strcmp($date, $earlier) <= 0 ? 'prev' : (strcmp($date, $later) >= 0 ? 'next' : 'today');

$hasJars = false;

// echo '<pre>'; dd($linetypes);

$showas = new Showas("ledger_showas");
$showas->options = ['list', 'spending', 'summaries', 'graph'];
ContextVariableSet::put('showas', $showas);

if (!$showas->value) {
    $showas->value = 'list';
}

$mask_fields = ['date'];
$currentgroup = date('Y-m-d');
$defaultgroup = (date('Y-m-d') >= $daterange->from && date('Y-m-d') <= $daterange->to) ? date('Y-m-d') : $daterange->from;
$addable = $linetypes;
$periods = ['gst'];

return compact(
    'addable',
    'currentgroup',
    'defaultgroup',
    'fields',
    'generic',
    'group',
    'hasJars',
    'lines',
    'linetypes',
    'mask_fields',
    'opening',
    'periods',
    'repeater',
    'showas',
    'title',
);
