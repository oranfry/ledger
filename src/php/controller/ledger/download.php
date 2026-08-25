<?php

use OranFry\Ledger\Config;

$ledger = Config::load(
    $viewdata,
    defined('LEDGER_CONFIG') ? LEDGER_CONFIG : null,
);

return (array) $ledger->download(
    LINETYPE_NAME,
    LINE_ID,
    FIELD_NAME,
);
