#!/usr/bin/php
<?php

require_once('nokia_ams_plugin_configuration.php');
require_once('nokia_validate_xml.php');

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
    // stopping execution immediaetly 
    echo $param_value;
    return;
} else {
    $olt_name = $argv[1];
    $olt_host = $argv[2];
    $ont = $argv[3];
    $stats_type = $argv[4];
    $stats_param = $argv[5];

    // prepare ONT
    if ($stats_type == '/type=UNI') {
        $ont = prepare_ont($ont);
    }
}

$host = $sdc_server_host;
$sdc_service_url  = 'https://' . $host . '/' . $sdc_service_url;
$sdc_service_name = 'https://' . $host . '/' . $sdc_service_name;
$credentials = $sdc_server_user . ':' . $sdc_server_password;

// request payload (SOAP format)
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
               <tmf:propNm>' . $stats_type . '/' . $ont . '</tmf:propNm>
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

// prepare ONT for bandwidht stats
function prepare_ont($ont)
{
    // for bandwidth stats
    $new_ont = $ont . '.C14.P1';
    return $new_ont;
}
?>