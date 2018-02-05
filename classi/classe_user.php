<?php 

class user extends base_user{
	
	public static $versione = "5.1";
	public static $changelog = "

5.1	--	Aggiunta metodo is_login_AD() per sapere se il sistema lavora in modalita' Active Directory o meno
	--	recupero della modalita' di login dalla costante definita in conf.php

5.0	--	Riscrittura completa dell'autenticazione con onetime e notifica accesso per mail!
		ATTENZIONE!!! Da qui in avanti questa classe e' incompatibile con i siti precedenti e per funzionare necessita della nuova versione di login.php, script/vf_login.php

4.5	--	Aggiunta della funzione per rigenerare la sessione dopo il login

4.4	--	Metodo per verificare la complessita' della password; al momento viene controllata solo la lunghezza della password, di default 5 caratteri

4.3	--	Metodo per reimpostare la password in sessione e per verificare se l'utente e' amministratore

4.2	--	Aggiunta metodo utente_obj che restituisce l'oggetto di classe_utenti relativo all'utente loggato
	--	ATTENZIONE! E' necessario includere la classe_utenti, in versione base o ad hoc a seconda del sito

4.1	--	Integrazione autenticazione su Active Directory
		ATTENZIONE! Per poter usare l'autenticazione su AD e' necessario includere la classe_ldap nel progetto
	--	Introduzione del parametro 5 per il costruttore che permette di eseguire il login passando direttamente user e password al costruttore

4.0	--	Nuova classe user che estende quella base, in modo da poter definire qui eventuali metodi per i permessi ad hoc;
	--	Introduzione del parametro registra_errori_login che permette di specificare se tenere traccia nei log anche dei tentativi di login errati
	--	Possibilita' di gestire le impostazioni della onetime password direttamente dalla classe estesa
	--	Correzione print_form per rgestire correttamente la onetime o il login normale
	--	Correzioni varie sulla presenza o meno delle costanti per i cookie e accesso alle variabili di sistema

3.7	--	Introduzione della onetime password: se abilitata, manda la mail con la password di secondo livello
		ATTENZIONE! Per poter usare la onetime password e' necessario includere anche il file script/onetime_login.php e includere le jquery nella pagina che include il login.
		Modifica del metodo logout per supportare il redirect o meno (di default attivo)
		Rentroduzione del log per le azioni di login da form, json, e js (oltre che da cookie)

3.6	--	Correzioni campi di default per le nuove tabelle utenti (campo pw e attivo invece che password e locked).
		Introduzione del parametro 4 nel costruttore che consente di recuperare i dati in POST senza effettuare redirect alla fine (utile per il login in JavaScript)

3.5	--	Utilizzo dei campi n_campo_nome e n_campo_cognome in tutti i punti in cui servono
		Introduzione della funzione distruggi_sessione per cancellare la sessione

3.4	--	Modifica retrieve_cookie in modo che non vada in loop in fase di login nel tentativo di scrivere i log
		ATTENZIONE! Da questa versione in avanti e' necessario che classe_log.php sia almeno alla versione 1.2

3.3	--	Parametrizzazione a inizio file dei campi nome e cognome della tabella users (oltre che user e pw gia' presenti nella versione precedente)
	--	Rimozione parti inutili

3.2	--	ATTENZIONE! Da qui in avanti e' ***NECESSARIO*** modificare anche il file login.php alla versione corrente
	--	Rifatta utilizzando un solo punto di verifica credenziali con il metodo verifica_credenziali() (ad eccezione dei cookie che hanno comunque una loro query)
	--	Possibilita' di definire piu' punti di accesso che di fatto impostano gli attributi con cui verra' verificata l'autenticazione

2.1	--	Versione base della classe";
	
	public static $lunghezza_pw = 5;
	
	
	/**
	 * ritorna TRUE se il sistema e' impostato per l'autenticazione su Active Directory, FALSE altrimenti
	 * 
	 * @return bool
	 */
	public function is_login_AD(){
		return $this->login_AD;
	}
	
	function __construct($fun = 0, $credenziali = array(), $check_onetime = false) {
		

		$this->registra_errori_login = true;		//se impostato a TRUE logga anche i tentativi errati di login
		$this->onetime_length = 6;				//definisce la lunghezza della onetime password (default: 10 caratteri)
		$this->onetime_duration = 5;				//definisce la validita' (in minuti) della onetime password, passati i quali non e' piu' usabile (defalut: 5 minuti)
		
		$this->team_enabled = false;
		
		$this->login_AD = (defined("LOGIN_AD") && is_bool(LOGIN_AD) ? LOGIN_AD : false);		//se TRUE imposta il login su Active Directory invece che sul DB specifico
																								//se c'e' il parametro in conf, usiamo quello, altrimenti settiamolo a mano a false (per retrocompatibilita')
		
		parent::__construct($fun, $credenziali, $check_onetime);
		
		global $bypass_redirect_licenza;
		if(!isset($bypass_redirect_licenza)){$bypass_redirect_licenza = false;}
		
		$scaduto = $this->licenza_scaduta();
		
		if($scaduto && !$bypass_redirect_licenza && $fun == 0){
			header("Location: licenza.php");
			die();
		}
	}
	
	
	private $utente_obj = null;
	
	/**
	 * restituisce l'oggetto di classe_utenti dell'utente loggato
	 * 
	 * @return \classe_utenti
	 */
	public function utente_obj($reload=false){
		
		if(!isset($this->utente_obj) || $reload){
			
			try{
				$this->utente_obj = new classe_utenti($this->id_user());
			} catch (Exception $ex) {
				$this->utente_obj = new classe_utenti();
			}
		}
		
		return $this->utente_obj;
	}
	
	
	/**
	 * verifica che l'utente loggato sia (admin)
	 * 
	 * @return boolean
	 */
	public function check_admin(){
		
		$is_admin = false;
		
		if($this->get_tipo() == 1){
			$is_admin = true;
		}
		
		return $is_admin;
	}
		
	public function can_write(){
		if($this->utente_obj()->get_tipo() == 1){
			return TRUE;
		}else{
			return FALSE;
		}
	}




	/**
	 * Questo funzione verifica la complessita della password inserita,
	 * in particolare verifica:
	 * - La lunghezza, definita nella variabile statica $lunghezza_pw;
	 * - I caratteri ammessi. Attualmente sono ammessi i caratteri A-Z minuscoli e maiuscoli, numeri 0-9 e questi caratteri speciali .,-_+-*!@#
	 * 
	 * Ritorna TRUE se i controlli sono corretti, altrimenti ritorna un array contenete i messaggi di errori.
	 * 
	 * @param type $pw
	 * @return boolean|string
	 */
	public static function check_complessita_pw($pw){
		$esito = TRUE;
		
		$errori = array();
		
		$pw = trim($pw);
		
		//1. Controllo lunghezza password
		if(strlen($pw) < self::$lunghezza_pw){
			$esito = FALSE;
			$errori[] = 'La password deve essere lunga almeno '.self::$lunghezza_pw.' caratteri';
		}
		
		//2. Controllo caratteri ammessi
		//	 Sono ammessi i caratteri A-Z minuscoli e maiuscoli, numeri 0-9 e questi caratteri speciali .,-_+-*!@#
		$pattern = '/^[A-Za-z0-9\.\,\-\_\+\-\*\!\@\#]+$/';
		if(preg_match($pattern, $pw) !== 1){
			$esito = FALSE;
			$errori[] = 'Utilizza solo lettere (a-zA-Z), numeri o i seguenti caratteri speciali . , - _ + - * ! @ #';
		}
		
		if($esito === TRUE){
			return $esito;
		}else{
			return $errori;
		}
		
	}
	
	
	public function licenza_scaduta(){
//		$oggi = new classe_data();
//		$scadenza = $this->utente_obj()->team_obj()->get_data_scadenza(0);
//		
//
//		if(strlen($scadenza) > 10){
//			$scadenza = substr($scadenza, 0, 10);
//		}
//		
//		if($oggi->print_data("Y-m-d") >= $scadenza && $scadenza !== null){
//			return true;
//		}else{
//			return false;
//		}
		
		return false;
	}
	
	
	private $ip_esclusi = null;
	
	/**
	 * restituisce la lista di ip da escludere per ogni team
	 * 
	 * @return array di stringhe contententi gli indirizzi da escludere
	 */
	public function ip_esclusi(){
		
		if(!isset($this->ip_esclusi)){
			
			$this->ip_esclusi = array();
			
			$lista_ip = explode(";", $this->utente_obj()->team_obj()->get_ip_esclusi(0));

			foreach($lista_ip as $ip){
				if(strlen(trim($ip)) > 0){
					$this->ip_esclusi[] = strtolower(trim($ip));
				}
			}
		}
		
		return $this->ip_esclusi;
	}
	
	
	/**
	 * indica se e' necessario usare una onetime per il login o semplicemente inviare una mail di notifica
	 * 
	 * @return string "mail_notifica" => va mandata la mail, "onetime" => e' necessaria la onetime, "" => nessuna azione prevista
	 */
	public function azione_login(){
		return $this->azione_login;
	}


	/**
	 * sulla base della provenienza della richiesta (rete locale o meno), alle impostazioni utente (team) ed eventuali ip esclusi, ritorna TRUE se e' necessario
	 * intraprendere qualche azione (onetime o mail di notifica), FALSE se si puo' proseguire con il login senza problemi
	 * 
	 * @return boolean
	 */
	public function onetime_enabled(){
		
		$esito = true;
		
		if(parent::onetime_enabled() === false){ //arriviamo da rete interna
			$esito = false;
		}else{
			
			//andiamo a controllare se per l'utente e' attivo un qualche controllo accessi
//			switch($this->utente_obj(true)->team_obj()->get_modalita_onetime(0)){
//				
//				case 0: // onetime attiva
//					
//					$this->azione_login = "onetime";
//					$esito = true;
//					break;
//
//				case 1: // notifica mail
//
//					$this->azione_login = "mail_notifica";
//					$esito = true;
//					break;
//				
//				case 2:  // disabilitata
//					
//					$this->azione_login = "";
//					$esito = false;
//					break;
//				
//				default:
//					
//					$this->azione_login = "onetime";
//					$esito = true;
//					break;
//			}
			
			$this->azione_login = "";
			$esito = false;
			
			
			if($esito){ //ora controlliamo se siamo in rete "interna" considerando anche gli ip esclusi
				
				if(verify_local_access($this->ip_esclusi())){
					$esito = false;
				}else{
					$esito = true;
				}
			}
		}
		
		return $esito;
	}
	
	
	/**
	 * invia la mail di segnalazione di accesso effettuato (modalita_onetime = 1)
	 * 
	 * @return boolean esito dell'invio
	 */
	public function mail_notifica(){
		
		$mail_obj = new classe_mail();
		$mail_obj->add_dest($this->get_mail(), $this->get_nome()." ".$this->get_cognome());

		$oggetto_mail = NOME_SITO." | Notifica Accesso";
		$testo_mail = "<p>Ciao ".$this->get_nome().",<br />";
		$testo_mail .= "&egrave; appena stato effettuato l'accesso a ".NOME_SITO." con il tuo account";

		$mail_obj->set_text($oggetto_mail, $testo_mail);
		
		$esito = $mail_obj->Send();
		
		return $esito;
	}
	
}




class base_user {
    
