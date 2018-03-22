<?php
			
class classe_log_eventi_rpi extends base_log_eventi_rpi {

	/* -----------------------------------
	 * filtri per la ricerca
	 */
	
	private $pagina = 1;
	private $elementi_pagina = -1;
	private $campo_ordinamento = "1";
	private $tipo_ordinamento = "ASC";
	private $elementi_ricerca = "";
	private $numero_risultati = -1;

	private $pag_totali = 1;

	//----------------------------------- */


	public function __construct($id = 0) {
		parent::__construct($id);
		
		$campi_obbligatori = array("");  //array predisposto per contenere i nomi dei campi obbligatori
		attributo::imposta_obbligatori($campi_obbligatori, $this->attr);
		
		
		//predisposizione per impostare a mano il tipo di un determinato attributo
		$indice_campo = attributo::find_attr("__nome_campo__", $this->attr);
		
		if($indice_campo !== false){
			$this->attr[$indice_campo]->set_tipo("");
		}
		
		//se si vuole disabilitare il salvataggio dello storico dati, rimuovere il commento alla riga seguente
		//$this->save_old_data(false);
	}
	


	/**
	 * recupera automaticamente i dati da POST o GET a seconda del parametro e chiama il setter relativo applicato all'oggetto
	 * sulla base dei campi passati come primo parametro
	 * 
	 * @param array $elementi elenco dei campi da settare (senza il get_ davanti)
	 * @param string $source GET | POST
	 * @return boolean esito dei vari setter e del metodo verifica_obbligatori
	 */
	public function retrieve_from($elementi = array(), $source = "POST"){
		
		$errore = false;
		
		$source = strtoupper($source);
		
		if($source !== "POST" && $source !== "GET"){
			return false;
		}
		
		foreach($elementi as $value){
			
			$valore = null;
			
			$nome_setter = "set_".$value;

			$indice_campo = attributo::find_attr($value, $this->attr);
			$tipo_dato = null;
			
			if($indice_campo !== false){
				$tipo_dato = $this->attr[$indice_campo]->get_tipo();
			}
			
			switch($tipo_dato){
				case null:
					$errore = true;
					break;

				case "bool_int":
					if($source === "GET"){
						$valore = isset($_GET[$value]) && is_numeric($_GET[$value]) ? trim($_GET[$value]) : 0;
					}else{
						$valore = isset($_POST[$value]) && is_numeric($_POST[$value]) ? trim($_POST[$value]) : 0;
					}
					break;
				
				default:
					if($source === "GET"){
						$valore = isset($_GET[$value]) && is_string($_GET[$value]) ? trim($_GET[$value]) : "";
						
					}else{
						$valore = isset($_POST[$value]) && is_string($_POST[$value]) ? trim($_POST[$value]) : "";
					}
					
					break;
			}
			
			if($tipo_dato !== null){
				
				if(method_exists($this, $nome_setter)){
					$esito = call_user_func_array(array($this, $nome_setter), array($valore));
					
					if(!$esito){
						$errore = true;
					}
				}
				
			}
		}
		
		$obbligatori = $this->verifica_obbligatori();
			
		return !$errore && $obbligatori;
	}




	/* -----------------------------------
	 * metodi per la ricerca
	 */
	 
	/**
	 * imposta il numero della pagina di ricerca (a partire da 1)
	 *
	 * @param int $pagina
	 */
	public function filtro_pagina($pagina) {
		if(is_numeric($pagina) && $pagina > 0){
			$this->pagina = $pagina;
		}
	}
	
	/**
	 * imposta il numero di elementi per pagina
	 * @param int $elementi_pagina
	 */
	public function filtro_elementi_pagina($elementi_pagina) {
		if(is_numeric($elementi_pagina)){
			$this->elementi_pagina = $elementi_pagina;
		}
	}
	
	/**
	 * imposta una stringa di ricerca
	 * @param string $elementi_ricerca
	 */
	public function filtro_elementi_ricerca($elementi_ricerca) {
		//$this->elementi_ricerca = formatsql($elementi_ricerca);
		$this->elementi_ricerca = ($elementi_ricerca);
	}
	

