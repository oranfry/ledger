<script><?php
    ?>window.sum_fields = <?= json_encode(array_values(array_map(fn ($field) => $field->name, array_filter($fields, fn ($field) => (bool) array_filter($field->summary ?? [], fn ($s) => $s->scheme === 'sum'))))) ?>;<?php
    ?>window.linetypes = <?= json_encode(array_combine(array_map(fn ($linetype) => $linetype->name, $linetypes), $linetypes)) ?>;<?php
    ?>window.lines = <?= json_encode($lines) ?>;<?php
    ?>window.base_version = '<?= $base_version ?>';<?php
    ?>softCvsApply();<?php
?>
</script><?php

$variant = defined('LEDGER_CONFIG') && LEDGER_CONFIG !== 'default' ? '/' . LEDGER_CONFIG : null;

ss_include('src/php/partial/js/ledger-extra' . $variant . '.php', $viewdata);