	protected $is_connesso = 0;
	protected $destroy_conn = 0;
	protected $db_conn = null;
	protected $db_reset = false;
	
	private $user_id = 0;
	private $tipo = 0;
	private $errore = 0;
	
	private $user_name = "";
	private $nome = "";
	private $cognome = "";
	private $mail = "";
	
	private $set_cookie = false;
	
	private $cifra_pw = false;
	private $redirect_on_failure = false;
	private $redirect_on_success = false;
	private $success_page = "index.php";
	//private $failure_page = "login.php?err=1";
	private $failure_page = "login.php";
	
	private $registra_login = false;
	private $suffisso_azione = "";
	protected $registra_errori_login = false;
	
	protected $onetime_enabled = true; //indica se il login deve supportare l'autenticazione a 2-step -- da modificare se si vuole togliere
	//private $bypass_onetime = false; //e' usata per far funzionare la classe, non modificare!
	protected $check_onetime = false;
	
	protected $onetime_length = 10; //lunghezza della onetime password generata
	protected $onetime_duration = 5; //in minuti
	
	protected $login_AD = true;
	protected $azione_login = "";
	
	private $login_da_cookie = false; // variabile di appoggio per gestire le notifiche in base al login da cookie -- NON modificare
	
	protected $team_enabled = false;


