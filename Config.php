<?php

namespace Ledger;

use jars\contract\Client as JarsClient;
use subsimple\Config as SubsimpleConfig;

class Config
{
    public function __construct(array $viewdata, ?string $version = null)
    {
    }

    public function cumulative(): bool
    {
        return false;
    }

    public function dateinfo(): ?object
    {
        return null;
    }

    public function defaultgroup(): ?string
    {
        return null;
    }

    public function error(): ?string
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

    public function lines(): ?array
    {
        return null;
    }

    public function linetypes(): array
    {
        return [];
    }

    public static function load(array $viewdata, ?string $config_name = null, ?string $version = null)
    {
        $configs = SubsimpleConfig::get()->ledger;

        if (is_array($configs)) {
            $config_class = @$configs[$config_name ?? 'default'];
        } elseif (!$config_name) {
            $config_class = $configs;
        } else {
            throw new Exception("Config name was specified but config->ledger is not an array");
        }

        if (!is_string($config_class)) {
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
