<?php

/**
 * versione riscritta per interagire con database MS SQL
 */

class classe_DB extends MS_SQL{
	
}

class MS_SQL{
	
		
	private $DB_conn = null;
	private $error_code = 0;
	private $error_message = "";
	private $attivo = false;
	
	public static $versione = "1.5";
	public static $changelog = "
1.5	--	Supporto per la codifica UTF-8

1.4	--	Aggiunta del metodo is_attivo() che ritorna TRUE se la connessione e' attiva verso il DB (se non e' stata chiusa)

1.3	--	Possibilita' di ritornare un booleano se il metodo esegui_query incontra un errore invece di terminare l'esecuzione in modo da poterlo gestire diversamente
	--	Metodi begin_transaction e end_transaction per gestire le transaction in SQL Server

1.2	--	Versione base
";
	
	
	private $tipologia = "MSSQL";
	private $codifica_db = "UTF-8";  //specifica la codifica con cui vengono trasferiti i dati tra PHP e MySQL, per usare quello di default indicare NULL
	
	public function get_codifica_db(){
		return $this->codifica_db;
	}
	
	public function get_tipologia() {
		return $this->tipologia;
	}
	
	function __construct($host = "", $user = "", $pw = "", $db = ""){
		
		if(strlen($host) <= 0){$host = DB_HOST;}
		if(strlen($user) <= 0){$user = DB_USER;}
		if(strlen($pw) <= 0){$pw = DB_PASSWORD;}
		if(strlen($db) <= 0){$db = DB_NAME;}
		
		
		$connectionInfo = array("UID" => $user, "PWD" => $pw, "Database" => $db);
		
		if($this->codifica_db !== null){
			$connectionInfo["CharacterSet"] = $this->codifica_db;
		}
		
		
		try{
			
			if (!$this->DB_conn = sqlsrv_connect($host, $connectionInfo))
			{
				$this->error_code = -1;
				$this->error_message = print_r(sqlsrv_errors(), true);
				
				if(defined('DEBUG') && DEBUG){
					$this->attivo = false;
					die("<p>".$this->error_message);
				}else{
					$this->attivo = false;
					die("<p>DB Error.");
				}
			}
			
			$this->attivo = true;
		}
		catch (Exception $e){
			if(defined('DEBUG') && DEBUG){
				$this->error_code = $e->getCode();
				$this->error_message = $e->getMessage();
				$this->attivo = false;
				
				die("<p>".$e->getMessage());
			}else{
				$this->error_code = $e->getCode();
				$this->error_message = "Failed connecting to DB";
				$this->attivo = false;
				
				die("<p>DB Error.");
			}
		}
	}
	
	
	public function is_attivo(){
		return $this->attivo;
	}
	
	
	public function Close(){
		sqlsrv_close($this->DB_conn);
		$this->DB_conn = null;
		$this->attivo = false;
	}
	
	
	/**
	 * il metodo riceve una query parametrizzata ed eventualmente l'array con i dati con cui fare il bind, esegue la query e restituisce un array con i risultati
	 * 
	 * @param string $query query in formato parametrico
	 * @param array $dati [optional] array con le informazioni parametriche della query
	 * @return array
	 */
	public function query_risultati($query, $dati = null){
		
		$ArrayRisultati = array();
		
		//sostituisamo eventuali marker nominali con marker di posizione
		$this->prepare_query($query, $dati);
		
		$getResult = sqlsrv_query($this->DB_conn, $query, $dati);
		
		if($getResult === false){
			$this->error_message = print_r(sqlsrv_errors(), true);
			
			if(defined('DEBUG') && DEBUG){								
				die("<p>".$this->error_message);
			}else{
				die("<p>Error Executing Query");
			}
		}
		
		while($row = sqlsrv_fetch_array($getResult, SQLSRV_FETCH_ASSOC)){
			array_push($ArrayRisultati, $row);
		}
		
		return $ArrayRisultati;
		
	}
	
	

	/**
	 * il metodo riceve una query parametrizzata ed eventualmente l'array con i dati con cui fare il bind e la esegue
	 * 
	 * @param string $query query in formato parametrico
	 * @param array $dati [optional] array con le informazioni parametriche della query
	 * @param bool $stop_on_error [defalut: TRUE] se TRUE termina l'esecuzione dello script in caso di errore, se FALSE restituisce l'esito
	 * @return boolean
	 */
	public function esegui_query($query, $dati = null, $stop_on_error = true){
		
		$esito = true;
		
		//sostituisamo eventuali marker nominali con marker di posizione
		$this->prepare_query($query, $dati);
		
		$getResult = sqlsrv_query($this->DB_conn, $query, $dati);
		
		if($getResult === false){
			$this->error_message = print_r(sqlsrv_errors(), true);
			$esito = false;
			
			if($stop_on_error){
				if(defined('DEBUG') && DEBUG){
					die("<p>".$this->error_message);
				}else{
					die("<p>Error Executing Query");
				}
			}
		}
		
		return $esito;
	}
	
	
	/**
	 * inizia una TRANSACTION (sara' necessario invocare il metodo che la chiude)
	 */
	public function begin_transaction(){
		
		if (sqlsrv_begin_transaction($this->DB_conn) === false) {
			
			$this->error_message = print_r(sqlsrv_errors(), true);

			if(defined('DEBUG') && DEBUG){
				die("<p>".$this->error_message);
			}else{
				die("<p>Error on TRANSACTION");
			}
		}
	}
	
	
	/**
	 * termina una TRANSACTION iniziata precedentemente eseguendo un COMMIT o un ROLLBACK a seconda dell'esito
	 * 
	 * @param bool $esito se TRUE esegue un COMMIT, se FALSE il ROLLBACK
	 * @return boolean TRUE se e' stato eseguito il COMMIT, FALSE se abbiamo eseguito il ROLLBACK
	 */
	public function end_transaction($esito = true){
		
		$result = false;
		
		if( $esito ) {
			sqlsrv_commit($this->DB_conn);
			$result = true;
			//echo "Transaction committed.<br />";
		} else {
			sqlsrv_rollback($this->DB_conn);
			$result = false;
			//echo "Transaction rolled back.<br />";
		}
		
		return $result;
	}
	
	
	/**
	 * questa funzione permette di debuggare una singola query parametrica;
	 * 
	 * passando la stringa e l'array con i parametri di cui fare il bind, esegue un replace dei valori e stampa una stringa che e'
	 * grosso modo "analoga" a quella che dovrebbe essere eseguita dal PDO (eccetto gli escape e le sostituzioni particolari)
	 * 
	 * @param string $sql queri parametrica
	 * @param array $dati parametri da "bindare"
	 * @return string query composta
	 */
	public function debug_query($sql, $dati = null){
		
		if(isset($dati) && $dati != null && count($dati) > 0){
			
			if(strpos($sql, "?") !== false){
				
				//sostituiamo i ? in ordine
				foreach ($dati as $value){
					$sql = preg_replace("/\?/", "'".$value."'", $sql, 1);
				}				
			}else{
				foreach ($dati as $key => $value){
					$sql = str_replace(":".$key, "'".$value."'", $sql);
				}
			}
			
		}
		return $sql;
	}
	
	
	/**
	 * restituisce l'ultimo id inserito nel database o una stringa vuota in caso di errore
	 * 
	 * @return type l'ultimo id inserito o una stringa vuota in caso di errore
	 */
	public function ultimo_id(){
		
		$query = "SELECT @@IDENTITY AS id";
		$arr = $this->query_risultati($query);
		
		if(count($arr) > 0){
			return $arr[0]['id'];
		}else{
			return "";
		}		
	}
	
	
	/**
	 * se invocata su una query con i marker nominali (non ? ma :etichetta), modifica la query e l'arry di valori in modo che diventi per posizione
	 * 
	 * @param string $sql la query da modificare
	 * @param array $dati_query array con i valori
	 */
	private function prepare_query(&$sql, &$dati_query){
	
		if($dati_query !== null){
			$chiavi = array_keys($dati_query); //recuperiamo le chiavi dell'array con i valori
			$matches = array();
			$valori = array();

			if(count($chiavi) > 0){

				if(!is_int($chiavi[0])){ //facciamo la sostituzione solo se le chiavi non sono numeriche

					$reg_expr = "/:[a-zA-Z0-9_-]+\b/";

					preg_match_all($reg_expr, $sql, $matches); //andiamo a cercare tutte le parole intere che iniziano per :

					foreach($matches[0] as $value){

						if(isset($dati_query[$value])){$valori[] = $dati_query[$value];} //cerchiamo con anche i due punti 

						$value = str_replace(":", "", $value);
						if(isset($dati_query[$value])){$valori[] = $dati_query[$value];} //cerchiamo dopo aver tolto i :				
					}

					//sostituiamo tutte le etichette con ?
					$sql = preg_replace($reg_expr, "?", $sql);

					//rigeneriamo l'array con i valori
					$dati_query = $valori;
				}		
			}
		}
	}
	
}

?>