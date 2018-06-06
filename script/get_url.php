<?
date_default_timezone_set('Europe/Rome');
require_once('../classi/master_class.php');
init_sessione();

$debug = 0;

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

$dispositivo_id = $arr[0]["id"];
$palinsesto_id = $arr[0]["palinsesto_id"]; 

$scena_live = null;
$rel_fascia_scene = null;

//recupero la fascia oraria corrente
$fascia_oraria = $conn->query_risultati(
	"SELECT * FROM fascia_oraria WHERE palinsesto_id = ? AND ora_inizio <= ? AND (ora_fine >= ? OR ora_fine = '00:00:00')",
	array($palinsesto_id, date("H:i:00"), date("H:i:00"))
);

//recupero tutte le relazioni scena-fascia_oraria
if(count($fascia_oraria) == 0){
	// da capire cosa fare
}

$arr_scena_fascia_oraria = $conn->query_risultati(
	"SELECT rel_scene_fascia_oraria.*, scene_live.data_start, scene_live.live, scene_live.id AS id_scene_live
 	 FROM rel_scene_fascia_oraria LEFT JOIN scene_live ON scene_live.rif_rel_fascia_scene = rel_scene_fascia_oraria.id
	 WHERE fascia_oraria_id = ? AND (scene_live.live IS NULL OR scene_live.live = 0)",
	array($fascia_oraria[0]["id"])
);

//recupero la scena corrente
$arr_scena_live = $conn->query_risultati(
		"SELECT id from scene_live where live = 1"
	);

if(count($arr_scena_live)>0){
	try{
		$scena_live = new classe_scene_live($arr_scena_live[0]["id"]);
	} catch(Throwable $e){
		
		//capire come gestirla
	}
}

if(isset($scena_live)){
	$rel_fascia_scene = new classe_rel_scene_fascia_oraria($scena_live->get_rif_rel_fascia_scene(0));
}

//recupero tutte le persone davanti allo schermo
$arr_eventi = $conn->query_risultati(
	"SELECT * FROM log_eventi_rpi WHERE disappearance_datetime IS NULL"
);

$adesso = new DateTime();
//controlliamo se la scena in corso è forzata
if(isset($rel_fascia_scene)){
	if($rel_fascia_scene->is_forzata() || count($arr_eventi)==0){
		//controlliamo che non sia finito il suo tempo di esecuzione
		$inizio_scena = new DateTime($scena_live->get_data_start(0));
		$diff = $adesso->diff($inizio_scena);
		if($diff->s<$rel_fascia_scene->get_durata_ms(0)/1000){
			echo $rel_fascia_scene->genera_output_geturl();
			$conn->Close();
			die();
		}
	}
}

//per ogni scena-fascia_oraria assegno un punteggio
$array_punteggi = array();
foreach($arr_scena_fascia_oraria as $scena_fascia_oraria){
	$rel_obj = new classe_rel_scene_fascia_oraria($scena_fascia_oraria["id"]);
	$rel_obj->data_start = $scena_fascia_oraria["data_start"];
	
	$array_punteggi[$scena_fascia_oraria["id"]] = $rel_obj->calcola_punteggio_di_matching($arr_eventi);
}

if($debug){
	//log array dei punteggi e delle persone presenti davanti alla telecamera
	file_put_contents('../../request_data/punteggi.txt', "\r\n-----------------------------------------------", FILE_APPEND);
	file_put_contents('../../request_data/punteggi.txt', "\r\nPUNTEGGI:\r\n", FILE_APPEND);
	file_put_contents('../../request_data/punteggi.txt', json_encode($array_punteggi, JSON_PRETTY_PRINT), FILE_APPEND);
	file_put_contents('../../request_data/punteggi.txt', "\r\PERSONE:\r\n", FILE_APPEND);
	file_put_contents('../../request_data/punteggi.txt', json_encode($arr_eventi, JSON_PRETTY_PRINT), FILE_APPEND);
}

/*echo json_encode($array_punteggi,JSON_PRETTY_PRINT);
die();*/

//recuperalo la scena col punteggio maggiore e la faccio vedere
$id_scena_da_vedere = array_search(max($array_punteggi), $array_punteggi);
$scena_da_vedere = new classe_rel_scene_fascia_oraria($id_scena_da_vedere);
$scena_da_vedere->aggiorna_scene_live();


//aggiungo alla tabella log_scene la nuova scena in visione
$arr_log_scena = $conn->query_risultati(
	"SELECT id FROM log_scene WHERE data_end IS NULL"
);

if(count($arr_log_scena) > 0){
	$log_scena_obj = new classe_log_scene($arr_log_scena[0]["id"]);
	$log_scena_obj->set_data_end($adesso);
	$log_scena_obj->salva(false);
}

$log_scena_obj = new classe_log_scene();
$log_scena_obj->set_rel_fascia_scene_id($scena_da_vedere->get_id());
$log_scena_obj->set_scena_id($scena_da_vedere->get_scena_id());
$log_scena_obj->set_dispositivo_id($dispositivo_id);
$log_scena_obj->set_data_start($adesso);
$log_scena_obj->set_processato(0);
$log_scena_obj->salva(false);

echo $scena_da_vedere->genera_output_geturl();

$conn->Close();
?>