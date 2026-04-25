<?php
/*
$servername = "10.210.0.242";
$username = "admin_torneo";
$password = "torneo_crazy";
$dbname = "torneo";
*/
$servername = "89.40.172.111";
$username = "itpbrgro_wp761";
$password = "36-S@9AQ0].pWj)8";
$dbname = "itpbrgro_wp761";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

function cryptpsw($psw){
  $salt = "chiave_per_cifratura";
  return crypt($psw, $salt);
}
?>