#!/usr/bin/php
<?php

$finalArray = array('data' => NULL);
if (isset($argv[1])) {
    $host = $argv[1];
} else {
    $host = '172.17.66.13';
}

if (isset($argv[2])) {
    $user = $argv[2];
} else {
    $user = 'techno';
}

if (isset($argv[3])) {
    $password = $argv[3];
} else {
    $password = '4EPf3nme';
}

if (isset($argv[4])) {
    $port = $argv[4];
} else {
    $port = 22;
}

$connection = ssh2_connect($host, $port);
if (!ssh2_auth_password($connection, $user, $password)) {
    $finalArray['data'][] = array('port' => '0');
    print_r(json_encode($finalArray));
    return;
}

// EXECUTING SECOND COMMAND
// $command = 'show interface port detail' . PHP_EOL;
$command = 'show equipment ont optics xml' . PHP_EOL;
// if ($shell == NULL) {
//     $finalArray['data'][] = array('pwwort' => '0');
//     print_r(json_encode($finalArray));
//     return;
// }
if (!($shell = ssh2_shell($connection, 'xterm'))) {
    $finalArray['data'][] = array('possssrt' => '0');
    print_r(json_encode($finalArray));
    return;
}

fwrite($shell, $command);

sleep(1);

// unset($fh);
$fh = fopen('/usr/lib/zabbix/externalscripts/nokia_interface_details.xml', 'wa+');
$counter = 0;
while (!feof($shell)) {
    // echo "<br>";
    // echo "test";
    // echo "<br>";
    $counter += 1;
    echo $counter . "\n";
    $line = trim(fgets($shell));
    echo $line . "\n";
    flush();
    fputs($fh, $line . "\r\n");
}

// while ($line = fgets($shell)) {
//     $counter += 1;
//     echo $counter;

//     $line = trim($line);
//     // if (preg_match('/^</', $line)) {
//     flush();
//     fputs($fh, $line . "\r\n");
//     // }
// }
fclose($fh);
fclose($shell);

die('In-progress End');

$command = 'show equipment ont optics xml' . PHP_EOL;
$shell = NULL;
if (!($shell = ssh2_shell($connection, 'xterm'))) {
    $finalArray['data'][] = array('possssrt' => '0');
    print_r(json_encode($finalArray));
    return;
}

fwrite($shell, $command);

sleep(10);

$fh = fopen('/usr/lib/zabbix/externalscripts/nokia_optics_stats_temp.xml', 'wa+');

while ($line = fgets($shell)) {
    $line = trim($line);
    if (preg_match('/^</', $line)) {
        flush();
        fputs($fh, $line . "\r\n");
    }
}
fclose($fh);

// EXECUTING THIRD COMMAND
$command = 'show equipment ont status pon detail xml' . PHP_EOL;
if ($shell == NULL) {
    $finalArray['data'][] = array('port' => '0');
    print_r(json_encode($finalArray));
    return;
}

fwrite($shell, $command);
sleep(1);

unset($fh);

$fh = fopen('/usr/lib/zabbix/externalscripts/nokia_equipment_status.xml', 'wa+');

while ($line = fgets($shell)) {
    $line = trim($line);
    // if (preg_match('/^</', $line)) {
    flush();
    fputs($fh, $line . "\r\n");
    // }
}
fclose($fh);

// EXECUTING SECOND COMMAND
$command = 'show interface port detail xml' . PHP_EOL;
if ($shell == NULL) {
    $finalArray['data'][] = array('pwwort' => '0');
    print_r(json_encode($finalArray));
    return;
}

fwrite($shell, $command);

sleep(10);

unset($fh);
$fh = fopen('/usr/lib/zabbix/externalscripts/nokia_interface_details.xml', 'wa+');

while ($line = fgets($shell)) {
    $line = trim($line);
    // if (preg_match('/^</', $line)) {
    flush();
    fputs($fh, $line . "\r\n");
    // }
}
fclose($fh);
fclose($shell);

// READING XML FILES

chmod('/usr/lib/zabbix/externalscripts/nokia_optics_stats_temp.xml', 0777);
chmod('/usr/lib/zabbix/externalscripts/nokia_interface_details.xml', 0777);
chmod('/usr/lib/zabbix/externalscripts/nokia_equipment_status.xml', 0777);

sleep(1);

echo "TEST";

    // $xmlObject = simplexml_load_file('/usr/lib/zabbix/externalscripts/nokia_optics_stats_temp.xml');

    // $jsonString = json_encode($xmlObject);

    // $jsonArray = json_decode($jsonString, true);

    // $jsonArray = $jsonArray['hierarchy']['hierarchy']['hierarchy']['hierarchy']['instance'];

    // $jsonArray = array_map(function ($arr) {
    //     return array('port' => $arr['res-id']);
    // }, $jsonArray);

    // $finalArray['data'] = $jsonArray;

    // print_r(json_encode($finalArray));
