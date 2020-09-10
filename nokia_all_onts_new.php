#!/usr/bin/php
<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('nokia_validate_xml.php');
$ont_json_array = array('data' => array());

// idm server host
if (!isset($argv[1])) {
   $param_one = '172.16.66.20:8443';
} else {
   $param_one = $argv[1];
}

// idm service url
if (!isset($argv[2])) {
   $param_two = 'idm/services/InventoryRetrievalMgrExtns';
} else {
   $param_two = $argv[2];
}

// idm server user_name   
if (!isset($argv[3])) {
   $param_three = 'techno';
} else {
   $param_three = $argv[3];
}

// idm server password
if (!isset($argv[4])) {
   $param_four = '4EPf3nme';
} else {
   $param_four = $argv[4];
}

// olt name
if (!isset($argv[5])) {
   $param_five = 'AMS';
} else {
   $param_five = $argv[5];
}

// olt ip address or host
if (!isset($argv[6])) {
   $param_six = 'olt0.test02';
} else {
   $param_six = $argv[6];
}

// idm service name
if (!isset($argv[7])) {
   $param_seven = 'idm/services';
} else {
   $param_seven = $argv[7];
}

$host = $param_one;
$service_url = 'https://' . $host . '/' . $param_two;
$service_name = 'https://' . $host . '/' . $param_seven;
$user_name = $param_three;
$password = $param_four;

$credentials = $user_name . ':' . $password;

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
                     <tmf854:mdNm>' . $param_five . '</tmf854:mdNm>
                     <tmf854:meNm>' . $param_six . '</tmf854:meNm>
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

   if ($response === FALSE) {
      $response = curl_error($ch);
   }
   curl_close($ch);

   // preparing json

   $validateXML = new NokiaValidateXml(NULL, NULL);
   $cleaned_xml_string = $validateXML->cleanXMLStringCustom($response, ['soapenv:', 'tmf854:', 'sdc:'], NULL);

   // Array ( [Fault] => Array ( [faultcode] => VersionMismatch [faultstring] => Only SOAP 1.1 or SOAP 1.2 messages are supported in the system [detail] => Array ( ) ) ) 1

   if ($cleaned_xml_string != 'exception') {
      $xml_object = simplexml_load_string($cleaned_xml_string, 'SimpleXMLElement', LIBXML_NOWARNING);

      $json = json_encode($xml_object);
      $json_array = json_decode($json, true);
      if (!isset($json_array['Body'])) {
         print_r(json_encode($json_array));
         return;
      }
      $inventory_object_array = $json_array['Body']['getInventoryResponse']['inventoryObjectData']['inventoryObject'];
      $ont_find_pattern = "/^\/rack=([0-9]+)\/shelf=([0-9]+)\/slot=LT([0-9]+)\/port=([0-9]+)\/remote_unit=([0-9]+)$/";

      foreach ($inventory_object_array as $value) {
         if (isset($value['eh']['name']['ehNm'])) {
            if (preg_match($ont_find_pattern, trim($value['eh']['name']['ehNm']))) {
               array_push($ont_json_array['data'], array(
                  'ont' => trim($value['eh']['name']['ehNm']),
                  'hostName' => trim($value['eh']['name']['mdNm']),
                  'host' => trim($value['eh']['name']['meNm'])
               ));
            }
         }
      }
      print_r(json_encode($ont_json_array));
      return;
   } else {
      array_push($json['data'], array('ont' => '0'));
      print_r(json_encode($json_array));
      return;
   }
} catch (Exception $e) {
   print_r(json_encode($json_array));
   return;
}
?>