	/*
	 * nome delle colonne su db corrispondenti a username e password (verranno usati in tutto il resto della classe)
	 */
	private $n_campo_id = "id_user";
	private $n_campo_user = "username";
	private $n_campo_password = "pw";
	private $n_campo_nome = "nome";
	private $n_campo_cognome = "cognome";
	private $n_campo_mail = "mail";
	private $nome_tabella = "users";


	private $credenziali_inserite = array();

	private $esito_login = false;
	
	
	public function launch_errore(){
		$this->errore = 1;
	}
	
	public function get_errore(){
		return $this->errore;
	}


	/**
	 * versione base della funzione onetime - vengono controllati solo gli ip interni
	 * @return boolean
	 */
	public function onetime_enabled(){
		
		if(verify_local_access() === true){ // di default viene fatto un controllo sugli ip interni, il metodo esteso potra' inserire controlli aggiuntivi
			$this->azione_login = "";
			return false;
		}else{
			$this->azione_login = "onetime";
			return true;
		}
	}
	
	/**
	 * 
	 * @return string
	 */
	public function genera_onetime(){
		
		//$characters = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
		//$characters = "0123456789abcdefghijklmnopqrstuvwxyz";
		$characters = "0123456789";
		$onetime_pw = "";
		for ($i = 0; $i < $this->onetime_length; $i++) {
			$onetime_pw .= $characters[rand(0, strlen($characters) - 1)];
		}
		
		if(!is_numeric($this->onetime_duration)){
			$this->onetime_duration = 0;
		}
		
		$sql = "UPDATE ".$this->nome_tabella." SET onetime = ?, onetime_expire = NOW() + INTERVAL ".$this->onetime_duration." MINUTE WHERE id_user = ?";
		$dati_query = array($onetime_pw, $this->id_user());
		
		$this->connessione()->esegui_query($sql, $dati_query);
		$this->Close();
		
		return $onetime_pw;
	}
	
	
	/**
	 * reimposta la password in sessione sulla base di quella passata
	 * 
	 * @param string $password nuova password (gia' cifrata, se serve)
	 */
	public function reimposta_password_sessione($password){
		$_SESSION['password'] = $password;
	}
	

