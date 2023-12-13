<?php

namespace Ledger;

class Router extends \subsimple\Router
{
    protected static $routes = [
        'GET /' => ['PAGE' => 'ledger/index'],
        'POST /ajax/save' => ['AUTHSCHEME' => 'cookie', 'LAYOUT' => 'json', 'PAGE' => 'ledger/ajax/save'],
        'GET /config' => ['AUTHSCHEME' => 'cookie', 'LAYOUT' => 'json', 'PAGE' => 'ledger/ajax/config'],
        'GET /lines' => ['AUTHSCHEME' => 'cookie', 'LAYOUT' => 'json', 'PAGE' => 'ledger/ajax/lines'],
    ];
}
