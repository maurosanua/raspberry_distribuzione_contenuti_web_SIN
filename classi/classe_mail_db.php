<?php

class classe_mail_db {
	
	
	public static $versione = "2.0";
	public static $changelog = "
2.0	--	Verifica dell'oggetto connessione su piu' classi e creazione nuove connessioni con la nuova classe_DB

1.0	--	Versione base
	";
	

	protected $is_connesso = 0;
	protected $destroy_conn = 0;
	protected $db_conn = null;
	protected $db_reset = false;

	private $id = 0;
	
	private $exist = false;

	private $oggetto = null;
	private $testo = null;
	private $mittente = null;
	private $destinatario = null;
	private $dt_insert = null;
	private $dt_sent = null;
	private $inviata = null;
	private $insert_by = null;
	private $insert_page = null;

	private $errore = false;
	private $attr = array();


	public function __construct($id = 0) {

		if(!isset($id) || !is_numeric($id)){$id = 0;}
		$this->id = $id;


		$this->attr[] = $this->oggetto = new attributo("oggetto");
		$this->attr[] = $this->testo = new attributo("testo");
		$this->attr[] = $this->mittente = new attributo("mittente");
		$this->attr[] = $this->destinatario = new attributo("destinatario");
		$this->attr[] = $this->dt_insert = new attributo("dt_insert", "data_time");
		$this->attr[] = $this->dt_sent = new attributo("dt_sent", "data_time");
		$this->attr[] = $this->inviata = new attributo("inviata", "int");
		$this->attr[] = $this->insert_by = new attributo("insert_by", "int");
		$this->attr[] = $this->insert_page = new attributo("insert_page");
		
		$this->inviata->set_valore(0);

		if($id > 0){

			$sql = "SELECT * FROM mail WHERE id_mail = ?";
			$dati_query = array($this->id);

			$arr = $this->connessione()->query_risultati($sql, $dati_query);
			$this->Close();

			if(count($arr) > 0){

				$this->exist = true;

				$this->oggetto->set_valore($arr[0]["oggetto"]);
				$this->testo->set_valore($arr[0]["testo"]);
				$this->mittente->set_valore($arr[0]["mittente"]);
				$this->destinatario->set_valore($arr[0]["destinatario"]);
				$this->dt_insert->set_valore($arr[0]["dt_insert"]);
				$this->dt_sent->set_valore($arr[0]["dt_sent"]);
				$this->inviata->set_valore($arr[0]["inviata"]);
				$this->insert_by->set_valore($arr[0]["insert_by"]);
				$this->insert_page->set_valore($arr[0]["insert_page"]);

			}else{
				throw new Exception("Element Not Found", -1); //indicare qui l'eccezione specifica da lanciare
			}	

		}	
	}



	/*********************************
	 * aggiungiamo qua i metodi ad hoc
	 */







	/*****************
	 * metodi standard
	 */