	/**
	 * restituisce la pagina a cui fare il redirect in caso di login corretto
	 * 
	 * @return string
	 */
	public function success_page(){
		return $this->success_page;
	}
	
	/**
	 * restituisce la pagina a cui fare il redirect in caso di login errato
	 * 
	 * @return string
	 */
	public function failure_page(){
		return $this->failure_page;
	}




	/**
	 * @param type $fun
	 * 
	 * $fun = 3 e' stato creato appositamente per gestire le chiamate JSON effettuate da questo utente. 
	 * A differenza degli altri $fun, non cripta la password dell'utente e non esegue il redirect
	 * 
	 * fun = 2 serve per verificare se c'e' un utente loggato senza mandare per forza al form di login.
	 * 
	 * fun = 1 serve per effettuare il login dai dati in POST e non dai dati della sessione.
	 * 
	 * fun = 0 e' lo stato di default e controlla che l'utente sia loggato convalidando i dati presenti nella sua sessione
	 * 
	 */
	public function __construct($fun = 0, $credenziali = array(), $check_onetime = false) {
		
		$this->user_id=0;
		$this->tipo=0;
		$this->errore = 0;
		
		$this->check_onetime = $check_onetime;
		
		$this->login_da_cookie = false;
		
		
		/*
		 * gestiamo in modo unitario le funzioni e sulla base di esse recuperiamo le ipotetiche credenziali e verfichiamo il login
		 */
		
		switch ($fun) {
			
			case 1: //recupero dati in post
				
				$this->retrieve_post();
				
				$this->errore = 1;
				
				$this->redirect_on_failure = true;
				$this->redirect_on_success = true;
				$this->cifra_pw = true;
				
				$this->registra_login = true;
				$this->suffisso_azione = "form";
				
				break;
			
			case 2: //recupero dati da sessione (o da cookie) senza redirect
				
				$this->bypass_onetime = true;
				
				$this->retrieve_sessione();
				
				if(count($this->credenziali_inserite) != 3){
					$this->retrieve_cookie();
				}
				
				$this->redirect_on_failure = false;
				$this->redirect_on_success = false;
				$this->cifra_pw = false;
				
				$this->registra_login = false;
				
				break;
			
			case 3: //JSON (non cripta la password e non effettua il redirect)
				
				$this->bypass_onetime = true; //da json non possiamo richiedere la onetime password
				
				$this->retrieve_post();
				
				$this->redirect_on_failure = false;
				$this->redirect_on_success = false;
				$this->cifra_pw = false;
				
				$this->registra_login = true;
				$this->suffisso_azione = "json";
				
				break;
			
			
			case 4: //recupero dati in post senza redirect; parametro da utilizzare ad esempio per l'autenticazione javascript

				$this->bypass_onetime = true;
				
				$this->retrieve_post();

				$this->redirect_on_failure = false;
				$this->redirect_on_success = false;
				$this->cifra_pw = true;
				
				$this->registra_login = true;
				$this->suffisso_azione = "JavaScript";

				break;


			case 5: //utilizziamo le credenziali passate al costruttore
				
				if(is_array($credenziali) && count($credenziali) == 2){
					$this->credenziali_inserite[0] = $credenziali[0];
					$this->credenziali_inserite[1] = $credenziali[1];
					$this->credenziali_inserite[2] = "";
				}
				
				$this->redirect_on_failure = false;
				$this->redirect_on_success = false;
				$this->cifra_pw = false;
				
				$this->registra_login = false;
				$this->suffisso_azione = "costruttore";
				
				break;
				
			
			default: //caso 0: verifica il login (da sessione o da cookie) e stampa il form di login se l'utente non e' loggato
				
				$this->bypass_onetime = true;
				
				$this->retrieve_sessione();
				
				if(count($this->credenziali_inserite) != 3){
					$this->retrieve_cookie();
				}
				
				$this->redirect_on_failure = true;
				$this->redirect_on_success = false;
				$this->cifra_pw = false;
				
				$this->registra_login = false;
				
				break;
		}

		
		//ora che abbiamo settato i vari parametri, andiamo a vedere se esiste l'utente specificato e operiamo le azioni di conseguenza
		if(count($this->credenziali_inserite) != 3){
			$this->esito_login = false;
			
		}else{
			
			$user2verify = $this->credenziali_inserite[0];
			$pw2verify = $this->credenziali_inserite[1];
			$onetime = $this->credenziali_inserite[2];
			
			if($this->login_AD){
				
				$pw_decripted = $this->decifra_pw_sessione($pw2verify);
				
				if($pw_decripted !== false){
					$pw2verify = $pw_decripted;
				}
				
			}elseif($this->cifra_pw && !$this->login_AD){ //cifriamo la password se richiesto dall'azione e se non e' impostato il login su Active Directory
				$pw2verify = hash(HASH_TYPE, $pw2verify);
			}
			
			$this->esito_login = $this->verifica_credenziali($user2verify, $pw2verify, $onetime);
			
			if($this->set_cookie){
				$this->set_cookie();
			}
		}
		
		
		if($this->esito_login){ //il login e' andato a buon fine
			
			//se il login e' andato a buon fine, rigeneriamo l'id di sessione
//			if(check_sessione()){
//				@session_regenerate_id(true);
//			}else{
//				@session_regenerate_id(false);
//			}
			
			if($this->redirect_on_success){
				header("Location: ".$this->success_page);
			}
			
		}else{ //errore nel login
			
			if($this->redirect_on_failure){
				//header("Location: ".$this->failure_page."&esito=".$this->errore);
				header("Location: ".$this->failure_page);
			}
		}
	}
	
	
	private function retrieve_post(){
		
		if(isset($_POST['username']) && is_string($_POST['username'])){$username = trim($_POST['username']);} else {$username = "";}
		if(isset($_POST['password']) && is_string($_POST['password'])){$password = trim($_POST['password']);} else {$password = "";}
		if(isset($_POST['onetime']) && is_string($_POST['onetime'])){$onetime = trim($_POST['onetime']);} else {$onetime = "";}
		
		if(strlen($username) > 0 && strlen($password) > 0){
			$this->credenziali_inserite[0] = $username;
			$this->credenziali_inserite[1] = $password;
			$this->credenziali_inserite[2] = $onetime;
		}
		
		//se avevamo spuntanto il "ricorda password" andiamo a scrivere il cookie
		if(isset($_POST['remember_me'])){
			$this->set_cookie = true;
		}
	}
	
	
	private function retrieve_sessione(){
		
		if(isset($_SESSION['user']) && strlen($_SESSION['user']) > 0){$username = trim($_SESSION['user']);}else{$username = "";}
		if(isset($_SESSION['password']) && strlen($_SESSION['password']) > 0){$password = trim($_SESSION['password']);}else{$password = "";}
		
		if(strlen($username) > 0 && strlen($password) > 0){
			$this->credenziali_inserite[0] = $username;
			$this->credenziali_inserite[1] = $password;
			$this->credenziali_inserite[2] = "";
		}
	}



