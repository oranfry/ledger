<?php

$data = $jars->save(
    json_decode(file_get_contents('php://input')),
    @getallheaders()['X-Base-Version'],
);

return [
    'data' => $data,
    'headers' => [
        'X-Version' => $jars->version(),
    ],
];
