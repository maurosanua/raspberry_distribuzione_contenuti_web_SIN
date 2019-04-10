<?php
date_default_timezone_set('Europe/Rome');
require_once('../classi/master_class.php');
init_sessione();

$conn = new DB_PDO();

$tipo = isset($_GET["tipo"])?$_GET["tipo"]:"";

echo $tipo;
$arr_dispositivo = $conn->query_risultati(
    "SELECT * FROM dispositivi"
    );

$id_dispositivo = null;
if(count($arr_dispositivo)>0){
    $id_dispositivo = $arr_dispositivo[0]["id"];
}

if(strlen($tipo)==0 || $tipo == "empty"){
	$sql = "UPDATE log_eventi_rpi set processato = :proc, disappearance_datetime =:disapp where disappearance_datetime is null";
	$conn->esegui_query($sql,["proc"=>1,"disapp"=>date("Y-m-d H:i:s")]);
	echo "reset";
	
}else{
	
	$log_obj = new classe_log_eventi_rpi();
	$log_obj->set_camera_id(null);
	$log_obj->set_data_evento(date("Y-m-d H:i:s"));
	$log_obj->set_appearance_datetime(date("Y-m-d H:i:s"));
	$log_obj->set_processato(0);
	$log_obj->set_dispositivo_id($id_dispositivo);
	$log_obj->set_disappearance_datetime(null);
	$log_obj->set_genere($tipo);
	$log_obj->salva(false);	
}


            

            
            
            
            
            
            
            



	
	
$conn->Close();
?>