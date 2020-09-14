<?php
class NokiaOltConnection
{
    public $host;
    public $port;
    public $user;
    public $password;

    function __construct($host, $port, $user, $password)
    {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->password = $password;
    }

    function get_connection()
    {
        $connection = ssh2_connect($this->host, $this->port);
        if (!ssh2_auth_password($connection, $this->user, $this->password)) {
            return NULL;
        }
        return $connection;
    }

    function get_shell($connection, $shell_type)
    {
        if (!($shell = ssh2_shell($connection, $shell_type))) {
            return NULL;
        } else {
            return $shell;
        }
    }
}
