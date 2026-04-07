<?php
$lines = file(__DIR__ . '/admin/admin-users.php');
foreach ($lines as $i => $line) {
    if (strpos($line, 'SELECT id, fullname') !== false || strpos($line, 'SELECT id, full_name') !== false) {
        echo ($i + 1) . ': ' . trim($line) . "\n";
    }
}