	/**
	 * imposta il criterio di ordinamento dei dati dell'elenco_ricerca
	 *
	 * @param int $filtro 
	 */
	public function filtro_campo_ordinamento($filtro){
		
		switch($filtro){
			case 1:
				$this->campo_ordinamento = 1;
				break;
			

			default:
				$this->campo_ordinamento = 1;
				break;
		}
		
	}
	

	/**
	 * imposta il tipo di ordinamento dell'elenco ricerca
	 * 
	 * @param string $tipo_ordinamento [ASC | DESC]
	 */
	public function filtro_tipo_ordinamento($tipo_ordinamento = "ASC") {
		
		$tipo_ordinamento = strtoupper($tipo_ordinamento);
		
		if($tipo_ordinamento === "ASC" || $tipo_ordinamento === "DESC"){
			$this->tipo_ordinamento = $tipo_ordinamento;
		}
	}
	


	public function get_pag_totali() {
		return $this->pag_totali;
	}

	public function get_numero_risultati(){
		return $this->numero_risultati;
	}
	


	/**
	 * restituisce un array di oggetti classe_log_eventi_rpi con i risultati della query
	 * 
	 * @return \classe_log_eventi_rpi array con gli oggetti di tipo classe_log_eventi_rpi
	 */
	public function elenco_ricerca(){

		$dati_query = array();

		$ordinamento = "";
		$limit = "";
		$criterio_1 = "";
		$criterio_2 = "";
		$criterio_ricerca = "";

		if($this->elementi_pagina > 0){ //impostiamo il LIMIT
			if(defined("DB_TYPE") && DB_TYPE == "MSSQL"){
				$limit = " OFFSET ".(($this->pagina-1) * $this->elementi_pagina)." ROWS FETCH NEXT ".$this->elementi_pagina." ROWS ONLY";
			}elseif(defined("DB_TYPE") && DB_TYPE == "PGSQL"){
				$limit = " OFFSET ".(($this->pagina-1) * $this->elementi_pagina)." LIMIT ".$this->elementi_pagina;
			}else{
				$limit = " LIMIT ".(($this->pagina-1) * $this->elementi_pagina).",".$this->elementi_pagina;
			}
		}

		if(strlen($this->campo_ordinamento) > 0){
			$ordinamento = " ORDER BY ".$this->campo_ordinamento." ".$this->tipo_ordinamento;
		}

		
		if(strlen($this->elementi_ricerca) > 0){

			$cont_parole = 0;

			//tagliamo sullo spazio
			$parole = explode(" ", trim($this->elementi_ricerca));

			foreach ($parole as $parola){

				$parola = trim($parola);

				if(strlen($parola) > 2){ //togliamo le parole piu' corte di due caratteri

					$cont_parole++;

					//aggiungiamo la parola all'elenco dei bindings (usando i parametri con i nomi e non per posizione)
					$dati_query["parola_".$cont_parole] = "%".$parola."%";
					$dati_query["parola_".$cont_parole."_esatta"] = $parola;

					//componiamo la condizione in AND sulle parole e in OR sui campi
					$criterio_ricerca .= " (
							campo_1 LIKE :parola_$cont_parole OR
							campo_2 LIKE :parola_$cont_parole
						) AND";
				}
			}
		}


		//andiamo a comporre la query
		$sql = "SELECT id AS id_ricerca FROM log_eventi_rpi";

		if(strlen($criterio_1) > 0 || strlen($criterio_2) > 0 || strlen($criterio_ricerca) > 0){
			$sql .= " WHERE".$criterio_1.$criterio_2.$criterio_ricerca;

			//togliamo l'ultimo " AND"
			$sql = substr($sql, 0, -4);
		}

		//aggiungiamo le ultime info (se non ci sono di fatto attacchiamo delle stringhe vuote)
		$sql_count = $sql; //query per contare gli elementi senza il limit -> paginazione
		$sql .= $ordinamento.$limit;

