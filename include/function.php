<?php

/*
 * Data: 05/06/2015
 * Versione 2.5
 */

function versione_function(){
   return "2.5";
}
 

/**
 * recupera il numero di serie della cpu della raspberry
 * 
 * @return string cpu serial number
 */ 
function rpi_serial_number(){
	
	$comando = "cat /proc/cpuinfo |grep Serial| awk '{print $3}'";
	// $comando = "cat /proc/cpuinfo |grep Serial|cut -d' ' -f2";

	$out = trim(shell_exec($comando));

	return $out;
}

function win_serial_number(){
	
    
	$comando = "wmic DISKDRIVE where 'Index=0' get model,serialnumber";
	// $comando = "cat /proc/cpuinfo |grep Serial|cut -d' ' -f2";

	$out = trim(shell_exec($comando));
	$out = explode("\r\n",$out)[1];
	$out=trim($out);
	return sha1($out);
}

//definiamo qui la costante SERIALE in modo che venga popolata usando la funzione appena definita
if (SISTEMA == "linux") {
	define ("SERIALE", rpi_serial_number());
} else if (SISTEMA == "windows") {
	define ("SERIALE", win_serial_number());
}



/**
 * restituisce il nome del file opportunamente modificato per escludere eventuali caratteri "pericolosi" (spazi compresi)
 * volendo e' possibile specificare un nuovo pattern con cui effettuare la sostituzione
 * 
 * @param string $nome_file nome da formattare
 * @param string $pattern [opt] pattern dei caratteri da rimuovere
 * @return string nome file formattato
 */
function format_filename($nome_file, $pattern = null){

	$default_pattern = "/[^a-zA-Z0-9_\.]/s";

	if($pattern === null){
		$pattern = $default_pattern;
	}

	$nome_file = str_replace(" ", "_", $nome_file);
	$nome_file = preg_replace($pattern, "", $nome_file);


	return $nome_file;
}
 

/**
 * Verifica se l'accesso viene eseguito in locale oppure dall'esterno
 * 
 * @return boolean
 */
function verify_local_access(){
	
	$local_addr = (isset($_SERVER['LOCAL_ADDR']) && is_string($_SERVER['LOCAL_ADDR']) ? (string)$_SERVER['LOCAL_ADDR'] : "");
	$remote_addr = (isset($_SERVER['REMOTE_ADDR']) && is_string($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : "");
	
	$server_domain = explode(".", $local_addr);
	$user_domain = explode(".", $remote_addr);

	
	array_pop($server_domain);
	array_pop($user_domain);

	$server_domain = implode($server_domain);
	$user_domain = implode($user_domain);
	//echo $_SERVER['SERVER_ADDR']."-".$user_domain;
	
	if ($server_domain === $user_domain){ return true;}
	
	return false;
}



/**
 * recupera le informazioni sulla lingua imposata nel browser e le inserisce in sessione, in modo che si possano mostrare i testi nella lingua corretta
 */
function language_from_browser(){
	$lang = "en";
	
	if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])){
		$lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
	}
	
	if($lang === "it"){
		$lingua_sito = "ITA";
	}else{
		$lingua_sito = "ENG";
	}
	
	if(!isset($_SESSION["accesso_effettuato"])){
		$_SESSION["accesso_effettuato"] = true;
		$_SESSION["lingua_sito"] = $lingua_sito;
	}
}
 
 
 /**
 * questa funzione stampa (o ritorna) il valore passato come parametro.
 * 
 * se il sito lavora in multi-lingua, cerca se esiste la traduzione per la lingua corrente, altrimenti inserisce la parola nel db (in modo da poter essere tradotta in seguito)
 * 
 * @param string $testo testo da stampare
 * @param boolean $really_print [default: true] se impostato a false non stampa il testo ma lo ritorna
 */
