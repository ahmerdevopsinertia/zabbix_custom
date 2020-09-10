#!/usr/bin/php
<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$param_value = 0;

// idm server host
if (!isset($argv[1])) {
    $idm_server_host = '172.16.66.20:8443';
} else {
    $idm_server_host = $argv[1];
}

// idm service url
if (!isset($argv[2])) {
    $idm_service_url = 'sdc/services/PerformanceManagementRetrievalExtns';
} else {
    $idm_service_url = $argv[2];
}

// idm service name
if (!isset($argv[3])) {
    $idm_service_name = 'idm/services';
} else {
    $idm_service_name = $argv[3];
}

// idm server user
if (!isset($argv[4])) {
    $idm_server_user = 'techno';
} else {
    $idm_server_user = $argv[4];
}

// idm server password
if (!isset($argv[5])) {
    $idm_server_password = '4EPf3nme';
} else {
    $idm_server_password = $argv[5];
}

// olt name, olt ip address or host, ont and stats param
if ((!isset($argv[6])) || (!isset($argv[7])) || (!isset($argv[8])) || (!isset($argv[9])) || (!isset($argv[10]))) {
    $olt_name = 'AMS';
    $olt_host = 'olt0.test02';
    $ont = '/rack=1/shelf=1/slot=LT3/port=16/remote_unit=1';
    $stats_type = '/type=Optical Measurements';
    $stats_param = 'gponOntAniOpInfoTxOpticalSignalLevel';

    // extract ont name
    $ont = extract_ont_generic($ont, $stats_type);

    // return 0;
} else {
    $olt_name = $argv[6];
    $olt_host = $argv[7];
    $ont = $argv[8];
    $stats_type = $argv[9];
    $stats_param = $argv[10];

    // extract ont name
    $ont = extract_ont_generic($ont, $stats_type);
}

$host = $idm_server_host;
$idm_service_url  = 'https://' . $host . '/' . $idm_service_url;
$idm_service_name = 'https://' . $host . '/' . $idm_service_name;
$credentials = $idm_server_user . ':' . $idm_server_password;

$request_payload = '<soapenv:Envelope xmlns:sdc="sdcNbi" xmlns:tmf="tmf854.v1" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
<soapenv:Header>
   <tmf:header tmf854Version="?" extAuthor="?" extVersion="?">
      <tmf:msgName>GetPerformanceMonitoringDataForObjectsRequest</tmf:msgName>
      <tmf:msgType>REQUEST</tmf:msgType>
      <tmf:communicationPattern>SimpleResponse</tmf:communicationPattern>
      <tmf:communicationStyle>RCP</tmf:communicationStyle>
   </tmf:header>
</soapenv:Header>
<soapenv:Body>
      <sdc:GetPerformanceMonitoringDataForObjectsRequest>
         <sdc:pmInputList>
            <sdc:pmObjectSelect>
               <tmf:mdNm>' . $olt_name . '</tmf:mdNm>
               <tmf:meNm>' . $olt_host . '</tmf:meNm>
               <tmf:propNm>' . $stats_type . $ont . '</tmf:propNm>
            </sdc:pmObjectSelect>
            <sdc:pmParameterList>
							<sdc:pmParameter>
							     <sdc:pmParameterName>' . $stats_param . '</sdc:pmParameterName>
               </sdc:pmParameter>
            </sdc:pmParameterList>
         </sdc:pmInputList>
      </sdc:GetPerformanceMonitoringDataForObjectsRequest>
   </soapenv:Body>
</soapenv:Envelope>';

try {
    //setting the curl parameters
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $idm_service_url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $request_payload);
    curl_setopt($ch, CURLOPT_USERPWD, $credentials);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept-Encoding: gzip,deflate',
        'Content-Type: text/xml;charset=UTF-8',
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1000);
    curl_setopt($ch, CURLOPT_TIMEOUT, 1000);

    $response = curl_exec($ch);

    if ($response === FALSE) {
        $response = curl_error($ch);
    }
    curl_close($ch);

    // preparing json
    require_once('nokia_validate_xml.php');
    $validateXML = new NokiaValidateXml(NULL, NULL);
    $cleaned_xml_string = $validateXML->cleanXMLStringCustom($response, ['soapenv:', 'tmf854:', 'sdc:'], NULL);

    // die(print_r(htmlspecialchars($cleaned_xml_string)));

    // Array ( [Fault] => Array ( [faultcode] => VersionMismatch [faultstring] => Only SOAP 1.1 or SOAP 1.2 messages are supported in the system [detail] => Array ( ) ) ) 1

    if ($cleaned_xml_string != 'exception') {
        $xml_object = simplexml_load_string($cleaned_xml_string, 'SimpleXMLElement', LIBXML_NOWARNING);
        $json = json_encode($xml_object);
        $json_array = json_decode($json, true);

        if (!isset($json_array['Body'])) {
            echo $param_value;
            return;
        }
        $param_status = $json_array['Body']['GetPerformanceMonitoringDataForObjectsResponse']['pmDataListForObject']['pmDataList']['pmData']['pmParameterStatus'];
        if ($param_status == 'PMIS_Valid') {
            $param_value = $json_array['Body']['GetPerformanceMonitoringDataForObjectsResponse']['pmDataListForObject']['pmDataList']['pmData']['pmParameterValue'];
        }
    }
    echo trim($param_value);
} catch (Exception $e) {
    echo $param_value;
    return;
}

// extract ont for bandwidth (up and down) stats
function extract_ont_generic($ont, $stats_type)
{
    // /rack=1/shelf=1/slot=LT3/port=16/remote_unit=1
    // R1.S1.LT3.PON16.ONT2.C14.P1
    // R1.S1.LT3.PON16.ONT1

    $new_ont = NULL;

    $pattern_rack = '/^rack=([0-9]+)$/';
    $pattern_shelf = '/^shelf=([0-9]+)$/';
    $pattern_slot = '/^slot=LT([0-9]+)$/';
    $pattern_port = '/^port=([0-9]+)$/';
    $pattern_remote_unit = '/^remote_unit=([0-9]+)$/';

    $ont_split = explode('/', $ont);

    foreach ($ont_split as $item) {
        // Rack
        if (preg_match($pattern_rack, $item)) {
            $rack_split = explode('=', $item);
            $rack_number = $rack_split[1];
            $new_ont = '/R' . $rack_number . '.';
        }

        // Shelf
        if (preg_match($pattern_shelf, $item)) {
            $shelf_split = explode('=', $item);
            $shelf_number = $shelf_split[1];
            $new_ont .= 'S' . $shelf_number . '.';
        }

        // Slot
        if (preg_match($pattern_slot, $item)) {
            $slot_split = explode('=', $item);
            $slot_number = $slot_split[1];
            $new_ont .= $slot_number . '.';
        }

        // Port
        if (preg_match($pattern_port, $item)) {
            $port_split = explode('=', $item);
            $port_number = $port_split[1];
            $new_ont .= 'PON' . $port_number . '.';
        }

        // Remote Unit
        if (preg_match($pattern_remote_unit, $item)) {
            $remote_unit_split = explode('=', $item);
            $ont_number = $remote_unit_split[1];
            $new_ont .= 'ONT' . $ont_number;
        }
    }

    if ($stats_type == '/type=UNI') {
        // for bandwidth stats
        $new_ont .= '.C14.P1';
    }
    return $new_ont;
}
?>