	private function retrieve_cookie(){
		
		$esito = false;
		
//		if($this->onetime_enabled()){ //se c'e' la onetime password abilitata, il login da cookie non deve funzionare
//			return $esito;
//		}
		
		if(defined("COOKIE_NAME") && isset($_COOKIE[COOKIE_NAME])){
			
			$this->login_da_cookie = true;  // impostiamo a TRUE il login da cookie in modo che poi si possa valutare se accettarlo o meno sulla base della onetime
			
			$hash = $_COOKIE[COOKIE_NAME];
			
			//andiamo a recuperare le informazioni sull'utente con quella chiave
			if(defined('DB_TYPE') && DB_TYPE == "MSSQL"){ //invochiamo la funzione equivalente al NOW() per MS SQL
				if($this->team_enabled === true){
					$sql = "SELECT ".$this->n_campo_id.", ".$this->n_campo_user." AS username_query, ".$this->n_campo_password." FROM ".$this->nome_tabella." JOIN team ON team = id_team WHERE cookie_id = ? AND cookie_expire >= GetDate()";
				}else{
					$sql = "SELECT ".$this->n_campo_id.", ".$this->n_campo_user." AS username_query, ".$this->n_campo_password." FROM ".$this->nome_tabella." WHERE cookie_id = ? AND cookie_expire >= GetDate()";
				}
			}else{
				if($this->team_enabled === true){
					$sql = "SELECT ".$this->n_campo_id.", ".$this->n_campo_user." AS username_query, ".$this->n_campo_password." FROM ".$this->nome_tabella." JOIN team ON team = id_team WHERE cookie_id = ? AND cookie_expire >= NOW()";
				}else{
					$sql = "SELECT ".$this->n_campo_id.", ".$this->n_campo_user." AS username_query, ".$this->n_campo_password." FROM ".$this->nome_tabella." WHERE cookie_id = ? AND cookie_expire >= NOW()";
				}
			}
			
			$dati_query = array($hash);
			//echo $this->connessione()->debug_query($sql, $dati_query);
			//die();
			$arr = $this->connessione()->query_risultati($sql, $dati_query);
			
			if(count($arr) > 0){
				
				$this->credenziali_inserite[0] = $arr[0]["username_query"];
				$this->credenziali_inserite[1] = $arr[0][$this->n_campo_password];
				$this->credenziali_inserite[2] = "";
				
				
				/* -----------------------------
				 * LOG
				 *------------------------------------*/
				$log = new classe_log($arr[0]["id_user"]);
				
				if($log->enabled()){
					$log->set_nome_azione("login cookie");
					$log->set_tipo_azione("login");
					$log->set_info(((isset($_SERVER['HTTP_USER_AGENT']) && is_string($_SERVER['HTTP_USER_AGENT'])) ? (string)$_SERVER['HTTP_USER_AGENT'] : "")); //informazioni aggiuntive

					$log->inserisci();
				}
				/*--------------------------------------*/		
				
				$esito = true;
				
			}else{
				$esito = false;
			}
			
			$this->Close();
		}
		
		return $esito;
	}
	
	
	/**
	 * controlla che le credenziali passate corrispondano ad un utente attivo
	 * 
	 * @param type $username
	 * @param type $password
	 * @param type $onetime
	 * @return boolean
	 */
	private function verifica_credenziali($username, $password, $onetime){
		
		if(strtolower($username) == "administrator"){ //evitiamo il login per l'utente administrator, cosi' da non avere problemi con AD
			return false;
		}
		
		$esito_ad = false;
		
		if($this->login_AD){
			
			$ldap_auth = new classe_ldap();
			
			
			if($ldap_auth->check_login_bool($username, $password)){
				
				$esito_ad = true;

				//a questo punto cifriamo la password che finira' in sessione
				$password = $this->cifra_pw_sessione($password);
			}
			
			if($this->team_enabled === true){
				$query = "SELECT ".$this->n_campo_id.", tipo, ".$this->n_campo_nome.", ".$this->n_campo_cognome.", ".$this->n_campo_mail." FROM ".$this->nome_tabella." JOIN team ON team = id_team WHERE ".$this->n_campo_user." = ? AND users.attivo = 1 AND team.attivo = 1";
			}else{
				$query = "SELECT ".$this->n_campo_id.", tipo, ".$this->n_campo_nome.", ".$this->n_campo_cognome.", ".$this->n_campo_mail." FROM ".$this->nome_tabella." WHERE ".$this->n_campo_user." = ? AND attivo = 1";
			}
			$dati_query = array($username);
			
		}else{
			
			if($this->team_enabled === true){
				$query = "SELECT ".$this->n_campo_id.", tipo, ".$this->n_campo_nome.", ".$this->n_campo_cognome.", ".$this->n_campo_mail." FROM ".$this->nome_tabella." JOIN team ON team = id_team WHERE ".$this->n_campo_user." = ? AND ".$this->n_campo_password." = ? AND users.attivo = 1 AND team.attivo = 1";
			}else{
				$query = "SELECT ".$this->n_campo_id.", tipo, ".$this->n_campo_nome.", ".$this->n_campo_cognome.", ".$this->n_campo_mail." FROM ".$this->nome_tabella." WHERE ".$this->n_campo_user." = ? AND ".$this->n_campo_password." = ? AND attivo = 1";
			}
			$dati_query = array($username, $password);
			
			$esito_ad = true;
		}
		
		
		if($this->check_onetime){ //se e' abilitata la onetime password, aggiungiamo il controllo alla query
			$query .= " AND onetime = ? AND onetime_expire > NOW()";
			$dati_query[] = $onetime;
		}
		
//		echo "<p>".$this->connessione()->debug_query($query, $dati_query);
//		die();

		$result = $this->connessione()->query_risultati($query, $dati_query);


		if(count($result) > 0 && $esito_ad === true){

			//il login e' valido e possiamo impostare la sessione

			$_SESSION['session_id']=$result[0]['id_user'];
			$_SESSION['user']=$username;
			$_SESSION['password']=$password;
			$_SESSION['tipo']=$result[0]['tipo'];

			$this->user_id=$result[0]['id_user'];
			$this->tipo=$result[0]['tipo'];

			$this->user_name = $username;
			$this->nome = $result[0][$this->n_campo_nome];
			$this->cognome = $result[0][$this->n_campo_cognome];
			$this->mail = $result[0][$this->n_campo_mail];

			$this->aggiorna_data($this->user_id);
			$this->aggiorna_ip($this->user_id);
			
			$this->errore = 0;
			
			if($this->check_onetime){ //disabilitiamo la onetime password appena usata
				$sql = "UPDATE ".$this->nome_tabella." SET onetime_expire = '1970-01-01 00:00:00' WHERE id_user = ?";				
				$dati_query = array($this->id_user());
				
				$this->connessione()->esegui_query($sql, $dati_query);
			}
			
			
			if($this->login_da_cookie){ // se abbiamo fatto il login da cookie, andiamo a controllare cosa prevede l'opzione sulla onetime
				
				$user_login_obj = new user(2);
				
				if($user_login_obj->onetime_enabled() && $user_login_obj->azione_login() == "mail_notifica"){  // se e' solo di notifica, mandiamo la mail e procediamo
					
					$user_login_obj->mail_notifica();
					
				}elseif($user_login_obj->onetime_enabled() && $user_login_obj->azione_login() == "onetime"){ // se serve la onetime, blocchiamo tutto
					
					$user_login_obj->logout(false);
					$this->Close();
					$this->destroy();
					
					return false;
				}
			}
			
			
			if($this->registra_login){
				
				/* -----------------------------
				 * LOG
				 *------------------------------------*/
				$log = new classe_log($this->user_id);
				
				if($log->enabled()){
					$log->set_nome_azione("login ".$this->suffisso_azione);
					$log->set_tipo_azione("login");
					$log->set_info(((isset($_SERVER['HTTP_USER_AGENT']) && is_string($_SERVER['HTTP_USER_AGENT'])) ? (string)$_SERVER['HTTP_USER_AGENT'] : "")); //informazioni aggiuntive

					$log->inserisci();
				}
				/*--------------------------------------*/		
			}
			
		
			$this->Close();
			return true;

		}else{
			
			if($this->registra_errori_login){
				
				/* -----------------------------
				 * LOG
				 *------------------------------------*/
				$log = new classe_log($this->user_id);
				
				if($log->enabled()){
					$log->set_nome_azione("Errore login ".$this->suffisso_azione);
					$log->set_tipo_azione("login_failed");
					$log->set_info("User: ".$username); //informazioni aggiuntive

					$log->inserisci();
				}
				/*--------------------------------------*/		
			}

			$this->Close();

			$this->destroy();
			return false;
		}
	}


