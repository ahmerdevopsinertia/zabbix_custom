#!/usr/bin/php
<?php

$output = 0;
if (isset($argv[1])) {
    $port = $argv[1];
} else {
    $output = floatVal($output);
    echo $output;return;
}

if (isset($argv[2])) {
    $type = strtoupper($argv[2]);
} else {
   $output = floatVal($output);
   echo $output;return;
}

$xmlObject = simplexml_load_file('/usr/lib/zabbix/externalscripts/nokia_optics_stats.xml');

$jsonString = json_encode($xmlObject);

$jsonArray = json_decode($jsonString, true);

$jsonArray = $jsonArray['hierarchy']['hierarchy']['hierarchy']['hierarchy']['instance'];

$like = $port;
$resultant_array = array_values(array_filter($jsonArray, function ($item) use ($like) {
    if (stripos($item['res-id'], $like) !== false) {
        return true;
    }
    return false;
}));


if (count($resultant_array) > 0) {
    if ($type == 'RX') {
        if ($resultant_array[0]['info'][0] == 'unknown') {
            $output = -100;
        } else {
            $output = $resultant_array[0]['info'][0];
        }
    } else {
        if ($resultant_array[0]['info'][1] == 'unknown') {
            $output = -100;
        } else {
            $output = $resultant_array[0]['info'][1];
        }
    }
}

echo floatVal($output);