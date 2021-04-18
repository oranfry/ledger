<?php
namespace ledger\linetype;

class transferin extends \Linetype
{
    public function __construct()
    {
        $this->table = 'transfer';
        $this->label = 'Internal Transfer';
        $this->icon = 'arrowright';
        $this->fields = [
            (object) [
                'name' => 'date',
                'type' => 'date',
                'id' => true,
                'groupable' => true,
                'fuse' => '{t}.date',
            ],
            (object) [
                'name' => 'from',
                'type' => 'text',
                'fuse' => '{t}.fromjar',
            ],
            (object) [
                'name' => 'jar',
                'type' => 'text',
                'fuse' => '{t}.tojar',
                'label' => 'to',
            ],
            (object) [
                'name' => 'amount',
                'type' => 'number',
                'dp' => 2,
                'fuse' => '{t}.amount',
                'summary' => 'sum',
            ],
        ];
        $this->unfuse_fields = [
            '{t}.date' => (object) [
                'expression' => ':{t}_date',
                'type' => 'date',
            ],
            '{t}.tojar' => (object) [
                'expression' => ':{t}_jar',
                'type' => 'varchar(40)',
            ],
            '{t}.fromjar' => (object) [
                'expression' => ':{t}_from',
                'type' => 'varchar(40)',
            ],
            '{t}.amount' => (object) [
                'expression' => ':{t}_amount',
                'type' => 'decimal(18, 2)',
            ],
        ];
    }

    public function validate($line)
    {
        $errors = [];

        if (!@$line->date) {
            $errors[] = 'no date';
        }

        if (!@$line->from) {
            $errors[] = 'no from jar';
        }

        if (!@$line->jar) {
            $errors[] = 'no to jar';
        }

        if (!(float)@$line->amount) {
            $errors[] = 'no amount';
        }

        if ($line->amount < 0) {
            $errors[] = 'amount is negative';
        }

        return $errors;
    }
}
