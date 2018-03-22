<?php
date_default_timezone_set('Europe/Rome');
require_once('../classi/master_class.php');
init_sessione();

$conn = new DB_PDO();

$tipo = isset($_GET["tipo"])?$_GET["tipo"]:"";

echo $tipo;

$log_obj = new classe_log_eventi_rpi();
$log_obj->set_data_evento(date("Y-m-d H:i:s"));
$log_obj->set_processato(0);


if(strlen($tipo)==0 || $tipo == "empty"){
	$sql = "UPDATE log_eventi_rpi set processato = 1";
	$conn->esegui_query($sql);
	$log_obj->set_processato(1);
	
	
}else{
	//echo "qui";
	$log_obj->set_genere($tipo);
	
}


$log_obj->salva(false);
	
	
$conn->Close();
?>