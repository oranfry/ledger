<?php
namespace ledger\blend;

class spending extends \Blend
{
    public function __construct()
    {
        $this->label = 'Spending';
        $this->amountltzero = true;
        $this->past = false;
        $this->cum = false;
        $this->linetypes = ['transaction'];
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