function stampa($testo, $really_print = true){
	
		
	$testo_tradotto = $testo;
		
	if(defined('MULTILINGUA') && MULTILINGUA){
		
		$new_conn = false;
	
		global $conn;
		if(!isset($conn) || !(is_a($conn, "classe_DB") || is_a($conn, "DB_PDO") || is_a($conn, "MS_SQL"))){
			$conn = new classe_DB();
			$new_conn = true;
		}
		
		$lingua_sito = "ITA";
				
		if(isset($_SESSION["lingua_sito"]) && strlen($_SESSION["lingua_sito"]) > 0){
			$lingua_sito = $_SESSION["lingua_sito"];
		}
		
//		echo $lingua_sito;
//		die();
		
		$nome_colonna = "valore";
		
		switch ($lingua_sito) {
			case "SPA":
				$nome_colonna = "valore_spa";
				break;
			
			default:
				$nome_colonna = "valore";
				break;
		}
		
		//andiamo a vedere se c'e' gia' il record sul db
		if($lingua_sito != "ITA"){ //in questo modo andiamo a fare connessioni solo se non siamo nel contesto di default
			
			$sql = "SELECT * FROM traduzioni WHERE valore = ?";
			$dati_query = array($testo);

			$arr = $conn->query_risultati($sql, $dati_query);

			if(count($arr) == 0){ //se non abbiamo trovato nessuna corrispondenza, inseriamolo, cosi' lo troviamo nel db per la prossima volta (e potremo segnarci la traduzione)

				$sql = "INSERT INTO traduzioni (valore) VALUES (?)";
				$dati_query = array($testo);

				$conn->esegui_query($sql, $dati_query);

			}else{			
				//andiamo a vedere se il campo della lingua corrente e' valorizzato correttamente
				if(isset($arr[0][$nome_colonna]) && strlen($arr[0][$nome_colonna]) > 0){
					$testo_tradotto = $arr[0][$nome_colonna];
				}
			}
		}
		
		if($new_conn){
			$conn->Close();
		}
	}
	
	
	if($really_print){
		echo $testo_tradotto;
	}else{
		return $testo_tradotto;
	}
}

function stampa_errore_frontend($testo){
?>
	<div class="row padding-0 fascia_intestazione text-center">
		<div class="corpo text-left">
			<div class="col-sm-8 col-xs-12">
				<h3>Errore</h3>
			</div>
		</div>
	</div>
	<div class="row padding-0 text-center out_corpo margin_bottom_100">
		<div class="corpo text-left" style="min-height: 300px;">
			<?= $testo ?>
		</div>
	</div>
<?
}


function stampa_errore_backend($testo){
?>
	<div id="page-wrapper">
		<div class="row">
			<div class="col-lg-12">
				<h3 class="page-header"> <? stampa("Errore"); ?></h3>
			</div>
		</div>
		<div class="row">
			<div class="col-lg-12">
				<?= $testo ?>
			</div>
		</div>
	</div>
<?
}


/**
 * ordina l'array multidimensionale passato come primo parametro sulla base degli altri parametri e lo restituisce
 * 
 * @param array $data l'array da ordinare
 * @param string $orderby nome della colonna e direzione (asc|desc)
 * @param boolean $children_key specifica se ordinare anche i figli
 * @return type
 */
function array_multiorderby($data, $orderby, $children_key = false) {
    // parsing orderby
    $args = array();
    $x = explode(' ', str_replace(',', ' ', $orderby));
    foreach ($x as $item) {
        $item = trim($item);
        if ($item == '')
            continue;
        if (strtolower($item) == 'asc')
            $item = SORT_ASC;
        else if (strtolower($item) == 'desc')
            $item = SORT_DESC;
        $args[] = $item;
    }

    // order
    foreach ($args as $n => $field) {
        if (is_string($field)) {
            $tmp = array();
            foreach ($data as $key => $row)
                $tmp[$key] = $row[$field];
            $args[$n] = $tmp;
        }
    }
    $args[] = &$data;
    call_user_func_array('array_multisort', $args);
    $data = array_pop($args);

    // order children
    if ($children_key) {
        foreach ($data as $k => $v)
            if (is_array($v[$children_key]))
                $data[$k][$children_key] = array_multiorderby($v[$children_key], $orderby, $children_key);
    }

    // return
    return $data;
}



