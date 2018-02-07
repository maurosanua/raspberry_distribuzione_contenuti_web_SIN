<?
date_default_timezone_set('Europe/Rome');
require_once('../classi/master_class.php');
init_sessione();

$conn = new DB_PDO();



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
	
	$sql = "SELECT * FROM log_eventi where processato = 0 order by data_evento DESC";
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


$conn->Close();
?>