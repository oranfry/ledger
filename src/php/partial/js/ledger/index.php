<script>
    window.lines = <?= json_encode($lines); ?>;

    softChangeInstance();
</script>

<?php $variant = defined('LEDGER_CONFIG') && LEDGER_CONFIG !== 'default' ? '/' . LEDGER_CONFIG : null; ?>
<?php ss_include('src/php/partial/js/ledger-extra' . $variant . '.php', $viewdata); ?>