/**
 * restituisce una stringa random (numeri e lettere maiuscole/minuscole) della lunghezza desiderata
 * 
 * @param int $length
 * @return string
 */
function random_password($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}


/**
 * restituisce le coordinate Google dell'indirizzo passato come parametro
 * 
 * @param string $address indirizzo
 * @return string coordinate
 */
function get_GoogleCoordinates($address){
 
	$address = str_replace(" ", "+", $address); // replace all the white space with "+" sign to match with google search pattern

	$url = "http://maps.google.com/maps/api/geocode/json?sensor=false&language=it&address=$address";

	$response = file_get_contents($url);

	$json = json_decode($response,TRUE); //generate array object from the response from the web
	
//	echo "<p>$url</p>";
//	var_dump($json);
//	die();

	if(isset($json["status"]) && $json["status"] == "OK"){
		return ($json['results'][0]['geometry']['location']['lat'].",".$json['results'][0]['geometry']['location']['lng']);
	}else {
		return "";
	} 
}
 
 
/**
 * restituisce la distanza tra due punti date le loro coordinate
 * 
 * @param float $lat1
 * @param float $lng1
 * @param float $lat2
 * @param float $lng2
 * @param boolean $miles specifica se restituire il valore in miglia o km [defalut: false -> km]
 * @return float distanza tra i due punti
 */
function distance($lat1, $lng1, $lat2, $lng2, $miles = false){

	//echo $lat1."-".$lng1."-".$lat2."-".$lng2."---";

	$pi80 = M_PI / 180;
	$lat1 *= $pi80;
	$lng1 *= $pi80;
	$lat2 *= $pi80;
	$lng2 *= $pi80;

	$r = 6372.797; // mean radius of Earth in km
	$dlat = $lat2 - $lat1;
	$dlng = $lng2 - $lng1;
	$a = sin($dlat / 2) * sin($dlat / 2) + cos($lat1) * cos($lat2) * sin($dlng / 2) * sin($dlng / 2);
	$c = 2 * atan2(sqrt($a), sqrt(1 - $a));
	$km = $r * $c;

	//echo ($miles ? ($km * 0.621371192) : $km);
	return ($miles ? ($km * 0.621371192) : $km);
}
 



/**
 * riceve un parametro intero e restituisce si/no, yes/no a seconda del valore e della lingua
 * 
 * @param int $int 1 | 0
 * @param string $lingua it | en
 * @return string
 */
function yes_no($int, $lingua="it"){
	
	$valore = "";
	switch ($lingua) {
		case "it":
			if($int == 1) $valore = "Si";
			elseif($int == 0) $valore = "No";
			break;

		case "en":
			if($int == 1) $valore = "Yes";
			elseif($int == 0) $valore = "No";
			break;
			
	}
	
	return $valore;
}



function rewrite_url($url_param, $pagina_corrente, $pagina_destinazione, $par_name = "pag"){
	
	if(strlen($url_param) > 0){
		
		if(strpos($url_param, "$par_name=".$pagina_corrente) !== false){//vediamo se c'e' il parametro pag
			
			$url_param = str_replace("$par_name=".$pagina_corrente, "$par_name=".$pagina_destinazione, $url_param);
			
		}else{//aggiungiamo il parametro pag agli altri
			
			$url_param .= "&$par_name=".$pagina_destinazione;
			
		}
	}else{
		$url_param = "$par_name=".$pagina_destinazione;
	}
	
	//controlliamo che l'url inizi con il ?
	if(strlen($url_param) > 0 && substr($url_param, 0, 1) != "?"){
		$url_param = "?".$url_param;
	}
	
	return $url_param;
	
}


/*	
 * FORMATTAZIONE STRINGHE
 -------------------------------------------------------------------------------------------------------------------------------- */

 

