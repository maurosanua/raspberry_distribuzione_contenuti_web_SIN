<?php

//queste dovrebbero essere indispensabili in tutte le pagine


$prefisso = "";

if(file_exists('include/function.php')){ 
	$prefisso = "";
	
} elseif (file_exists('../include/function.php')) {
	$prefisso = "../";
	
} elseif (file_exists('../../include/function.php')) {
	$prefisso = "../../";
	
} else{
	echo "File di inclusione non trovati";
	die();
}


require_once $prefisso."include/conf.php";
require_once $prefisso."include/par.php";
require_once $prefisso.'include/function.php';
require_once $prefisso.'classi/classe_attributi.php';

require_once $prefisso.'classi/classe_log.php';
require_once $prefisso.'classi/classe_data.php';
require_once $prefisso.'classi/classe_mail.php';

require_once $prefisso.'classi/classe_mysql.php';
//require_once $prefisso.'classi/classe_mssql.php';
//require_once $prefisso.'classi/classe_pgsql.php';

require_once $prefisso.'classi/classe_user.php';
require_once $prefisso.'classi/classe_utenti.php';

require_once $prefisso.'classi/classe_last_event.php';
require_once $prefisso.'classi/classe_log_eventi.php';
require_once $prefisso.'classi/classe_scene.php';


?>