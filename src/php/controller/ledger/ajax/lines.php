<?php

use Ledger\Config;

$ledger = Config::load(
    $viewdata,
    defined('LEDGER_CONFIG') ? LEDGER_CONFIG : null,
    @$_GET['version'],
);

$lines = $ledger->lines();

return [
    'data' => $lines,
];
