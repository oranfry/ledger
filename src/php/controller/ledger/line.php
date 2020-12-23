<?php
$linetype = Linetype::load(AUTH_TOKEN, LINETYPE_NAME);

$parenttype = null;
$parentlink = null;
$parentid = null;

if (LINE_ID) {
    $line = @$linetype->find_lines(AUTH_TOKEN, [(object)['field' => 'id', 'value' => LINE_ID]])[0];

    if (!$line) {
        error_response('No such line', 400);
    }

    $linetype->load_children(AUTH_TOKEN, $line);
}

$suggested_values = $linetype->get_suggested_values(AUTH_TOKEN);

$hasFileFields = in_array('file', array_map(function ($f) {
    return $f->type;
}, $linetype->fields));

return [
    'hasFileFields' => $hasFileFields,
    'line' => @$line,
    'linetype' => $linetype,
    'parentid' => $parentid,
    'parentlink' => $parentlink,
    'parenttype' => $parenttype,
    'suggested_values' => $suggested_values,
    'title' => ucwords($linetype->name),
];
