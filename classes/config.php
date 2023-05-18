<?php

namespace ledger;

use jars\contract\Client as JarsClient;
use subsimple\Config as SubsimpleConfig;

class config
{
    public function __construct(array $viewdata, ?string $version = null)
    {
    }

    public function cumulative(): bool
    {
        return false;
    }

    public function defaultgroup(): ?string
    {
        return null;
    }

    public function fields(): array
    {
        return [
            (object) ['name' => 'icon', 'type' => 'icon'],
            (object) ['name' => 'date', 'type' => 'string'],
            (object) ['name' => 'account', 'type' => 'string'],
            (object) ['name' => 'description', 'type' => 'string'],
            (object) ['name' => 'amount', 'type' => 'number', 'summary' => 'sum', 'dp' => 2],
        ];
    }

    public function lines(): array
    {
        return [];
    }

    public function linetypes(): array
    {
        return [];
    }

    public static function load(array $viewdata, ?string $version = null)
    {
        if (!is_string($config_class = defined('LEDGER_CONFIG') ? @SubsimpleConfig::get()->ledger[LEDGER_CONFIG] : @SubsimpleConfig::get()->ledger)) {
            throw new Exception("No class specified for ledger config '$config_name'");
        }

        return new $config_class($viewdata, $version);
    }

    public function opening(): string
    {
        return '0.00';
    }

    public function showas(): array
    {
        return ['list', 'spending', 'summaries', 'graph'];
    }

    public function title(): string
    {
        return 'Ledger';
    }

    public function variables(): array
    {
        return [];
    }
}
