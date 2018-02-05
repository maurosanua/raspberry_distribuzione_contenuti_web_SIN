<?php
			
class classe_settings extends base_settings {

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
	
	private $filtro_attivo = -1;
	

	//----------------------------------- */

	/**
	 *
	 * @var \classe_settings_fatturazione
	 */
	private $valori_db = array();

	
	public function __construct($id = 0, $load_attivi = false) {
		
		parent::__construct($id);
		
		$campi_obbligatori = array("");  //array predisposto per contenere i nomi dei campi obbligatori
		attributo::imposta_obbligatori($campi_obbligatori, $this->attr);
		
		
		//predisposizione per impostare a mano il tipo di un determinato attributo
		$indice_campo = attributo::find_attr("__nome_campo__", $this->attr);
		
		if($indice_campo !== false){
			$this->attr[$indice_campo]->set_tipo("");
		}
		
		
		if($load_attivi){
			$this->load_from_db();
		}
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


	public function get_pag_totali() {
		return $this->pag_totali;
	}

	public function get_numero_risultati(){
		return $this->numero_risultati;
	}
	

	public function filtro_attivo($filtro_attivo) {
		if(is_numeric($filtro_attivo)){
			$this->filtro_attivo = $filtro_attivo;
		}
	}

	
	/**
	 * restituisce un array di oggetti classe_settings_fatturazione con i risultati della query
	 * 
	 * @return \classe_settings_fatturazione array con gli oggetti di tipo classe_settings_fatturazione
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
			}else if(defined("DB_TYPE") && DB_TYPE == "PGSQL"){
				$limit = " OFFSET ".(($this->pagina-1) * $this->elementi_pagina)." LIMIT ".$this->elementi_pagina;
			}else {
				$limit = " LIMIT ".(($this->pagina-1) * $this->elementi_pagina).",".$this->elementi_pagina;
			}
		}

		if(strlen($this->campo_ordinamento) > 0){
			$ordinamento = " ORDER BY ".$this->campo_ordinamento." ".$this->tipo_ordinamento;
		}

		
		if($this->filtro_attivo > -1){
			$criterio_1 .= " attivo = :attivo AND";
			$dati_query["attivo"] = $this->filtro_attivo;
		}
				
		if(strlen($this->elementi_ricerca) > 0){

			$cont_parole = 0;

			//tagliamo sullo spazio
			$parole = explode(" ", trim($this->elementi_ricerca));

			foreach ($parole as $parola){

				$parola = trim($parola);

				if(strlen($parola) > 2){ //togliamo le parole pi� corte di due caratteri

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
		$sql = "SELECT id_setting AS id_ricerca FROM settings";

		if(strlen($criterio_1) > 0 || strlen($criterio_2) > 0 || strlen($criterio_ricerca) > 0){
			$sql .= " WHERE".$criterio_1.$criterio_2.$criterio_ricerca;

			//togliamo l'ultimo " AND"
			$sql = substr($sql, 0, -4);
		}

		//aggiungiamo le ultime info (se non ci sono di fatto attacchiamo delle stringhe vuote)
		$sql_count = $sql.$ordinamento; //query per contare gli elementi senza il limit -> paginazione
		$sql .= $ordinamento.$limit;

		//echo "<p>".$this->connessione()->debug_query($sql, $dati_query);
		$arr = $this->connessione()->query_risultati($sql, $dati_query);

		
		/*
		 * andiamo a contare il numero di record totali
		 */
		$arr_count = $this->connessione()->query_risultati($sql_count, $dati_query);
		
		//Contiamo i risultati della ricerca
		$this->numero_risultati = count($arr_count);
		

		//andiamo a calcolare ed aggiornare il numero di pagine totali
		if($this->elementi_pagina > 0){
			$this->pag_totali = ceil(count($arr_count)/$this->elementi_pagina);
		}else{
			$this->pag_totali = 1;
		}


		$risultati = array();

		$cont = 0; //ci serve per inserire le righe corrette nell'array dei risultati (nel caso di query su pi� tabelle)

		//scorriamo tutto l'array di risultati e creiamo gli oggetti opportuni da inserire nell'array che restituiremo
		foreach ($arr as $value) {

			try{
				$risultati[] = new classe_settings($value["id_ricerca"]);
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
	 
	
	
	private function load_from_db(){
		
		$this->filtro_attivo(1);
		$this->filtro_elementi_pagina(-1);
		
		$this->valori_db = $this->elenco_ricerca();
		
	}
		
	
	/**
	 * restituisce il valore corrispondente al settings passato per nome
	 * 
	 * @param string $nome nome del campo da cercare nella tabella settings
	 * @param int $attivo filtro sullo stato del settings [-1: qualunque stato, 1: attivo, 0: non attivo)
	 * @param string valore dell'attributo
	 * @return type
	 */
	public function recupera_valore($nome, $attivo = -1, $formattazione_dato = 1){
		
		$valore = "";
		
		foreach($this->valori_db as $value){
						
			if($value->get_nome() == $nome && ($attivo == -1 || $attivo == $value->get_attivo(0))){
				$valore = $value->get_valore($formattazione_dato);
			}
		}
		
		
		return $valore;
	}

		
	
}




class base_settings {

	protected $is_connesso = 0;
	protected $destroy_conn = 0;
	protected $db_conn = null;
	protected $db_reset = false;

	protected $id = 0;
	
	protected $exist = false;

	private $nome = null;
	private $etichetta = null;
	private $attivo = null;
	private $valore = null;
	private $avanzate = null;

	private $errore = false;
	protected $attr = array();


	public function __construct($id = 0) {

		$this->is_connesso = 0;
		$this->destroy_conn = 0;
		$this->db_conn = null;
		
		$this->exist = false;
		
		$this->errore = false;
		$this->attr = array();


		if(!isset($id) || !is_numeric($id)){$id = 0;}
		$this->id = $id;


		$this->attr[] = $this->nome = new attributo("nome");
		$this->attr[] = $this->etichetta = new attributo("etichetta");
		$this->attr[] = $this->attivo = new attributo("attivo", "int");
		$this->attr[] = $this->valore = new attributo("valore");
		$this->attr[] = $this->avanzate = new attributo("avanzate", "int");

		if($id > 0){

			$sql = "SELECT * FROM settings WHERE id_setting = ?";
			$dati_query = array($this->id);

			$arr = $this->connessione()->query_risultati($sql, $dati_query);
			$this->Close();

			if(count($arr) > 0){

				$this->exist = true;

				$this->nome->set_valore($arr[0]["nome"]);
				$this->etichetta->set_valore($arr[0]["etichetta"]);
				$this->attivo->set_valore($arr[0]["attivo"]);
				$this->valore->set_valore($arr[0]["valore"]);
				$this->avanzate->set_valore($arr[0]["avanzate"]);

			}else{
				throw new Exception("Element Not Found", -1); //indicare qui l'eccezione specifica da lanciare
			}	

		}	
	}


	/*****************
	 * metodi standard
	 */


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
				$log->set_nome_azione("delete settings");
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

	

	private function verifica_obbligatori(){

		$esito = true;

		foreach ($this->attr as $val){

			if($val->is_obbligatorio() && strlen($val->get_valore())<=0){
				$esito = false;
			}

			if(!$val->is_corretto()){
				$this->errore = true;
			}
		}		
		return $esito;
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

				$sql = "INSERT INTO settings (nome, etichetta, attivo, valore, avanzate) VALUES (?, ?, ?, ?, ?)";

				$dati_query = array(
								$this->nome->get_valore(99), 
								$this->etichetta->get_valore(99), 
								$this->attivo->get_valore(99), 
								$this->valore->get_valore(99), 
								$this->avanzate->get_valore(99)
							);

				
				$this->connessione()->esegui_query($sql, $dati_query);
				
				$this->id = $this->connessione()->ultimo_id();


				/* -----------------------------
				 * LOG
				 *------------------------------------*/
				if($make_log === true){
					
					$log = new classe_log();

					if($log->enabled()){
						$log->set_nome_azione("insert settings");
						$log->set_tipo_azione("insert");
						$log->set_info("id: ".$this->id); //informazioni aggiuntive
						
						$log->inserisci();
					}
				}
				/*--------------------------------------*/

				$this->Close();


			}else{//aggiorniamo l'oggetto

				$sql = "UPDATE settings SET nome = ?, etichetta = ?, attivo = ?, valore = ?, avanzate = ?"
						." WHERE id_setting = ?";

				$dati_query = array(
								$this->nome->get_valore(99), 
								$this->etichetta->get_valore(99), 
								$this->attivo->get_valore(99), 
								$this->valore->get_valore(99), 
								$this->avanzate->get_valore(99), 
								$this->id
							);

				

				$this->connessione()->esegui_query($sql, $dati_query);
				

				/* -----------------------------
				 * LOG
				 *------------------------------------*/
				if($make_log === true){
					
					$log = new classe_log();

					if($log->enabled()){
						$log->set_nome_azione("update settings");
						$log->set_tipo_azione("update");
						$log->set_info("id: ".$this->id); //informazioni aggiuntive
						
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
	 * @param string $nome
	 * @return boolean true se il valore e' corretto, false altrimenti
	 */
	public function set_nome($nome) {
		$this->nome->set_valore($nome);
		return $this->nome->is_corretto();
	}
	
	/**
	 * 
	 * @param string $etichetta
	 * @return boolean true se il valore e' corretto, false altrimenti
	 */
	public function set_etichetta($etichetta) {
		$this->etichetta->set_valore($etichetta);
		return $this->etichetta->is_corretto();
	}
	
	/**
	 * 
	 * @param string $attivo
	 * @return boolean true se il valore e' corretto, false altrimenti
	 */
	public function set_attivo($attivo) {
		$this->attivo->set_valore($attivo);
		return $this->attivo->is_corretto();
	}
	
	/**
	 * 
	 * @param string $valore
	 * @return boolean true se il valore e' corretto, false altrimenti
	 */
	public function set_valore($valore) {
		$this->valore->set_valore($valore);
		return $this->valore->is_corretto();
	}
	
	/**
	 * 
	 * @param string $avanzate
	 * @return boolean true se il valore e' corretto, false altrimenti
	 */
	public function set_avanzate($avanzate) {
		$this->avanzate->set_valore($avanzate);
		return $this->avanzate->is_corretto();
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
	
	public function get_nome($formattazione_dato = 1) {
		return $this->nome->get_valore($formattazione_dato);
	}
	
	public function get_etichetta($formattazione_dato = 1) {
		return $this->etichetta->get_valore($formattazione_dato);
	}
	
	public function get_attivo($formattazione_dato = 1) {
		return $this->attivo->get_valore($formattazione_dato);
	}
	
	public function get_valore($formattazione_dato = 1) {
		return $this->valore->get_valore($formattazione_dato);
	}
	
	public function get_avanzate($formattazione_dato = 1) {
		return $this->avanzate->get_valore($formattazione_dato);
	}



	/**
	 * 
	 * @global null $conn
	 * @return \classe_DB
	 */
	protected function connessione(){
		//se esiste gia' una connessione utilizza quella, altrimenti ne crea una nuova
		global $conn;

		if(!$this->db_reset && isset($conn) && (is_a($conn, "classe_DB") || is_a($conn, "DB_PDO") || is_a($conn, "MS_SQL"))){
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