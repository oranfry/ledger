<?php

if (!$numLinetypes = count($ledger->linetypes())) {
    return;
}

$prepop = $ledger->groupingInfo()->prepopulate ?? null;
$prepopData = [];

if ($prepop instanceof Closure) {
    $prepopData = $prepop($lineGrouping);

    if (!is_array($prepopData)) {
        $prepopData = [];
    }
} elseif (is_string($prepop) && $lineGrouping) {
    $prepopData[$prepop] = $lineGrouping;
}

$prepopJson = $prepopData ? str_replace("'", '&#39;', json_encode($prepopData)) : null;

if ($numLinetypes > 1) {
    ?><div class="inline-rel"><?php
        ?><div class="inline-modal inline-modal--right"><?php
            ?><nav<?php
                ?> style="text-align: left;"<?php

                if ($prepopJson) {
                    ?> data-prepop='<?= $prepopJson ?>'<?php
                }
            ?>><?php
                foreach ($ledger->linetypes() as $linetype) {
                    ?><a<?php
                        ?> href="#"<?php
                        ?> class="trigger-add-line"<?php
                        ?> data-type="<?= $linetype->name ?>"<?php
                    ?>><?php
                        echo '+' . $linetype->name;
                    ?></a><br><?php
                }
            ?></nav><?php
        ?></div><?php
}

?><a<?php
    ?> href="#"<?php
    ?> class="<?= $numLinetypes > 1 ? 'inline-modal-trigger' : 'trigger-add-line' ?>"<?php
    ?> style="line-height: 0; display: block;"<?php

    if ($numLinetypes === 1) {
        ?> data-type="<?= $ledger->linetypes()[0]->name ?>"<?php

        if ($prepopJson) {
            ?> data-prepop='<?= $prepopJson ?>'<?php
        }
    }
?>><?php
    ?><i class="icon icon--gray icon--plus"></i><?php
?></a><?php

if ($numLinetypes > 1) {
    ?></div><?php
}
