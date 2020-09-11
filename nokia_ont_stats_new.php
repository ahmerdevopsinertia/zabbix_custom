#!/usr/bin/php
<?php

require_once('nokia_ams_plugin_configuration.php');
require_once('nokia_validate_xml.php');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// fetching Nokia AMS plugin sdc service urls & credentials
$ams_configuration_instance = new NokiaAMSPluginConfiguration();
$ams_configuration = $ams_configuration_instance->get();

$sdc_server_host = $ams_configuration['sdc_server_host'];
$sdc_service_url = $ams_configuration['sdc_service_url'];
$sdc_service_name = $ams_configuration['sdc_service_name'];
$sdc_server_user = $ams_configuration['sdc_server_user'];
$sdc_server_password = $ams_configuration['sdc_server_password'];

$param_value = 0;

// olt name, olt ip address or host, ont and stats param
if ((!isset($argv[1])) || (!isset($argv[2])) || (!isset($argv[3])) || (!isset($argv[4])) || (!isset($argv[5]))) {
    $olt_name = 'AMS';
    $olt_host = 'olt0.test02';
    $ont = '/rack=1/shelf=1/slot=LT3/port=16/remote_unit=1';
    $stats_type = '/type=Optical Measurements';
    $stats_param = 'gponOntAniOpInfoTxOpticalSignalLevel';

    // extract ont name
    $ont = extract_ont($ont, $stats_type);

    // echo $param_value;
    // return;
} else {
    $olt_name = $argv[1];
    $olt_host = $argv[2];
    $ont = $argv[3];
    $stats_type = $argv[4];
    $stats_param = $argv[5];

    // extract ont name
    $ont = extract_ont($ont, $stats_type);
}

$host = $sdc_server_host;
$sdc_service_url  = 'https://' . $host . '/' . $sdc_service_url;
$sdc_service_name = 'https://' . $host . '/' . $sdc_service_name;
$credentials = $sdc_server_user . ':' . $sdc_server_password;

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
    $ch = curl_init($sdc_service_url);
    curl_setopt($ch, CURLOPT_URL, $sdc_service_url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $request_payload);
    curl_setopt($ch, CURLOPT_USERPWD, $credentials);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept-Encoding: gzip,deflate',
        'Content-Type: text/xml;charset=UTF-8',
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1000);
    curl_setopt($ch, CURLOPT_TIMEOUT, 1000);
    $response = curl_exec($ch);
    $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($responseCode != 200) {
        curl_close($ch);
        echo $param_value;
        return;
    }
    curl_close($ch);

    // preparing result
    $validateXML = new NokiaValidateXml(NULL, NULL);
    // cleaning extra string from xml to validate the xml in next steps
    $cleaned_xml_string = $validateXML->cleanXMLStringCustom($response, ['soapenv:', 'tmf854:', 'sdc:'], NULL);

    if ($cleaned_xml_string != 'exception') {
        $xml_object = simplexml_load_string($cleaned_xml_string, 'SimpleXMLElement', LIBXML_NOWARNING);
        $json = json_encode($xml_object);
        $json_array = json_decode($json, true);

        if (!isset($json_array['Body']) || (isset($json_array['Body']['Fault']))) {
            echo $param_value;
            return;
        }
        $param_status = $json_array['Body']['GetPerformanceMonitoringDataForObjectsResponse']['pmDataListForObject']['pmDataList']['pmData']['pmParameterStatus'];
        if ($param_status == 'PMIS_Valid') {
            $param_value = $json_array['Body']['GetPerformanceMonitoringDataForObjectsResponse']['pmDataListForObject']['pmDataList']['pmData']['pmParameterValue'];
            if ($stats_type == '/type=UNI') {
                if ($param_value == 'Instance Unavailable') {
                    $param_value = 0;
                }
            }
        }
    }
    echo trim($param_value);
} catch (Exception $e) {
    echo $param_value;
    return;
}

function extract_ont($ont, $stats_type)
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