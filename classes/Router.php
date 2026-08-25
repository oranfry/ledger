<?php

namespace OranFry\Ledger;

use OranFry\Jars\Contract\Constants;

class Router extends \OranFry\Subsimple\Router
{
    protected static $routes = [
        'POST /ajax/save' => [
            'PAGE' => 'ledger/ajax/save',
            'AUTHSCHEME' => 'cookie',
            'LAYOUT' => 'json',
        ],

        'GET /-download/([a-z]+)/(' . Constants::ID_PATTERN . ')(?:/([0-9A-Za-z_-]+))?' => [
            'PAGE' => 'ledger/download',
            'LAYOUT' => 'ledger/download',
            0 => 'LINETYPE_NAME',
            1 => 'LINE_ID',
            2 => 'FIELD_NAME',
        ],

        'GET /.*' => [
            'PAGE' => 'ledger/index',
        ],
    ];
}
