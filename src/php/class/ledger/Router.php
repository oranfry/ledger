<?php
namespace ledger;

class Router extends \Router
{
    protected static $routes = [
        'GET /ledger' => ['PAGE' => 'ledger/index'],
        'GET /ledger/([a-z]+)' => ['LINETYPE_NAME', 'PAGE' => 'ledger/line', 'LINE_ID' => null],
        'GET /ledger/([a-z]+)/([A-Z0-9]+)' => ['LINETYPE_NAME', 'LINE_ID', 'PAGE' => 'ledger/line'],
    ];
}
