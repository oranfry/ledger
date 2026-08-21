<?php

$file = $jars->record(TABLE_NAME, RECORD_ID, $content_type, $filename);

return compact('file', 'content_type', 'filename');