		//echo "<p>".  $this->connessione()->debug_query($sql, $dati_query);
		$arr = $this->connessione()->query_risultati($sql, $dati_query);

		
		/*
		 * andiamo a contare il numero di record totali
		 */
		$arr_count = $this->connessione()->query_risultati($sql_count, $dati_query);
		
		//Contiamo i risultati della ricerca
		$this->numero_risultati = count($arr_count);
		

		//andiamo a calcolare ed aggiornare il numero di pagine totali
		if($this->elementi_pagina > 0){
			$this->pag_totali = ceil($this->numero_risultati/$this->elementi_pagina);
		}else{
			$this->pag_totali = 1;
		}


		$risultati = array();

		$cont = 0; //ci serve per inserire le righe corrette nell'array dei risultati (nel caso di query su piu' tabelle)

		//scorriamo tutto l'array di risultati e creiamo gli oggetti opportuni da inserire nell'array che restituiremo
		foreach ($arr as $value) {

			try{
				$risultati[] = new classe_log_eventi_rpi($value["id_ricerca"]);
			}catch (Exception $e){}

			$cont++;
		}

		$this->Close();
		return $risultati;
	}

	/*
	 * fine ricerca
	 * ----------------------------------- */



	/****************
	 * metodi ad hoc
	 */
	public function salva($make_log = true){
		$this->set_updated_at(date("Y-m-d H:i:s"));
		if($this->get_id()==0){
			$this->set_created_at(date("Y-m-d H:i:s"));
		}
		return parent::salva($make_log);
	}

}




class base_log_eventi_rpi {

	protected $is_connesso = 0;
	protected $destroy_conn = 0;
	protected $db_conn = null;
	protected $db_reset = false;

	protected $id = 0;
	
	protected $exist = false;

	private $data_evento = null;
	private $genere = null;
	private $eta = null;
	private $etnia = null;
	private $processato = null;
	private $camera_id = null;
	private $appearance_datetime = null;
	private $created_at = null;
	private $updated_at = null;

	private $errore = false;
	protected $attr = array();
	protected $campi_mancanti = array();
	protected $campi_errati = array();
	
	/*
	 * campi relativi al salvataggio dati precedenti in fase di update
	 */
	protected $save_old_data = true;
	protected $old_data = array();


	public function __construct($id = 0) {

		$this->is_connesso = 0;
		$this->destroy_conn = 0;
		$this->db_conn = null;
		
		$this->exist = false;
		
		$this->errore = false;
		$this->attr = array();


		if(!isset($id) || !is_numeric($id)){$id = 0;}
		$this->id = $id;


		$this->attr[] = $this->data_evento = new attributo("data_evento", "data_time");
		$this->attr[] = $this->genere = new attributo("genere");
		$this->attr[] = $this->eta = new attributo("eta");
		$this->attr[] = $this->etnia = new attributo("etnia");
		$this->attr[] = $this->processato = new attributo("processato", "bool_int");
		$this->attr[] = $this->camera_id = new attributo("camera_id", "int");
		$this->attr[] = $this->appearance_datetime = new attributo("appearance_datetime", "data_time");
		$this->attr[] = $this->created_at = new attributo("created_at");
		$this->attr[] = $this->updated_at = new attributo("updated_at");

		if($id > 0){

			$sql = "SELECT * FROM log_eventi_rpi WHERE id = ?";
			$dati_query = array($this->id);

			$arr = $this->connessione()->query_risultati($sql, $dati_query);
			$this->Close();

			if(count($arr) > 0){

				$this->exist = true;

				$this->data_evento->set_valore($arr[0]["data_evento"]);
				$this->genere->set_valore($arr[0]["genere"]);
				$this->eta->set_valore($arr[0]["eta"]);
				$this->etnia->set_valore($arr[0]["etnia"]);
				$this->processato->set_valore($arr[0]["processato"]);
				$this->camera_id->set_valore($arr[0]["camera_id"]);
				$this->appearance_datetime->set_valore($arr[0]["appearance_datetime"]);
				$this->created_at->set_valore($arr[0]["created_at"]);
				$this->updated_at->set_valore($arr[0]["updated_at"]);
		
				$this->old_data["id"] = $this->id;
				foreach($this->attr as $attr){
					$this->old_data[$attr->get_nome_attr()] = $attr->get_valore(0);
				}

			}else{
				throw new Exception("Element Not Found", -1); //indicare qui l'eccezione specifica da lanciare
			}	

		}	
	}


