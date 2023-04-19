<?php

namespace ledger;

class config
{
    public function cumulative(): bool
    {
        return false;
    }

    public function defaultgroup(): ?string
    {
        return null;
    }

    public function fields(): array
    {
        return [
            (object) ['name' => 'icon', 'type' => 'icon'],
            (object) ['name' => 'date', 'type' => 'string'],
            (object) ['name' => 'account', 'type' => 'string'],
            (object) ['name' => 'description', 'type' => 'string'],
            (object) ['name' => 'amount', 'type' => 'number', 'summary' => 'sum'],
        ];
    }

    public function group(): string
    {
        return 'ledger/all';
    }

    public function icons(): array
    {
        return [];
    }

    public function opening_group(): string
    {
        return 'ledgeropenings/all';
    }

    public function showas(): array
    {
        return ['list', 'spending', 'summaries', 'graph'];
    }

    public function title(): string
    {
        return 'Ledger';
    }

    public function variables(): array
    {
        return [];
    }
}
