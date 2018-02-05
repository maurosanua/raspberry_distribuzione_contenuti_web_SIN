<?php
			
class classe_utenti extends base_utenti {
	
	public static $versione = "2.1";
	public static $changelog = "
2.1	--	Aggiornamento per compatibilità con PostgreSQL. Aggiunto il campo scrittura

2.0	--	Verifica dell'oggetto connessione su piu' classi e creazione nuove connessioni con la nuova classe_DB

1.1	--	Aggiunta array tipi e metodi per far funzionare la gestione utenti base (verifica univocita' username, campo mail del tipo giusto...)

1.0	--	Versione base della classe generata sulla tabella base";
	

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

	
	public static $arr_tipi = array(0 => "Utente Base", 1 => "Admin");
	

	public function __construct($id = 0) {
		parent::__construct($id);
		
		
		$campi_obbligatori = array("user", "pw", "mail");
		attributo::imposta_obbligatori($campi_obbligatori, $this->attr);
		
		
		//andiamo a modificare il tipo del campo mail
		$indice_campo = attributo::find_attr("mail", $this->attr);
		
		if($indice_campo !== false){
			$this->attr[$indice_campo]->set_tipo("mail");
		}
		
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


	public function get_pag_totali() {
		return $this->pag_totali;
	}

	public function get_numero_risultati(){
		return $this->numero_risultati;
	}
	


	/**
	 * restituisce un array di oggetti classe_users con i risultati della query
	 * 
	 * @return \classe_users array con gli oggetti di tipo classe_users
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
		$sql = "SELECT id_user AS id_ricerca FROM users";

		if(strlen($criterio_1) > 0 || strlen($criterio_2) > 0 || strlen($criterio_ricerca) > 0){
			$sql .= " WHERE".$criterio_1.$criterio_2.$criterio_ricerca;

			//togliamo l'ultimo " AND"
			$sql = substr($sql, 0, -4);
		}

		//aggiungiamo le ultime info (se non ci sono di fatto attacchiamo delle stringhe vuote)
		$sql_count = $sql.$ordinamento; //query per contare gli elementi senza il limit -> paginazione
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
			$this->pag_totali = ceil(count($arr_count)/$this->elementi_pagina);
		}else{
			$this->pag_totali = 1;
		}


		$risultati = array();

		$cont = 0; //ci serve per inserire le righe corrette nell'array dei risultati (nel caso di query su pi� tabelle)

		//scorriamo tutto l'array di risultati e creiamo gli oggetti opportuni da inserire nell'array che restituiremo
		foreach ($arr as $value) {

			try{
				$risultati[] = new classe_utenti($value["id_ricerca"]);
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
	 
	
	/**
	 * verifica che lo username passato non sia gia' usato da un altro utente
	 * 
	 * @param string $username
	 * @return boolean true se non esiste, false se esiste gia'
	 */
	public function username_univoco($username){
		
		$username = trim($username);
		
		$sql = "SELECT id_user FROM users WHERE username = ? AND id_user != ?";
		$dati_query = array($username, $this->id);
		
		$arr = $this->connessione()->query_risultati($sql, $dati_query);
		$this->Close();
		
		if(count($arr) > 0){
			return false;
		}else{
			return true;
		}
		
	}

	
	/**
	 * elimina fisicamente l'utente dal db
	 * 
	 * @return boolean
	 */
	public function delete($make_log = true) {
		
		$esito = true;
		
		$sql = "DELETE FROM users WHERE id_user = ?";
		$dati_query = array($this->id);
		
		$this->connessione()->esegui_query($sql, $dati_query);
		
		parent::delete($make_log = true);
		
		$this->Close();
		
		return $esito;
	}
	
	

	/**
	 * rigenera l'oggetto corrente recuperando l'id a partire dallo username passato
	 * 
	 * @param string $username username dell'utente
	 */
	public function load_from_user($username){
		
		$sql = "SELECT id_user FROM users WHERE username = ?";
		$dati_query = array((string)$username);
		
		$arr = $this->connessione()->query_risultati($sql, $dati_query);
		
		if(count($arr) > 0){
			try{
				self::__construct($arr[0]["id_user"]);
			} catch (Exception $ex) {
				self::__construct();
			}
		}else{
			self::__construct();
		}
	}

	public function set_dtLast($value){
		return parent::set_dtlast($value);
	}
	
	public function get_dtLast($value=1){
		return parent::get_dtlast($value);
	}
}




class base_utenti {

	protected $is_connesso = 0;
	protected $destroy_conn = 0;
	protected $db_conn = null;
	protected $db_reset = false;

	protected $id = 0;
	
	protected $exist = false;

	private $nome = null;
	private $cognome = null;
	private $mail = null;
	private $cellulare = null;
	private $username = null;
	private $pw = null;
	private $sesso = null;
	private $attivo = null;
	private $dtlast = null;
	private $ip = null;
	private $tipo = null;
	private $cookie_id = null;
	private $cookie_expire = null;
	private $onetime = null;
	private $onetime_expire = null;
	private $scrittura = null;

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


		$this->attr[] = $this->nome = new attributo("nome");
		$this->attr[] = $this->cognome = new attributo("cognome");
		$this->attr[] = $this->mail = new attributo("mail");
		$this->attr[] = $this->cellulare = new attributo("cellulare");
		$this->attr[] = $this->username = new attributo("username");
		$this->attr[] = $this->pw = new attributo("pw");
		$this->attr[] = $this->sesso = new attributo("sesso");
		$this->attr[] = $this->attivo = new attributo("attivo", "bool_int");
		$this->attr[] = $this->dtlast = new attributo("dtlast", "data_time");
		$this->attr[] = $this->ip = new attributo("ip");
		$this->attr[] = $this->tipo = new attributo("tipo", "int");
		$this->attr[] = $this->cookie_id = new attributo("cookie_id");
		$this->attr[] = $this->cookie_expire = new attributo("cookie_expire", "data_time");
		$this->attr[] = $this->onetime = new attributo("onetime");
		$this->attr[] = $this->onetime_expire = new attributo("onetime_expire", "data_time");
		$this->attr[] = $this->scrittura = new attributo("scrittura", "bool_int");

		if($id > 0){

			$sql = "SELECT * FROM users WHERE id_user = ?";
			$dati_query = array($this->id);

			$arr = $this->connessione()->query_risultati($sql, $dati_query);
			$this->Close();

			if(count($arr) > 0){

				$this->exist = true;

				$this->nome->set_valore($arr[0]["nome"]);
				$this->cognome->set_valore($arr[0]["cognome"]);
				$this->mail->set_valore($arr[0]["mail"]);
				$this->cellulare->set_valore($arr[0]["cellulare"]);
				$this->username->set_valore($arr[0]["username"]);
				$this->pw->set_valore($arr[0]["pw"]);
				$this->sesso->set_valore($arr[0]["sesso"]);
				$this->attivo->set_valore($arr[0]["attivo"]);
				$this->dtlast->set_valore($arr[0]["dtlast"]);
				$this->ip->set_valore($arr[0]["ip"]);
				$this->tipo->set_valore($arr[0]["tipo"]);
				$this->cookie_id->set_valore($arr[0]["cookie_id"]);
				$this->cookie_expire->set_valore($arr[0]["cookie_expire"]);
				$this->onetime->set_valore($arr[0]["onetime"]);
				$this->onetime_expire->set_valore($arr[0]["onetime_expire"]);
				$this->scrittura->set_valore($arr[0]["scrittura"]);
		
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
				$log->set_nome_azione("delete users");
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

				$sql = "INSERT INTO users (nome, cognome, mail, cellulare, username, pw, sesso, attivo, dtlast, ip, tipo, cookie_id, cookie_expire, onetime, onetime_expire, scrittura) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

				$dati_query = array(
								$this->nome->get_valore(99), 
								$this->cognome->get_valore(99), 
								$this->mail->get_valore(99), 
								$this->cellulare->get_valore(99), 
								$this->username->get_valore(99), 
								$this->pw->get_valore(99), 
								$this->sesso->get_valore(99), 
								$this->attivo->get_valore(99), 
								$this->dtlast->get_valore(99), 
								$this->ip->get_valore(99), 
								$this->tipo->get_valore(99), 
								$this->cookie_id->get_valore(99), 
								$this->cookie_expire->get_valore(99), 
								$this->onetime->get_valore(99), 
								$this->onetime_expire->get_valore(99), 
								$this->scrittura->get_valore(99)
							);

				
				$this->connessione()->esegui_query($sql, $dati_query);
				
				$this->id = $this->connessione()->ultimo_id();


				/* -----------------------------
				 * LOG
				 *------------------------------------*/
				if($make_log === true){
					
					$log = new classe_log();

					if($log->enabled()){
						$log->set_nome_azione("insert users");
						$log->set_tipo_azione("insert");
						$log->set_info("id: ".$this->id); //informazioni aggiuntive
						
						$log->inserisci();
					}
				}
				/*--------------------------------------*/

				$this->Close();


			}else{//aggiorniamo l'oggetto

				$sql = "UPDATE users SET nome = ?, cognome = ?, mail = ?, cellulare = ?, username = ?, pw = ?, sesso = ?, attivo = ?, dtlast = ?, ip = ?, tipo = ?, cookie_id = ?, cookie_expire = ?, onetime = ?, onetime_expire = ?, scrittura = ?"
						." WHERE id_user = ?";

				$dati_query = array(
								$this->nome->get_valore(99), 
								$this->cognome->get_valore(99), 
								$this->mail->get_valore(99), 
								$this->cellulare->get_valore(99), 
								$this->username->get_valore(99), 
								$this->pw->get_valore(99), 
								$this->sesso->get_valore(99), 
								$this->attivo->get_valore(99), 
								$this->dtlast->get_valore(99), 
								$this->ip->get_valore(99), 
								$this->tipo->get_valore(99), 
								$this->cookie_id->get_valore(99), 
								$this->cookie_expire->get_valore(99), 
								$this->onetime->get_valore(99), 
								$this->onetime_expire->get_valore(99), 
								$this->scrittura->get_valore(99), 
								$this->id
							);

				

				$this->connessione()->esegui_query($sql, $dati_query);
				

				/* -----------------------------
				 * LOG
				 *------------------------------------*/
				if($make_log === true){
					
					$log = new classe_log();

					if($log->enabled()){
						$log->set_nome_azione("update users");
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
	 * @param string $nome
	 * @return boolean true se il valore e' corretto, false altrimenti
	 */
	public function set_nome($nome) {
		$this->nome->set_valore($nome);
		return $this->nome->is_corretto();
	}
	
	/**
	 * 
	 * @param string $cognome
	 * @return boolean true se il valore e' corretto, false altrimenti
	 */
	public function set_cognome($cognome) {
		$this->cognome->set_valore($cognome);
		return $this->cognome->is_corretto();
	}
	
	/**
	 * 
	 * @param string $mail
	 * @return boolean true se il valore e' corretto, false altrimenti
	 */
	public function set_mail($mail) {
		$this->mail->set_valore($mail);
		return $this->mail->is_corretto();
	}
	
	/**
	 * 
	 * @param string $cellulare
	 * @return boolean true se il valore e' corretto, false altrimenti
	 */
	public function set_cellulare($cellulare) {
		$this->cellulare->set_valore($cellulare);
		return $this->cellulare->is_corretto();
	}
	
	/**
	 * 
	 * @param string $username
	 * @return boolean true se il valore e' corretto, false altrimenti
	 */
	public function set_username($username) {
		$this->username->set_valore($username);
		return $this->username->is_corretto();
	}
	
	/**
	 * 
	 * @param string $pw
	 * @return boolean true se il valore e' corretto, false altrimenti
	 */
	public function set_pw($pw) {
		$this->pw->set_valore($pw);
		return $this->pw->is_corretto();
	}
	
	/**
	 * 
	 * @param string $sesso
	 * @return boolean true se il valore e' corretto, false altrimenti
	 */
	public function set_sesso($sesso) {
		$this->sesso->set_valore($sesso);
		return $this->sesso->is_corretto();
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
	 * @param string $dtlast
	 * @return boolean true se il valore e' corretto, false altrimenti
	 */
	public function set_dtlast($dtlast) {
		$this->dtlast->set_valore($dtlast);
		return $this->dtlast->is_corretto();
	}
	
	/**
	 * 
	 * @param string $ip
	 * @return boolean true se il valore e' corretto, false altrimenti
	 */
	public function set_ip($ip) {
		$this->ip->set_valore($ip);
		return $this->ip->is_corretto();
	}
	
	/**
	 * 
	 * @param string $tipo
	 * @return boolean true se il valore e' corretto, false altrimenti
	 */
	public function set_tipo($tipo) {
		$this->tipo->set_valore($tipo);
		return $this->tipo->is_corretto();
	}
	
	/**
	 * 
	 * @param string $cookie_id
	 * @return boolean true se il valore e' corretto, false altrimenti
	 */
	public function set_cookie_id($cookie_id) {
		$this->cookie_id->set_valore($cookie_id);
		return $this->cookie_id->is_corretto();
	}
	
	/**
	 * 
	 * @param string $cookie_expire
	 * @return boolean true se il valore e' corretto, false altrimenti
	 */
	public function set_cookie_expire($cookie_expire) {
		$this->cookie_expire->set_valore($cookie_expire);
		return $this->cookie_expire->is_corretto();
	}
	
	/**
	 * 
	 * @param string $onetime
	 * @return boolean true se il valore e' corretto, false altrimenti
	 */
	public function set_onetime($onetime) {
		$this->onetime->set_valore($onetime);
		return $this->onetime->is_corretto();
	}
	
	/**
	 * 
	 * @param string $onetime_expire
	 * @return boolean true se il valore e' corretto, false altrimenti
	 */
	public function set_onetime_expire($onetime_expire) {
		$this->onetime_expire->set_valore($onetime_expire);
		return $this->onetime_expire->is_corretto();
	}
	
	/**
	 * 
	 * @param string $scrittura
	 * @return boolean true se il valore e' corretto, false altrimenti
	 */
	public function set_scrittura($scrittura) {
		$this->scrittura->set_valore($scrittura);
		return $this->scrittura->is_corretto();
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
	
	public function get_cognome($formattazione_dato = 1) {
		return $this->cognome->get_valore($formattazione_dato);
	}
	
	public function get_mail($formattazione_dato = 1) {
		return $this->mail->get_valore($formattazione_dato);
	}
	
	public function get_cellulare($formattazione_dato = 1) {
		return $this->cellulare->get_valore($formattazione_dato);
	}
	
	public function get_username($formattazione_dato = 1) {
		return $this->username->get_valore($formattazione_dato);
	}
	
	public function get_pw($formattazione_dato = 1) {
		return $this->pw->get_valore($formattazione_dato);
	}
	
	public function get_sesso($formattazione_dato = 1) {
		return $this->sesso->get_valore($formattazione_dato);
	}
	
	public function get_attivo($formattazione_dato = 1) {
		return $this->attivo->get_valore($formattazione_dato);
	}
	
	public function get_dtlast($formattazione_dato = 1) {
		return $this->dtlast->get_valore($formattazione_dato);
	}
	
	public function get_ip($formattazione_dato = 1) {
		return $this->ip->get_valore($formattazione_dato);
	}
	
	public function get_tipo($formattazione_dato = 1) {
		return $this->tipo->get_valore($formattazione_dato);
	}
	
	public function get_cookie_id($formattazione_dato = 1) {
		return $this->cookie_id->get_valore($formattazione_dato);
	}
	
	public function get_cookie_expire($formattazione_dato = 1) {
		return $this->cookie_expire->get_valore($formattazione_dato);
	}
	
	public function get_onetime($formattazione_dato = 1) {
		return $this->onetime->get_valore($formattazione_dato);
	}
	
	public function get_onetime_expire($formattazione_dato = 1) {
		return $this->onetime_expire->get_valore($formattazione_dato);
	}
	
	public function get_scrittura($formattazione_dato = 1) {
		return $this->scrittura->get_valore($formattazione_dato);
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