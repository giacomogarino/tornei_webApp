<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "maturita_2017";

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