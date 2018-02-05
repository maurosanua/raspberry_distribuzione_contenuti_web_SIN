<?php



class classe_opentok extends OpenTok\OpenTok{
	
	private $connesso=0;
	private $conn_destroy=0;
	private $conn=null;
	
	private $errore = false;
	private $message_error = "";


	private $apiKey;
	private $apiSecret;
	
	/**
	 *
	 * @var \OpenTok\Session
	 */
	private $sessione_obj = null;
	
	/**
	 *
	 * @var \classe_token
	 */
	private $arr_token = array();
	
	private $default_token_options = null;
			
	

	function __construct($api_key = "", $api_secret = "", $options = array()) {
		
		if(strlen($api_key) > 0){
			$this->apiKey = $api_key;
		}else{
			$this->apiKey = API_KEY;
		}
		
		if(strlen($api_secret) > 0){
			$this->apiSecret = $api_secret;
		}else{
			$this->apiSecret = API_SECRET;
		}
		
		$this->default_token_options = array(
											'role'       => OpenTok\Role::MODERATOR,
											'expireTime' => time()+(30 * 24 * 60 * 60) //30 giorni
										);
		
		parent::__construct($this->apiKey, $this->apiSecret, $options);
		
	}

	
	
	/**
	 * crea una nuova sessione e la assegna all'oggetto corrente
	 * 
	 * @param bool $overwrite indica se ricreare la sessione anche nel caso essa esista
	 */
	public function crea_sessione($overwrite = false){
		
		if($overwrite || !isset($this->sessione_obj)){
			$this->sessione_obj = $this->createSession();
		}		
	}
	
	
	
	/**
	 * crea un token e lo aggiunge all'elenco dei token dell'oggetto corrente
	 * 
	 * @param array $options eventuali opzioni aggiuntive (ruolo, scadenza, informazioni...)
	 */
	public function genera_token($options = null){
		
		//se non c'e' una sessione valida, creiamone una nuova
		if(!isset($this->sessione_obj)){
			$this->crea_sessione();
		}
		
		if(!isset($options)){
			$options = $this->default_token_options;
		}
		
		$token_obj = new classe_token();
		
//		var_dump($options);
//		die();
		
		//settiamo le informazioni
		foreach ($options as $key => $value){
			if($key == "role"){ $token_obj->role = $value;}
			if($key == "expireTime"){ $token_obj->set_expireTime($value);}
			if($key == "data"){ $token_obj->info = $value;}
		}
		
		$token_obj->id_token = $this->generateToken($this->sessione_obj->getSessionId(), $options);
		
		$this->arr_token[] = $token_obj;
		
	}
	
	
	
	/**
	 * restituisce il numero di sessioni valide a partire da un dato giorno
	 * 
	 * @param int $giorni numero di giorni da aggiungere alla data odierna per valutare i token validi
	 * @return int numero di sessioni valide quel dato giorno
	 */
	public function n_sessioni_valide($giorni = 1){
		
		$now = new classe_data();
		$now->add_days($giorni);
		
		$n_token = 0;
		
		$sql = "SELECT COUNT(DISTINCT(sessione)) as num FROM sessioni_opentok JOIN token ON id_sessione=rif_sessione WHERE sessioni_opentok.attivo = 1 AND token.attivo = 1 AND dtExpire > ?";
		$dati_query = array($now->print_data("Y-m-d H:i:s"));
		
		$arr = $this->connessione()->query_risultati($sql, $dati_query);
		$this->Close();
		
		if(count($arr) > 0){
			$n_token = $arr[0]["num"];
		}
		
		return $n_token;
	}
	
	
	
