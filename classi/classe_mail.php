<?

require_once("class.phpmailer.php");
require_once("class.smtp.php");
require_once("classe_AESCipher.php");
//require_once("classe_mail_db.php");


class classe_mail extends PHPMailer{
	
	protected $is_connesso = 0;
	protected $destroy_conn = 0;
	protected $db_conn = null;
	protected $db_reset = false;
	
	private $mail_to_db=0;
	private $destinatario = "";
	
	private $charset_email = "UTF-8";
	private $use_postman = false;
	private $postman_client_id = "";
	private $postman_client_secret = "";
	
	private $bcc = array();


	private $versione = "2.2";
	private $changelog = "
2.2	--	Intoduzione supporto invio mail tramite postman

2.1	--	Correzione switch case -> aggiunta break mancante nel caso dell'header recuperato da DB

2.0	--	Verifica dell'oggetto connessione su piu' classi e creazione nuove connessioni con la nuova classe_DB
	--	Invio delle mail con codifica UTF-8

1.3	--	Parametro per specificare se caricare i settings da db o meno; 
	--	Attivazione della modalita' debug se l'intero sistema e' in modalita' debug

1.2	--	Introduzione del parametro SMTPSecure per gestire le connessioni ssl/tls

1.1	--	Correzione bug sul return del metodo send (in assenza di salvataggio su db non ritornava niente, quindi veniva interpretato come FALSE)

1.0	--	Versione base
	";
	
	public function get_versione() {
		return $this->versione;
	}
	
	public function changelog(){
		return $this->changelog;
	}
	
	
	/**
	 * 
	 * @param int $mail_to_db 0: invia subito la mail; 1: salva la mail su db per essere inviata in seguito; 2: invia e salva
	 * @param boolean $load_from_db true: carica i settings da db
	 */
	function __construct($mail_to_db = 0, $load_from_db = true){

		$this->header_email = "";
		
		parent::__construct(false);

	
		$this->mail_to_db = $mail_to_db;
		$this->IsSMTP();
		$this->IsHTML(true);
		
		if(defined('DEBUG') && DEBUG){
			$this->SMTPDebug = 1;
		}else{
			$this->SMTPDebug = 0;
		}
	
		if($load_from_db){
			$this->load_from_db();
		}
		
	}
	
	/**
	 * recupera i parametri di configurazione della mail dal database
	 */
	public function load_from_db(){
		

		$sql = "SELECT * FROM settings WHERE attivo=1";
		$conf_par = $this->connessione()->query_risultati($sql);
		
		foreach($conf_par as $par){
			
			switch ($par["nome"]){
				case "mail_mittente":
					$this->From = $par["valore"];
					break;
				
				case "nome_mittente":
					$this->FromName = $par["valore"];
					break;
				
				case "server_smtp":
					$this->Host = $par["valore"];
					break;
				
				case "porta_smtp":
					$this->Port = $par["valore"];
					break;
				
				case "smtp_user":
					$this->Username = $par["valore"];
					break;
				
				case "smtp_pw":
					$this->Password = $par["valore"];
					break;
				
				case "smtp_auth":
					$this->SMTPAuth = filter_var($par["valore"], FILTER_VALIDATE_BOOLEAN);
					break;
				
				case "smtp_secure":
					
					if($par["valore"] == "ssl" || $par["valore"] == "tls"){
						$this->SMTPSecure = $par["valore"];
					}else{
						$this->SMTPSecure = "";
					}
					break;
					
				case "header_email":
					$this->header_email = $par["valore"];
					break;

				case "use_postman":
					if($par["valore"]=="1"){
						$this->use_postman = true;
					}
					break;

				case "postman_client_id":
					$this->postman_client_id = $par["valore"];
					break;

				case "postman_client_secret":
					$this->postman_client_secret = $par["valore"];
					break;
			}
		}
		
		$this->Close();
		
	}
	