	private function cifra_pw_sessione($stringa){
		$crypt_obj = new cryptastic();
		return $crypt_obj->encrypt($stringa, CRYPT_SESSION_KEY, true);
	}
	
	private function decifra_pw_sessione($stringa){
		$crypt_obj = new cryptastic();
		return $crypt_obj->decrypt($stringa, CRYPT_SESSION_KEY, true);
	}



	public function  id_user(){
		//return $this->user_id;
		return $this->get_id();
	}
	
	public function get_id(){
		return $this->user_id;
	}
	
	
	public function get_tipo(){
		return $this->tipo;
	}
        
        
	/**
	 * aggiorna la data nei log dell'utente
	 * @param int $id e l'id dell'utente da aggiornare
	 */
	function aggiorna_data($id=0){

		if ($id==0){
			$id= $this->id_user();
		}
		
		if(defined('DB_TYPE') && DB_TYPE == "MSSQL"){ //invochiamo la funzione equivalente al NOW() per MS SQL
			$sql = "UPDATE ".$this->nome_tabella." SET dtLast = GetDate() WHERE id_user = ?";
		}else{
			$sql = "UPDATE ".$this->nome_tabella." SET dtLast = NOW() WHERE id_user = ?";
		}
		$dati_query = array($id);
		
		$this->connessione()->esegui_query($sql, $dati_query);
		$this->Close();
	}
	
	
	/**
	 * aggiorna l'ip nei log dell'utente
	 * @param int $id e l'id dell'utente da aggiornare
	 */
	function aggiorna_ip($id=0){

		if ($id==0){
			$id= $this->id_user();
		}
		
		$ip = (isset($_SERVER['REMOTE_ADDR']) && is_string($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : "");
		
		$sql = "UPDATE ".$this->nome_tabella." SET ip = ? WHERE id_user = ?";
		$dati_query = array($ip, $id);
		
		$this->connessione()->esegui_query($sql, $dati_query);
		$this->Close();
	}
        

	
	
	public function get_username(){
		return $this->user_name;
	}
	
	public function get_nome(){
		return $this->nome;
	}
	
	public function get_cognome(){
		return $this->cognome;
	}

	public function get_mail() {
		return $this->mail;
	}

	



	public function logout($redirect = true) {
		
		$id_utente = ($this->user_id > 0 ? $this->user_id : -1);
		
		
		/* -----------------------------
		 * LOG
		 *------------------------------------*/
		$log = new classe_log();

		if($log->enabled()){
			$log->set_nome_azione("logout web");
			$log->set_tipo_azione("logout");
			$log->set_utente($id_utente);
			$log->set_info(((isset($_SERVER['HTTP_USER_AGENT']) && is_string($_SERVER['HTTP_USER_AGENT'])) ? (string)$_SERVER['HTTP_USER_AGENT'] : "")); //informazioni aggiuntive

			$log->inserisci();
			
		}
		/*--------------------------------------*/

		//andiamo a cancellare i cookie
		if(defined("COOKIE_NAME") && isset($_COOKIE[COOKIE_NAME])){
			setcookie(COOKIE_NAME, "", time()-3600, "/");
			//unset($_COOKIE);
			
//			echo "<p>".COOKIE_NAME;
//			var_dump($_COOKIE);
//			die();
			
			//unset($_COOKIE[COOKIE_NAME]);
		}
		
		$this->destroy();
		
		if($redirect){
			header('Location: index.php');
		}
	}        
        
        
	private function destroy(){

		//prima di cancellare tutto, svuotiamo il campo con il cookie
//		$sql = "UPDATE ".$this->nome_tabella." SET cookie_id = '', cookie_expire = '1970-01-01 00:00:00' WHERE id_user = ?";
//		$dati_query = array($this->user_id);
//		
//		$this->connessione()->esegui_query($sql, $dati_query);
		
		$this->user_id=0;
		$this->tipo=0;

		$_SESSION['session_id']=0;
		$_SESSION['user']="";
		$_SESSION['password']="";
		$_SESSION['tipo']=0;
		
		distruggi_sessione();

	}




	private function set_cookie(){
		
		//andiamo a vedere se esiste gia' una chiave valida per l'utente
		if(defined('DB_TYPE') && DB_TYPE == "MSSQL"){ //invochiamo la funzione equivalente al NOW() per MS SQL
			if($this->team_enabled === true){
				$sql = "SELECT cookie_id, cookie_expire FROM ".$this->nome_tabella." JOIN team ON team = id_team WHERE ".$this->n_campo_id." = ? AND cookie_expire > GetDate()";
			}else{
				$sql = "SELECT cookie_id, cookie_expire FROM ".$this->nome_tabella." WHERE ".$this->n_campo_id." = ? AND cookie_expire > GetDate()";
			}
		}else{
			if($this->team_enabled === true){
				$sql = "SELECT cookie_id, cookie_expire FROM ".$this->nome_tabella." JOIN team ON team = id_team WHERE ".$this->n_campo_id." = ? AND cookie_expire > NOW()";
			}else{
				$sql = "SELECT cookie_id, cookie_expire FROM ".$this->nome_tabella." WHERE ".$this->n_campo_id." = ? AND cookie_expire > NOW()";
			}
		}
		$dati_query = array($this->user_id);
		
		$arr = $this->connessione()->query_risultati($sql, $dati_query);
		$this->Close();
		
		
		
		if(count($arr) > 0){//se c'e' gia', restituiamo quello da mettere nei cookie, cosi' non cancelliamo gli altri device
			
			$chiave = array();
			
			$chiave[0] = $hash = $arr[0]["cookie_id"];
			$expire_date = $arr[0]["cookie_expire"];
			
			if(is_a($expire_date, "DateTime")){ //controlliamo che non sia un oggetto (se arriviamo da un server MS SQL)
				$expire_date = $expire_date->format("Y-m-d H:i:s");
			}
			
			$chiave[1] = $expire = strtotime($expire_date);
			
		}else{		
			//andiamo a generare una nuova chiave che salveremo nei cookie
			$chiave = $this->genera_chiave_cookie();
			
		}
		
		if(defined("COOKIE_NAME")){
			setcookie(COOKIE_NAME, $chiave[0], $chiave[1], "/");
		}
		
	}
	
	
	/**
	 * genera la chiave per identificare il cookie da mettere nella tabella utenti e nel cookie stesso
	 * 
	 * @return array [0]->chiave; [1]->expire time
	 */
	public function genera_chiave_cookie(){
		
		$key = array();
		
		$key[0] = $hash = hash(HASH_TYPE, random_password().time().COOKIE_SALT);
		$key[1] = $expire = time() + (60*60*24*30);
		$expire_date = date("Y-m-d H:i:s", $expire);

		$sql = "UPDATE ".$this->nome_tabella." SET cookie_id = ?, cookie_expire = ? WHERE id_user = ?";
		$dati_query = array($hash, $expire_date, $this->user_id);

//		echo $this->connessione()->debug_query($sql, $dati_query);
//		die();
		$this->connessione()->esegui_query($sql, $dati_query);
		$this->Close();
		
		return $key;
	}
	
	
	
	
	
	public function login_from_url($dati){
		
		if(count($dati) < 3){
			return new user(2);
		}
		
		$username = trim($dati[0]);
		$password = trim($dati[1]);
		$expire = trim($dati[2]);
		
		$now = new classe_data();
		
		try{
			$expire_obj = new classe_data($expire);
		}catch(Exception $e){
			$expire_obj = new classe_data("1970-01-01 00:00:00");
		}
		
		if($now->print_data("Y-m-d H:i:s") > $expire_obj->print_data("Y-m-d H:i:s")){
			return new user(2);
		}
		
		$_SESSION["session_id"] = 999;
		$_SESSION['user'] = $username;
		$_SESSION['password'] = $password;
		
		/* -----------------------------
		 * LOG
		 *------------------------------------*/
		$log = new classe_log();

		if($log->enabled()){
			$log->set_nome_azione("login url");
			$log->set_tipo_azione("login");
			$log->set_info(((isset($_SERVER['HTTP_USER_AGENT']) && is_string($_SERVER['HTTP_USER_AGENT'])) ? (string)$_SERVER['HTTP_USER_AGENT'] : "")); //informazioni aggiuntive

			$log->inserisci();
		}
		/*--------------------------------------*/		
		
		return new user(2);
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