	//creiamo il metodo delete di default come privato, in modo che ci si ricordi di sistemarlo quando serve
	private function delete(){
	


		/* -----------------------------
		 * LOG
		 *------------------------------------*/
		$log = new classe_log();

		if($log->enabled()){
			$log->set_nome_azione("delete mail");
			$log->set_tipo_azione("delete");
			$log->set_info("id: ".$this->id); //informazioni aggiuntive

			$log->inserisci();
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
	 * 
	 * @return boolean esito del salvataggio
	 */
	public function salva(){

		if($this->verifica_obbligatori() && !$this->errore){

			//andiamo a inserire le informazioni nel db
			
			
			//al momento non e' prevista la modifica di questo tipo di oggetti
			if($this->id == 0 || 1==1){
				
				
				global $user_login_obj;
				if (!isset($user_login_obj)){
					$user_login_obj = new user(2);
				}
				
				$this->set_insert_by($user_login_obj->id_user());

				$sql = "INSERT INTO mail (oggetto, testo, mittente, destinatario, dt_insert, dt_sent, inviata, insert_by, insert_page, send_after) VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, ?, NOW())";

				$dati_query = array(
								$this->oggetto->get_valore(), 
								$this->testo->get_valore(), 
								$this->mittente->get_valore(), 
								$this->destinatario->get_valore(), 
								//$this->dt_insert->get_valore(), 
								$this->dt_sent->get_valore(), 
								$this->inviata->get_valore(), 
								$this->insert_by->get_valore(), 
								$this->insert_page->get_valore()
							);

				
				$this->connessione()->esegui_query($sql, $dati_query);
				$this->Close();


				$this->id = $this->connessione()->ultimo_id();


				/* -----------------------------
				 * LOG
				 *------------------------------------*/
				$log = new classe_log();

				if($log->enabled()){
					$log->set_nome_azione("insert mail");
					$log->set_tipo_azione("insert");
					$log->set_info("id: ".$this->id); //informazioni aggiuntive

					$log->inserisci();
				}
				/*--------------------------------------*/



			}else{//aggiorniamo l'oggetto

				$sql = "UPDATE mail SET oggetto = ?, testo = ?, mittente = ?, destinatario = ?, dt_sent = ?, inviata = ?, insert_by = ?, insert_page = ?"
						." WHERE id_mail=".$this->id;

				$dati_query = array(
								$this->oggetto->get_valore(), 
								$this->testo->get_valore(), 
								$this->mittente->get_valore(), 
								$this->destinatario->get_valore(), 
								
								$this->dt_sent->get_valore(), 
								$this->inviata->get_valore(), 
								$this->insert_by->get_valore(), 
								$this->insert_page->get_valore()
							);

				

				$this->connessione()->esegui_query($sql, $dati_query);
				$this->Close();



				/* -----------------------------
				 * LOG
				 *------------------------------------*/
				$log = new classe_log();

				if($log->enabled()){
					$log->set_nome_azione("update mail");
					$log->set_tipo_azione("update");
					$log->set_info("id: ".$this->id); //informazioni aggiuntive

					$log->inserisci();
				}
				/*--------------------------------------*/


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
	 * @param string $oggetto
	 * @return boolean true se il valore ? corretto, false altrimenti
	 */
	public function set_oggetto($oggetto) {
		$this->oggetto->set_valore($oggetto);
		return $this->oggetto->is_corretto();
	}
	
	/**
	 * 
	 * @param string $testo
	 * @return boolean true se il valore ? corretto, false altrimenti
	 */
	public function set_testo($testo) {
		$this->testo->set_valore($testo);
		return $this->testo->is_corretto();
	}
	
	/**
	 * 
	 * @param string $mittente
	 * @return boolean true se il valore ? corretto, false altrimenti
	 */
	public function set_mittente($mittente) {
		$this->mittente->set_valore($mittente);
		return $this->mittente->is_corretto();
	}
	
	/**
	 * 
	 * @param string $destinatario
	 * @return boolean true se il valore ? corretto, false altrimenti
	 */
	public function set_destinatario($destinatario) {
		$this->destinatario->set_valore($destinatario);
		return $this->destinatario->is_corretto();
	}
	
	/**
	 * 
	 * @param string $dt_insert
	 * @return boolean true se il valore ? corretto, false altrimenti
	 */
	public function set_dt_insert($dt_insert) {
		$this->dt_insert->set_valore($dt_insert);
		return $this->dt_insert->is_corretto();
	}
	
	/**
	 * 
	 * @param string $dt_sent
	 * @return boolean true se il valore ? corretto, false altrimenti
	 */
	public function set_dt_sent($dt_sent) {
		$this->dt_sent->set_valore($dt_sent);
		return $this->dt_sent->is_corretto();
	}
	
	/**
	 * 
	 * @param string $inviata
	 * @return boolean true se il valore ? corretto, false altrimenti
	 */
	public function set_inviata($inviata) {
		$this->inviata->set_valore($inviata);
		return $this->inviata->is_corretto();
	}
	
	/**
	 * 
	 * @param string $insert_by
	 * @return boolean true se il valore ? corretto, false altrimenti
	 */
	public function set_insert_by($insert_by) {
		$this->insert_by->set_valore($insert_by);
		return $this->insert_by->is_corretto();
	}
	
	/**
	 * 
	 * @param string $insert_page
	 * @return boolean true se il valore ? corretto, false altrimenti
	 */
	public function set_insert_page($insert_page) {
		$this->insert_page->set_valore($insert_page);
		return $this->insert_page->is_corretto();
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
	
	public function get_oggetto($formattazione_dato = 1) {
		return $this->oggetto->get_valore($formattazione_dato);
	}
	
	public function get_testo($formattazione_dato = 1) {
		return $this->testo->get_valore($formattazione_dato);
	}
	
	public function get_mittente($formattazione_dato = 1) {
		return $this->mittente->get_valore($formattazione_dato);
	}
	
	public function get_destinatario($formattazione_dato = 1) {
		return $this->destinatario->get_valore($formattazione_dato);
	}
	
	public function get_dt_insert($formattazione_dato = 1) {
		return $this->dt_insert->get_valore($formattazione_dato);
	}
	
	public function get_dt_sent($formattazione_dato = 1) {
		return $this->dt_sent->get_valore($formattazione_dato);
	}
	
	public function get_inviata($formattazione_dato = 1) {
		return $this->inviata->get_valore($formattazione_dato);
	}
	
	public function get_insert_by($formattazione_dato = 1) {
		return $this->insert_by->get_valore($formattazione_dato);
	}
	
	public function get_insert_page($formattazione_dato = 1) {
		return $this->insert_page->get_valore($formattazione_dato);
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