#!/usr/bin/php
<?php

$output = 0;
if (isset($argv[1])) {
    $ont = $argv[1];
} else {
    $output = floatVal($output);
    echo $output;
    return;
}

if (isset($argv[2])) {
    $type = strtolower($argv[2]);
} else {
    $output = floatVal($output);
    echo $output;
    return;
}

if (isset($argv[3])) {
    $host = $argv[3];
} else {
    $host = '172.17.66.13';
}

$relativePath = '/usr/lib/zabbix/externalscripts/';
$xmlFile = $host . '_interface_stats.xml';

$fh = fopen($relativePath . $xmlFile, 'r+');

while ($line = fgets($fh)) {
    if (preg_match('/^port : ont:/', $line)) {
        if (trim(explode(':', $line)[2]) == $ont) {
            $counter = 1;
            while ($counter < 12) {
                $line = fgets($fh);
                if ($counter == 11) {
                    $output = explode(':', $line);
                    if ($type == 'in-octets') {
                        $split_output = explode(" ", $output[1]);
                        $output = $split_output[1];
                        break;
                    }

                    if ($type == 'out-octets') {
                        $output = $output[2];
                        break;
                    }
                }
                $counter++;
            }
        }
    }
}

fclose($fh);
echo $output;
