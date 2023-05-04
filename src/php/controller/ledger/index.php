<?php

use contextvariableset\Showas;
use obex\Obex;
use subsimple\Config;

$version = null;

if (is_string(@$_GET['version']) && preg_match('/^[a-f0-9]{64}$/', $_GET['version'])) {
    $version = $_GET['version'];
}

if (!is_string($config_class = defined('LEDGER_CONFIG') ? @Config::get()->ledger[LEDGER_CONFIG] : @Config::get()->ledger)) {
    error_response("No class specified for ledger config '$config_name'");
}

$config = new $config_class($jars);

foreach ($variables = $config->variables() as $variable) {
    ContextVariableSet::put($variable->prefix, $variable);
}

[$report, $group] = explode('/', $config->group(), 2);

$icons = $config->icons();

foreach ($linetypes = $jars->linetypes($report) as $linetype) {
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

// if ($periods = @$config->periods) {
//     if (is_callable(@$config->group)) {
//         $group = ($config->group)($report, $daterange);
//     }
// }

// $accounts = Obex::map(Obex::find($jars->group('lists', 'all', $version), 'name', 'is', 'accounts')->listitems, 'item');
// sort($accounts);

$lines = $jars->group($report, $group, $version);
$opening = '0.00';

if ($config->cumulative()) {
    $defo = false;
    $delta = '0.00';

    [$opening_report, $opening_group] = explode('/', $config->opening_group(), 2);

    foreach ($jars->group($opening_report, $opening_group, $version) ?? [] as $_group => $row) {
        $opening = $row->opening;
        $delta = $row->delta;

        if (strcmp($_group, $group) >= 0) {
            $defo = true;
            break;
        }
    }

    if (!$defo) {
        $dp = max(strlen(preg_replace('/[^.]*\.?/', '', $opening, 1)), strlen(preg_replace('/[^.]*\.?/', '', $delta, 1)));
        $opening = bcadd($opening, $delta, $dp);
    }
}

$fields = $config->fields();
$generic_builder = array_map(fn () => [], array_flip(Obex::map($fields, 'name')));
$summaries = ['initial' => (object) []];
$_opening = (object) [];
$has_date = (bool) Obex::find($fields, 'name', 'is', 'date');

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
    $line->icon ??= $icons[$line->type] ?? 'doc';

    foreach ($fields as $field) {
        $line->{$field->name} ??= null;

        if (count($generic_builder[$field->name]) < 2 && !in_array($line->{$field->name}, $generic_builder[$field->name])) {
            $generic_builder[$field->name][] = $line->{$field->name};
        }
    }

    if ($has_date && !isset($summaries[$line->date])) {
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
$showas = new Showas('ledger_showas');
$showas->options = $config->showas();

if (!$graphfields) {
    $showas->options = array_diff($showas->options, ['graph']);
}

ContextVariableSet::put('showas', $showas);

if (!$showas->value) {
    $showas->value = 'list';
}

$mask_fields = [];
$currentgroup = null;

if ($has_date) {
    $mask_fields = ['date'];
    $currentgroup = date('Y-m-d');
}

$defaultgroup = $config->defaultgroup();
$addable = $linetypes;

ksort($account_summary);

// if (isset($account_summary['jartransfer']) && !(float)$account_summary['jartransfer']) {
//     unset($account_summary['jartransfer']);
// }

$title = $config->title();

return compact(
    'account_summary',
    'addable',
    'currentgroup',
    'defaultgroup',
    'fields',
    'generic',
    'graphfields',
    'group',
    'has_date',
    'lines',
    'linetypes',
    'mask_fields',
    'opening',
    'showas',
    'summaries',
    'title',
    'variables',
);
