<?php

if ($_SERVER['REQUEST_URI'] === '/slow') {
    sleep(3);
}

header('Content-Type: application/json');
echo '{"ok":true}';
