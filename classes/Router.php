<?php

namespace OranFry\Ledger;

class Router extends OranFry\Subsimple\Router
{
    protected static $routes = [
        'GET /' => ['PAGE' => 'ledger/index'],
        'POST /ajax/save' => ['AUTHSCHEME' => 'cookie', 'LAYOUT' => 'json', 'PAGE' => 'ledger/ajax/save'],
    ];
}
