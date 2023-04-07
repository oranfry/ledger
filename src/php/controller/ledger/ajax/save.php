<?php

$lines = json_decode(file_get_contents('php://input'));
$data = $jars->save($lines);

return [
    'data' => $data,
    'headers' => [
        'X-Version' => $jars->version(),
    ],
];
