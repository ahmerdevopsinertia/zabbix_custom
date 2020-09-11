#!/usr/bin/php
<?php

require_once('nokia_ams_plugin_configuration.php');
require_once('nokia_validate_xml.php');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$ont_json_array = array('data' => array());

// fetching Nokia AMS plugin idm service urls & credentials
$ams_configuration_instance = new NokiaAMSPluginConfiguration();
$ams_configuration = $ams_configuration_instance->get();

$idm_server_host = $ams_configuration['idm_server_host'];
$idm_service_url = $ams_configuration['idm_service_url'];
$idm_service_name = $ams_configuration['idm_service_name'];
$idm_server_user = $ams_configuration['idm_server_user'];
$idm_server_password = $ams_configuration['idm_server_password'];

$param_value = 0;

// olt name
if (!isset($argv[1])) {
   $olt_name = 'AMS';
} else {
   $olt_name = $argv[1];
}

// olt ip address or host
if (!isset($argv[2])) {
   $olt_host = 'olt0.test02';
} else {
   $olt_host = $argv[2];
}

$service_url = 'https://' . $idm_server_host . '/' . $idm_service_url;
$service_name = 'https://' . $idm_server_host . '/' . $idm_service_name;
$credentials = $idm_server_user . ':' . $idm_server_password;

$request_payload = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns="alu.v1" xmlns:alu="alu.v1" xmlns:tmf854="tmf854.v1" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
   <soapenv:Header>
      <tmf854:header tmf854Version="1.1">
         <tmf854:activityName>getInventory</tmf854:activityName>
         <tmf854:msgName>getInventory</tmf854:msgName>
         <tmf854:msgType>REQUEST</tmf854:msgType>
         <tmf854:senderURI>' . $service_name . '</tmf854:senderURI>
         <tmf854:destinationURI>' . $service_name . '</tmf854:destinationURI>
         <tmf854:correlationId>1</tmf854:correlationId>
         <tmf854:communicationPattern>MultipleBatchResponse</tmf854:communicationPattern>
         <tmf854:communicationStyle>RPC</tmf854:communicationStyle>
         <tmf854:requestedBatchSize>200</tmf854:requestedBatchSize>
         <tmf854:timestamp>20070405070101.1-0400</tmf854:timestamp>
      </tmf854:header>
   </soapenv:Header>
   <soapenv:Body>
      <getInventory>
         <filter>
            <scopeList>
               <scope>
                  <baseObject>
                     <tmf854:mdNm>' . $olt_name . '</tmf854:mdNm>
                     <tmf854:meNm>' . $olt_host . '</tmf854:meNm>
                  </baseObject>
                  <level>WHOLE_SUBTREE</level>
               </scope>
            </scopeList>
         </filter>
      </getInventory>
   </soapenv:Body>
</soapenv:Envelope>';

try {

   //setting the curl parameters

   $ch = curl_init();
   curl_setopt($ch, CURLOPT_URL, $service_url);
   curl_setopt($ch, CURLOPT_POSTFIELDS, $request_payload);
   curl_setopt($ch, CURLOPT_USERPWD, $credentials);
   curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Accept-Encoding: gzip,deflate',
      'Content-Type: text/xml;charset=UTF-8',
      'SOAPAction: getInventory'
   ));
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
   curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
   curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
   curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1000);
   curl_setopt($ch, CURLOPT_TIMEOUT, 1000);

   $response = curl_exec($ch);
   $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

   if ($responseCode != 200) {
      curl_close($ch);
      array_push($ont_json_array['data'], array('ont' => 0, 'ontNumber' => '0', 'oltName' => '0', 'oltHost' => '0'));
      print_r(json_encode($ont_json_array));
      return;
   }
   curl_close($ch);

   // preparing final result

   $validateXML = new NokiaValidateXml(NULL, NULL);

   // cleaning extra string from xml to validate the xml in next steps
   $cleaned_xml_string = $validateXML->cleanXMLStringCustom($response, ['soapenv:', 'tmf854:', 'sdc:'], NULL);

   if ($cleaned_xml_string != 'exception') {
      $xml_object = simplexml_load_string($cleaned_xml_string, 'SimpleXMLElement', LIBXML_NOWARNING);
      $json = json_encode($xml_object);
      $json_array = json_decode($json, true);

      if (!isset($json_array['Body']) || (isset($json_array['Body']['Fault']))) {
         array_push($ont_json_array['data'], array('ont' => 0, 'ontNumber' => '0', 'oltName' => '0', 'oltHost' => '0'));
         print_r(json_encode($ont_json_array));
         return;
      }
      $inventory_object_array = $json_array['Body']['getInventoryResponse']['inventoryObjectData']['inventoryObject'];
      $ont_find_pattern = "/^\/rack=([0-9]+)\/shelf=([0-9]+)\/slot=LT([0-9]+)\/port=([0-9]+)\/remote_unit=([0-9]+)$/";

      foreach ($inventory_object_array as $value) {
         if (isset($value['eh']['name']['ehNm'])) {
            if (preg_match($ont_find_pattern, trim($value['eh']['name']['ehNm']))) {
               $ont_split = explode('/', trim($value['eh']['name']['ehNm']));
               $ont_split = explode('=', $ont_split[5]);
               $ont_split = $ont_split[1];
               array_push($ont_json_array['data'], array(
                  'ontNumber' => (int)$ont_split,
                  'ont' => trim($value['eh']['name']['ehNm']),
                  'oltName' => trim($value['eh']['name']['mdNm']),
                  'oltHost' => trim($value['eh']['name']['meNm'])
               ));
            }
         }
      }
      print_r(json_encode($ont_json_array));
      return;
   } else {
      array_push($ont_json_array['data'], array('ont' => 0, 'ontNumber' => '0', 'oltName' => '0', 'oltHost' => '0'));
      print_r(json_encode($ont_json_array));
      return;
   }
} catch (Exception $e) {
   array_push($ont_json_array['data'], array('ont' => 0, 'ontNumber' => '0', 'oltName' => '0', 'oltHost' => '0'));
   print_r(json_encode($ont_json_array));
   return;
}
?>