/**
 * restituisce una stringa tagliata di n caratteri facendo in modo che non vengano interrote le parole
 * 
 * opzionalmente e' possibile fare in modo che venga restituita una stringa con i tag html correttamente richiusi
 * 
 * @param string $stringa testo di partenza
 * @param int $max_char numero di caratteri approssimativo da tenere (si ferma alla fine della parola, se e' a meta')
 * @param bool $repair_html [default: false] se impostato a TRUE chiude eventuali tag HTML rimasti interrotti
 * @param string $encoding [default: utf8] indica la codifica con cui viene riprocessata la stringa se $repair_html = TRUE
 * @return string la stringa opportunamente tagliata e modificata
 */
function TagliaStringa($stringa, $max_char, $repair_html = false, $encoding="utf8"){
	
	if(strlen($stringa) > $max_char){
		
		$stringa_tagliata = substr($stringa, 0, $max_char);
		$last_space = strrpos($stringa_tagliata, " ");
		$stringa_ok = substr($stringa_tagliata, 0, $last_space);
		
		if($repair_html){
			
			if(class_exists("tidy")){ //se esiste la classe tidy, usiamo quella, che funziona meglio
				$tidy = new tidy();
				$options = array("show-body-only" => true);

				$stringa_ok = $tidy->repairString($stringa_ok, $options, $encoding);
				
			}else{ //altrimenti basiamoci sulle regexpr
				$stringa_ok = truncateHTML($stringa, $last_space);
			}
			
		}
		
		return $stringa_ok; //."...";
	}else{
		return $stringa;
	}
}


function truncateHTML($html, $length)
{
    $truncatedText = substr($html, $length);
    $pos = strpos($truncatedText, ">");
    if($pos !== false)
    {
        $html = substr($html, 0,$length + $pos + 1);
    }
    else
    {
        $html = substr($html, 0,$length);
    }

    preg_match_all('#<(?!meta|img|br|hr|input\b)\b([a-z]+)(?: .*)?(?<![/|/ ])>#iU', $html, $result);
    $openedtags = $result[1];

    preg_match_all('#</([a-z]+)>#iU', $html, $result);
    $closedtags = $result[1];

    $len_opened = count($openedtags);

    if (count($closedtags) == $len_opened)
    {
        return $html;
    }

    $openedtags = array_reverse($openedtags);
    for ($i=0; $i < $len_opened; $i++)
    {
        if (!in_array($openedtags[$i], $closedtags))
        {
            $html .= '</'.$openedtags[$i].'>';
        }
        else
        {
            unset($closedtags[array_search($openedtags[$i], $closedtags)]);
        }
    }


    return $html;
}



/**
 * meglio evitare di usare una teconologia per cui sia necessaria
 */
function formatsql($testo){
	
	//return $testo;
	
	if(isset($testo) && strlen($testo)>0){
		
		//return filter_var($testo, FILTER_SANITIZE_FULL_SPECIAL_CHARS);        
        return str_replace("'", "&#39;", $testo);
		
	}else{
		return "";
	}
}

function format_json($testo){
	if(isset($testo) && strlen($testo)>0){
		
		//return filter_var($testo, FILTER_SANITIZE_FULL_SPECIAL_CHARS); 
		
        $testo = str_replace("&#39;", "'", $testo);
		//$testo = html_entity_decode($testo, ENT_HTML401, 'ISO-8859-1');
		$testo = strip_tags($testo);
		//$testo = utf8_encode($testo);
		$testo = str_replace(array("\n","\r"), "", $testo);
		
		return $testo;
		
	}else{
		return "";
	}
}



 /**
 * [deprecated]
 * 
 * @param type $testo
 * @return string
 */
function eliminaapo($testo){
	if(isset($testo) && strlen($testo)>0){
		
		//return filter_var($testo, FILTER_SANITIZE_FULL_SPECIAL_CHARS);        
        return str_replace("'", "&#39;", $testo);
		
	}else{
		return "";
	}
}



/**
 * Questa funzione toglie dalla stringa $testo i tag HTML, 
 * se si da un valore anche a $lunghezza, la funzione stampa a 
 * video la stringa $testo con il numero di caratteri passati da 
 * $lunghezza senza troncare l'ultima parola.  
 * 
 * @param type $testo
 * @param type $lunghezza
 * @return string
 */
