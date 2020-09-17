#!/usr/bin/php
<?php

require_once('nokia_ams_plugin_configuration.php');
require_once('nokia_validate_xml.php');

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
   // stopping execution immediaetly 
   array_push($ont_json_array['data'], array('ont' => 0, 'ontNumber' => '0', 'oltName' => '0', 'oltHost' => '0'));
   print_r(json_encode($ont_json_array));
   return;
} else {
   $olt_name = $argv[1];
}

// olt ip address or host
if (!isset($argv[2])) {
   array_push($ont_json_array['data'], array('ont' => 0, 'ontNumber' => '0', 'oltName' => '0', 'oltHost' => '0'));
   print_r(json_encode($ont_json_array));
   return;
} else {
   $olt_host = $argv[2];
}

$service_url = 'https://' . $idm_server_host . '/' . $idm_service_url;
$service_name = 'https://' . $idm_server_host . '/' . $idm_service_name;
$credentials = $idm_server_user . ':' . $idm_server_password;

// request payload (SOAP format)
$request_payload = '<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:tmf854="tmf854.v1" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:alu="alu.v1" xmlns="alu.v1">
   <soapenv:Header>
      <tmf854:header tmf854Version="1.1">
         <tmf854:activityName>query</tmf854:activityName>
         <tmf854:msgName>query</tmf854:msgName>
         <tmf854:msgType>REQUEST</tmf854:msgType>
         <tmf854:senderURI>' . $service_name . '</tmf854:senderURI>
         <tmf854:destinationURI>' . $service_name . '</tmf854:destinationURI>
         <tmf854:correlationId>1</tmf854:correlationId>
         <tmf854:communicationPattern>MultipleBatchResponse</tmf854:communicationPattern>
         <tmf854:communicationStyle>RPC</tmf854:communicationStyle>
         <tmf854:requestedBatchSize>1500</tmf854:requestedBatchSize>
         <tmf854:timestamp>20151124103328.840+0700</tmf854:timestamp>
      </tmf854:header>
   </soapenv:Header>
   <soapenv:Body>
      <alu:query>
         <alu:baseObjectList>
            <alu:baseObject>
               <tmf854:mdNm>' . $olt_name . '</tmf854:mdNm>
               <tmf854:meNm>' . $olt_host . '</tmf854:meNm>
            </alu:baseObject>
         </alu:baseObjectList>
         <alu:level>WHOLE_SUBTREE</alu:level>
         <alu:source>AMS</alu:source>
         <alu:filterList>
            <alu:filter>
               <alu:type>ONT</alu:type>
            </alu:filter>
         </alu:filterList>
      </alu:query>
   </soapenv:Body>
</soapenv:Envelope>
';

try {

   //setting the curl parameters

   $ch = curl_init();
   curl_setopt($ch, CURLOPT_URL, $service_url);
   curl_setopt($ch, CURLOPT_POSTFIELDS, $request_payload);
   curl_setopt($ch, CURLOPT_USERPWD, $credentials);
   curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Accept-Encoding: gzip,deflate',
      'Content-Type: text/xml;charset=UTF-8',
      'SOAPAction: query'
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
      $inventory_object_array = $json_array['Body']['queryResponse']['queryObjectData']['queryObject'];

      $ont_find_pattern = "/^\/type=ONT\/R([0-9]+).S([0-9]+).LT([0-9]+).PON([0-9]+).ONT([0-9]+)$/";
      foreach ($inventory_object_array as $value) {
         if ((isset($value['name']['mdNm']) && isset($value['name']['meNm']) && isset($value['name']['ptpNm']))) {
            $trimmed_value = $value['name']['ptpNm'];
            // echo $trimmed_value;
            if (preg_match($ont_find_pattern, $trimmed_value)) {
               // echo '<br>' . 'MATCHED' . PHP_EOL;
               // extracting ONT detail 
               $ont_detail = extract_ont_detail($trimmed_value);
               if ($ont_detail == 'exception') {
                  $ont_detail = 0;
               } else {
                  // extracting ONT number
                  $ont_number = extract_ont_number($ont_detail);
                  if ($ont_number == 'exception') {
                     $ont_number = 0;
                  } else {
                     $ont_number = (int) $ont_number;
                  }
               }

               array_push($ont_json_array['data'], array(
                  'ontNumber' => $ont_number,
                  'ont' => $ont_detail,
                  'oltName' => trim($value['name']['mdNm']),
                  'oltHost' => trim($value['name']['meNm'])
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

function extract_ont_detail($ont)
{
   // /type=ONT/R1.S1.LT3.PON16.ONT2
   try {
      $split_ont = explode('/', $ont);
      return $split_ont[2];
   } catch (Exception $e) {
      return 'exception';
   }
}

function extract_ont_number($ont_detail)
{
   // R1.S1.LT3.PON16.ONT2
   // extracting 2 written with ONT from above string 
   try {
      $split_ont = explode('.', $ont_detail);
      $ont_number = $split_ont[4];
      $ont_number = ltrim($ont_number, substr($ont_number, 0, 3));
      return $ont_number;
   } catch (Exception $e) {
      return 'exception';
   }
}
?>