	/*****************
	 * metodi standard
	 */

	
	
	/**
	 * imposta se salvare anche i dati precedenti nei log in fase di update o meno
	 * 
	 * @param bool $save
	 */
	public function save_old_data($save = true){
		if(is_bool($save)){
			$this->save_old_data = $save;
		}
	}
	
	

	//creiamo il metodo delete di default come protected, in modo che possa essere implementato effettivamente nella classe derivata
	protected function delete($make_log = true){
	
		if(defined('DB_WRITE_DISABLED') && DB_WRITE_DISABLED === true){ //il database e' in manutenzione quindi non facciamo salvare
			return false;
		}

		/* -----------------------------
		 * LOG
		 *------------------------------------*/
		if($make_log === true){
			
			$log = new classe_log();

			if($log->enabled()){
				$log->set_nome_azione("delete log_eventi_rpi");
				$log->set_tipo_azione("delete");
				$log->set_info("id: ".$this->id); //informazioni aggiuntive
				
				$log->inserisci();
			}
		}
		/*--------------------------------------*/

	}



	/**
	 * restituisce true se l'oggetto esiste sul database, false altrimenti
	 * (ad esempio in fase di creazione di un oggetto vuoto)
	 * 
	 * @return boolean
	 */
	public function exists(){
		return $this->exist;
	}

	

	protected function verifica_obbligatori(){

		$esito = true;

		foreach ($this->attr as $val){

			if($val->is_obbligatorio() && strlen($val->get_valore())<=0){
				$esito = false;
				$this->campi_mancanti[] = $val;
			}

			if(!$val->is_corretto()){
				$this->errore = true;
				$this->campi_errati[] = $val;
			}
		}		
		return $esito;
	}	
		

	/**
	 * restituisce l'array con l'elenco di oggetti tutti gli attributi
	 * 
	 * @return \attributo
	 */
	public function attributi($array = false){
		if(!$array){
			return $this->attr;
		} else {
			$j_out = array();
			foreach($this->attr as $attr){
				$j_out[$attr->get_nome_attr()] = $attr->get_valore(0);
			}
			return $j_out;
		}
	}
	
	/**
	 * restituisce un array di oggetti attributo con gli attributi che non sono stati valorizzati (e sono obbligatori)
	 * 
	 * @return \attributo
	 */
	public function campi_mancanti(){
		return $this->campi_mancanti;
	}
	
	
	/**
	 * restituisce un array di oggetti attributo con gli attributi che sono stati valorizzati con dei dati non conformi al tipo
	 * 
	 * @return \attributo
	 */
	public function campi_errati(){
		return $this->campi_errati;
	}
	
	
	/**
	 * restituisce un array con tutti i campi che hanno dato errore, sia se obbligatori, sia se errati
	 * 
	 * @return \attributo
	 */
	public function campi_errati_generici(){
		
		$result = $this->campi_mancanti();
		
		if(count($result) === 0){
			$result = $this->campi_errati();
		}else{
			
			$trovato = false;
		
			foreach($this->campi_errati() as $value){

				foreach($result as $value2){
					if($value->get_nome_attr() == $value2->get_nome_attr()){
						$trovato = true;
					}
				}
				
				if(!$trovato){
					$result[] = $value;
				}
			}
		}
		
		return $result;
	}


