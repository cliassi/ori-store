<?php
require_once('rb.php');
$config = new Config();
$config->db_host = DB_HOST;
$config->db_name = 'store';
$config->db_user = DB_USER;
$config->db_pass = DB_PASS;
$config->db_port = DB_PORT;

$c = mysqli_connect($config->db_host, $config->db_user, $config->db_pass, $config->db_name, $config->db_port);
$c->query("SET timezone = '+8:00'");
R::setup('mysql:host='.$config->db_host.';port='.$config->db_port.';dbname='.$config->db_name,$config->db_user,$config->db_pass); 
R::freeze(true); 


class Config
{
	var $db_host = "localhost";
	var $db_user = "root";
	var $db_pass = "";
	var $db_port = 3306;
	var $db_name = "";

	function __get($name){
		return $this->$name;
	}

	function __set($name, $value){
		$this->$name = $value;
	}
}

if( !function_exists('random_bytes') )
{
    function random_bytes($length = 6)
    {
        $characters = '0123456789';
        $characters_length = strlen($characters);
        $output = '';
        for ($i = 0; $i < $length; $i++)
            $output .= $characters[rand(0, $characters_length - 1)];

        return $output;
    }
}