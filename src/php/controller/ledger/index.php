<?php

use ContextVariableSets\ContextVariableSet;
use Ledger\Config;
use obex\Obex;
use Tools\ContextVariableSets\Showas;

$ledger = Config::load(
    $viewdata,
    defined('LEDGER_CONFIG') ? LEDGER_CONFIG : null,
    @$_GET['version'],
);

foreach (($variables = $ledger->variables()) as $variable) {
    ContextVariableSet::put($variable->prefix, $variable);
}

// foreach ($linetypes as $linetype) {
//     foreach ($linetype->find_incoming_links(AUTH_TOKEN) as $incoming) {
//         $parentaliasshort = $incoming->parent_link . '_' . $incoming->parent_linetype;

//         $field = (object) ['name' => $parentaliasshort];
//         $value = @$line->{$parentaliasshort} ?: @$_GET[$field->name] ?: @$field->default;

//         $options = [];
//         if (@$line->{$parentaliasshort}) {
//             $options[] = $line->{$parentaliasshort};
//         }
//     }
// }

$fields = $ledger->fields();
$generic_builder = array_map(fn () => [], array_flip(Obex::map($fields, 'name')));
$_opening = (object) [];
$summaries = [];
$graphfields = [];

$opening = $ledger->opening();

// scan and prepare field summaries

foreach ($fields as $field) {
    if (!$field->summary ??= []) {
        continue;
    }

    // expand 'max' to [{scheme:max}]

    if (is_string($field->summary)) {
        $field->summary = [$field->summary];
    }

    // expand ['max', 'min'] to [{scheme:max},{scheme:min}]

    foreach ($field->summary as $i => $item) {
        if (is_string($item)) {
            $field->summary[$i] = $item = (object) [
                'scheme' => $item,
            ];
        }

        $item->scheme ??= 'last';
        $item->identifier ??= $item->scheme;
        $item->alias = $field->name . (count($field->summary) > 1 ? ' (' . $item->identifier . ')' : null);

        $graphfields[] = $graphfield = (object) [
            'alias' => $item->alias,
            'color' => $item->color ?? $field->color ?? null,
            'guides' => $item->guides ?? $field->guides ?? null,
            'max' => $item->max ?? $field->max ?? null,
            'min' => $item->min ?? $field->min ?? null,
            'name' => $field->name,
            'nearest' => $item->nearest ?? $field->nearest ?? null,
            'scheme' => $item->scheme,
            'unit' => $item->unit ?? $field->unit ?? null,
        ];

        switch ($item->scheme) {
            case 'sum':
                if (empty($summaries['initial'])) {
                    $summaries['initial'] = (object) [];
                }

                $_opening->{$field->name} = $summaries['initial']->{$field->name} = $opening;
                break;
            case 'groupsum':
            case 'average':
            case 'first':
            case 'last':
            case 'max':
            case 'min':
                $graphfield->bridge = false;
        }
    }
}

$groupingInfo = $ledger->groupingInfo();
$lines = $ledger->lines($base_version);

$groupings = $groupingInfo->groupings ?? [];

// fill gaps with null and build the generic

foreach ($lines ?? [] as $line) {
    foreach ($fields as $field) {
        $line->{$field->name} ??= null;

        if (count($generic_builder[$field->name]) < 2 && !in_array($line->{$field->name}, $generic_builder[$field->name])) {
            $generic_builder[$field->name][] = $line->{$field->name};
        }
    }
}

// group lines

foreach ($lines ?? [] as $line) {
    $grouping = $ledger->lineGrouping($line);

    // either build group list dynamically, or check against static list

    if (!in_array($grouping, $groupings)) {
        if ($groupingInfo->groupings ?? null) {
            throw new Exception('Unrecognized grouping [' . $grouping . ']');
        } else {
            $groupings[] = $grouping;
        }
    }

    $line->_grouping = $grouping;
}

if (!($groupingInfo->groupings ?? null)) {
    sort($groupings);
}

// sort by grouping

if ($lines) {
    usort($lines, fn ($a, $b) => array_search($a->_grouping, $groupings) <=> array_search($b->_grouping, $groupings));
}

// compute summaries

