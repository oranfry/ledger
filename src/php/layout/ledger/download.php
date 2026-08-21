<?php

header('Content-Type: ' . $content_type);
header('Content-Length: ' . strlen($file));
header('Content-Disposition: attachment; filename=' . $filename);

foreach (@$headers ?? [] as $header) {
    header($header);
}

echo $file;
