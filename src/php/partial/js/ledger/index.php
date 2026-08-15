<?php

use OranFry\Obex\Obex;

echo '<script>';
    ?>window.sum_fields = <?= json_encode(array_values(array_map(fn ($field) => $field->name, array_filter($fields, fn ($field) => (bool) array_filter($field->summary ?? [], fn ($s) => $s->scheme === 'sum'))))) ?>;<?php
    ?>window.linetypes = <?= json_encode(Obex::key($ledger->linetypes(), 'name')) ?>;<?php
    ?>window.lines = <?= json_encode($lines) ?>;<?php
    ?>window.base_version = '<?= $base_version ?>';<?php
    ?>window.ledgerBaseUrl = '<?= $baseUrl ?>';<?php
    ?>window.toolsPluginMountPoint = '<?= TOOLS_PLUGIN_MOUNT_POINT ?>';<?php
echo '</script>';

$variant = defined('LEDGER_CONFIG') && LEDGER_CONFIG !== 'default' ? '/' . LEDGER_CONFIG : null;

ss_include('src/php/partial/js/ledger-extra' . $variant . '.php', $viewdata);

echo '<script>';
    ?>ledgerOnResize();<?php
    ?>ledgerRefreshDisplayedLineEditor();<?php
    ?>softCvsApply();<?php
echo '</script>';