	/**
	 * invia la mail e la salva su db (se richiesto)
	 * ritorna l'esito dell'operazione
	 * 
	 * @return boolean esito
	 */
	public function Send() {
		
		$esito = false;
		if(!$this->use_postman){
			if ($this->mail_to_db == 0 || $this->mail_to_db == 2){
				$esito = parent::Send();			
			}
			
			if ($this->mail_to_db == 1 || $this->mail_to_db == 2){
				//salviamo sul db
				
				$mail_obj = new classe_mail_db();
				$mail_obj->set_destinatario($this->destinatario);
				$mail_obj->set_mittente($this->From);
				$mail_obj->set_oggetto($this->Subject);
				$mail_obj->set_testo($this->Body);
				$mail_obj->set_inviata(1);
				$mail_obj->set_dt_sent(date("Y-m-d H:i:s"));
				$mail_obj->set_insert_page($_SERVER["REQUEST_URI"]);
				
				$esito = $mail_obj->salva();
			}
		}else{

			$arr_destinatari = explode(";",$this->destinatario);
			$out_dest = $arr_destinatari;
			foreach($arr_destinatari as $line){
				if(strlen($line)==0){
					$out_dest = array_diff($out_dest,[$line]);
				}
			}

			$json_message = array(
				// Source is required
				'Source' => $this->FromName." <".$this->From.">",
				// Destination is required
				'Destination' => array(
					'ToAddresses' => $out_dest,
					'BccAddresses' => $this->bcc
				),
				// Message is required
				'Message' => array(
					// Subject is required
					'Subject' => array(
						// Data is required
						'Data' => $this->Subject,
						'Charset' => 'UTF-8',
					),
					// Body is required
					'Body' => array(
						'Text' => array(
							// Data is required
							'Data' => "",
							'Charset' => 'UTF-8',
						),
						'Html' => array(
							// Data is required
							'Data' => $this->Body,
							'Charset' => 'UTF-8',
						),
					),
				)
			);

			try{
				$cipher = new classe_AESCipher($this->postman_client_secret);
				$messaggio = $cipher->encrypt(json_encode($json_message));

				$url = 'http://postman.sinergo.it';
				
				$eol = "\r\n";
				$data = '';
				$mime_boundary=md5(time());
				//
				$data .= '--' . $mime_boundary . $eol;
				$data .= 'Content-Disposition: form-data; name="id"' . $eol . $eol;
				$data .= $this->postman_client_id . $eol;
				$data .= '--' . $mime_boundary . $eol;
				$data .= 'Content-Disposition: form-data; name="info"' . $eol . $eol;
				$data .= $messaggio . $eol;
				$data .= '--' . $mime_boundary . $eol;
				$data .= "--" . $mime_boundary . "--" . $eol . $eol;

				// use key 'http' even if you send the request to https://...
				$options = array(
					'http' => array(
						'header'  => "Content-type: multipart/form-data; boundary=" . $mime_boundary,
						'method'  => 'POST',
						'content' => $data
					)
				);
			
				$context  = stream_context_create($options);
				$result = file_get_contents($url, FILE_TEXT, $context);
				$result = json_decode($result,true);
			}catch(Exception $e){
				$result = false;
			}
			
			if(!$result){
				$esito = false;
			}else{
				if(array_key_exists("status",$result)){
					if($result['status']=="OK"){
						$esito = true;
					}
				}
			}
			
		}
		return $esito;
	}
	
	
	/**
	 * permette di impostare a mano tutti i parametri per l'invio della mail
	 * 
	 * @param string $nome_mitt nome visualizzato del mittente della mail
	 * @param string $mail_mitt indirizzo mail del mittente
	 * @param string $server_smtp nome o indirizzo ip del server SMTP
	 * @param int $porta porta protocollo SMTP [default: 25]
	 * @param boolean $authentication specifica se e' necessaria l'autenticazione [default: false]
	 * @param string $smtpUser user name per l'autenticazione SMTP [default: ""]
	 * @param string $smtpPw password per l'autenticazione SMTP [default: ""]
	 */
	public function set_param($nome_mitt, $mail_mitt, $server_smtp = "localhost", $porta = 25, $authentication = false, $smtpUser = "", $smtpPw = ""){
		
		$this->FromName = $nome_mitt;
		$this->From = $mail_mitt;
		$this->Host = $server_smtp;
		$this->SMTPAuth = $authentication;
		
		if($authentication){
			$this->Username = $smtpUser;
			$this->Password = $smtpPw;
		}
		
		$this->Port = $porta;
	}
	
