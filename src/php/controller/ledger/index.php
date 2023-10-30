<?php

use ContextVariableSets\ContextVariableSet;
use Ledger\Config;
use obex\Obex;
use Tools\ContextVariableSets\Showas;

$ledger = Config::load(
    $viewdata,
    defined('LEDGER_CONFIG') ? LEDGER_CONFIG : null,
    @$_GET['version'],
);

foreach (($variables = $ledger->variables()) as $variable) {
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

$fields = $ledger->fields();
$generic_builder = array_map(fn () => [], array_flip(Obex::map($fields, 'name')));
$summaries = ['initial' => (object) []];
$_opening = (object) [];

$graphfields = [];

$opening = $ledger->opening();

foreach ($fields as $field) {
    if (@$field->summary == 'sum') {
        $graphfields[] = $field->name;
        $_opening->{$field->name} = $opening;
        $summaries['initial']->{$field->name} = $opening;
    }
}

$account_summary = [];

$dateinfo = $ledger->dateinfo();

foreach (($lines = $ledger->lines()) ?? [] as $line) {
    foreach ($fields as $field) {
        $line->{$field->name} ??= null;

        if (count($generic_builder[$field->name]) < 2 && !in_array($line->{$field->name}, $generic_builder[$field->name])) {
            $generic_builder[$field->name][] = $line->{$field->name};
        }
    }

    if ($dateinfo) {
        if (!isset($summaries[$line->{$dateinfo->field}])) {
            $summaries[$line->{$dateinfo->field}] = $summary = (object) [];

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
}

$generic = (object) array_map(fn ($values) => count($values) == 1 ? reset($values) : null, $generic_builder);
$showas = new Showas('ledger_showas');
$showas->options = $ledger->showas();

if (!$graphfields) {
    $showas->options = array_diff($showas->options, ['graph']);
}

ContextVariableSet::put('showas', $showas);

if (!$showas->value) {
    $showas->value = 'list';
}

$mask_fields = [];
$currentgroup = null;

if ($dateinfo) {
    $mask_fields = [$dateinfo->field];
    $currentgroup = date('Y-m-d');
}

$addable = $linetypes = $ledger->linetypes();

ksort($account_summary);

$defaultgroup = $ledger->defaultgroup();
$error = $lines === null ? $ledger->error() : null;
$title = $ledger->title();

if ($verified_data = $ledger->verifiedData()) {
    foreach (array_keys($verified_data) as $group) {
        $lastgroup = null;
        $found = false;

        foreach ($lines as $_line) {
            if ($_line->{$dateinfo->field} == $group) {
                $found = true;

                break;
            }

            if ($_line->{$dateinfo->field} > $group) {
                break;
            }

            $lastgroup = $_line->{$dateinfo->field};
        }

        if (!$found) {
            $line = (object) ['_skip' => true, $dateinfo->field => $group];
            $summary = (object) [];

            foreach ($fields as $field) {
                if (@$field->summary == 'sum') {
                    $line->{$field->name} = '0.00';
                    $summary->{$field->name} = $lastgroup ? $summaries[$lastgroup]->{$field->name} : $opening;
                }
            }

            $lines[] = $line;
            $summaries[$group] = $summary;;
        }
    }

    usort($lines, fn ($a, $b) => $a->{$dateinfo->field} <=> $b->{$dateinfo->field});
}

return compact(
    'account_summary',
    'addable',
    'currentgroup',
    'dateinfo',
    'defaultgroup',
    'error',
    'fields',
    'generic',
    'graphfields',
    'lines',
    'linetypes',
    'mask_fields',
    'opening',
    'showas',
    'summaries',
    'title',
    'variables',
    'verified_data',
);