	/**
	 * in assenza di altri errori controlla che tutti i campi obbligatori siano stati inseriti
	 * quindi procede al salvataggio su db e restituisce l'esito
	 * 
	 * @param boolean $make_log [default: true] se FALSE non esegue i log dopo insert/update
	 * @return boolean esito del salvataggio
	 */
	public function salva($make_log = true){
		
		if(defined('DB_WRITE_DISABLED') && DB_WRITE_DISABLED === true){ //il database e' in manutenzione quindi non facciamo salvare
			return false;
		}

		if($this->verifica_obbligatori() && !$this->errore){

			//andiamo a inserire le informazioni nel db
			if($this->id == 0){

				$sql = "INSERT INTO log_eventi_rpi (data_evento, genere, eta, etnia, processato, camera_id, appearance_datetime, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

				$dati_query = array(
								$this->data_evento->get_valore(99), 
								$this->genere->get_valore(99), 
								$this->eta->get_valore(99), 
								$this->etnia->get_valore(99), 
								$this->processato->get_valore(99), 
								$this->camera_id->get_valore(99), 
								$this->appearance_datetime->get_valore(99), 
								$this->created_at->get_valore(99), 
								$this->updated_at->get_valore(99)
							);

				
				$this->connessione()->esegui_query($sql, $dati_query);
				
				$this->id = $this->connessione()->ultimo_id();


				/* -----------------------------
				 * LOG
				 *------------------------------------*/
				if($make_log === true){
					
					$log = new classe_log();

					if($log->enabled()){
						$log->set_nome_azione("insert log_eventi_rpi");
						$log->set_tipo_azione("insert");
						$log->set_info("id: ".$this->id); //informazioni aggiuntive
						
						$log->inserisci();
					}
				}
				/*--------------------------------------*/

				$this->Close();


			}else{//aggiorniamo l'oggetto

				$sql = "UPDATE log_eventi_rpi SET data_evento = ?, genere = ?, eta = ?, etnia = ?, processato = ?, camera_id = ?, appearance_datetime = ?, created_at = ?, updated_at = ?"
						." WHERE id = ?";

				$dati_query = array(
								$this->data_evento->get_valore(99), 
								$this->genere->get_valore(99), 
								$this->eta->get_valore(99), 
								$this->etnia->get_valore(99), 
								$this->processato->get_valore(99), 
								$this->camera_id->get_valore(99), 
								$this->appearance_datetime->get_valore(99), 
								$this->created_at->get_valore(99), 
								$this->updated_at->get_valore(99), 
								$this->id
							);

				

				$this->connessione()->esegui_query($sql, $dati_query);
				

				/* -----------------------------
				 * LOG
				 *------------------------------------*/
				if($make_log === true){
					
					$log = new classe_log();

					if($log->enabled()){
						$log->set_nome_azione("update log_eventi_rpi");
						$log->set_tipo_azione("update");
						$log->set_info("id: ".$this->id); //informazioni aggiuntive
						
						if($this->save_old_data && method_exists($log, "set_old_data")){
							$log->set_old_data($this->old_data);
						}
						
						$log->inserisci();
					}
				}
				/*--------------------------------------*/
				
				$this->Close();


			}

			return true;			

		}else{
			return false;
		}
	}
		

	/******************************
	 * setter
	 ******************************/
	
	/**
	 * 
	 * @param string $data_evento
	 * @return boolean true se il valore e' corretto, false altrimenti
	 */
	public function set_data_evento($data_evento) {
		$this->data_evento->set_valore($data_evento);
		return $this->data_evento->is_corretto();
	}
	
	/**
	 * 
	 * @param string $genere
	 * @return boolean true se il valore e' corretto, false altrimenti
	 */
	public function set_genere($genere) {
		$this->genere->set_valore($genere);
		return $this->genere->is_corretto();
	}
	
	/**
	 * 
	 * @param string $eta
	 * @return boolean true se il valore e' corretto, false altrimenti
	 */
	public function set_eta($eta) {
		$this->eta->set_valore($eta);
		return $this->eta->is_corretto();
	}
	
