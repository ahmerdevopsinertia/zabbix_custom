#!/usr/bin/php
<?php

$xmlStrings = '<?xml version="1.0" encoding="UTF-8"?>' .
    '<runtime-data  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="file:cli_show_output.xsd" display-level="normal">' .
    '<hierarchy name="show" type="static">' .
    '<hierarchy name="equipment" type="static">' .
    '<hierarchy name="ont" type="static">' .
    '<hierarchy name="optics" type="static">';
'<instance>' .
    '<res-id name="ont-idx" short-name="ont-idx" type="Gpon::OntIndexGpon::OntIndex">1/1/3/1/1</res-id>' .
    '<info name="rx-signal-level" short-name="rx-signal-level" type="Gpon::OntOpticalSignalLevel">unknown</info>' .
    '<info name="tx-signal-level" short-name="tx-signal-level" type="Gpon::OntOpticalSignalLevel">unknown</info>' .
    '<info name="ont-temperature" short-name="ont-temperature" type="Gpon::OntTemperature">unknown</info>' .
    '<info name="ont-voltage" short-name="ont-voltage" type="Gpon::OntVoltage">unknown</info>' .
    '<info name="laser-bias-curr" short-name="laser-bias-curr" type="Gpon::OntLaserBias">unknown</info>' .
    '<info name="olt-rx-sig-level" short-name="olt-rx-sig-level" type="Gpon::OntOltRxSignalLevel">invalid</info>' .
    '</instance>' .
    '<instance>' .
    '<res-id name="ont-idx" short-name="ont-idx" type="Gpon::OntIndexGpon::OntIndex">1/1/3/16/1</res-id>' .
    '<info name="rx-signal-level" short-name="rx-signal-level" type="Gpon::OntOpticalSignalLevel">unknown</info>' .
    '<info name="tx-signal-level" short-name="tx-signal-level" type="Gpon::OntOpticalSignalLevel">unknown</info>' .
    '<info name="ont-temperature" short-name="ont-temperature" type="Gpon::OntTemperature">unknown</info>' .
    '<info name="ont-voltage" short-name="ont-voltage" type="Gpon::OntVoltage">unknown</info>' .
    '<info name="laser-bias-curr" short-name="laser-bias-curr" type="Gpon::OntLaserBias">unknown</info>' .
    '<info name="olt-rx-sig-level" short-name="olt-rx-sig-level" type="Gpon::OntOltRxSignalLevel">invalid</info>' .
    '</instance>' .
    '<instance>' .
    '<res-id name="ont-idx" short-name="ont-idx" type="Gpon::OntIndexGpon::OntIndex">1/1/3/16/2</res-id>' .
    '<info name="rx-signal-level" short-name="rx-signal-level" type="Gpon::OntOpticalSignalLevel">-14.686</info>' .
    '<info name="tx-signal-level" short-name="tx-signal-level" type="Gpon::OntOpticalSignalLevel">2.030</info>' .
    '<info name="ont-temperature" short-name="ont-temperature" type="Gpon::OntTemperature">48.000</info>' .
    '<info name="ont-voltage" short-name="ont-voltage" type="Gpon::OntVoltage">3.28</info>' .
    '<info name="laser-bias-curr" short-name="laser-bias-curr" type="Gpon::OntLaserBias">7850.0</info>' .
    '<info name="olt-rx-sig-level" short-name="olt-rx-sig-level" type="Gpon::OntOltRxSignalLevel">-11.9</info>' .
    '</instance>';

for ($i = 0; $i <= 100; $i++) {
    $xmlStrings .= '<instance>' .
        '<res-id name="ont-idx" short-name="ont-idx" type="Gpon::OntIndexGpon::OntIndex">1/1/3/16/' . $i . '</res-id>' .
        '<info name="rx-signal-level" short-name="rx-signal-level" type="Gpon::OntOpticalSignalLevel">-14.686</info>' .
        '<info name="tx-signal-level" short-name="tx-signal-level" type="Gpon::OntOpticalSignalLevel">2.030</info>' .
        '<info name="ont-temperature" short-name="ont-temperature" type="Gpon::OntTemperature">48.000</info>' .
        '<info name="ont-voltage" short-name="ont-voltage" type="Gpon::OntVoltage">3.28</info>' .
        '<info name="laser-bias-curr" short-name="laser-bias-curr" type="Gpon::OntLaserBias">7850.0</info>' .
        '<info name="olt-rx-sig-level" short-name="olt-rx-sig-level" type="Gpon::OntOltRxSignalLevel">-11.9</info>' .
        '</instance>';
}

$xmlStrings .= '</hierarchy>' .
    '</hierarchy>' .
    '</hierarchy>' .
    '</hierarchy>' .
    '</runtime-data>';

$fh = fopen('/usr/lib/zabbix/externalscripts/sample_xml_gen.xml', 'wa+');
fputs($fh, $xmlStrings);
fclose($fh);

chmod('/usr/lib/zabbix/externalscripts/sample_xml_gen.xml', 0777);

sleep(1);

$xmlObject = simplexml_load_file('/usr/lib/zabbix/externalscripts/sample_xml_gen.xml');

$jsonString = json_encode($xmlObject);

$jsonArray = json_decode($jsonString, true);

$jsonArray = $jsonArray['hierarchy']['hierarchy']['hierarchy']['hierarchy']['instance'];

$jsonArray = array_map(function ($arr) {
    return array('port' => $arr['res-id']);
}, $jsonArray);

$finalArray['data'] = $jsonArray;

print_r(json_encode($finalArray));