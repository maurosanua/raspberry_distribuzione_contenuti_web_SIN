<?
date_default_timezone_set('Europe/Rome');
require_once('../classi/master_class.php');
init_sessione();

$conn = new DB_PDO();

$url = "https://app.innovafarmacia.it//sites/mastersumo/monitor/chicco/trailer";
$code = 3;
$durata = 5;

$cambio_scena = true;

//per prima cosa vediamo se c'è qualcosa in corso, se non c'è nulla poi passiamo a capire cosa trasmettere.

$sql = "SELECT * FROM scene where live = 1";
$arr = $conn->query_risultati($sql);

if (count($arr)>0){
	//vediamo da quanto è in corso
	$start_time = strtotime($arr[0]["data_start"]);
	$adesso = strtotime(date("Y-m-d H:i:s"));
	
	
	$diff = $adesso-$start_time;
	
	if($diff<$arr[0]["durata"]){
		//va bene così
		
		$url = $arr[0]["url"];
		$code = $arr[0]["id"];
		$durata = $arr[0]["durata"];
		
		$cambio_scena = false;
	}else{
		$cambio_scena = true;
	}
	
	//echo $diff;
}


if($cambio_scena){
	
	//dobbiamo prima valutare se è subentrato un evento.
	//in assenza di eventi mettiamo la scena di default.
	
	$sql = "SELECT * FROM log_eventi where processato = 0";
	$arr = $conn->query_risultati($sql);

	if (count($arr)>0){
		
	}
	
	if (count($arr)==0 || $cambio_scena){
		//cerco una scena di default
		$sql = "SELECT * FROM scene where genere is null and eta is null and razza is null order by data_start ASC";
		$arr = $conn->query_risultati($sql);

		if (count($arr)>0){
			
			$sql = "UPDATE scene set live = 0";
			$conn->esegui_query($sql);
			
			$scena_obj = new classe_scene($arr[0]["id"]);
			$scena_obj->set_live(1);
			$scena_obj->set_data_start(date("Y-m-d H:i:s"));
			$esito = $scena_obj->salva(FALSE);
			
			//var_dump($esito);
			
			$url = $scena_obj->get_url();
			$code = $scena_obj->get_id();
			$durata = $scena_obj->get_durata();
		}
	}
	
}

$arr_return = array("url"=>$url,"durata"=>$durata, "code"=>$code, "status"=>"OK");

echo json_encode($arr_return);

?>