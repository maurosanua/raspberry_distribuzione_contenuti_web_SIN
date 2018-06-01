<?php
			
class classe_scene_live extends base_scene_live {

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
	 * restituisce un array di oggetti classe_scene_live con i risultati della query
	 * 
	 * @return \classe_scene_live array con gli oggetti di tipo classe_scene_live
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
		$sql = "SELECT id AS id_ricerca FROM scene_live";

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
				$risultati[] = new classe_scene_live($value["id_ricerca"]);
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
	
	 public function from_rel_fascia_scene($id){
		 
		 $id_scena = $this->connessione()->query_risultati(
			"select id from scene_live where rif_rel_fascia_scene = ?",
			[$id]
		 );
		 if(count($id_scena)>0){
			 return new classe_scene_live($id_scena[0]["id"]);
		 }else{
			 return new classe_scene_live();
		 }
	 }


	public function salva($make_log = true){
		$this->set_updated_at(date("Y-m-d H:i:s"));
		if($this->get_id()==0){
			$this->set_created_at(date("Y-m-d H:i:s"));
		}
		return parent::salva($make_log);
	}

}




class base_scene_live {

	protected $is_connesso = 0;
	protected $destroy_conn = 0;
	protected $db_conn = null;
	protected $db_reset = false;

	protected $id = 0;
	
	protected $exist = false;

	private $rif_rel_fascia_scene = null;
	private $live = null;
	private $data_start = null;
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


		$this->attr[] = $this->rif_rel_fascia_scene = new attributo("rif_rel_fascia_scene", "int");
		$this->attr[] = $this->live = new attributo("live", "bool_int");
		$this->attr[] = $this->data_start = new attributo("data_start", "data_time");
		$this->attr[] = $this->created_at = new attributo("created_at");
		$this->attr[] = $this->updated_at = new attributo("updated_at");

		if($id > 0){

			$sql = "SELECT * FROM scene_live WHERE id = ?";
			$dati_query = array($this->id);

			$arr = $this->connessione()->query_risultati($sql, $dati_query);
			$this->Close();

			if(count($arr) > 0){

				$this->exist = true;

				$this->rif_rel_fascia_scene->set_valore($arr[0]["rif_rel_fascia_scene"]);
				$this->live->set_valore($arr[0]["live"]);
				$this->data_start->set_valore($arr[0]["data_start"]);
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
				$log->set_nome_azione("delete scene_live");
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

				$sql = "INSERT INTO scene_live (rif_rel_fascia_scene, live, data_start, created_at, updated_at) VALUES (?, ?, ?, ?, ?)";

				$dati_query = array(
								$this->rif_rel_fascia_scene->get_valore(99), 
								$this->live->get_valore(99), 
								$this->data_start->get_valore(99), 
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
						$log->set_nome_azione("insert scene_live");
						$log->set_tipo_azione("insert");
						$log->set_info("id: ".$this->id); //informazioni aggiuntive
						
						$log->inserisci();
					}
				}
				/*--------------------------------------*/

				$this->Close();


			}else{//aggiorniamo l'oggetto

				$sql = "UPDATE scene_live SET rif_rel_fascia_scene = ?, live = ?, data_start = ?, created_at = ?, updated_at = ?"
						." WHERE id = ?";

				$dati_query = array(
								$this->rif_rel_fascia_scene->get_valore(99), 
								$this->live->get_valore(99), 
								$this->data_start->get_valore(99), 
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
						$log->set_nome_azione("update scene_live");
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
	 * @param string $rif_rel_fascia_scene
	 * @return boolean true se il valore e' corretto, false altrimenti
	 */
	public function set_rif_rel_fascia_scene($rif_rel_fascia_scene) {
		$this->rif_rel_fascia_scene->set_valore($rif_rel_fascia_scene);
		return $this->rif_rel_fascia_scene->is_corretto();
	}
	
	/**
	 * 
	 * @param string $live
	 * @return boolean true se il valore e' corretto, false altrimenti
	 */
	public function set_live($live) {
		$this->live->set_valore($live);
		return $this->live->is_corretto();
	}
	
	/**
	 * 
	 * @param string $data_start
	 * @return boolean true se il valore e' corretto, false altrimenti
	 */
	public function set_data_start($data_start) {
		$this->data_start->set_valore($data_start);
		return $this->data_start->is_corretto();
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
	
	public function get_rif_rel_fascia_scene($formattazione_dato = 1) {
		return $this->rif_rel_fascia_scene->get_valore($formattazione_dato);
	}
	
	public function get_live($formattazione_dato = 1) {
		return $this->live->get_valore($formattazione_dato);
	}
	
	public function get_data_start($formattazione_dato = 1) {
		return $this->data_start->get_valore($formattazione_dato);
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