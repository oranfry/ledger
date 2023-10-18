<table class="easy-table"><?php
    ?><thead><?php
        ?><tr><?php
            ?><th>account</th><?php
            ?><th class="right">amount</th><?php
        ?></tr><?php
    ?></thead><?php
    ?><tbody><?php
        foreach ($account_summary as $account => $amount):
            ?><tr><?php
                ?><td><?= $account ?></td><?php
                ?><td class="right"><?= $amount ?></td><?php
            ?></tr><?php
        endforeach;
    ?></tbody><?php
?></table><?php
