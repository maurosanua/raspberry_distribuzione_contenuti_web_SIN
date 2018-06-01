<?php
date_default_timezone_set('Europe/Rome');
require_once('classi/master_class.php');

$conn = new DB_PDO();


$scena_id = isset($_GET["scena_id"])?$_GET["scena_id"]:0;

$scena_obj = new classe_scene($scena_id);

$contenuto = $scena_obj->get_link(0);


$sql = "SELECT * FROM dispositivi where numero_serie = ?";
$dati_query = array(SERIALE);
$arr = $conn->query_risultati($sql,$dati_query);


$larghezza = 1366;
$altezza = 768;

if(count($arr)>0){
	
	if(strlen($arr[0]["iframe_w"])>0){
		$larghezza = $arr[0]["iframe_w"];
	}
	
	if(strlen($arr[0]["iframe_h"])>0){
		$altezza = $arr[0]["iframe_h"];
	}
	
}

$conn->Close();
header("location: http://www.youtube.com/embed/".$contenuto."?modestbranding=1&autoplay=1&controls=0&fs=0&loop=1&rel=0&showinfo=0&disablekb=1&playlist=IsBInsOj8TY");
?>