	/**
	 * aggiunge un destinatario alla mail
	 * 
	 * @param string $mail_dest indirizzo mail destinatario
	 * @param string $nome_dest nome visualizzato del destinatario [default: ""]
	 */
	public function add_dest($mail_dest, $nome_dest = ""){
		$this->AddAddress($mail_dest, $nome_dest);
		$this->destinatario .= $nome_dest." <".$mail_dest.">;";
	}
	
	public function go_postman($stato,$client_id="",$client_secret=""){
		$this->use_postman = $stato;
		if(strlen($client_id)>0){
			$this->postman_client_id = $client_id;
		}
		if(strlen($client_secret)>0){
			$this->postman_client_secret = $client_secret;
		}
	}

	/**
	 * imposta l'oggetto e il testo della mail
	 * 
	 * @param string $oggetto oggetto della mail
	 * @param string $testo testo della mail (in formato HTML)
	 */
	public function set_text($oggetto, $testo){
		$this->Subject = $oggetto;
		$this->CharSet = $this->charset_email;
		$this->MsgHTML("<html><head></head><body><div style='".$this->header_email."'>".$testo."</div></body></html>");
	}
	
	
	public function replace_tag($nome="", $cognome="", $mail="", $password="", $importo="", $nome_sito="", $url_sito="", $n_ordine="", $codice_traking="", $promo_newsletter="", $quota_minima=""){
		
		if(isset($this->Body) && strlen($this->Body) > 0){
			$this->Body = str_replace("[nome]", $nome, $this->Body);
			$this->Body = str_replace("[cognome]", $cognome, $this->Body);
			$this->Body = str_replace("[mail]", $mail, $this->Body);
			$this->Body = str_replace("[password]", $password, $this->Body);
			$this->Body = str_replace("[importo]", $importo, $this->Body);
			$this->Body = str_replace("[nome_sito]", $nome_sito, $this->Body);
			$this->Body = str_replace("[url_sito]", $url_sito, $this->Body);
			$this->Body = str_replace("[n_ordine]", $n_ordine, $this->Body);
			$this->Body = str_replace("[codice_traking]", $codice_traking, $this->Body);
			$this->Body = str_replace("[promo_newsletter]", $promo_newsletter, $this->Body);
			$this->Body = str_replace("[quota_minima]", $quota_minima, $this->Body);
			
		}		
	}
	
	
	/**
	 * imposta le informazioni sul mittente della mail
	 * 
	 * @param string $mail_mitt indirizzo mail mittente
	 * @param string $nome_mitt nome visualizzato [default: ""]
	 */
	public function set_mittente($mail_mitt, $nome_mitt = ""){
		$this->From = $mail_mitt;
		$this->FromName = $nome_mitt;		
	}
	
    
    public function add_ccn($mail_ccn){
        $this->AddBCC($mail_ccn);
		$this->bcc[] = $mail_ccn;
    }

	public function reset_postman_bcc(){
		$this->bcc = array();
    }

    /**
	 * recupera da db l'oggetto e il testo della mail appartenente alla categoria passata e li imposta nell'oggetto.
	 * 
	 * @param string $cat_mail il tipo di mail di cui recuperare oggetto e testo
	 */
	public function text_from_db($cat_mail, $informazione){
		
		$cat_mail = formatsql($cat_mail);
		
		$sql = "SELECT oggetto, testo, firma FROM testi_mail WHERE cat_mail='$cat_mail' AND lingua=".lingua_impostata();
		$par_mail = $this->connessione()->query_risultati($sql);
		
		$this->CharSet = $this->charset_email;
		
		if(count($par_mail) > 0){
			
			//$this->Subject = html_entity_decode($par_mail[0]["oggetto"], ENT_QUOTES, "ISO-8859-1");
			$this->Subject = html_entity_decode($par_mail[0]["oggetto"], ENT_QUOTES, $this->charset_email);
			$this->MsgHTML("<html><head></head><body><div style='".$this->header_email."'>".$par_mail[0]["testo"].$informazione.$par_mail[0]["firma"]."</div></body></html>");
			
		}else{
			
			$this->Subject = "";
			$this->MsgHTML("<html><head></head><body><div style='".$this->header_email."'><p>$informazione</p></div></body></html>");
			
		}
		
		$this->Close();
		
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