#!/usr/bin/php
<?php

$output = NULL;
if (isset($argv[1]) && $argv[1] != 0) {
    $port = $argv[1];
} else {
    echo $output;
    return;
}

$xmlObject = simplexml_load_file('/usr/lib/zabbix/externalscripts/nokia_equipment_status.xml');

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
