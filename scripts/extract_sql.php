#!/usr/bin/env php
<?php
$filename = $argv[1];
$tablename = $argv[2];
$search_str = "INSERT INTO `{$tablename}`";

$handle = fopen($filename, "r");
if ($handle) {
    while (($line = fgets($handle)) !== false) {
        if (strpos($line, $search_str) !== false) {
            // Replace INSERT INTO with INSERT IGNORE INTO
            echo str_replace("INSERT INTO", "INSERT IGNORE INTO", $line);
            break;
        }
    }
    fclose($handle);
}
?>
