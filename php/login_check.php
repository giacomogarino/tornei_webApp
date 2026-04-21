<?php
include("../conf/db_config.php");

//print_r($_POST);

//echo "<div>il tuo utente e': ".$_POST["user"]."</div>";

//prepare statement -> parte che pulisce la query
$stmt = $conn->prepare("SELECT * FROM utente WHERE email=? and password=?"); //prepara la query
$stmt->bind_param("ss", $_POST["email"], cryptpsw($_POST["password"])); //serve per evitare la sql injection
$stmt->execute();

//salvaaggio dei dati in una singola riga
$result =$stmt->get_result();
$row = $result->fetch_assoc(); //crea un array etichettato/dizionario e restituisce una sola riga
//print_r($row);

//controlla se $row è settaxto
if(isset($row)){  
    session_start(); //serve per creare la seessione-> crea un legame tra macchina e server
    
    $_SESSION['login']='ok'; //vuol dire che la sessione è attiva (variabile globale-> possono leggerla tutte le pagine)
    $_SESSION['id_utente']=$row['id'];
    $_SESSION['nome_utente']=$row['nome'];

    if(isset($row["n_patente"])){
        $_SESSION['autista']='y';
    }else{
        $_SESSION['autista']='n';
    }
    header("location: ../home.php");
    //echo "<div>Benvenuto: ".$row['nome']."</div>"; //stampa row
}else{
    header("location: ../index.php?msg=errLogin");
    //echo "<div>utente non trovato</div>";
}

$stmt->close();
$conn->close();
?>
