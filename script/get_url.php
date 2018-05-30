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
$scena_base = false;
$url = "";
$durata = "";
$code = 0;

$palinsesto_id = $arr[0]["palinsesto_id"]; 

$scena_live = null;
$rel_fascia_scene = null;
//recupero la scena corrente

$arr_scena_live = $conn->query_risultati(
		"SELECT id from scene_live where live = 1"
	);
try{
	$scena_live = new classe_scene_live($arr_scena_live[0]["id"]);
}catch(Throwable $e){
	
	//capire come gestirla
}

if(isset($scena_live)){
	$arr_scena_fascia_oraria = $conn->query_risultati(
		"SELECT id from rel_scene_fascia_oraria where id = :id_rel_scena",
		array("id_rel_scena"=>$scena_live->get_rif_rel_fascia_scene())
	);

	try{
	$rel_fascia_scene = new classe_rel_scene_fascia_oraria($arr_scena_fascia_oraria[0]["id"]);
	}catch(Throwable $e){
	
	//capire come gestirla
	}
}

//controlliamo se la scena in corso è forzata
if(isset($rel_fascia_scene)){
	if($rel_fascia_scene->is_forzata()){
		//controlliamo che non sia finito il suo tempo di esecuzione
		$inizio_scena = new DateTime($scena_live->get_data_start(0));
		$adesso = new DateTime();
		$diff = $adesso->diff($inizio_scena);
		if($diff->s<$rel_fascia_scene->get_durata_ms(0)/1000){
			echo $rel_fascia_scene->genera_output_geturl();
			$conn->Close();
			die();
		}
	}
}

//controlliamo se ci sono persone davanti allo schermo




//per prima cosa vediamo se c'è qualcosa in corso, se non c'è nulla poi passiamo a capire cosa trasmettere.

$sql = "SELECT scene_live.data_start, rel_scene_fascia_oraria.id as id_rel, rel_scene_fascia_oraria.*, scene_live.* 
		FROM fascia_oraria JOIN rel_scene_fascia_oraria on fascia_oraria.id = rel_scene_fascia_oraria.fascia_oraria_id 
		LEFT JOIN scene_live on scene_live.rif_rel_fascia_scene = rel_scene_fascia_oraria.id 
		where palinsesto_id = ? and scene_live.live = 1 and ora_inizio <= ?  and (ora_fine >= ? or ora_fine = '00:00:00')";

$dati_query = array($palinsesto_id, date("H:i:00"), date("H:i:00"));
$arr = $conn->query_risultati($sql,$dati_query);

//var_dump($arr);


if (count($arr)>0){
	//vediamo da quanto è in corso
	$start_time = strtotime($arr[0]["data_start"]);
	$adesso = strtotime(date("Y-m-d H:i:s"));
	
	
	$diff = $adesso-$start_time;
	
	if($diff< ($arr[0]["durata_ms"]/1000)){
		//echo($arr[0]["data_start"]);
		//va bene così
		
		$fascia_scena_obj = new classe_rel_scene_fascia_oraria($arr[0]["id_rel"]);
		
		
		$scena_obj = new classe_scene($arr[0]["scena_id"]);
		$url = $scena_obj->genera_url();
		$code = $scena_obj->get_id();
		$durata = $fascia_scena_obj->get_durata_ms();
		
		
		$eta = json_decode($fascia_scena_obj->get_eta(0), true);
		$etnia = json_decode($fascia_scena_obj->get_etnia(0), true);
		
		if (strlen($fascia_scena_obj->get_sesso())==0 && count($etnia)==0 && count($eta)==0){
			//sto eseguento una scena generica
			$cambio_scena = true;
			$scena_base = true;
			
		}else{
			$cambio_scena = false;
		}
		
		
	}else{
		$cambio_scena = true;
	}
	
	//echo $diff;
}

