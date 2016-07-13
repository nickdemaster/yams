<?php

// API key must match what is sent from the client
$apikey="";

//dataconnect to MySQL
$hostname = "";
$username = "";
$password = "";
$schema = "yams";
$port = "3306";


$link = mysqli_connect($hostname, $username, $password, $schema, $port);

if (!$link) {
    die('Connect Error (' . mysqli_connect_errno() . ') '
            . mysqli_connect_error());
}


?>