	/**
	 * 
	 * @param string $etnia
	 * @return boolean true se il valore e' corretto, false altrimenti
	 */
	public function set_etnia($etnia) {
		$this->etnia->set_valore($etnia);
		return $this->etnia->is_corretto();
	}
	
	/**
	 * 
	 * @param string $processato
	 * @return boolean true se il valore e' corretto, false altrimenti
	 */
	public function set_processato($processato) {
		$this->processato->set_valore($processato);
		return $this->processato->is_corretto();
	}
	
	/**
	 * 
	 * @param string $camera_id
	 * @return boolean true se il valore e' corretto, false altrimenti
	 */
	public function set_camera_id($camera_id) {
		$this->camera_id->set_valore($camera_id);
		return $this->camera_id->is_corretto();
	}
	
	/**
	 * 
	 * @param string $appearance_datetime
	 * @return boolean true se il valore e' corretto, false altrimenti
	 */
	public function set_appearance_datetime($appearance_datetime) {
		$this->appearance_datetime->set_valore($appearance_datetime);
		return $this->appearance_datetime->is_corretto();
	}
	
	/**
	 * 
	 * @param string $created_at
	 * @return boolean true se il valore e' corretto, false altrimenti
	 */
	public function set_created_at($created_at) {
		$this->created_at->set_valore($created_at);
		return $this->created_at->is_corretto();
	}
	
	/**
	 * 
	 * @param string $updated_at
	 * @return boolean true se il valore e' corretto, false altrimenti
	 */
	public function set_updated_at($updated_at) {
		$this->updated_at->set_valore($updated_at);
		return $this->updated_at->is_corretto();
	}
	

	/******************************
	 * getter
	 ******************************/
	 
	/*
	 * di default i getter richiedono il dato formattato per html. Se serve il dato puro, richiederlo con il parametro 0
	 */

	public function get_id() {
		return $this->id;
	}
	
	public function get_data_evento($formattazione_dato = 1) {
		return $this->data_evento->get_valore($formattazione_dato);
	}
	
	public function get_genere($formattazione_dato = 1) {
		return $this->genere->get_valore($formattazione_dato);
	}
	
	public function get_eta($formattazione_dato = 1) {
		return $this->eta->get_valore($formattazione_dato);
	}
	
	public function get_etnia($formattazione_dato = 1) {
		return $this->etnia->get_valore($formattazione_dato);
	}
	
	public function get_processato($formattazione_dato = 1) {
		return $this->processato->get_valore($formattazione_dato);
	}
	
	public function get_camera_id($formattazione_dato = 1) {
		return $this->camera_id->get_valore($formattazione_dato);
	}
	
	public function get_appearance_datetime($formattazione_dato = 1) {
		return $this->appearance_datetime->get_valore($formattazione_dato);
	}
	
	public function get_created_at($formattazione_dato = 1) {
		return $this->created_at->get_valore($formattazione_dato);
	}
	
	public function get_updated_at($formattazione_dato = 1) {
		return $this->updated_at->get_valore($formattazione_dato);
	}



	/**
	 * 
	 * @global null $conn
	 * @return \classe_DB
	 */
	protected function connessione(){
		//se esiste gia' una connessione utilizza quella, altrimenti ne crea una nuova
		global $conn;

		if(!$this->db_reset && isset($conn) && (is_a($conn, "classe_DB") || is_a($conn, "PgSQL_PDO") || is_a($conn, "DB_PDO") || is_a($conn, "MS_SQL"))){
			return $conn;
		}else{

			if($this->destroy_conn == 1 || !isset($this->db_conn) || $this->db_reset){

				$this->db_conn = new classe_DB();

				$this->is_connesso = 1;
				$this->destroy_conn = 0;
				return $this->db_conn;
			}else{
				return $this->db_conn;
			}
		}
	}

	protected function Close(){

		if($this->is_connesso == 1){

			//chiudiamo la connessione
			$this->connessione()->Close();
			$this->destroy_conn = 1;
			$this->is_connesso = 0;
		}
	}
		
}

?>