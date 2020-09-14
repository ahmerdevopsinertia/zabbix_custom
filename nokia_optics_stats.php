#!/usr/bin/php
<?php

require_once('nokia_olt_connection.php');

$json = array('data' => NULL);
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

// call connection function
$connection_class = new NokiaOltConnection($host, $port, $user, $password);
$connection = $connection_class->get_connection();
$relativePath = '/usr/lib/zabbix/externalscripts/';
$xmlFile = $host . '_optics_stats.xml';

if ($connection != NULL) {
    $shell  = $connection_class->get_shell($connection, 'xterm');
    if ($shell != NULL) {
        $command = 'show equipment ont optics xml' . PHP_EOL;
        fwrite($shell, $command);
        sleep(1);
        $fh = fopen($relativePath . $xmlFile, 'wa+');
        while ($line = fgets($shell)) {
            $line = trim($line);
            if (preg_match('/^</', $line)) {
                flush();
                fputs($fh, $line . "\r\n");
            }
        }
        fclose($fh);
        fclose($shell);

        chmod($relativePath . $xmlFile, 0777);

        sleep(1);

        $xml_object = simplexml_load_file($relativePath . $xmlFile);

        $json_string = json_encode($xml_object);

        $json_array = json_decode($json_string, true);

        $json_array = $json_array['hierarchy']['hierarchy']['hierarchy']['hierarchy']['instance'];

        $json_array = array_map(function ($arr) {
            return array('port' => $arr['res-id']);
        }, $json_array);

        $json['data'] = $json_array;

        print_r(json_encode($json));
    } else {
        $json['data'][] = array('port' => '0');
        print_r(json_encode($json));
        return;
    }
} else {
    $json['data'][] = array('port' => '0');
    print_r(json_encode($json));
    return;
}
