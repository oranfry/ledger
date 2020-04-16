<?php
namespace ledger\blend;

class ledger extends \Blend
{
    public function __construct()
    {
        $this->label = 'Transactions';
        $this->linetypes = ['plaintransaction'];
        $this->groupby = 'date';
        $this->fields = [
            (object) [
                'name' => 'icon',
                'type' => 'icon',
                'derived' => true,
            ],
            (object) [
                'name' => 'date',
                'type' => 'date',
                'groupable' => true,
                'main' => true,
            ],
            (object) [
                'name' => 'account',
                'type' => 'text',
                'groupable' => true,
            ],
            (object) [
                'name' => 'description',
                'type' => 'text',
                'default' => '',
                'sacrifice' => true,
            ],
            (object) [
                'name' => 'parenttype',
                'type' => 'icon',
                'derived' => true,
                'translate' => [],
                'customlink' => '"/line?type={$parenttype}&id={$parentid}&back=' . base64_encode($_SERVER['REQUEST_URI']) . '"',
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
