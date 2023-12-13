<?php

use Ledger\Config;

$ledger = Config::load(
    $viewdata,
    defined('LEDGER_CONFIG') ? LEDGER_CONFIG : null,
    @$_GET['version'],
);

$dateinfo = $ledger->dateinfo();
$defaultgroup = $ledger->defaultgroup();
$fields = $ledger->fields();
$linetypes = $ledger->linetypes();
$showas = $ledger->showas();
$title = $ledger->title();

return [
    'data' => compact('dateinfo', 'defaultgroup', 'fields', 'linetypes', 'showas', 'title'),
];
