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
//########################### NUOVA VERSIONE #########################################################
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
	 WHERE fascia_oraria_id = ? and scene_live.live = 0",
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

$arr_eventi = $conn->query_risultati(
	"SELECT * FROM log_eventi_rpi WHERE disappearance_datetime IS NULL"
);


//controlliamo se la scena in corso è forzata
if(isset($rel_fascia_scene)){
	if($rel_fascia_scene->is_forzata()||count($arr_eventi)==0){
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

//recupero tutte le persone davanti allo schermo
$array_punteggi = array();
foreach($arr_scena_fascia_oraria as $scena_fascia_oraria){
	$rel_obj = new classe_rel_scene_fascia_oraria($scena_fascia_oraria["id"]);
	$rel_obj->data_start = $scena_fascia_oraria["data_start"];
	
	$array_punteggi[$scena_fascia_oraria["id"]] = $rel_obj->calcola_punteggio_di_matching($arr_eventi);
}

/*echo json_encode($array_punteggi,JSON_PRETTY_PRINT);
die();*/

$id_scena_da_vedere = array_search(max($array_punteggi), $array_punteggi);
$scena_da_vedere = new classe_rel_scene_fascia_oraria($id_scena_da_vedere);

$scena_da_vedere->aggiorna_scene_live();



echo $scena_da_vedere->genera_output_geturl();

$conn->Close();
?>