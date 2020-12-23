<?php

use contextvariableset\Daterange;
use contextvariableset\Value;

$api = get_api_client();
$ledger = $api->blend('ledger');
$fields = filter_objects($ledger->fields, 'name', 'notin', ['type', 'superjar']);
$mask_fields = ['date'];

array_unshift($fields, (object) [
    'name' => 'icon',
    'type' => 'icon',
    'derived' => true,
]);

ContextVariableSet::put('daterange', $daterange = new Daterange('daterange'));

$filters = [
    (object) ['field' => 'date', 'cmp' => '>=', 'value' => $daterange->from],
    (object) ['field' => 'date', 'cmp' => '<=', 'value' => $daterange->to],
];

$past_filters = [
    (object) ['field' => 'date', 'cmp' => '<', 'value' => $daterange->from],
];

if (@filter_objects($ledger->fields, 'name', 'is', 'jar')[0]) {
    ContextVariableSet::put('jar', $jarFilter = new Value('jar'));

    $jars = $api->search('managejars');
    $options = array_map(function($j) {
        return $j->jar;
    }, $jars);
    sort($options);
    $jarFilter->options = $options;

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

$past_summary = $api->summary('ledger', $past_filters);
$records = $api->search('ledger', $filters);

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

return [
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
    'currentgroup' => $currentgroup,
    'defaultgroup' => $defaultgroup,
];

function addlink($type, $group, $groupfield, $defaultgroup, $parent_query, $prepop = [])
{
    $url = "/ledger/{$type}";

    $query = $prepop;

    if ($groupfield) {
        $query[$groupfield] = $group ?: @$defaultgroup;
    }

    $query['back'] = base64_encode($_SERVER['REQUEST_URI']);

    $url .= '?' . http_build_query($query) . (@$parent_query ? '&' . $parent_query : '');

    return $url;
}

function editlink($id, $type)
{
    $back = base64_encode($_SERVER['REQUEST_URI']);

    return "/ledger/{$type}/{$id}?back={$back}";
}