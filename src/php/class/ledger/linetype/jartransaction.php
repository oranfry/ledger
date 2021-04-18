<?php
namespace ledger\linetype;

class jartransaction extends \Linetype
{
    public function __construct()
    {
        $this->label = 'Transaction';
        $this->table = 'transaction';
        $this->icon = 'dollar';
        $this->fields = [
            (object) [
                'name' => 'date',
                'type' => 'date',
                'groupable' => true,
                'fuse' => '{t}.date',
            ],
            (object) [
                'name' => 'jar',
                'type' => 'text',
                'groupable' => true,
                'fuse' => '{t}.jar',
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
            (object) [
                'name' => 'broken',
                'type' => 'text',
                'derived' => true,
                'fuse' => "if ({t}.jar is null or {t}.jar = '', 'broken', '')",
            ],
        ];
        $this->unfuse_fields = [
            '{t}.date' => (object) [
                'expression' => ':{t}_date',
                'type' => 'date',
            ],
            '{t}.jar' => (object) [
                'expression' => ':{t}_jar',
                'type' => 'varchar(40)',
            ],
            '{t}.account' => (object) [
                'expression' => ':{t}_account',
                'type' => 'varchar(40)',
            ],
            '{t}.description' => (object) [
                'expression' => ':{t}_description',
                'type' => 'varchar(255)',
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

        if (!@$line->jar) {
            $errors[] = 'no jar';
        }

        if (!@$line->amount) {
            $errors[] = 'no amount';
        }

        return $errors;
    }
}
