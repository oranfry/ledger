<?php

$data = json_decode(file_get_contents('php://input'));

if (strtolower(getallheaders()['X-Differential'] ?? 'false') === 'true') {
    $data = array_values(array_filter(array_map(function ($line) use ($jars): ?object {
        $orig = $jars->get($line->type, $line->id);

        unset($line->type, $line->id);

        if (!$obvars = get_object_vars($line)) {
            return null;
        }

        $changed = false;

        foreach (get_object_vars($line) as $prop => $value) {
            if ($orig->$prop !== $value) {
                $orig->$prop = $value;
                $changed = true;
            }
        }

        if (!$changed) {
            return null;
        }

        return $orig;
    }, $data)));
}

$data = $jars->save($data, @getallheaders()['X-Base-Version']);

return [
    'data' => $data,
    'headers' => [
        'X-Version' => $jars->version(),
    ],
];
