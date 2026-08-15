<?php

if (count($ledger->linetypes()) > 1) {
    ?><div class="inline-rel"><?php
        ?><div class="inline-modal inline-modal--right"><?php
            ?><nav style="text-align: left;"><?php
                foreach ($ledger->linetypes() as $linetype) {
                    ?><a href="#" class="trigger-add-line" data-type="<?= $linetype->name ?>">+<?= $linetype->name ?></a><br><?php
                }
            ?></nav><?php
        ?></div><?php
}

if (count($ledger->linetypes())) {
    ?><a<?php
        ?> href="#"<?php
        ?> class="<?php
        echo count($ledger->linetypes()) > 1 ? 'inline-modal-trigger' : 'trigger-add-line';
        ?>"<?php
        ?> style="line-height: 0; display: block;"<?php
        ?> data-type="<?= $ledger->linetypes()[0]->name ?>"<?php
    ?>><?php
        ?><i class="icon icon--gray icon--plus"></i><?php
    ?></a><?php
}

if (count($ledger->linetypes()) > 1) {
    ?></div><?php
}