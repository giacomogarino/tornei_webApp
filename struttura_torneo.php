<?php

$formato = $torneo['formato'];

switch($formato) {

    case 'eliminazione_diretta':
        require("components/torneo_elim_diretta.php");
        break;

    case 'gironi':
        require("components/torneo_gironi.php");
        break;

    case 'misto':
        require("components/torneo_misto.php");
        break;

    default:
        echo "Formato torneo non valido";
}
?>