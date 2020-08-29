<?php
namespace ledger\linetype;

class transaction extends \Linetype
{
    public function __construct()
    {
        $this->label = 'Transaction';
        $this->icon = 'dollar';
        $this->table = 'transaction';
        $this->summaries = null;
        $this->showass = ['list', 'calendar', 'graph'];
        $this->fields = [
            (object) [
                'name' => 'icon',
                'type' => 'icon',
                'fuse' => "'dollar'",
                'derived' => true,
            ],
            (object) [
                'name' => 'date',
                'type' => 'date',
                'groupable' => true,
                'fuse' => '{t}.date',
            ],
            (object) [
                'name' => 'account',
                'type' => 'text',
                'fuse' => '{t}.account',
            ],
            (object) [
                'name' => 'description',
                'type' => 'text',
                'fuse' => '{t}.description',
            ],
            (object) [
                'name' => 'amount',
                'type' => 'number',
                'dp' => 2,
                'summary' => 'sum',
                'fuse' => '{t}.amount',
            ],
        ];
        $this->unfuse_fields = [
            '{t}.date' => ':{t}_date',
            '{t}.amount' => ':{t}_amount',
            '{t}.account' => ':{t}_account',
            '{t}.description' => ':{t}_description',
        ];
    }

    public function get_suggested_values()
    {
        $suggested_values = [];

        $suggested_values['account'] = get_values('transaction', 'account');

        return $suggested_values;
    }

    public function validate($line)
    {
        $errors = [];

        if ($line->date == null) {
            $errors[] = 'no date';
        }

        if ($line->amount == null) {
            $errors[] = 'no amount';
        }

        return $errors;
    }
}
