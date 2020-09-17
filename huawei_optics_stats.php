#!/usr/bin/php
<?php

$json_array = array('data' => array());

if (isset($argv[1])) {
    $host = $argv[1];
} else {
    // stopping execution immediaetly 
    array_push($json_array['data'], array('ifIndex' => 0, 'ontId' => 0, 'ifDesc' => 0));
    print_r(json_encode($json_array));
    return;
}

if (isset($argv[2])) {
    $community = $argv[2];
} else {
    // stopping execution immediaetly 
    array_push($json_array['data'], array('ifIndex' => 0, 'ontId' => 0, 'ifDesc' => 0));
    print_r(json_encode($json_array));
}

if (isset($argv[3])) {
    $oid = $argv[3];
} else {
    // stopping execution immediaetly 
    array_push($json_array['data'], array('ifIndex' => 0, 'ontId' => 0, 'ifDesc' => 0));
    print_r(json_encode($json_array));
}

if (isset($argv[4])) {
    $oid_description = $argv[4];
} else {
    // stopping execution immediaetly 
    array_push($json_array['data'], array('ifIndex' => 0, 'ontId' => 0, 'ifDesc' => 0));
    print_r(json_encode($json_array));
}

try {

    // executing SNMP commands
    snmp_set_quick_print(TRUE);

    // executing SNMP commands
    $snmp2_result = snmp2_real_walk($host, $community, $oid);
    $snmp2_ifDesc = snmp2_real_walk($host, $community, $oid_description);

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

        // preparing final result
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
} catch (Exception $e) {
    array_push($json_array['data'], array('ifIndex' => 0, 'ontId' => 0, 'ifDesc' => 0));
    print_r(json_encode($json_array));
    return;
}
