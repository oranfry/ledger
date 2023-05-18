<script>
    window.lines = <?= json_encode($lines); ?>;

    softChangeInstance();
</script>

<?php ss_include('src/php/partial/js/ledger-extra' . (defined('LEDGER_CONFIG') ? '/' . LEDGER_CONFIG : null) . '.php', $viewdata); ?>
