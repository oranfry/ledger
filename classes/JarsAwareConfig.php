<?php

namespace OranFry\Ledger;

use OranFry\Jars\Contract\Client as JarsClient;

class JarsAwareConfig extends Config
{
    protected ?int $base_version = null;
    protected JarsClient $jars;
    protected ?int $version = null;

    public function __construct(array $viewdata)
    {
        parent::__construct($viewdata);

        if (!@$viewdata['jars'] instanceof JarsClient) {
            throw new Exception('Please make sure $viewdata["jars"] is set to an instance of [' . JarsClient::class . ']');
        }

        $this->jars = $viewdata['jars'];

        if (isset($viewdata['version']) && is_int($viewdata['version'])) {
            $this->version = $viewdata['version'];
        }
    }

    public function js(): void
    {
        ?>window.base_version = '<?= $this->base_version ?>';<?php
        ?>window.ledgerSaveHeaders = (window.ledgerSaveHeaders ?? []).concat([injectJarsHeaders]);<?php
    }

    public function save(array $data): array
    {
        if (strtolower(getallheaders()['X-Differential'] ?? 'false') === 'true') {
            $data = array_values(array_filter(array_map(function ($line): ?object {
                $orig = $this->jars->get($line->type, $line->id);

                unset($line->type, $line->id);

                if (!$obvars = get_object_vars($line)) {
                    return null;
                }

                $changed = false;

                foreach (get_object_vars($line) as $prop => $value) {
                    if ($orig->$prop !== $value) {
                        $orig->$prop = $value;
                        $changed = true;
                    }
                }

                if (!$changed) {
                    return null;
                }

                return $orig;
            }, $data)));
        }

        return $this->jars->save($data, @getallheaders()['X-Base-Version']);
    }
}
