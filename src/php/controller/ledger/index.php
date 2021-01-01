<?php

use contextvariableset\Daterange;
use contextvariableset\Value;

$ledger = Blend::load(AUTH_TOKEN, 'ledger');
$fields = filter_objects($ledger->fields, 'name', 'notin', ['type', 'superjar']);
$mask_fields = ['date'];

array_unshift($fields, (object) [
    'name' => 'icon',
    'type' => 'icon',
    'derived' => true,
]);

ContextVariableSet::put('daterange', $daterange = new Daterange('daterange'));

$filters = [];
$past_filters = [];

if (@$daterange->from) {
    $filters[] = (object) ['field' => 'date', 'cmp' => '>=', 'value' => $daterange->from];
    $past_filters[] = (object) ['field' => 'date', 'cmp' => '<', 'value' => $daterange->from];
}

if (@$daterange->to) {
    $filters[] = (object) ['field' => 'date', 'cmp' => '<=', 'value' => $daterange->to];
}

$accounts = get_flat_list('accounts');
sort($accounts);

$jars = null;

if (@filter_objects($ledger->fields, 'name', 'is', 'jar')[0]) {
    ContextVariableSet::put('jar', $jarFilter = new Value('jar'));

    $jars = get_flat_list('jars');
    sort($jars);

    $jarFilter->options = $jars;

    if ($jarFilter->value) {
        $filter = (object) ['field' => 'jar', 'cmp' => '=', 'value' => $jarFilter->value];
        $filters[] = $filter;
        $past_filters[] = $filter;
        $mask_fields[] = 'jar';
    }

    if (@filter_objects($ledger->fields, 'name', 'is', 'superjar')[0]) {
        ContextVariableSet::put('superjar', $superjarFilter = new Value('superjar'));

        $superjarFilter->options = ['daily', 'longterm'];

        if ($superjarFilter->value) {
            $filter = (object) ['field' => 'superjar', 'cmp' => '=', 'value' => $superjarFilter->value];
            $filters[] = $filter;
            $past_filters[] = $filter;
        }
    }
}

if (@$daterange->from) {
    $past_summary = $ledger->summary(AUTH_TOKEN, $past_filters);
} else {
    $past_summary = (object) ['amount' => 0];
}

$records = $ledger->search(AUTH_TOKEN, $filters);

usort($records, function($a, $b){
    return strcmp($a->date, $b->date);
});

$summaries = [
    'initial' => $past_summary,
];

$balance = $past_summary->amount;
$generic_builder = [];

foreach ($fields as $field) {
    $generic_builder[$field->name] = [];
}

foreach ($records as $record) {
    $record->icon = @[
        'transaction' => 'dollar',
        'transferin' => 'arrowright',
        'transferout' => 'arrowleft',
    ][$record->type] ?? 'doc';

    $balance = bcadd($balance, $record->amount, 2);

    if (!isset($summaries[$record->date])) {
        $summaries[$record->date] = (object) [];
    }

    $summaries[$record->date]->amount = $balance;

    foreach ($fields as $field) {
        if (count($generic_builder[$field->name]) < 2 && !in_array($record->{$field->name}, $generic_builder[$field->name])) {
            $generic_builder[$field->name][] = $record->{$field->name};
        }
    }
}

$generic = (object) [];

foreach ($generic_builder as $field => $values) {
    if (count($values) == 1) {
        $generic->{$field} = $values[0];
    }
}

$currentgroup = date('Y-m-d');
$defaultgroup = (date('Y-m-d') >= $daterange->from && date('Y-m-d') <= $daterange->to) ? date('Y-m-d') : $daterange->from;

$linetypes = array_map(function($type) {
    $linetype = Linetype::load(AUTH_TOKEN, $type);

    return $linetype;
}, $ledger->linetypes);

$title = 'Ledger &bull; ' . $daterange->getTitle() . (@$jarFilter->value ? ' &bull; ' . $jarFilter->value : '') . (@$superjarFilter->value ? ' &bull; ' . $superjarFilter->value : '');

foreach ($linetypes as $linetype) {
    foreach ($linetype->fields as $field) {
        if (in_array($field->name, ['jar', 'from', 'to'])) {
            $field->options = $jars;
        }

        if ($field->name == 'account') {
            $field->options = $accounts;
        }
    }
}

return [
    'currentgroup' => $currentgroup,
    'defaultgroup' => $defaultgroup,
    'fields' => $fields,
    'generic' => $generic,
    'groupfield' => 'date',
    'hasJars' => (bool) @$jarFilter,
    'hasSuperjars' => (bool) @$superjarFilter,
    'linetypes' => $linetypes,
    'mask_fields' => $mask_fields,
    'records' => $records,
    'summaries' => $summaries,
    'title' => $title,
];