	/**
	 * disabilita i token in scadenza
	 * 
	 * @param int $giorni numero di giorni entro cui considerare "in scadenza" il token
	 */
	public function spegni_token_scadenza($giorni = 1){
		
		$now = new classe_data();
		$now->add_days($giorni);
		
		$sql = "SELECT id_token FROM sessioni_opentok JOIN token ON id_sessione=rif_sessione WHERE sessioni_opentok.attivo = 1 AND token.attivo = 1 AND dtExpire <= ?";
		$dati_query = array($now->print_data("Y-m-d H:i:s"));
		
		$arr = $this->connessione()->query_risultati($sql, $dati_query);
		$this->Close();
		
		foreach ($arr as $value){
			$sql = "UPDATE token SET attivo = 0 WHERE id_token = ?";
			$dati_query = array($value["id_token"]);
			
			$this->connessione()->esegui_query($sql, $dati_query);
			$this->Close();
		}
	}


	
	
	
	
	
	public function clear(){
		$this->sessione_obj = null;
		$this->arr_token = array();
		
		$this->errore = false;
		$this->message_error = "";
	}
	
	
	/**
	 * salva i token associati alla sessione corrente su DB
	 * 
	 * @return boolean esito del salvataggio
	 */
	public function salva(){
		
		/*
		 * facciamo qualche controllo
		 */
		
		if(!isset($this->sessione_obj)){
			$this->errore = true;
			$this->message_error .= "<p>Sessione non valida o non inizializzata correttamente";
		}
		
		if(count($this->arr_token) == 0){
			$this->errore = true;
			$this->message_error .= "<p>Nessun Token generato";
		}
		
		
		if(!$this->errore){
			
			$sql = "INSERT INTO sessioni_opentok (sessione, attivo, evento) VALUES (?, ?, ?)";
			$dati_query = array($this->sessione_obj->getSessionId(), 1, -1);

			$this->connessione()->esegui_query($sql, $dati_query);
			
			$id_sessione = $this->connessione()->ultimo_id();
			$this->Close();
			
			//andiamo a salvare tutti i token su db
			foreach ($this->arr_token as $token){
				
				$sql = "INSERT INTO token (rif_sessione, token, dtExpire, attivo) VALUES (?, ?, ?, ?)";
				$dati_query = array($id_sessione, $token->id_token, $token->get_scadenza(), 1);
				
				$this->connessione()->esegui_query($sql, $dati_query);
				$this->Close();
				
			}
			
			return true;
		}else{
			return false;
		}
		
	}
	
	
	
	
	/**
	 * 
	 * @global null $conn
	 * @return \DB_PDO
	 */
	private function connessione(){
		//se esiste gi?? una connessione utilizza quella, altrimenti ne crea una nuova
	   GLOBAL $conn;

	   if (isset($conn)){
		   return $conn;

	   }else{

		   if($this->conn_destroy==1 || !isset($this->conn)){              

				//$conn = null;
				$this->conn = new classe_DB();

				$this->connesso=1;
				$this->conn_destroy=0;
				return $this->conn;

		   }else{               
			   return $this->conn;
		   }           
	   }        
	}

	private function Close() {

		if ($this->connesso==1){

			//chiudiamo la connessione
			$this->connessione()->Close();
			$this->conn_destroy=1;
			$this->connesso=0;
		}
	}
	
}



class classe_token {
	
	public $id_token = "";
	public $role = "";
	private $expireTime = 0;
	public $info = "";
	
	
	/**
	 * imposta la scadenza del token (in formato numerico)
	 * 
	 * @param int $time
	 */
	public function set_expireTime($time){
		$this->expireTime = $time;
	}
	
	/**
	 * restituisce la scadenza del token in un formato sql compatibile
	 * 
	 * @return string [formato: yyyy-mm-dd hh:ii:ss]
	 */
	public function get_scadenza(){
		
		try{
			$data = new classe_data(date('Y-m-d H:i:s', $this->expireTime));
		}catch(Exception $e){
			$data = new classe_data("1970-01-01 00:00:00");
		}
		
		return $data->print_data("Y-m-d H:i:s");
	}
}
?>