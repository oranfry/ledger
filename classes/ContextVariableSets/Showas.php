<?php

namespace OranFry\Ledger\ContextVariableSets;

class Showas extends \OranFry\ContextVariableSets\ContextVariableSet
{
    public $value;
    public $options = [];

    public static $icons = [
        'calendar' => 'calendar',
        'graph' => 'linegraph',
        'list' => 'list',
        'pie' => 'piegraph',
        'spending' => 'dollar',
        'summaries' => 'sigma',
        'stats' => 'info',
        'summarystats' => 'info',
        'raw' => 'leaf',
    ];

    public function __construct(string $prefix, array $default_data = [], ?string $partial = null)
    {
        parent::__construct($prefix, $default_data, $partial);

        $data = $this->getRawData();

        $this->value = @$data['value'];
    }

    public function input_names(): array
    {
        return ['value'];
    }
}