if($cambio_scena){
	
	//dobbiamo prima valutare se è subentrato un evento.
	//in assenza di eventi mettiamo la scena di default.
	
	$sql = "SELECT * FROM log_eventi_rpi where processato = 0 order by data_evento DESC";
	$arr_eventi = $conn->query_risultati($sql);
	

	if (count($arr_eventi)>0){
		
		
		$log_evento_rpi = new classe_log_eventi_rpi($arr_eventi[0]["id"]);
		
		$razza = $arr_eventi[0]["etnia"];
		$eta = $arr_eventi[0]["eta"];
		$genere = $arr_eventi[0]["genere"];
		
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
				
				$sql = "SELECT scene_live.id as id_scene_live, rel_scene_fascia_oraria.id as id_rel, scene.*, rel_scene_fascia_oraria.*, scene_live.* FROM fascia_oraria JOIN rel_scene_fascia_oraria on fascia_oraria.id = rel_scene_fascia_oraria.fascia_oraria_id LEFT JOIN scene_live on scene_live.rif_rel_fascia_scene = rel_scene_fascia_oraria.id "
				. " JOIN scene on rel_scene_fascia_oraria.scena_id = scene.id "
				. " where palinsesto_id = ? and ora_inizio <= ? and (ora_fine >= ? or ora_fine = '00:00:00') and sesso = ? and eta like ? and etnia like ? and ( scene_live.live = 0 or scene_live.live IS NULL)"
				. " order by scene_live.data_start ASC";
				$dati_query = array($palinsesto_id, date("H:i:00"), date("H:i:00"),$genere, "%".$eta."%", "%".$razza."%");
				
				//echo $conn->debug_query($sql, $dati_query);
				
				$arr = $conn->query_risultati($sql, $dati_query);

				if (count($arr)>0){
					$cambio_scena = false;
					$fascia_scena_obj = new classe_rel_scene_fascia_oraria($arr[0]["id_rel"]);
					
					$esito = $fascia_scena_obj->aggiorna_scene_live($arr[0]['id_scene_live']);
					
					if($esito){
						$log_evento_rpi->set_processato(1);
						$log_evento_rpi->salva(FALSE);						
					}

					//var_dump($esito);
					$scena_obj = new classe_scene($arr[0]["scena_id"]);
					$url = $scena_obj->genera_url();
					$code = $scena_obj->get_id();
					$durata = $fascia_scena_obj->get_durata_ms();
				}
			}
			
			
			
			//almeno due
			if($cambio_scena){
				

				
				$sql = "SELECT scene_live.id as id_scene_live,rel_scene_fascia_oraria.id as id_rel, scene.*, rel_scene_fascia_oraria.*, scene_live.* FROM fascia_oraria JOIN rel_scene_fascia_oraria on fascia_oraria.id = rel_scene_fascia_oraria.fascia_oraria_id LEFT JOIN scene_live on scene_live.rif_rel_fascia_scene = rel_scene_fascia_oraria.id"
				. " JOIN scene on rel_scene_fascia_oraria.scena_id = scene.id "
				. " where palinsesto_id = ? and ora_inizio <= ? and (ora_fine >= ? or ora_fine = '00:00:00') and sesso = ? and eta like ? and ( scene_live.live = 0 or scene_live.live IS NULL)"
				. " order by scene_live.data_start ASC";
				$dati_query = array($palinsesto_id, date("H:i:00"), date("H:i:00"),$genere, "%".$eta."%");
				
				
				$arr = $conn->query_risultati($sql, $dati_query);

				if (count($arr)>0){
					$cambio_scena = false;

					$fascia_scena_obj = new classe_rel_scene_fascia_oraria($arr[0]["id_rel"]);
					
					$esito = $fascia_scena_obj->aggiorna_scene_live($arr[0]['id_scene_live']);
					
					if($esito){
						$log_evento_rpi->set_processato(1);
						$log_evento_rpi->salva(FALSE);
						
					}
					

					//var_dump($esito);
					$scena_obj = new classe_scene($arr[0]["scena_id"]);
					$url = $scena_obj->genera_url();
					$code = $scena_obj->get_id();
					$durata = $fascia_scena_obj->get_durata_ms();
				}
			}
			
			
			
			
			
			if($cambio_scena){
				//vediamo se almeno ce ne è uno
			
				$sql = "SELECT scene_live.id as id_scene_live, rel_scene_fascia_oraria.id as id_rel, scene.*, rel_scene_fascia_oraria.*, scene_live.* FROM fascia_oraria JOIN rel_scene_fascia_oraria on fascia_oraria.id = rel_scene_fascia_oraria.fascia_oraria_id LEFT JOIN scene_live on scene_live.rif_rel_fascia_scene = rel_scene_fascia_oraria.id"
				. " JOIN scene on rel_scene_fascia_oraria.scena_id = scene.id "
				. " where palinsesto_id = ? and ora_inizio <= ? and (ora_fine >= ? or ora_fine = '00:00:00') and (sesso = ? or eta like ? or etnia like ?) and ( scene_live.live = 0 or scene_live.live IS NULL)"
				. " order by scene_live.data_start ASC";
				$dati_query = array($palinsesto_id, date("H:i:00"), date("H:i:00"),$genere, "%".$eta."%", "%".$razza."%");
				
//				echo $conn->debug_query($sql, $dati_query);
//				die();
				$arr = $conn->query_risultati($sql, $dati_query);
			
				
				if (count($arr)>0){
					$cambio_scena = false;

					$fascia_scena_obj = new classe_rel_scene_fascia_oraria($arr[0]["id_rel"]);
					
					$esito = $fascia_scena_obj->aggiorna_scene_live($arr[0]['id_scene_live']);
					
					if($esito){
						$log_evento_rpi->set_processato(1);
						$log_evento_rpi->salva(FALSE);
						
					}
					
					

					//var_dump($esito);
					$scena_obj = new classe_scene($arr[0]["scena_id"]);
					$url = $scena_obj->genera_url();
					$code = $scena_obj->get_id();
					$durata = $fascia_scena_obj->get_durata_ms();
			

				}
			}
			

			//dovremmo gestire se sono più di uno, perché potrebbe voler dire che sono entrate tante persone insieme
		}
		
		

	}
	
	if (count($arr)==0 || ($cambio_scena && !$scena_base)){
		//echo "quiasda";
		//cerco una scena di default
		$sql = "SELECT scene_live.id as id_scene_live, rel_scene_fascia_oraria.id as id_rel, scene.*, rel_scene_fascia_oraria.*, scene_live.* FROM fascia_oraria JOIN rel_scene_fascia_oraria on fascia_oraria.id = rel_scene_fascia_oraria.fascia_oraria_id LEFT JOIN scene_live on scene_live.rif_rel_fascia_scene = rel_scene_fascia_oraria.id"
				. " JOIN scene on rel_scene_fascia_oraria.scena_id = scene.id "
				. " where palinsesto_id = ? and ora_inizio <= ? and (ora_fine >= ? or ora_fine = '00:00:00') and sesso is null and etnia = '[]' and eta = '[]'"
				. "order by scene_live.data_start ASC";
		$dati_query = array($palinsesto_id, date("H:i:00"), date("H:i:00"));
		
		//echo $conn->debug_query($sql, $dati_query);
		
		$arr = $conn->query_risultati($sql, $dati_query);
		
		if (count($arr)>0){
			
			$fascia_scena_obj = new classe_rel_scene_fascia_oraria($arr[0]["id_rel"]);
					
			$esito = $fascia_scena_obj->aggiorna_scene_live($arr[0]['id_scene_live']);
					
					

			//var_dump($esito);
			$scena_obj = new classe_scene($arr[0]["scena_id"]);
			$url = $scena_obj->genera_url();
			$code = $scena_obj->get_id();
			$durata =  $fascia_scena_obj->get_durata_ms();
			
		}
	}
	
}



echo $fascia_scena_obj->genera_output_geturl();


$conn->Close();
?>