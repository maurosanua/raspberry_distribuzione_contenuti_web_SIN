<?php

class classe_DB extends DB_PDO{
	
}

class DB_PDO{
	
		
	private $DB_conn = null;
	private $error_code = 0;
	private $error_message = "";
	private $attivo = false;


	public static $versione = "4.0";
	public static $changelog = "
4.0	--	Connessione al DB con codifica variabile (default abilitato in utf-8)
	--	ATTENZIONE! Se si riporta questa classe nei siti in cui il DB e/o il codice non e' in utf-8 di default e' bene impostare il valore di codifica_db a NULL
	
	--	Metodi begin_transaction e end_transaction equivalenti a quelli della classe MSSQL
	--	Possibilita' di ritornare un booleano se il metodo esegui_query incontra un errore invece di terminare l'esecuzione in modo da poterlo gestire diversamente

3.2	--	Versione PDO
";
	
	
	private $tipologia = "MySQL PDO";
	private $codifica_db = "utf8";  //specifica la codifica con cui vengono trasferiti i dati tra PHP e MySQL, per usare quello di default indicare NULL

	public function get_tipologia() {
		return $this->tipologia;
	}
	
	
	public function get_codifica_db(){
		return $this->codifica_db;
	}

	

	function __construct($host = "", $user = "", $pw = "", $db = ""){
		
		if(strlen($host) <= 0){$host = DB_HOST;}
		if(strlen($user) <= 0){$user = DB_USER;}
		if(strlen($pw) <= 0){$pw = DB_PASSWORD;}
		if(strlen($db) <= 0){$db = DB_NAME;}
		
		
		try{
			$this->DB_conn = new PDO("mysql:host=".$host.";dbname=".$db.($this->codifica_db !== null ? ";charset=".$this->codifica_db : ""), $user, $pw);
			$this->DB_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->attivo = true;
		}
		catch (Exception $e){
			if(defined('DEBUG') && DEBUG){
				$this->error_code = $e->getCode();
				$this->error_message = $e->getMessage();
				
				die("<p>".$e->getMessage());
			}else{
				$this->error_code = $e->getCode();
				$this->error_message = "Failed connecting to DB";
				
				die("<p>DB Error.");
			}
		}
	}
	
	
	public function is_attivo(){
		return $this->attivo;
	}
	
	
	public function Close(){
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
		
		try{
			$STH = $this->DB_conn->prepare($query);
		}catch(Exception $e){
			
			if(defined('DEBUG') && DEBUG){
				$this->error_code = $e->getCode();
				$this->error_message = $e->getMessage();
				
				echo "<p>Query: <br>".$query;
				echo "<p>Param: <br>";
				var_dump($dati);
				
				die("<p>Error:<br>".$e->getMessage());
				
			}else{
				$this->error_code = $e->getCode();
				$this->error_message = "Failed Executing Query";
				
				die("<p>Failed Executing Query.");
			}
		}
		

		
		try{
			
			//se c'e' l'array con i dati lo passiamo alla funzione che esegue la query
			if(isset($dati) && $dati != null && count($dati) > 0){
				$STH->execute($dati);
			}else{ //altrimenti eseguiamo direttamente quello che ci e' stato passato
				$STH->execute();
			}
			
			$STH->setFetchMode(PDO::FETCH_BOTH);
			
		}catch (Exception $e){
			
			if(defined('DEBUG') && DEBUG){
				$this->error_code = $e->getCode();
				$this->error_message = $e->getMessage();
				
				echo "<p>Query: <br>".$query;
				echo "<p>Param: <br>";
				var_dump($dati);
				
				die("<p>Error:<br>".$e->getMessage());
				
			}else{
				$this->error_code = $e->getCode();
				$this->error_message = "Failed Executing Query";
				
				die("<p>Failed Executing Query.");
			}
		}
		
		$ArrayRisultati=Array();
		
		while ($risultati = $STH->fetch()){
		    array_push($ArrayRisultati,$risultati) ;
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
		
		try{
			$STH = $this->DB_conn->prepare($query);
		}catch(Exception $e){
			
			$esito = false;
			
			if(defined('DEBUG') && DEBUG){
				$this->error_code = $e->getCode();
				$this->error_message = $e->getMessage();
				
				if($stop_on_error){
					echo "<p>Query: <br>".$query;
					echo "<p>Param: <br>";
					var_dump($dati);

					die("<p>Error:<br>".$e->getMessage());
					
				}else{
					return $esito;
				}
				
			}else{
				
				$this->error_code = $e->getCode();
				$this->error_message = "Failed Executing Query";
				
				if($stop_on_error){
					die("<p>Failed Executing Query.");
				}else{
					return $esito;
				}
			}
		}
		
		
		try{			
			//se c'e' l'array con i dati lo passiamo alla funzione che esegue la query
			if(isset($dati) && $dati != null && count($dati) > 0){
				$STH->execute($dati);
			}else{ //altrimenti eseguiamo direttamente quello che ci e' stato passato
				$STH->execute();
			}
			
			$STH->setFetchMode(PDO::FETCH_BOTH);
			
		}catch (Exception $e){
			
			$esito = false;
			
			if(defined('DEBUG') && DEBUG){
				$this->error_code = $e->getCode();
				$this->error_message = $e->getMessage();
				
				if($stop_on_error){
					echo "<p>Query: <br>".$query;
					echo "<p>Param: <br>";
					var_dump($dati);

					die("<p>Error:<br>".$e->getMessage());
					
				}else{
					return $esito;
				}
				
			}else{
				$this->error_code = $e->getCode();
				$this->error_message = "Failed Executing Query";
				
				if($stop_on_error){
					die("<p>Failed Executing Query.");
				}else{
					return $esito;
				}
			}
		}
		
		return $esito;
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
		
		$query = "select LAST_INSERT_ID() as ultimoid";
		
		try{
			$STH = $this->DB_conn->prepare($query);
		}catch(Exception $e){
			
			if(defined('DEBUG') && DEBUG){
				$this->error_code = $e->getCode();
				$this->error_message = $e->getMessage();
				
				echo "<p>Query: <br>".$query;
//				echo "<p>Param: <br>";
//				var_dump($dati);
				
				die("<p>Error:<br>".$e->getMessage());
				
			}else{
				$this->error_code = $e->getCode();
				$this->error_message = "Failed Executing Query";
				
				die("<p>Failed Executing Query.");
			}
		}
		
		try{
			
			$STH->execute();
			$STH->setFetchMode(PDO::FETCH_BOTH);
			
		}catch (Exception $e){
			
			if(defined('DEBUG') && DEBUG){
				$this->error_code = $e->getCode();
				$this->error_message = $e->getMessage();
				
				echo "<p>Query: <br>".$query;
//				echo "<p>Param: <br>";
//				var_dump($dati);
				
				die("<p>Error:<br>".$e->getMessage());
				
			}else{
				$this->error_code = $e->getCode();
				$this->error_message = "Failed Executing Query";
				
				die("<p>Failed Executing Query.");
			}
		}
		
		$ArrayRisultati=Array();
		
		while ($risultati = $STH->fetch()){
		    array_push($ArrayRisultati,$risultati) ;
		}
		
		if(isset($ArrayRisultati[0]['ultimoid'])){
			return $ArrayRisultati[0]['ultimoid'];
		}else{
			return "";
		}
		
	}
	
	
	/**
	 * inizia una TRANSACTION (sara' necessario invocare il metodo che la chiude)
	 */
	public function begin_transaction(){
		
		$sql = "START TRANSACTION";
		$this->esegui_query($sql);
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
			$this->esegui_query("COMMIT");
			$result = true;
			//echo "Transaction committed.<br />";
		} else {
			$this->esegui_query("ROLLBACK");
			$result = false;
			//echo "Transaction rolled back.<br />";
		}
		
		return $result;
	}
	
}

?>