<?php

namespace OranFry\Ledger;

class Router extends \OranFry\Subsimple\Router
{
    protected static $routes = [
        'POST /ajax/save' => ['AUTHSCHEME' => 'cookie', 'LAYOUT' => 'json', 'PAGE' => 'ledger/ajax/save'],
        'GET /.*' => ['PAGE' => 'ledger/index'],
    ];
}