function format_longtext($testo,$lunghezza=0){
	
	
	if(isset($testo) && strlen($testo)>0){
		
		$testo = strip_tags($testo);
		
		if($lunghezza>0){
			$testo = TagliaStringa($testo, $lunghezza);
		}
	
	}else{
		$testo="";
	}
	
	return $testo;

}




/**
 * [deprecated]
 * Usare il metodo di classe_attributi
 * 
 * 
 * @param type $testo
 * @return string
 */
function format_xml($testo){
	
	//return $testo;
	
	if(isset($testo) && strlen($testo)>0){
		
		$testo = str_replace("&#39;", "'", $testo);
		$testo = strip_tags($testo);
		$testo = html_entity_decode($testo, ENT_QUOTES, 'ISO-8859-1');
		$testo = str_replace("&", "&amp;", $testo);
		$testo = utf8_encode($testo);
		//$testo = htmlentities($testo, ENT_IGNORE, 'utf-8');
		//$testo = utf8_encode($testo);
		
		return $testo;
		
	}else{
		return "";
	}
	
}


/**
 * applica il trim a tutti gli elementi dell'array passato
 * 
 * @param type $value
 * @param type $key
 */
function trim_array(&$value, &$key){
	$value = trim($value);
}

 
/*	
 * GESTIONE DELLE DATE
 -------------------------------------------------------------------------------------------------------------------------------- */
 
 /**
 * prende la data in formato mysql e la stampa al contrario
 * 
 * @param string $data data nel formato aaaa-mm-gg
 * @return string data nel formato gg/mm/aaaa
 */
 function print_data($data){
	
	if(strlen($data)==0){
		return "";
	}

	if(strlen($data)>10){
		$data = substr($data, 0, 10);
	}

		
	$arrDate = explode("-", $data); // break up date by slash
	if(count($arrDate)>=3){
		
		$intDay = $arrDate[2];
		$intMonth = $arrDate[1];
		$intYear = $arrDate[0];		
	
		return $intDay."/".$intMonth."/".$intYear;
	}else{
		return "";
	}

}
 
 
 /**
 * [deprecated: usare il metodo di classe_data]
 * 
 * @param type $data
 * @return string
 */
function format_data($data){
//controlla che la data sia in formato gg/mm/aaaa e se corretta la restituisce in formato aaaa-mm-gg, altrimenti restituisce una stringa vuota

	if(strlen($data)>10){
		$data = substr($data, 0, 10);
	}
	
	$pattern = "/^[0-3][0-9]\/[0-3][0-9]\/[0-9]{4}$/";
	
	$data_check = preg_match($pattern, $data);
	
	if($data_check==1){
		
		$arrDate = explode("/", $data); // break up date by slash
		$intDay = $arrDate[0];
		$intMonth = $arrDate[1];
		$intYear = $arrDate[2];	
		
		if (checkdate($intMonth,$intDay, $intYear)){
			return $intYear."-".$intMonth."-".$intDay;
		}else{
			return "0000-00-00";
		}	
		
		
	}else{

		//controlliamo se per caso � gi� nel formato corretto
		$new_patt = "/^[0-9]{4}-[0-3][0-9]-[0-3][0-9]$/";
		$data_check_normal = preg_match($new_patt, $data);

		
		if($data_check_normal==1){
			return $data;
		}else{
			return "0000-00-00";
		}
	}


}
 

/**
 * modifica la data passata come parametro impostando il valore di default se e' una di quelle "nulle"
 * 
 * @param string $data data passata come riferimento
 */
function replace_data(&$data){
	if($data == "" || $data == "0000-00-00" || $data == "1900-01-01" || $data == "1800-01-01"){
		//$data = "1900-01-01";
		$data = null;
	}
}

function codifica_utf8(&$value){
	$value = utf8_decode($value);
}


