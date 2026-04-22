<?php
$servername = "10.210.0.242";
$username = "admin_torneo";
$password = "torneo_crazy";
$dbname = "torneo";

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