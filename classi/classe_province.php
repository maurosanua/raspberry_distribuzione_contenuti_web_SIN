<?php
			
class classe_province extends base_province {
	
	public static $versione = "2.0";
	public static $changelog = "
1.0	--	Verifica dell'oggetto connessione su piu' classi e creazione nuove connessioni con la nuova classe_DB

1.0	--	Versione base: contiene il metodo elenco_province() comodo per integrare agilmente i menu a tendina
	";
	
	
	private $elenco_province = null;
	
	public function __construct($id = 0) {
		parent::__construct($id);
	}
	
	/****************
	 * metodi ad hoc
	 */
	 
	
	/**
	 * restituisce un array di tipo sql con tutte le province
	 * 
	 * @return array chiave => valore : "nome" => "valore"
	 */
	public function elenco_province(){
		
		if(!isset($this->elenco_province)){
			
			$sql = "SELECT nome, valore FROM province ORDER BY valore";
			$this->elenco_province = $this->connessione()->query_risultati($sql);

			$this->Close();
		}
		
		return $this->elenco_province;
	}

}




class base_province {

	protected $is_connesso = 0;
	protected $destroy_conn = 0;
	protected $db_conn = null;
	protected $db_reset = false;

	protected $id = 0;
	
	private $exist = false;

	private $nome = null;
	private $valore = null;
	private $regione = null;
	private $ordine = null;

	private $errore = false;
	protected $attr = array();


	public function __construct($id = 0) {

		if(!isset($id) || !is_numeric($id)){$id = 0;}
		$this->id = $id;


		$this->attr[] = $this->nome = new attributo("nome");
		$this->attr[] = $this->valore = new attributo("valore");
		$this->attr[] = $this->regione = new attributo("regione");
		$this->attr[] = $this->ordine = new attributo("ordine", "int");

		if($id > 0){

			$sql = "SELECT * FROM province WHERE id_provincia = ?";
			$dati_query = array($this->id);

			$arr = $this->connessione()->query_risultati($sql, $dati_query);
			$this->Close();

			if(count($arr) > 0){

				$this->exist = true;

				$this->nome->set_valore($arr[0]["nome"]);
				$this->valore->set_valore($arr[0]["valore"]);
				$this->regione->set_valore($arr[0]["regione"]);
				$this->ordine->set_valore($arr[0]["ordine"]);

			}else{
				throw new Exception("Element Not Found", -1); //indicare qui l'eccezione specifica da lanciare
			}	

		}	
	}



	/*********************************
	 * aggiungiamo qua i metodi ad hoc
	 */

	// da ora mettiamo i metodi personalizzati all'interno della classe estesa, cosi' che sia piu' facile aggiornare il codice
	




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
			$log->set_nome_azione("delete province");
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
			if($this->id == 0){

				$sql = "INSERT INTO province (nome, valore, regione, ordine) VALUES (?, ?, ?, ?)";

				$dati_query = array(
								$this->nome->get_valore(99), 
								$this->valore->get_valore(99), 
								$this->regione->get_valore(99), 
								$this->ordine->get_valore(99)
							);

				
				$this->connessione()->esegui_query($sql, $dati_query);
				$this->Close();


				$this->id = $this->connessione()->ultimo_id();


				/* -----------------------------
				 * LOG
				 *------------------------------------*/
				$log = new classe_log();

				if($log->enabled()){
					$log->set_nome_azione("insert province");
					$log->set_tipo_azione("insert");
					$log->set_info("id: ".$this->id); //informazioni aggiuntive

					$log->inserisci();
				}
				/*--------------------------------------*/



			}else{//aggiorniamo l'oggetto

				$sql = "UPDATE province SET nome = ?, valore = ?, regione = ?, ordine = ?"
						." WHERE id_provincia = ?";

				$dati_query = array(
								$this->nome->get_valore(99), 
								$this->valore->get_valore(99), 
								$this->regione->get_valore(99), 
								$this->ordine->get_valore(99), 
								$this->id
							);

				

				$this->connessione()->esegui_query($sql, $dati_query);
				$this->Close();



				/* -----------------------------
				 * LOG
				 *------------------------------------*/
				$log = new classe_log();

				if($log->enabled()){
					$log->set_nome_azione("update province");
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
	 * @param string $nome
	 * @return boolean true se il valore ? corretto, false altrimenti
	 */
	public function set_nome($nome) {
		$this->nome->set_valore($nome);
		return $this->nome->is_corretto();
	}
	
	/**
	 * 
	 * @param string $valore
	 * @return boolean true se il valore ? corretto, false altrimenti
	 */
	public function set_valore($valore) {
		$this->valore->set_valore($valore);
		return $this->valore->is_corretto();
	}
	
	/**
	 * 
	 * @param string $regione
	 * @return boolean true se il valore ? corretto, false altrimenti
	 */
	public function set_regione($regione) {
		$this->regione->set_valore($regione);
		return $this->regione->is_corretto();
	}
	
	/**
	 * 
	 * @param string $ordine
	 * @return boolean true se il valore ? corretto, false altrimenti
	 */
	public function set_ordine($ordine) {
		$this->ordine->set_valore($ordine);
		return $this->ordine->is_corretto();
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
	
	public function get_valore($formattazione_dato = 1) {
		return $this->valore->get_valore($formattazione_dato);
	}
	
	public function get_regione($formattazione_dato = 1) {
		return $this->regione->get_valore($formattazione_dato);
	}
	
	public function get_ordine($formattazione_dato = 1) {
		return $this->ordine->get_valore($formattazione_dato);
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