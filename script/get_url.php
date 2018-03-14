<?
date_default_timezone_set('Europe/Rome');
require_once('../classi/master_class.php');
init_sessione();

$conn = new DB_PDO();


$sql = "SELECT * FROM dispositivi where numero_serie = ?";
$dati_query = array(SERIALE);
$arr = $conn->query_risultati($sql,$dati_query);

if (count($arr)==0){
	//mando alla registrazione
	
	$arr_return = array("url"=>URL_RASPBERRY."/registrazione.php","durata"=>"10", "code"=>"-1", "status"=>"OK");

	echo json_encode($arr_return);

	die();
}


$cambio_scena = true;



$palinsesto_id = $arr[0]["palinsesto_id"]; 


//per prima cosa vediamo se c'è qualcosa in corso, se non c'è nulla poi passiamo a capire cosa trasmettere.

$sql = "SELECT rel_scene_fascia_oraria.id as id_rel, rel_scene_fascia_oraria.* FROM fascia_oraria JOIN rel_scene_fascia_oraria on fascia_oraria.id = rel_scene_fascia_oraria.fascia_oraria_id where palinsesto_id = ? and live = 1 and ora_inizio <= ?  and ora_fine >= ?";
$dati_query = array($palinsesto_id, date("H:i:00"), date("H:i:00"));
$arr = $conn->query_risultati($sql,$dati_query);


if (count($arr)>0){
	//vediamo da quanto è in corso
	$start_time = strtotime($arr[0]["data_start"]);
	$adesso = strtotime(date("Y-m-d H:i:s"));
	
	
	$diff = $adesso-$start_time;
	
	if($diff< ($arr[0]["durata_ms"]/1000)){
		//va bene così
		
		$fascia_scena_obj = new classe_rel_scene_fascia_oraria($arr[0]["id_rel"]);
		
		
		$scena_obj = new classe_scene($arr[0]["scena_id"]);
		$url = $scena_obj->genera_url();
		$code = $scena_obj->get_id();
		$durata = $fascia_scena_obj->get_durata_ms();
		

		
		$cambio_scena = false;
	}else{
		$cambio_scena = true;
	}
	
	//echo $diff;
}


if($cambio_scena){
	
	//dobbiamo prima valutare se è subentrato un evento.
	//in assenza di eventi mettiamo la scena di default.
	
	$sql = "SELECT * FROM log_eventi_rpi where processato = 0 order by data_evento DESC";
	$arr = $conn->query_risultati($sql);
	

	if (count($arr)>0){
		//echo "qui";
		
		
		
		$razza = $arr[0]["razza"];
		$eta = $arr[0]["eta"];
		$genere = $arr[0]["genere"];
		
		if($genere == ""){
			$genere = null;
		}
		
		if($eta == ""){
			$eta = null;
		}
		
		if($razza == ""){
			$razza = null;
		}
		
		if(strlen($razza)>0 || strlen($eta) >0 || strlen($genere) >0){
			//c'è almeno una persona
			
			//vediamo se li matcha tutti
			if($cambio_scena){
				
				$sql = "SELECT * FROM scene where "
						. "(genere = ?) and"
						. "(eta = ?) and"
						. "(razza = ?) and"
						. " live = 0"
						. " order by data_start ASC";
				$dati_query = array($genere, $eta, $razza);
				$arr = $conn->query_risultati($sql, $dati_query);

				if (count($arr)>0){
					$cambio_scena = false;

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
			
			
			
			//almeno due
			if($cambio_scena){
				
				$sql = "SELECT * FROM scene where (genere is not null or eta is not null or razza is not null) and "
						. "(genere = ?) and"
						. "(eta = ?) and"
						. " live = 0"
						. " order by data_start ASC";
				$dati_query = array($genere, $eta);
				$arr = $conn->query_risultati($sql, $dati_query);

				if (count($arr)>0){
					$cambio_scena = false;

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
			
			
			/*
			if($cambio_scena){
				
				$sql = "SELECT * FROM scene where (genere is not null or eta is not null or razza is not null) and "
						. "(genere = ?) and"
						. "(razza = ?) and"
						. " live = 0"
						. " order by data_start ASC";
				$dati_query = array($genere,  $razza);
				$arr = $conn->query_risultati($sql, $dati_query);

				if (count($arr)>0){
					$cambio_scena = false;

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
			*/
			
			
			
			/*
			if($cambio_scena){
				
				$sql = "SELECT * FROM scene where (genere is not null or eta is not null or razza is not null) and "
						. "(eta = ?) and"
						. "(razza = ?) and"
						. " live = 0"
						. " order by data_start ASC";
				$dati_query = array( $eta, $razza);
				$arr = $conn->query_risultati($sql, $dati_query);

				
				
				if (count($arr)>0){
					$cambio_scena = false;

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
			*/
			
			
			
			/*
			if($cambio_scena){
				//vediamo se almeno ce ne è uno
				$sql = "SELECT * FROM scene where (genere is not null or eta is not null or razza is not null) and "
						. "(genere = ?  or eta = ? or razza = ?) and"
						. " live = 0"
						. " order by data_start ASC";
				$dati_query = array($genere, $eta, $razza);
				$arr = $conn->query_risultati($sql, $dati_query);
			
				
				if (count($arr)>0){
					$cambio_scena = false;

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
			*/

			//dovremmo gestire se sono più di uno, perché potrebbe voler dire che sono entrate tante persone insieme
		}
		
		

	}
	
	if (count($arr)==0 || $cambio_scena){
		//cerco una scena di default
		$sql = "SELECT rel_scene_fascia_oraria.id as id_rel, scene.*, rel_scene_fascia_oraria.* FROM fascia_oraria JOIN rel_scene_fascia_oraria on fascia_oraria.id = rel_scene_fascia_oraria.fascia_oraria_id"
				. " JOIN scene on rel_scene_fascia_oraria.scena_id = scene.id "
				. " where palinsesto_id = ? and ora_inizio <= ? and ora_fine >=? order by data_start ASC";
		$dati_query = array($palinsesto_id, date("H:i:00"), date("H:i:00"));
		
		//echo $conn->debug_query($sql, $dati_query);
		
		$arr = $conn->query_risultati($sql, $dati_query);

		if (count($arr)>0){
			
			$sql = "UPDATE rel_scene_fascia_oraria set live = 0";
			$conn->esegui_query($sql);
			
			$fascia_scena_obj = new classe_rel_scene_fascia_oraria($arr[0]["id_rel"]);
			$fascia_scena_obj->set_live(1);
			$fascia_scena_obj->set_data_start(date("Y-m-d H:i:s"));
			$esito = $fascia_scena_obj->salva(FALSE);
			
			//var_dump($esito);
			$scena_obj = new classe_scene($arr[0]["scena_id"]);
			$url = $scena_obj->genera_url();
			$code = $scena_obj->get_id();
			$durata = $fascia_scena_obj->get_durata_ms();
		}
	}
	
}

$arr_return = array("url"=>$url,"durata"=>$durata, "code"=>$code, "status"=>"OK");

echo json_encode($arr_return);


$conn->Close();
?>