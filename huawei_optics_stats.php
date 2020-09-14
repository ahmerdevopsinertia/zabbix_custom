#!/usr/bin/php
<?php

if (isset($argv[1])) {
    $host = $argv[1];
} else {
    $host = '172.17.66.5';
}

if (isset($argv[2])) {
    $community = $argv[2];
} else {
    $community = 'olt_5800';
}

if (isset($argv[3])) {
    $oid = $argv[3];
} else {
    $oid = '1.3.6.1.4.1.2011.6.128.1.1.2.51.1.1';
}

if (isset($argv[4])) {
    $oid_description = $argv[4];
} else {
    $oid_description = 'ifDesc';
}

snmp_set_quick_print(TRUE);

$snmp2_result = snmp2_real_walk($host, $community, $oid);
$snmp2_ifDesc = snmp2_real_walk($host, $community, $oid_description);

$json_array = array('data' => array());

if (count($snmp2_result) > 0 && count($snmp2_ifDesc) > 0) {
    $ifDesc_array = array();
    foreach ($snmp2_ifDesc as $key => $value) {
        $explode_array = explode(".", $key);
        $ifDesc_value = explode(" ", $value);
        if (count($ifDesc_value) > 1) {
            $ifDesc_value = $ifDesc_value[1];
        } else {
            $ifDesc_value = 0;
        }

        $ifDesc_items = array('ifDesc' => $explode_array[1], 'value' => $ifDesc_value);
        array_push($ifDesc_array, $ifDesc_items);
    }

    $generated_array = array('ifIndex' => NULL, 'ontId' => NULL, 'ifDesc' => NULL);
    foreach ($snmp2_result as $key => $value) {
        $explode_array = explode(".", $key);
        $sliced_array = array_slice($explode_array, -2, 2);
        foreach ($ifDesc_array as $item) {
            if ($sliced_array[0] == $item['ifDesc']) {
                $generated_array['ifIndex'] = $sliced_array[0];
                $generated_array['ontId'] = $sliced_array[1];
                $generated_array['ifDesc'] = $item['value'];
                array_push($json_array['data'], $generated_array);
            }
        }
    }
    print_r(json_encode($json_array));
    return;
} else {
    array_push($json_array['data'], array('ifIndex' => 0, 'ontId' => 0, 'ifDesc' => 0));
    print_r(json_encode($json_array));
    return;
}
