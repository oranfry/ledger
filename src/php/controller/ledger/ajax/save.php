<?php

use OranFry\Ledger\Config;

$ledger = Config::load(
    $viewdata,
    defined('LEDGER_CONFIG') ? LEDGER_CONFIG : null,
    @$_GET['version'],
);

$data = $ledger->save(json_decode(file_get_contents('php://input')));

return [
    'data' => $data,
    'headers' => [
        'X-Version' => $jars->version(),
    ],
];
