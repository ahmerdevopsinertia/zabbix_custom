#!/usr/bin/php
<?php

require_once('nokia_olt_connection.php');

$json = array('data' => array());
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
$xmlFile = $host . '_interface_stats.xml';

if ($connection != NULL) {
    $shell  = $connection_class->get_shell($connection, 'xterm');
    if ($shell != NULL) {
        $command = 'show interface port detail' . PHP_EOL;
        fwrite($shell, $command);
        $fh = fopen($relativePath . $xmlFile, 'wa+');
        $count = 0;
        while ($count < 3) {
            sleep(1);
            while ($line = fgets($shell)) {
                $line = trim($line);
                if (
                    $line != 'Welcome to NOKIA OLT TEST ENnvironment'
                    && $line != 'typ:techno># show interface port detail'
                    && !preg_match('/^=/', $line)
                    && !preg_match('/^-/', $line)
                    && $line != 'port'

                ) {
                    //prepare json array
                    if (preg_match('/^port : ont:/', $line)) {
                        $ont_array = array('ont' => explode(':', $line)[2]);
                        array_push($json['data'], $ont_array);
                    }
                    //writes to file
                    flush();
                    fputs($fh, $line . "\r\n");
                }
            }
            $count++;
        }

        fclose($fh);
        fclose($shell);

        chmod($relativePath . $xmlFile, 0777);
        if (count($json['data']) == 0) {
            array_push($json['data'], array('ont' => '0'));
        }
        print_r(json_encode($json));
        return;
    } else {
        array_push($json['data'], array('ont' => '0'));
        print_r(json_encode($json));
        return;
    }
} else {
    array_push($json['data'], array('ont' => '0'));
    print_r(json_encode($json));
    return;
}
