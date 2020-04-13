<?php
namespace blend;

class spending extends \Blend
{
    public function __construct()
    {
        $this->label = 'Spending';
        $this->amountltzero = true;
        $this->past = false;
        $this->cum = false;
        $this->linetypes = ['plaintransaction'];
        $this->groupby = 'account';
        $this->fields = [
            (object) [
                'name' => 'date',
                'type' => 'date',
                'main' => true,
            ],
            (object) [
                'name' => 'account',
                'type' => 'text',
            ],
            (object) [
                'name' => 'amount',
                'type' => 'number',
                'summary' => 'sum',
                'dp' => 2,
            ],
        ];
        $this->showass = ['summaries', 'pie'];
    }
}
