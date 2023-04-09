<?php

use contextvariableset\Daterange;
use contextvariableset\Repeater;
use contextvariableset\Showas;
use contextvariableset\Value;
use obex\Obex;
use subsimple\Config;

$version = null;

if (is_string(@$_GET['version']) && preg_match('/^[a-f0-9]{64}$/', $_GET['version'])) {
    $version = $_GET['version'];
}

$config = match(true) {
    defined('LEDGER_CONFIG') => Config::get()->ledger[LEDGER_CONFIG] ?? (object) [],
    is_object($_config = @Config::get()->ledger) => $_config,
    default => (object) [],
};

$title = $config->title ?? 'Ledger';
$group = 'all';
$daterange = null;

$reports = @$config->report ? (array) $config->report : ['ledger'];

if (count($reports) > 1) {
    ContextVariableSet::put('report', $reportFilter = new Value('report'));

    $reportFilter->options = $reports;
}

$report = $reportFilter->value ?? reset($reports);

foreach ($linetypes = $jars->linetypes($report) as $linetype) {
    $linetype->icon ??= ($config->icons[$linetype->name] ?? 'doc');
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


if ($periods = @$config->periods) {
    $daterange = new Daterange('daterange', [
        'periods' => $periods,
        'allow_custom' => false,
    ]);

    ContextVariableSet::put('daterange', $daterange);
    ContextVariableSet::put('repeater', $repeater = new Repeater('ledger_repeater'));

    if (is_callable(@$config->group)) {
        $group = ($config->group)($report, $daterange);
    }
}

// $accounts = Obex::map(Obex::find($jars->group('lists', 'all', $version), 'name', 'is', 'accounts')->listitems, 'item');
// sort($accounts);

$lines = $jars->group($report, $group, $version);
$opening = '0.00';

if (($config->cumulative ?? true) && $daterange && $daterange->period !== 'alltime') {
    foreach ($jars->group('ledgeropenings', 'all', $version) ?? [] as $_group => $row) {
        $opening = $row->opening;

        if (strcmp($_group, $group) >= 0) {
            break;
        }
    }
}

$fields = [
    (object) ['name' => 'icon', 'type' => 'icon'],
    (object) ['name' => 'date', 'type' => 'string'],
    (object) ['name' => 'account', 'type' => 'string'],
    (object) ['name' => 'description', 'type' => 'string'],
    (object) ['name' => 'amount', 'type' => 'number', 'summary' => 'sum'],
];

if (@$config->fields) {
    $_fields = [];

    foreach ($config->fields as $field) {
        if (is_string($field)) {
            $field = Obex::find($fields, 'name', 'is', $field);
        }

        if (!is_object($field)) {
            error_response('Field that could not be resolved to an object');
        }

        $_fields[] = $field;
    }

    $fields = $_fields;
}

$generic_builder = array_map(fn () => [], array_flip(Obex::map($fields, 'name')));
$summaries = ['initial' => (object) []];
$_opening = (object) [];

$graphfields = [];

foreach ($fields as $field) {
    if (@$field->summary == 'sum') {
        $graphfields[] = $field->name;
        $_opening->{$field->name} = $opening;
        $summaries['initial']->{$field->name} = $opening;
    }
}

$account_summary = [];

foreach ($lines as $line) {
    $line->icon ??= $config->icons[$line->type] ?? 'doc';

    foreach ($fields as $field) {
        $line->{$field->name} ??= null;

        if (count($generic_builder[$field->name]) < 2 && !in_array($line->{$field->name}, $generic_builder[$field->name])) {
            $generic_builder[$field->name][] = $line->{$field->name};
        }
    }

    if (!isset($summaries[$line->date])) {
        $summaries[$line->date] = $summary = (object) [];

        foreach ($fields as $field) {
            if (@$field->summary == 'sum') {
                $summary->{$field->name} = $_opening->{$field->name};
            }
        }
    }

    foreach ($fields as $field) {
        if (@$field->summary == 'sum') {
            $summary->{$field->name} = bcadd($summary->{$field->name}, $line->{$field->name}, 2);
            $_opening->{$field->name} = bcadd($_opening->{$field->name}, $line->{$field->name}, 2);

            $account = @$line->account ?: 'unknown';
            $account_summary[$account] = bcadd($account_summary[$account] ?? '0', @$line->{$field->name} ?: '0.00', 2);
        }
    }
}

$generic = (object) array_map(fn ($values) => count($values) == 1 ? reset($values) : null, $generic_builder);

$hasJars = false;

$showas = new Showas("ledger_showas");
$showas->options = $config->showas ?? ['list', 'spending', 'summaries', 'graph'];

if (!$graphfields) {
    $showas->options = array_diff($showas->options, ['graph']);
}

ContextVariableSet::put('showas', $showas);

if (!$showas->value) {
    $showas->value = 'list';
}

$mask_fields = array_intersect(['date'], Obex::map($fields, 'name', 'is', 'date'));
$currentgroup = date('Y-m-d');
$defaultgroup = date('Y-m-d');

if ($daterange && (date('Y-m-d') < $daterange->from || date('Y-m-d') > $daterange->to)) {
    $defaultgroup = $daterange->from;
}

$addable = $linetypes;

ksort($account_summary);

if (isset($account_summary['jartransfer']) && !(float)$account_summary['jartransfer']) {
    unset($account_summary['jartransfer']);
}

return compact(
    'account_summary',
    'addable',
    'currentgroup',
    'defaultgroup',
    'fields',
    'generic',
    'graphfields',
    'group',
    'hasJars',
    'lines',
    'linetypes',
    'mask_fields',
    'opening',
    'showas',
    'summaries',
    'title',
);
