<?php

class classe_log {

	public static $versione = "2.2";
	public static $changelog = "
2.2	--	Inibizione compressione per PostgreSQL

2.1	--	Introduzione dello storico dei dati in fase di update (salvataggio dati in un blob compresso)
		ATTENZIONE! Da qui in avanti e' necessario modificare anche la tabella log aggiungendo il campo old_data come MEDIUMBLOB

2.0	--	Verifica dell'oggetto connessione su piu' classi e creazione nuove connessioni con la nuova classe_DB

1.2	--	Costruttore modificato per accettare l'id dell'utente senza prenderlo dalla classe user (utile per il log da cookie, altrimenti si va in loop)

1.1	--	A seconda del tipo di DB usa la funzione NOW() o GetDate() per impostare l'ora corrente

1.0	--	Versione base
";
	
	
	protected $is_connesso = 0;
	protected $destroy_conn = 0;
	protected $db_conn = null;
	protected $db_reset = false;

	private $nome_azione = "";
	private $tipo_azione = "";
	private $url = "";
	private $utente = 0;
	private $ip = "";
	private $dtLog = "1970-01-01 00:00:00";
	private $info = "";
	private $old_data = null;


	private $enabled = true;
	
	private $nome_tabella = "log";



	public function __construct($utente = 0) {
		
		if(!is_numeric($utente)){$utente = 0;}
		$this->utente = $utente;
		
		if($this->enabled){
			
			if($this->utente == 0){ //recuperiamo le informazioni sull'utente loggato, a meno che non l'abbiamo passato a mano
				
				global $user_login_obj;
				if(!isset($user_login_obj)){$user_login_obj = new user(2, $check_tipo=FALSE);}

				$this->utente = $user_login_obj->id_user();
			}


			//recuperiamo l'url
			if(isset($_SERVER["REQUEST_URI"]) && is_string($_SERVER["REQUEST_URI"])){
				$this->url = (string)$_SERVER["REQUEST_URI"];
			}else{
				$this->url = "Errore! \$_SERVER[\"REQUEST_URI\"] non disponibile.";
			}

			//recuperiamo l'ip
			if(isset($_SERVER['REMOTE_ADDR']) && is_string($_SERVER['REMOTE_ADDR'])){
				$this->ip = (string)$_SERVER['REMOTE_ADDR'];
			}else{
				$this->ip = "IP not available";
			}
		}
	}

	
	/**
	 * salva i dati precedenti alla modifica nel campo old_data compressi con gzip
	 * 
	 * @param array $old_data
	 */
	public function set_old_data($old_data){
		
		if(is_array($old_data) && count($old_data) > 0){
			$json = json_encode($old_data);
			$data = gzencode($json, 9);
			if(defined("DB_TYPE")&&DB_TYPE == "PGSQL"){
				$data = $json;
			}
			$this->old_data = $data;
		}
		
	}
	
	

	/**
	 * inserisce il log nella tabella opportuna
	 */
	public function inserisci(){
		
		if($this->enabled){
			
			if(defined('DB_TYPE') && DB_TYPE == "MSSQL"){ //invochiamo la funzione equivalente al NOW() per MS SQL
				$sql = "INSERT INTO ".$this->nome_tabella." (nome_azione, tipo_azione, url, utente, ip, dtLog, info, old_data) VALUES (?, ?, ?, ?, ?, GetDate(), ?, ?)";
			}else{
				$sql = "INSERT INTO ".$this->nome_tabella." (nome_azione, tipo_azione, url, utente, ip, dtLog, info, old_data) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?)";
			}

			$dati_query = array(
							$this->nome_azione, 
							$this->tipo_azione, 
							$this->url, 
							$this->utente, 
							$this->ip, 
							//$this->dtLog, 
							$this->info,
							$this->old_data
						);


			$this->connessione()->esegui_query($sql, $dati_query);
			$this->Close();
		}
	}
		

	/******************************
	 * setter
	 ******************************/
	
	public function set_nome_azione($nome_azione) {
		$this->nome_azione = $nome_azione;
	}

	public function set_tipo_azione($tipo_azione) {
		$this->tipo_azione = $tipo_azione;
	}

	public function set_url($url) {
		$this->url = $url;
	}

	public function set_utente($utente) {
		$this->utente = $utente;
	}

	public function set_ip($ip) {
		$this->ip = $ip;
	}

	public function set_dtLog($dtLog) {
		$this->dtLog = $dtLog;
	}

	public function set_info($info) {
		$this->info = $info;
	}

	

	

	/**
	 * restituisce true se i log sono attivi, false altrimenti
	 * 
	 * @return boolean funzionalita' di log abilitata o meno
	 */
	public function enabled(){
		return $this->enabled;
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