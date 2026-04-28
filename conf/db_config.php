<?php
/*
$servername = "10.210.0.242";
$username = "admin_torneo";
$password = "torneo_crazy";
$dbname = "torneo";
*/
$servername = "localhost";
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

//funzione che viene fatta ogni volta che c'è un accesso ogni 10 min, per controllare gli eventuali 
// tornei scaduti e li cambia da 'aperto' a 'in corso'
function aggiorna_tornei_scaduti($conn){
    $lock_file = sys_get_temp_dir() . '/torneo_cron.lock';
    
    if(file_exists($lock_file) && (time() - filemtime($lock_file)) < 600)
        return; // eseguito meno di 10 minuti fa, salta
    
    touch($lock_file);
    
    $conn->query("
        UPDATE torneo
        SET stato = 'in_corso'
        WHERE stato = 'aperto'
          AND data_chiusura_iscrizioni <= NOW()
    ");
}

aggiorna_tornei_scaduti($conn);
?>