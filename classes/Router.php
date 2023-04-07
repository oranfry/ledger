<?php

namespace ledger;

class Router extends \subsimple\Router
{
    protected static $routes = [
        'GET /' => ['PAGE' => 'ledger/index'],
        'POST /ajax/save' => ['AUTHSCHEME' => 'cookie', 'LAYOUT' => 'ledger/json', 'PAGE' => 'ledger/ajax/save'],
    ];
}
