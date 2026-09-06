<?php

use OranFry\Obex\Obex;

echo '<script>';
    ?>window.sum_fields = <?= json_encode(array_values(array_map(fn ($field) => $field->name, array_filter($fields, fn ($field) => (bool) array_filter($field->summary ?? [], fn ($s) => $s->scheme === 'sum'))))) ?>;<?php
    ?>window.linetypes = <?= json_encode(Obex::key($ledger->linetypes(), 'name')) ?>;<?php
    ?>window.lines = <?= json_encode($lines) ?>;<?php
    ?>window.ledgerBaseUrl = '<?= $baseUrl ?>';<?php
    ?>window.toolsPluginMountPoint = '<?= TOOLS_PLUGIN_MOUNT_POINT ?>';<?php

    $ledger->js();
echo '</script>';

$variant = defined('LEDGER_CONFIG') && LEDGER_CONFIG !== 'default' ? '/' . LEDGER_CONFIG : null;

ss_include('src/php/partial/js/ledger-extra' . $variant . '.php', $viewdata);

echo '<script>';
    ?>window.ledgerResizeTimer = null;<?php
    ?>$(window).on('resize', function(){ clearTimeout(window.ledgerResizeTimer); window.ledgerResizeTimer = setTimeout(window.ledgerOnResize, 300); });<?php
    ?>$('.savelineraw').on('click', window.ledgerRawlineSave);<?php
    ?>$lineContainer.on('click', window.ledgerDeselectAllLines);<?php
    ?>ledgerOnResize();<?php
    ?>ledgerRefreshDisplayedLineEditor();<?php
    ?>softCvsApply();<?php
echo '</script>';