if ($groupingInfo) {
    foreach ($lines ?? [] as $line) {
        if (!$summary = $summaries[$line->_grouping] ?? null) {
            $summary = $summaries[$line->_grouping] = (object) [];

            foreach ($fields as $field) {
                foreach ($field->summary as $fs) {
                    $summary->{$fs->alias} = match ($fs->scheme) {
                        'sum' => $_opening->{$field->name},
                        'average' => [],
                        default => '0.00',
                    };
                }
            }
        }

        foreach ($fields as $field) {
            foreach ($field->summary as $fs) {
                $alias = $fs->alias;

                switch ($fs->scheme) {
                    case 'sum':
                        $_opening->$alias = bcadd($_opening->$alias, $line->{$field->name}, 2);
                        // no break
                    case 'groupsum':
                        $summary->$alias = bcadd($summary->$alias, $line->{$field->name}, 2);
                        break;
                    case 'average':
                        $summary->$alias[] = $line->{$field->name};
                        break;
                    case 'first':
                        $summary->$alias ??= $line->{$field->name};
                        break;
                    case 'last':
                        $summary->$alias = $line->{$field->name};
                        break;
                    case 'max':
                        $summary->$alias = max($summary->$alias ?? -INF, $line->{$field->name});
                        break;
                    case 'min':
                        $summary->$alias = min($summary->$alias ?? INF, $line->{$field->name});
                }
            }
        }
    }
}

// summary scheme 'average' - time to resolve

foreach ($fields as $field) {
    foreach ($field->summary as $fs) {
        if ($fs->scheme === 'average' && @$summary->$alias) {
            $alias = $fs->alias;

            foreach ($summaries as $summary) {
                $summary->$alias = bcdiv(array_sum($summary->$alias), count($summary->$alias), $field->dp ?? 0);
            }
        }
    }
}

$generic = (object) array_map(fn ($values) => count($values) == 1 ? reset($values) : null, $generic_builder);
$showas = new Showas('ledger_showas');
$showas->options = $ledger->showas();

ContextVariableSet::put('showas', $showas);

if (!$showas->value) {
    $showas->value = reset($showas->options);
}

$mask_fields = [];
$addable = $linetypes = $ledger->linetypes();

$error = $lines === null ? $ledger->error() : null;
$title = $ledger->title();

if ($verified_data = $ledger->verifiedData()) {
    foreach (array_diff(array_keys($verified_data), ['initial']) as $group) {
        $lastgroup = null;
        $found = false;

        foreach ($lines as $_line) {
            if ($_line->_grouping == $group) {
                $found = true;

                break;
            }

            if ($_line->_grouping > $group) {
                break;
            }

            $lastgroup = $_line->_grouping;
        }

        if (!$found) {
            $line = (object) [
                '_skip' => true,
                '_grouping' => $group,
            ];

            $summary = (object) [];

            foreach ($fields as $field) {
                foreach ($field->summary as $fs) {
                    $alias = $fs->alias;

                    switch ($fs->scheme) {
                        case 'sum':
                        case 'groupsum':
                            $summary->$alias = $lastgroup ? $summaries[$lastgroup]->$alias : $opening;
                            $line->{$field->name} = '0.00';
                            break;
                        case 'average':
                        case 'first':
                        case 'last':
                        case 'max':
                        case 'min':
                            $line->{$field->name} = '0.00';
                            $summary->$alias = $summaries[$lastgroup]->$alias;
                    }
                }
            }

            $lines[] = $line;
            $summaries[$group] = $summary;;

            if (!($groupingInfo->groupings ?? null)) {
                $groupings[] = $group;
            }
        }
    }
}

// sort lines by grouping again

if (!($groupingInfo->groupings ?? null)) {
    sort($groupings);
}

if ($lines) {
    usort($lines, function ($a, $b) use ($groupings): int {
        return array_search($a->_grouping, $groupings) <=> array_search($b->_grouping, $groupings);
    });
}

return compact(
    'addable',
    'base_version',
    'error',
    'fields',
    'generic',
    'graphfields',
    'groupingInfo',
    'groupings',
    'lines',
    'linetypes',
    'mask_fields',
    'opening',
    'showas',
    'summaries',
    'title',
    'variables',
    'verified_data',
);
