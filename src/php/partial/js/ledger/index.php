<script><?php
    ?>window.lines = <?= json_encode($lines); ?>;<?php
    ?>window.base_version = '<?= $base_version ?>';<?php
    ?>softCvsApply();<?php
?>
</script><?php

$variant = defined('LEDGER_CONFIG') && LEDGER_CONFIG !== 'default' ? '/' . LEDGER_CONFIG : null;

ss_include('src/php/partial/js/ledger-extra' . $variant . '.php', $viewdata);
