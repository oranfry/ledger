<?php
namespace ledger\blend;

class ledger extends \Blend
{
    public function __construct()
    {
        $this->label = 'Transactions';
        $this->linetypes = ['transaction'];
        $this->groupby = 'date';
        $this->fields = [
            (object) [
                'name' => 'date',
                'type' => 'date',
            ],
            (object) [
                'name' => 'account',
                'type' => 'text',
            ],
            (object) [
                'name' => 'description',
                'type' => 'text',
                'default' => '',
            ],
            (object) [
                'name' => 'amount',
                'type' => 'number',
                'summary' => 'sum',
                'dp' => 2,
            ],
        ];
        $this->past = true;
        $this->cum = true;
        $this->showass = ['list', 'calendar', 'graph', 'summaries'];
    }
}
