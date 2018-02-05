<?php

/**
 * gestisce gli oggetti attributo, alla base di tutte le classi generate da DB
 * fornisce anche tutti i metodi statici per la validazione degli input, sia integrati nei setter/getter che ad hoc
 *
 * @author Fabio
 */

class attributo{
	
	public static $versione = "2.14";
	public static $changelog = "
2.14	--	correzione replace_comma_numeric in modo che NON ritorni zero se la stringa in ingresso non e' numerica (altrimenti non funziona il controllo sul tipo)

2.13	--	get_valore(12) -> converte una stringa da utf8 in windows-1252

2.12	--	correzione salvataggio date a NULL se viene passata una stringa vuota anche per MySQL

2.11	--	get_valore(7) -> esegue la formattazione per i json (come il parametro 4) ma applicando in uscita utf8_encode
		--	modifica di format_json per supportare la doppia modalita' (con o senza formattazione utf8)

2.10	--	get_valore(11) implemetato per la stampa della data con anche l'ora

2.9	--	get_valore(6): esegue lo strip_tags del dato (serve per ottenere il valore senza tutti i tag html)

2.8	--	Reintroduzione del metodo formatta_json (rimosso per errore) e razionalizzazione dei parametri di get_valore:
		get_valore(4): dati formattati per essere utilizzati in un json
		get_valore(5): dati formattati per essere utilizzati in finestre o funzioni javascript (effettua l'escape di ' e \")

2.7	--	Controllo validita' del dato anche per il tipo \"decimal\"

2.6	--	Gestione automatica dei numeri decimali (in fase di set sostituisce automaticamente la virgola con il punto e in get con parametro 1 applica il number format)
		Supporto nativo delle date nulle di default

2.5	--	Metodo formatta_js() per restituire i dati eseguendo l'escape di \" e ' che altrimenti danno problemi se usati in funzioni javascript

2.4	--	Aggiunta del metodo find_attr() che cerca all'interno di un array di oggetti attributo un particolare attributo passato per nome
	--	Aggiunta del metodo imposta_obbligatori() che puo' essere usato nella classe figlia per impsotare l'obbligatorieta' di alcuni attributi
	--	Aggiunta dell'opzione 10 per il metodo get_valore(): restituisce la data in formato \"stampabile\"
	--	Aggiunta dell'opzione 3 per il metodo get_valore(): restituisce i caratteri codificati in utf8, per poter essere gestiti correttamente nell'esportazione excel
	--	Metodi statici formatta_excel() e formatta_excel_import() da usare per esportare e importare i dati in Excel
	--	Costruttore per le date \"disabilitato\": - non viene forzato un valore standard fin da subito

2.3	--	Aggiunta di \" 00:00:00\" alla data nel caso in cui sia data_time ma non contenga l'ora (altrimenti il format_dataTime() fallisce)
	--	Aggiunta del parametro 99 per il metodo get_valore(): serve per formattare i dati per essere inseriti nel DB
		Se il DB e' MSSQL implica aggiungere una \"T\" nei campi data_time, altrimenti non funzionano in tutti i sistemi (es: 2014-08-01T12:00:00

2.2	--	Controllo validita' degli attributi \"data\" e \"data_time\" usando il nuovo metodo check_data() della classe_data
	--	Per gli attributi di tipo \"data\" e \"data_time\", il set_valore() applica direttamente la formattazione per essere inserite nel db
	--	il costruttore imposta il valore di default a 0 per i campi di tipo \"decimal\" come anche il metodo set_valore() nel caso in cui si passi una stringa vuota

2.1	--	Controllo validita' della mail usando il metodo statico (controlla anche che esista il filtro, altrimenti usa una regexpr)

2.0	--	Aggiunta del valore NULL per le date vuote
	--	Aggiunta del tipo \"decimal\"
	--	Metodi statici formatta_xml() e formatta_html
	";
	
	
	private $nome_attr = "";
	private $valore = "";
	private $tipo = "";
	private $obbligatorio = false;
	private $corretto = true;


	public function __construct($nome_attr, $tipo = "text") {
		$this->nome_attr = $nome_attr;
		$this->set_tipo($tipo);
		
		//per alcuni tipi forziamo il valore di default, altrimenti il db ci da' un errore
		switch ($tipo) {
			
			case "decimal":
				$this->valore = 0;
				break;
			
			case "data":
				//$this->valore = "1800-01-01";
				$this->valore = null;
				break;
			
			case "data_time":
				//$this->valore = "1800-01-01 00:00:00";
				$this->valore = null;
				break;
			
			default:
				break;
		}
	}
	

	public function set_obbligatorio($val){
		$this->obbligatorio = $val;
	}	

	public function set_nome($val){
		$this->nome_attr=$val;
	}
	
	public function set_tipo($tipo){
		
		switch ($tipo){
			
			case "text":
				$this->tipo = $tipo;
				break;
			
			case "int":
				$this->tipo = $tipo;
				break;
			
			case "data":
				$this->tipo = $tipo;
				break;
			
			case "data_time":
				$this->tipo = $tipo;
				break;
			
			case "mail":
				$this->tipo = $tipo;
				break;
			
			case "float":
				$this->tipo = $tipo;
				break;
			
			case "bool_int":
				$this->tipo = $tipo;
				break;
			
			case "decimal":
				$this->tipo = $tipo;
				break;
			
			default:
				$this->tipo = "text";
				break;
		}
	}



	public function set_valore($val){
		
		$val_string = "";
		
		if(is_a($val, "DateTime")){			
			$val_string = $val->format("Y-m-d H:i:s");
		}else{
			$val_string = $val;
		}
		
		
		if(($this->tipo == "data" || $this->tipo == "data_time")){
			
			if(strlen($val_string) == 0 || $val_string === null){
				$this->valore = null;
				
			}elseif($this->tipo == "data"){
				$this->valore = classe_data::format_data($val_string);
				
			}elseif($this->tipo == "data_time"){
				
				if(strlen($val_string) == 10){ //se e' data_time ma contiene solo la data, aggiungiamo le ore, cosi' i DB non vanno in errore
					$val_string .= " 00:00:00";
				}
				
				$this->valore = classe_data::format_dataTime($val_string);
			}
			
		}elseif($this->tipo == "decimal" || $this->tipo == "float"){
			
			//andiamo a vedere se e' null e con che cosa inizia
			if($val_string !== null && strlen($val_string) > 0 && substr($val_string, 0, 1) == "."){
				$this->valore = "0".$val_string;
				
			}elseif($val_string === ""){
				$this->valore = 0;
				
			}else{
				$this->valore = $this->replace_comma_numeric($val_string);
			}
			
		}else{
			if($val_string === null){
				$this->valore = null;
			}else{
				$this->valore = trim($val_string);
			}
		}


		if($this->obbligatorio && strlen($val_string)<=0){

			$this->corretto = false;

		}elseif(!$this->check_tipo()){

			$this->corretto = false;

		}else{

			$this->corretto = true;

		}

		if(strlen($this->valore) > 0){
			//$this->valore = formatsql($this->valore); //vediamo cosa succedere a togliere il formatsql
		}
	}
	

	private function check_tipo(){
		$esito = true;
		
		switch ($this->tipo){
			
			case "text":				
				break;
			
			case "int":
				if(strlen($this->valore)>0 && !is_numeric($this->valore)){$esito = false;}
				break;
			
			case "data":
				//if(format_data($this->valore)=="0000-00-00"){$esito = false;}
				if($this->valore !== null){ $esito = classe_data::check_dataTime($this->valore, 1); }
				break;
			
			case "data_time":
				if($this->valore !== null){ $esito = classe_data::check_dataTime($this->valore, -1); }
				break;
			
			case "float":
				if(strlen($this->valore)>0 && filter_var($this->valore, FILTER_VALIDATE_FLOAT)===false){$esito = false;}
				break;
				
			case "decimal":
				if(strlen($this->valore)>0 && filter_var($this->valore, FILTER_VALIDATE_FLOAT)===false){$esito = false;}
				break;
			
			case "mail":
				if(strlen($this->valore)>0 && attributo::check_email($this->valore)===false){$esito = false;}
				break;
				
			case "bool_int":
				if(strlen($this->valore)>0 && !is_numeric($this->valore) && ($this->valore!=0 || $this->valore!=1)){$esito = false;}
				break;
		}
		
		return $esito;
	}

	public function get_nome_attr(){
		return $this->nome_attr;
	}
	
	
	public function get_valore($formattazione_dato = 0){
		
		$valore_formattato = $this->valore;
		
		switch ($formattazione_dato) {
			
			case 0: //dato puro, non modificato
				$valore_formattato = $this->valore;
				break;

			case 1: //formattato per essere stampato in html
				
				if ($this->get_tipo()=="float" || $this->get_tipo()=="decimal"){
					if (is_numeric($this->valore)){
						$valore_formattato = number_format($this->formatta_html($this->valore),2,",","");
					}else{
						$valore_formattato = $this->formatta_html($this->valore);
					}
					
				}else{
					$valore_formattato = $this->formatta_html($this->valore);
				}
				
				break;
			
			case 2: //formattato per essere stampato in xml
				$valore_formattato = $this->formatta_xml($this->valore);
				break;
			
			case 3: //formattazione per decodificare l'utf8
				$valore_formattato = $this->formatta_excel($this->valore);
				break;
			
			case 4:
				$valore_formattato = $this->formatta_json($this->valore, false);
				break;			
			
			case 5: //formattato per essere usato in funzioni javascript
				$valore_formattato = $this->formatta_js($this->valore);
				break;
			
			case 6: //rimuove tutti i tag html da una stringa
				$valore_formattato = strip_tags($this->valore);
				break;
			
			case 7:
				$valore_formattato = $this->formatta_json($this->valore, true);
				break;
			
			case 10:
				if($this->tipo == "data_time" || $this->tipo == "data"){
					$valore_formattato = classe_data::stampa_data($this->valore);
				}
				break;
			
			case 11: //predisposizione per stampare data e ora formattate giuste
				if($this->tipo == "data_time" || $this->tipo == "data"){
					$valore_formattato = classe_data::stampa_dataTime($this->valore);
				}
				break;
				
			case 12: //per FPDF converte da UTF-8 a windows-1252
				$valore_formattato = $this->codifica_windows($this->valore);
				break;
				
			case 99: //dati modificati ad hoc per essere inseriti nel DB (al momento abbiamo problemi solo con i datetime per MSSQL ma in futuro chissa')
				
				if($valore_formattato === null && ($this->tipo == "data" || $this->tipo == "data_time")){
					
					return null;
					
				}elseif($this->tipo == "data_time"){
					if(defined('DB_TYPE') && DB_TYPE == "MSSQL"){
						
						$valore_formattato = str_replace(" ", "T", $this->valore); //sostituiamo lo spazio tra data e ora con la T per essere universali
					}
				}
				
				break;
			
			
			default:
				$valore_formattato = $this->valore;
				break;
		}
		
		
		return $valore_formattato;		
	}
	
	public function get_tipo(){
		return $this->tipo;
	}




	public function is_obbligatorio(){
		return $this->obbligatorio;
	}

	public function is_corretto(){
		return $this->corretto;
	}
	
	
	
	public static function formatta_html($testo){
		
		if(isset($testo) && strlen($testo)>0){
			$testo = str_replace("&#39;", "'", $testo);
			//$testo = htmlentities($testo, ENT_QUOTES, 'ISO-8859-1');

			$testo = str_replace('"', "&quot;", $testo);
			$testo = str_replace("'", "&#039;", $testo);
			
		}else{
			$testo = "";
		}
		
		return $testo;
	}
	
	
	public static function formatta_xml($testo){
		
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
	 * da usare per l'esportazione in excel
	 * 
	 * @param type $testo
	 * @return string
	 */
	public static function formatta_excel($testo){
		
		if(isset($testo) && strlen($testo)>0){
		
			$testo = utf8_encode($testo);
			
			return $testo;

		}else{
			return "";
		}
	}
	
	
	public static function formatta_excel_import($testo){
		
		if(isset($testo) && strlen($testo)>0){
		
			$testo = utf8_decode($testo);
			
			return $testo;

		}else{
			return "";
		}
	}
	
	
	public static function formatta_js($testo){
		
		if(isset($testo) && strlen($testo)>0){
			$testo = str_replace("&#39;", "'", $testo);
			//$testo = htmlentities($testo, ENT_QUOTES, 'ISO-8859-1');

			$testo = str_replace('"', "\"", $testo);
			$testo = str_replace("'", "\'", $testo);
			
		}else{
			$testo = "";
		}
		
		return $testo;
	}
	
	
	public static function formatta_json($testo, $utf_encode = false){
		
		if(isset($testo) && strlen($testo)>0){

			//return filter_var($testo, FILTER_SANITIZE_FULL_SPECIAL_CHARS); 

			$testo = str_replace("&#39;", "'", $testo);
			$testo = html_entity_decode($testo, ENT_HTML401, 'ISO-8859-1');
			//$testo = strip_tags($testo);

			$testo = str_replace(array("\n","\r"), "", $testo);
			
			if ($utf_encode){
				$testo = utf8_encode($testo);
			}

			return $testo;

		}else{
			return "";
		}
	}
	
	public static function codifica_windows($testo){
		
		if(isset($testo) && is_string($testo)){

			$testo = @iconv('UTF-8', 'windows-1252', $testo);
			
			return $testo;

		}else{
			return "";
		}
	}
	
	
	/**
	 * verifica la validita' di un indirizzo mail usando il FILTER_VALIDATE_EMAIL se presente o un'espressione regolare in caso contrario
	 * 
	 * @param string $mail indirizzo da verificare
	 * @return boolean esito della verifica
	 */
	public static function check_email($mail){
		
		$esito = false;
		
		if(strlen($mail) == 0){
			return false;
		}
		
		if(attributo::find_filter("validate_email")){
			
			if (filter_var($mail, FILTER_VALIDATE_EMAIL)===false){
				$esito = false;
			}else{
				$esito = true;
			}
			
		}else{
			
			if(strlen($mail) > 5){
				$pattern = '/^(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){255,})(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){65,}@)(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22))(?:\\.(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-+[a-z0-9]+)*\\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-+[a-z0-9]+)*)|(?:\\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\\]))$/iD';

				if (preg_match($pattern, $mail) === 1) {
					$esito = true;
				}
			}
		}
		
		return $esito;
	}
	
	
	/**
	 * verifica se e' presente il filtro passato come parametro
	 * 
	 * @param string $filtro nome filtro
	 * @return boolean esito
	 */
	private static function find_filter($filtro){
		
		$trovato = false;
		
		if(!function_exists("filter_list")){
			return false;
		}
		
		foreach (filter_list() as $value){
			if($value == $filtro){
				$trovato = true;
			}
		}
		
		return $trovato;
	}
	
	
	/**
	 * cerca all'interno di un array di oggetti "attributo" l'elemento con nome passato come primo parametro
	 * 
	 * se lo trova restituisce l'indice, altrimenti FALSE
	 * 
	 * @param string $nome nome dell'attributo da cercare
	 * @param \attributo $arr_attributi array di oggetti di tipo attributo
	 * @return mixed indice nell'array degli attributi con l'oggetto cercato | FALSE se non esiste
	 */
	public static function find_attr($nome, $arr_attributi){
		
		$trovato = false;
		
		$cont = 0;
		
		foreach ($arr_attributi as $attributo){
			if($attributo->get_nome_attr() == $nome){
				$trovato = $cont;
				break;
			}
			
			$cont++;
		}
		
		return $trovato;
	}
	
	
	
	/**
	 * riceve una serie di campi e li imposta come obbligatori
	 * 
	 * @param array $elenco_campi elenco dei nomi dei campi da rendere obbligatori
	 * @param \attributo $arr_attributi elenco degli attributi dell'oggetto
	 */
	public static function imposta_obbligatori($elenco_campi, $arr_attributi){
		
		foreach ($elenco_campi as $campo){
			$indice = attributo::find_attr($campo, $arr_attributi);
			
			if($indice !== false){
				$arr_attributi[$indice]->set_obbligatorio(true);
			}
		}
	}
	
	
	
	/**
	 * sostituisce all'interno di una stringa la virgola con il punto e ne ritorna il risultato
	 * 
	 * @param type $stringa
	 * @return string
	 */
    public static function replace_comma_numeric($stringa){
		return str_replace(',', '.', $stringa);
    }
}
?>