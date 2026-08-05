<?php

namespace OranFry\Ledger;

class FieldFilters
{
    static function apply(?string $value, ?string $pipeline): ?string
    {
        if (!$pipeline) {
            return $value;
        }

        foreach (explode('|', $pipeline) as $filter) {
            $value = self::applyOne($value, $filter);
        }

        return $value;
    }
    
    static function applyOne(?string $value, string $filter): ?string
    {
        if (!preg_match('/^([a-z]+)\(([^),]+)(,[^,)]+)*\)$/', $filter, $matches)) {
            throw new \Exception('Invalid filter encountered');
        }

        $method = 'filter_' . $matches[1];
        $arguments = explode(',', $matches[2] . @$matches[3]);

        if (!method_exists(self::class, $method)) {
            throw new \Exception('Unknown filter encountered [' . $method . ']');
        }

        return self::$method($value, ...$arguments);
    }

    static function filter_start(?string $value, $length): string
    {
        return substr($value ?? '', 0, intval($length));
    }
}
