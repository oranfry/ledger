<?php

namespace OranFry\Ledger;

class Router extends \OranFry\Subsimple\Router
{
    protected static $routes = [
        'POST /ajax/save' => [
            'PAGE' => 'ledger/ajax/save',
            'AUTHSCHEME' => 'cookie',
            'LAYOUT' => 'json',
        ],

        'GET /-download/([a-z]+)/([a-zA-Z0-9-]+)' => [
            'AUTHSCHEME' => 'cookie',
            'LAYOUT' => 'ledger/download',
            'PAGE' => 'ledger/download',
            0 => 'TABLE_NAME',
            1 => 'RECORD_ID',
        ],

        'GET /.*' => [
            'PAGE' => 'ledger/index',
        ],
    ];
}