function redirect_https(){
	
	return;
	
	$bypass_redirect = array("localhost", "127.0.0.1", "elendil.sinergo.it", "l115z.sinergo.it", "oakenshield.sinergo.it");
	
	if(isset($_SERVER['HTTP_HOST']) && is_string($_SERVER['HTTP_HOST']) && array_search(strtolower(trim($_SERVER['HTTP_HOST'])), $bypass_redirect) !== false){
		return;
	}
	
	if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "off"){
		$redirect = "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		header("Location: ".$redirect);
	}
//		var_dump($_SERVER['HTTPS']);
//		echo "<p>";
//		var_dump($_SERVER['SERVER_PORT']);
}


/*	
 * GESTIONE DELLA SESSIONE
 -------------------------------------------------------------------------------------------------------------------------------- */
 

/**
 * verifica se esiste gia' una sessione attiva e in caso contrario la inizializza
 */
function init_sessione(){
	
	$sessione_none = defined("PHP_SESSION_NONE") ? PHP_SESSION_NONE : 1; //se c'e' la costante che indica "sessione vuota" usiamo quel valore, altrimenti assegnamo 1 (che dovrebbe essere il valore corretto)
	
	if(function_exists("session_status") && session_status() == $sessione_none){ //se esiste la funzione per lo stato della sessione (PHP >= 5.4) usiamo quella
		session_start();
		
	}elseif(function_exists("session_id") && session_id() == ""){ //altrimenti usiamo il vecchio metodo (supportato da PHP 4 in avanti)
		session_start();
	}
}


/**
 * distrugge la sessione attiva (desettando anche tutte le variabili globali associate alla sessione) nel caso ne esista una
 */
function distruggi_sessione(){
	
	$sessione_active = defined("PHP_SESSION_ACTIVE") ? PHP_SESSION_ACTIVE : 2; //se c'e' la costante che indica "sessione attiva" usiamo quel valore, altrimenti assegnamo 2 (che dovrebbe essere il valore corretto)
	
	if(function_exists("session_status") && session_status() == $sessione_active){ //se esiste la funzione per lo stato della sessione (PHP >= 5.4) usiamo quella
		session_unset();
		session_destroy();
		
	}elseif(function_exists("session_id") && session_id() != ""){ //altrimenti usiamo il vecchio metodo (supportato da PHP 4 in avanti)
		session_unset();
		session_destroy();
	}
}


/**
 * verifica se e' presente una sessione attiva
 * 
 * @return boolean TRUE se la sessione esiste ed e' attiva, FALSE altrimenti
 */
function check_sessione(){
	
	$esito = false;
	
	$sessione_active = defined("PHP_SESSION_NONE") ? PHP_SESSION_ACTIVE : 2; //se c'e' la costante che indica "sessione vuota" usiamo quel valore, altrimenti assegnamo 2 (che dovrebbe essere il valore corretto)
	
	if(function_exists("session_status") && session_status() == $sessione_active){ //se esiste la funzione per lo stato della sessione (PHP >= 5.4) usiamo quella
		$esito = true;
		
	}elseif(function_exists("session_id") && session_id() != ""){ //altrimenti usiamo il vecchio metodo (supportato da PHP 4 in avanti)
		$esito = true;
	}
	
	return $esito;
}



/**
 * recupera il codice html dell'url, passando anche l'eventuale sessione php
 * @param string $url url web da invocare
 * @return string sorgente html della pagina
 */
function get_data_url($url) {
                
//	$opts = array('http' => array('header'=> 'Cookie: ' . (isset($_SERVER['HTTP_COOKIE']) ? $_SERVER['HTTP_COOKIE'] : "")."\r\n"));
//	$context = stream_context_create($opts);

	$strCookie = (isset($_COOKIE['PHPSESSID']) ? 'PHPSESSID=' . $_COOKIE['PHPSESSID'] . '; path=/' : "");

//            var_dump($context);
//            die();

	session_write_close();

	$ch = curl_init();
	$timeout = 5;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_COOKIE, $strCookie);
	$data = curl_exec($ch);
	curl_close($ch);
	return $data;
}
?>