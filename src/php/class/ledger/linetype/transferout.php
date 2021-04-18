<?php
namespace ledger\linetype;

class transferout extends \Linetype
{
    public function __construct()
    {
        $this->table = 'transfer';
        $this->label = 'Internal Transfer';
        $this->icon = 'arrowleft';
        $this->fields = [
            (object) [
                'name' => 'date',
                'type' => 'date',
                'id' => true,
                'groupable' => true,
                'fuse' => '{t}.date',
            ],
            (object) [
                'name' => 'jar',
                'type' => 'text',
                'fuse' => '{t}.fromjar',
            ],
            (object) [
                'name' => 'to',
                'type' => 'text',
                'fuse' => '{t}.tojar',
            ],
            (object) [
                'name' => 'amount',
                'type' => 'number',
                'dp' => 2,
                'fuse' => '-{t}.amount',
                'summary' => 'sum',
            ],
        ];
        $this->unfuse_fields = [
            '{t}.date' => (object) [
                'expression' => ':{t}_date',
                'type' => 'date',
            ],
            '{t}.tojar' => (object) [
                'expression' => ':{t}_to',
                'type' => 'varchar(40)',
            ],
            '{t}.fromjar' => (object) [
                'expression' => ':{t}_jar',
                'type' => 'varchar(40)',
            ],
            '{t}.amount' => (object) [
                'expression' => '0 - :{t}_amount',
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

        if (!@$line->jar) {
            $errors[] = 'no to jar';
        }


        if (!@$line->to) {
            $errors[] = 'no from jar';
        }

        if (!@$line->amount) {
            $errors[] = 'no amount';
        }

        if ($line->amount > 0) {
            $errors[] = 'amount is positive';
        }

        return $errors;
    }
}
