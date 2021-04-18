<?php
namespace ledger\linetype;

class transfer extends \Linetype
{
    public function __construct()
    {
        $this->table = 'transfer';
        $this->label = 'Internal Transfer';
        $this->icon = 'arrowleftright';
        $this->fields = [
            (object) [
                'name' => 'icon',
                'type' => 'icon',
                'fuse' => "'arrowleftright'",
                'derived' => true,
            ],
            (object) [
                'name' => 'date',
                'type' => 'date',
                'id' => true,
                'fuse' => '{t}.date',
            ],
            (object) [
                'name' => 'from',
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
                'fuse' => '{t}.amount',
            ],
        ];
        $this->unfuse_fields = [
            '{t}.date' => (object) [
                'expression' => ':{t}_date',
                'type' => 'date',
            ],
            '{t}.fromjar' => (object) [
                'expression' => ':{t}_from',
                'type' => 'varchar(40)',
            ],
            '{t}.tojar' => (object) [
                'expression' => ':{t}_to',
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

        if (!@$line->to) {
            $errors[] = 'no to jar';
        }

        if (!@$line->amount) {
            $errors[] = 'no amount';
        }

        return $errors;
    }
}
