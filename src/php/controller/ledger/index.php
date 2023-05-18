<?php

use contextvariableset\Showas;
use ledger\config as LedgerConfig;
use obex\Obex;

$config = LedgerConfig::load($viewdata, @$_GET['version']);

$defaultgroup = $config->defaultgroup();
$fields = $config->fields();
$lines = $config->lines();
$linetypes = $config->linetypes();
$opening = $config->opening();
$showas_options = $config->showas();
$title = $config->title();
$variables = $config->variables();

foreach ($variables as $variable) {
    ContextVariableSet::put($variable->prefix, $variable);
}

// foreach ($linetypes as $linetype) {
//     foreach ($linetype->find_incoming_links(AUTH_TOKEN) as $incoming) {
//         $parentaliasshort = $incoming->parent_link . '_' . $incoming->parent_linetype;

//         $field = (object) ['name' => $parentaliasshort];
//         $value = @$line->{$parentaliasshort} ?: @$_GET[$field->name] ?: @$field->default;

//         $options = [];
//         if (@$line->{$parentaliasshort}) {
//             $options[] = $line->{$parentaliasshort};
//         }
//     }
// }

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
$showas->options = $showas_options;

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

$addable = $linetypes;

ksort($account_summary);

// if (isset($account_summary['jartransfer']) && !(float)$account_summary['jartransfer']) {
//     unset($account_summary['jartransfer']);
// }

return compact(
    'account_summary',
    'addable',
    'currentgroup',
    'defaultgroup',
    'fields',
    'generic',
    'graphfields',
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
