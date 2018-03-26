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


//per prima cosa vediamo se c'è qualcosa in corso, se non c'è nulla poi passiamo a capire cosa trasmettere.

$sql = "SELECT rel_scene_fascia_oraria.id as id_rel, rel_scene_fascia_oraria.*, scene_live.* FROM fascia_oraria JOIN rel_scene_fascia_oraria on fascia_oraria.id = rel_scene_fascia_oraria.fascia_oraria_id LEFT JOIN scene_live on scene_live.rif_rel_fascia_scene = rel_scene_fascia_oraria.id where palinsesto_id = ? and scene_live.live = 1 and ora_inizio <= ?  and ora_fine >= ?";
$dati_query = array($palinsesto_id, date("H:i:00"), date("H:i:00"));
$arr = $conn->query_risultati($sql,$dati_query);



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
	$arr = $conn->query_risultati($sql);
	

	if (count($arr)>0){
		
		
		$log_evento_rpi = new classe_log_eventi_rpi($arr[0]["id"]);
		
		$razza = $arr[0]["etnia"];
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
				
				$sql = "SELECT scene_live.id as id_scene_live, rel_scene_fascia_oraria.id as id_rel, scene.*, rel_scene_fascia_oraria.*, scene_live.*, scene.id as scena_id FROM fascia_oraria JOIN rel_scene_fascia_oraria on fascia_oraria.id = rel_scene_fascia_oraria.fascia_oraria_id LEFT JOIN scene_live on scene_live.rif_rel_fascia_scene = rel_scene_fascia_oraria.id "
				. " JOIN scene on rel_scene_fascia_oraria.scena_id = scene.id "
				. " where palinsesto_id = ? and ora_inizio <= ? and ora_fine >=? and sesso = ? and eta like ? and etnia like ? and ( scene_live.live = 0 or scene_live.live IS NULL)"
				. " order by scene_live.data_start ASC";
				$dati_query = array($palinsesto_id, date("H:i:00"), date("H:i:00"),$genere, "%".$eta."%", "%".$razza."%");
				
				//echo $conn->debug_query($sql, $dati_query);
				
				$arr = $conn->query_risultati($sql, $dati_query);

				if (count($arr)>0){
					$cambio_scena = false;

					$sql = "UPDATE scene_live set live = 0";
					$conn->esegui_query($sql);
					if($arr[0]["id_scene_live"] !== null){
						$scene_live_obj = new classe_scene_live($arr[0]["id_scene_live"]);
						
					}else{
						$scene_live_obj = new classe_scene_live();
					}
					$scene_live_obj->set_rif_rel_fascia_scene( $arr[0]["id_rel"]);
					$scene_live_obj->set_live(1);
					$scene_live_obj->set_data_start(date("Y-m-d H:i:s"));
					$esito = $scene_live_obj->salva(FALSE);
					if($esito){
						$log_evento_rpi->set_processato(1);

						$log_messe_in_onda = new classe_log_messe_in_onda();
						$log_messe_in_onda->set_evento_id($log_evento_rpi->get_id());
						$log_messe_in_onda->set_scena_id($arr[0]["scena_id"]);
						$log_messe_in_onda->salva(FALSE);
					}
					$fascia_scena_obj = new classe_rel_scene_fascia_oraria($arr[0]["id_rel"]);
					
					

					//var_dump($esito);
					$scena_obj = new classe_scene($arr[0]["scena_id"]);
					$url = $scena_obj->genera_url();
					$code = $scena_obj->get_id();
					$durata = $fascia_scena_obj->get_durata_ms();
				}
			}
			
			
			
			//almeno due
			if($cambio_scena){
				

				
				$sql = "SELECT scene_live.id as id_scene_live,rel_scene_fascia_oraria.id as id_rel, scene.*, rel_scene_fascia_oraria.*, scene_live.*, scene.id as scena_id  FROM fascia_oraria JOIN rel_scene_fascia_oraria on fascia_oraria.id = rel_scene_fascia_oraria.fascia_oraria_id LEFT JOIN scene_live on scene_live.rif_rel_fascia_scene = rel_scene_fascia_oraria.id"
				. " JOIN scene on rel_scene_fascia_oraria.scena_id = scene.id "
				. " where palinsesto_id = ? and ora_inizio <= ? and ora_fine >=? and sesso = ? and eta like ? and ( scene_live.live = 0 or scene_live.live IS NULL)"
				. " order by scene_live.data_start ASC";
				$dati_query = array($palinsesto_id, date("H:i:00"), date("H:i:00"),$genere, "%".$eta."%");
				
				
				$arr = $conn->query_risultati($sql, $dati_query);

				if (count($arr)>0){
					$cambio_scena = false;

					$sql = "UPDATE scene_live set live = 0";
					$conn->esegui_query($sql);
					if($arr[0]["id_scene_live"] !== null){
						$scene_live_obj = new classe_scene_live($arr[0]["id_scene_live"]);
						
					}else{
						$scene_live_obj = new classe_scene_live();
					}
					$scene_live_obj->set_rif_rel_fascia_scene( $arr[0]["id_rel"]);
					$scene_live_obj->set_live(1);
					$scene_live_obj->set_data_start(date("Y-m-d H:i:s"));
					$esito = $scene_live_obj->salva(FALSE);
					if($esito){
						$log_evento_rpi->set_processato(1);

						
						$log_messe_in_onda = new classe_log_messe_in_onda();
						$log_messe_in_onda->set_evento_id($log_evento_rpi->get_id());
						$log_messe_in_onda->set_scena_id($arr[0]["scena_id"]);
						$log_messe_in_onda->salva(FALSE);
					}

					$fascia_scena_obj = new classe_rel_scene_fascia_oraria($arr[0]["id_rel"]);
		
					

					//var_dump($esito);
					$scena_obj = new classe_scene($arr[0]["scena_id"]);
					$url = $scena_obj->genera_url();
					$code = $scena_obj->get_id();
					$durata = $fascia_scena_obj->get_durata_ms();
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
			
			
			
			
			if($cambio_scena){
				//vediamo se almeno ce ne è uno
			
				$sql = "SELECT scene_live.id as id_scene_live, rel_scene_fascia_oraria.id as id_rel, scene.*, rel_scene_fascia_oraria.*, scene_live.*, scene.id as scena_id  FROM fascia_oraria JOIN rel_scene_fascia_oraria on fascia_oraria.id = rel_scene_fascia_oraria.fascia_oraria_id LEFT JOIN scene_live on scene_live.rif_rel_fascia_scene = rel_scene_fascia_oraria.id"
				. " JOIN scene on rel_scene_fascia_oraria.scena_id = scene.id "
				. " where palinsesto_id = ? and ora_inizio <= ? and ora_fine >=? and (sesso = ? or eta like ? or etnia like ?) and ( scene_live.live = 0 or scene_live.live IS NULL)"
				. " order by scene_live.data_start ASC";
				$dati_query = array($palinsesto_id, date("H:i:00"), date("H:i:00"),$genere, "%".$eta."%", "%".$razza."%");
				
//				echo $conn->debug_query($sql, $dati_query);
//				die();
				$arr = $conn->query_risultati($sql, $dati_query);
			
				
				if (count($arr)>0){
					$cambio_scena = false;

					$sql = "UPDATE scene_live set live = 0";
					$conn->esegui_query($sql);
					if($arr[0]["id_scene_live"] !== null){
						$scene_live_obj = new classe_scene_live($arr[0]["id_scene_live"]);
						
					}else{
						$scene_live_obj = new classe_scene_live();
					}
					$scene_live_obj->set_rif_rel_fascia_scene( $arr[0]["id_rel"]);
					$scene_live_obj->set_live(1);
					$scene_live_obj->set_data_start(date("Y-m-d H:i:s"));
					$esito = $scene_live_obj->salva(FALSE);
					if($esito){
						$log_evento_rpi->set_processato(1);

						
						$log_messe_in_onda = new classe_log_messe_in_onda();
						$log_messe_in_onda->set_evento_id($log_evento_rpi->get_id());
						$log_messe_in_onda->set_scena_id($arr[0]["scena_id"]);
						$log_messe_in_onda->salva(FALSE);
					}
					$fascia_scena_obj = new classe_rel_scene_fascia_oraria($arr[0]["id_rel"]);
		
					

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
		$sql = "SELECT scene_live.id as id_scene_live, rel_scene_fascia_oraria.id as id_rel, scene.*, rel_scene_fascia_oraria.*, scene_live.*, scene.id as scena_id  FROM fascia_oraria JOIN rel_scene_fascia_oraria on fascia_oraria.id = rel_scene_fascia_oraria.fascia_oraria_id LEFT JOIN scene_live on scene_live.rif_rel_fascia_scene = rel_scene_fascia_oraria.id"
				. " JOIN scene on rel_scene_fascia_oraria.scena_id = scene.id "
				. " where palinsesto_id = ? and ora_inizio <= ? and ora_fine >=? and sesso is null and etnia = '[]' and eta = '[]'"
				. "order by scene_live.data_start ASC";
		$dati_query = array($palinsesto_id, date("H:i:00"), date("H:i:00"));
		
		//echo $conn->debug_query($sql, $dati_query);
		
		$arr = $conn->query_risultati($sql, $dati_query);
		
		if (count($arr)>0){
			
			$sql = "UPDATE scene_live set live = 0";
			$conn->esegui_query($sql);
			
			if($arr[0]["id_scene_live"] !== null){
				$scene_live_obj = new classe_scene_live($arr[0]["id_scene_live"]);
				
			}else{
				$scene_live_obj = new classe_scene_live();
			}
			$scene_live_obj->set_rif_rel_fascia_scene( $arr[0]["id_rel"]);
			$scene_live_obj->set_live(1);
			$scene_live_obj->set_data_start(date("Y-m-d H:i:s"));
			$esito = $scene_live_obj->salva(FALSE);
			$fascia_scena_obj = new classe_rel_scene_fascia_oraria($arr[0]["id_rel"]);
			
			$log_messe_in_onda = new classe_log_messe_in_onda();
			$log_messe_in_onda->set_evento_id(Null);
			$log_messe_in_onda->set_scena_id($arr[0]["scena_id"]);
			$log_messe_in_onda->set_created_at(date("Y-m-d H:i:s"));
			$log_messe_in_onda->set_updated_at(date("Y-m-d H:i:s"));
			$log_messe_in_onda->salva(FALSE);
			
			//var_dump($esito);
			$scena_obj = new classe_scene($arr[0]["scena_id"]);
			$url = $scena_obj->genera_url();
			$code = $scena_obj->get_id();
			$durata =  $fascia_scena_obj->get_durata_ms();
			
		}
	}
	
}

$arr_return = array("url"=>$url,"durata"=>$durata, "code"=>$code, "status"=>"OK");

echo json_encode($arr_return);


$conn->Close();
?>