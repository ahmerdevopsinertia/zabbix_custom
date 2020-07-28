#!/usr/bin/php
<?php

$output = NULL;
if (isset($argv[1]) && $argv[1] != 0) {
    $port = $argv[1];
} else {
    echo $output;
    return;
}

if (isset($argv[2])) {
    $host = $argv[2];
} else {
    $host = '172.17.66.13';
}

$relativePath = '/usr/lib/zabbix/externalscripts/';
$xmlFile = $host . '_equipment_stats.xml';

$xmlObject = simplexml_load_file($relativePath . $xmlFile);

$jsonString = json_encode($xmlObject);

$jsonArray = json_decode($jsonString, true);

$jsonArray = $jsonArray['hierarchy']['hierarchy']['hierarchy']['hierarchy']['hierarchy']['instance'];

$like = $port;
$resultant_array = array_values(array_filter($jsonArray, function ($item) use ($like) {
    if (stripos($item['res-id'][1], $like) !== false) {
        return true;
    }
    return false;
}));

$output = $resultant_array[0]['info'][2];
echo $output;
