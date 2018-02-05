<?php

/*
 * scriviamo alcune funzioni comode per l'interazione con le date, dato che i metodi nativi sono ancora piuttosto lacunosi...
 */


class classe_data {
	
	public static $versione = "2.0";
	public static $changelog = "
2.0	--	Aggiunta la compatibilità con il formato timestamp di PostgreSQL

1.6	--	Aggiunta del supporto agli oggetti data e datatime di MS SQL per i metodi print_data e print_datatime

1.5	--	Aggiunta del metodo nome_mese_breve e nome_mese_breve che ritornano il nome del giorno e del mese breve in italiano

1.4	--	Aggiunta dei metodi nome_mese e nome_giorno che ritornano le stringhe relative alla data in oggetto
		Modifica degli array con i nomi sopra citati, resi statici e pubblici

1.3	--	Aggiunta del metodo statico stampa_dataTime() che analogo a stampa_data che stampa la data con anche l'ora

1.2	--	Aggiunta del metodo statico stampa_data() che estende la vecchia funzione print_data() permettendo di specificare una data che non verra' stampata

1.1	--	Aggiunta del metodo statico check_dataTime() per il controllo della data

1.0	--	Versione base della classe
	";
	
	
	private $data;
	private $data_time = false;

	public static $nomi_giorni_settimana = array("1" => "Luned&igrave;", "2" => "Marted&igrave;", "3" => "Mercoled&igrave;", "4" => "Gioved&igrave;", "5" => "Venerd&igrave;", "6" => "Sabato", "7" => "Domenica");
	public static $nomi_mesi = array("01" => 'Gennaio', 
									"02" => 'Febbraio', 
									"03" => 'Marzo', 
									"04" => 'Aprile', 
									"05" => 'Maggio', 
									"06" => 'Giugno', 
									"07" => 'Luglio', 
									"08" => 'Agosto', 
									"09" => 'Settembre', 
									"10" => 'Ottobre', 
									"11" => 'Novembre', 
									"12" => 'Dicembre');
	

	/**
	 * instanzia un oggetto impostando la data attuale se il parametro e' vuoto o la data passata come parametro
	 * 
	 * @param string $data_string [opt] data da impostare nel formato yyyy-mm-dd oppure yyyy-mm-dd H:i:s
	 */
	public function __construct($data_string = "") {
		
		$data_string = trim($data_string);
		
		
		//ora manipoliamo la data
		
		
		$data_string = trim($data_string);
		
		$this->data = new DateTime();
		
		if(strlen($data_string) > 0){
			
			//controlliamo se e' solo data o data/ora
			if(strlen($data_string) == 10){ //data semplice
				
				$data_string = $this->format_data($data_string)." 00:00:00"; //attacchiamo l'ora zero
				$this->data_time = false; //segnamoci che e' solo data e non data/ora
				
			}else{
				$data_string = $this->format_dataTime($data_string);
				$this->data_time = true; //segnamoci che e' data/ora
			}
			
			
			
			if($data_string == "0000-00-00 00:00:00"){
				$data_string = "0001-01-01 00:00:00";
			}
			
			
			
			$data_string = str_replace(" ", "-", $data_string);
			$data_string = str_replace(":", "-", $data_string);
			
			
			//die($data_string);
			
			//andiamo a settare la data passata come parametro
			$arr_data = explode("-", $data_string);
			
			if(count($arr_data) == 6){
			
				$year = $arr_data[0];
				$month = $arr_data[1];
				$day = $arr_data[2];

				$hour = $arr_data[3];
				$minute = $arr_data[4];
				$second = $arr_data[5];

				try{
					$this->data->setDate($year, $month, $day);
					$this->data->setTime($hour, $minute, $second);
				}catch (Exception $e){
					$this->data->setDate(0001, 01, 01);
					$this->data->setTime(00, 00, 00);
				}
				
			}else{ //data non valida per qualche motivo
				$this->data->setDate(0001, 01, 01);
				$this->data->setTime(00, 00, 00);
			}			
		}
		
	}
	
	
	
	public function return_data(){
		return new classe_data($this->print_data());
	}
	
	
	public function return_dataTime(){
		return new classe_data($this->print_data("d/m/Y H:i:s"));
	}
	
	
	public function get_array_settimana(){
		return classe_data::$nomi_giorni_settimana;
	}
	
	
	/**
	 * restituisce true se la data in oggetto ha anche l'ora impostata, false se e' solo data
	 * 
	 * @return boolean
	 */
	public function is_dataTime(){
		return $this->data_time;
	}
	
	
	
	/**
	 * aggiunge (o sottrae) il numero di giorni passato come parametro alla data in oggetto
	 * 
	 * @param int $n_of_days numero di giorni da aggiungere (con segno)
	 */
	public function add_days($n_of_days){
		
		if(!is_numeric($n_of_days)){
			$n_of_days = 0;
		}
					
		try{
			$this->data->modify($n_of_days." day");
		}catch (Exception $e){}

	}
	
	
	/**
	 * aggiunge (o sottrae) il numero di minuti passato come parametro alla data in oggetto
	 * 
	 * @param int $min numero di minuti da aggiungere (con segno)
	 */
	public function add_minutes($min){
		
		if(!is_numeric($min)){
			$min = 0;
		}
					
		try{
			$this->data->modify($min." minute");
		}catch (Exception $e){}
	}
	
	/**
	 * restituisce la data in oggetto formattata secondo quanto impostato
	 * 
	 * @param string $format [otp] formato della data (default: d/m/Y)
	 * @return string data formattata
	 */
	public function print_data($format = "d/m/Y"){
		
		return $this->data->format($format);
		
	}
	
	
	/**
	 * restituisce il numero del giorno nella settimana (da 1 a 7)
	 * contrariamente alla funzione nativa, assegnamo alla domenica il valore 7
	 * 
	 * @return int numero della settimana
	 */
	public function number_of_week(){
		
		if($this->data->format("w") == 0){
			return 7;
		}else{
			return $this->data->format("w");
		}
	}
	
	
	/**
	 * restituisce il primo giorno della settimana della data settata
	 * 
	 * @return \classe_data data del lunedi' della settimana corrente (dell'oggetto impostato)
	 */
	public function inizio_settimana(){
		
		$giorno_settimana = $this->number_of_week();
		
		if($giorno_settimana == 1){
			return new classe_data($this->print_data());
		}else{
			
			$differenza = $giorno_settimana - 1;
			
			$nuova_data = new classe_data($this->print_data());
			$nuova_data->add_days(-$differenza);
			
			return $nuova_data;
			
		}		
	}
	
		/**
	 * aggiunge (o sottrae) il numero di mesi passato come parametro alla data in oggetto
	 * 
	 * @param int $n_of_months numero di mesi da aggiungere (con segno)
	 */
	public function add_months($n_of_months){
		
		if(!is_numeric($n_of_months)){
			$n_of_months = 0;
		}
					
		try{
			$this->data->modify($n_of_months." month");
		}catch (Exception $e){}

	}
	
	
	/**
	 * ritorna la stringa con la data dell'ultimo giorno del mese della data in oggetto
	 * 
	 * @return string
	 */
	public function fine_mese(){		
		return $this->print_data("Y-m-t");
	}
	
	
	/**
	 * restituisce il nome del mese della data in oggetto
	 * 
	 * @return string
	 */
	public function nome_mese(){
		return isset(classe_data::$nomi_mesi[$this->print_data("m")]) ? classe_data::$nomi_mesi[$this->print_data("m")] : "";
	}
	
	
	/**
	 * restituisce il nome del giorno della settimana della data in oggetto
	 * 
	 * @return string
	 */
	public function nome_giorno(){
		return isset(classe_data::$nomi_giorni_settimana[$this->number_of_week()]) ? classe_data::$nomi_giorni_settimana[$this->number_of_week()] : "";
	}
	
	
	public function nome_giorno_breve(){
		return substr($this->nome_giorno(), 0, 3);
	}
	
	
	public function nome_mese_breve(){
		return substr($this->nome_mese(), 0, 3);
	}




	/************************************************
	 * metodi statici per la formattazione delle date
	 *----------------------------------------------------------------------------------*/
	
	/**
	 * controlla che la data sia in formato gg/mm/aaaa e se corretta la restituisce in formato aaaa-mm-gg, altrimenti restituisce una stringa di zeri
	 * 
	 * @param string $data data da validare (gg/mm/aaaa oppure aaaa-mm-gg)
	 * @return string data formattata nel formato aaaa-mm-gg oppure 0000-00-00 in caso di errore
	 */
	public static function format_data($data){
	//controlla che la data sia in formato gg/mm/aaaa e se corretta la restituisce in formato aaaa-mm-gg, altrimenti restituisce una stringa vuota

		
		$data = trim($data);
		
		if(strlen($data)>10){
			$data = substr($data, 0, 10);
		}

		$pattern = "/^(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/[0-9]{4}$/";

		$data_check = preg_match($pattern, $data);

		if($data_check==1){

			$arrDate = explode("/", $data); // break up date by slash
			
			if(count($arrDate) == 3){
			
				$intDay = $arrDate[0];
				$intMonth = $arrDate[1];
				$intYear = $arrDate[2];	

				if (checkdate($intMonth, $intDay, $intYear)){
					return $intYear."-".$intMonth."-".$intDay;
				}else{
					return "0000-00-00";
				}
				
			}else{ //errore sulla data non meglio precisato (e che non dovrebbe verificarsi, una volta arrivati qua)
				return "0000-00-00";
			}


		}else{

			//controlliamo se per caso e' gia' nel formato corretto
			$new_patt = "/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/";
			$data_check_normal = preg_match($new_patt, $data);


			if($data_check_normal==1){
				
				$data = str_replace("-", "/", $data);

				$arrDate = explode("/", $data); // break up date by slash

				if(count($arrDate) == 3){

					$intDay = $arrDate[2];
					$intMonth = $arrDate[1];
					$intYear = $arrDate[0];	

					if (checkdate($intMonth, $intDay, $intYear)){
						return $intYear."-".$intMonth."-".$intDay;
					}else{
						return "0000-00-00";
					}

				}else{ //errore sulla data non meglio precisato (e che non dovrebbe verificarsi, una volta arrivati qua)
					return "0000-00-00";
				}

			}else{
				return "0000-00-00";
			}
		}
	}
	
	
	
	/**
	 * controlla che la data sia nel formato gg/mm/aaaa hh:mm:ss (oppure direttamente in aaaa-mm-gg hh:mm:ss),
	 * che sia una data valida e la restituisce nel formato adatto ad essere inserita in un database (aaaa-mm-gg hh:mm:ss)
	 * 
	 * in caso di non validazione restituisce 0000-00-00 00:00:00
	 * 
	 * @param string $dataTime data nel formato gg/mm/aaaa hh:mm:ss oppure aaaa-mm-gg hh:mm:ss
	 * @return string data formattata in aaaa-mm-gg hh:mm:ss
	 */
	public static function format_dataTime($dataTime){
		
		$dataTime = trim($dataTime);
		
		//$pattern = "/^[0-3][0-9]\/[0-3][0-9]\/[0-9]{4} ([01]?[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/"; //pattern con ora senza il "leading zero" obbligatorio
		
		
		$pattern = "/^(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/[0-9]{4} ([01][0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/"; //pattern con ora con il "leading zero" obbligatorio
		
		$data_check = preg_match($pattern, $dataTime);
		
		if($data_check==1){
						
			//facciamo il replace di tutti i separatori, cosi' e' piu' semplice individuare gli elementi
			$dataTime = str_replace(" ", "/", $dataTime);
			$dataTime = str_replace(":", "/", $dataTime);

			$arrDate = explode("/", $dataTime); // break up date by slash
			
			if(count($arrDate) == 6){
			
				$intDay = $arrDate[0];
				$intMonth = $arrDate[1];
				$intYear = $arrDate[2];

				$intHour = $arrDate[3];
				$intMinute = $arrDate[4];
				$intSecond = $arrDate[5];
			
			
				if (checkdate($intMonth, $intDay, $intYear)){
					return $intYear."-".$intMonth."-".$intDay." ".$intHour.":".$intMinute.":".$intSecond;
				}else{
					return "0000-00-00 00:00:00";
				}
				
			}else{ //errore sulla data non meglio precisato (e che non dovrebbe verificarsi, una volta arrivati qua)
				return "0000-00-00 00:00:00";
			}


		}else{

			//controlliamo se per caso e' gia' nel formato corretto (aaaa-mm-gg hh:ii:ss(.mmm))
			$new_patt = "/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1]) ([01][0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9](.[0-9]+)?$/";
			$data_check_normal = preg_match($new_patt, $dataTime);


			if($data_check_normal==1){
				$dataTime = explode(".",$dataTime)[0];
				
				//facciamo il replace di tutti i separatori, cosi' e' piu' semplice individuare gli elementi
				$dataTime = str_replace(" ", "/", $dataTime);
				$dataTime = str_replace(":", "/", $dataTime);
				$dataTime = str_replace("-", "/", $dataTime);

				//invertiamo anno e giorno rispetto a prima
				
				$arrDate = explode("/", $dataTime); // break up date by slash
				
				if(count($arrDate) == 6){
				
					$intYear = $arrDate[0];
					$intMonth = $arrDate[1];
					$intDay = $arrDate[2];

					$intHour = $arrDate[3];
					$intMinute = $arrDate[4];
					$intSecond = $arrDate[5];

					if (checkdate($intMonth, $intDay, $intYear)){
						return $intYear."-".$intMonth."-".$intDay." ".$intHour.":".$intMinute.":".$intSecond;
					}else{
						return "0000-00-00 00:00:00";
					}
					
				}else{ //errore sulla data non meglio precisato (e che non dovrebbe verificarsi, una volta arrivati qua)
					return "0000-00-00 00:00:00";
				}
				
			}else{
				return "0000-00-00 00:00:00";
			}
		}
		
	}
	
	
	public static function format_dataTime_generic($dataTime){
		
		$pattern = "/^(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/[0-9]{4}( ([01][0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9])?$/";
		
		$data_check = preg_match($pattern, $dataTime);

		if($data_check==1){

			$arrDate = explode("/", $dataTime); // break up date by slash
			$intDay = $arrDate[0];
			$intMonth = $arrDate[1];
			$intYear = $arrDate[2];	

			if (checkdate($intMonth,$intDay, $intYear)){
				return $intYear."-".$intMonth."-".$intDay;
			}else{
				return "0000-00-00 00:00:00";
			}	


		}else{

			//controlliamo se per caso e' gia' nel formato corretto
			$new_patt = "/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])( ([01][0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9])?$/";
			$data_check_normal = preg_match($new_patt, $dataTime);


			if($data_check_normal==1){
				return $dataTime;
			}else{
				return "0000-00-00 00:00:00";
			}
		}
		
	}
	
	
	/**
	 * verifica che la data passata come parametro sia valida, a seconda del tipo di controllo
	 * 
	 * $opt = -1 -> sono ammesse sia date che datetime, nei due formati (gg/mm/aaaa, aaaa-mm-gg, gg/mm/aaaa H:i:s, aaaa-mm-gg H:i:s)
	 * 
	 * $opt = 0 -> sono ammesse SOLO datetime, nei due formati (gg/mm/aaaa H:i:s, aaaa-mm-gg H:i:s)
	 * 
	 * $opt = 1 -> sono ammesse SOLO date, nei due formati (gg/mm/aaaa, aaaa-mm-gg)
	 * 
	 * @param string $dataTime data da validare
	 * @param int $opt [-1 | 0 | 1]
	 * @return boolean esito del controllo
	 */
	public static function check_dataTime($dataTime, $opt = -1){
		
		$esito = false;
		
		$dataTime = trim($dataTime);
		
		//$pattern = "/^[0-3][0-9]\/[0-3][0-9]\/[0-9]{4} ([01]?[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/"; //pattern con ora senza il "leading zero" obbligatorio
		$pattern_1a = "/^(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/[0-9]{4} ([01][0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/"; //pattern con ora con il "leading zero" obbligatorio
		$pattern_1b = "/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1]) ([01][0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/";
		
		$pattern_2a = "/^(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/[0-9]{4}$/";
		$pattern_2b = "/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/";
		
		//$data_check = preg_match($pattern, $dataTime);

		if(preg_match($pattern_1a, $dataTime) == 1 && ($opt == -1 || $opt == 0)){ //controlliamo il formato gg/mm/aaaa hh:ii:ss
			
			//facciamo il replace di tutti i separatori, cosi' e' piu' semplice individuare gli elementi
			$dataTime = str_replace(" ", "/", $dataTime);
			$dataTime = str_replace(":", "/", $dataTime);

			$arrDate = explode("/", $dataTime); // break up date by slash
			
			if(count($arrDate) == 6){
			
				$intDay = $arrDate[0];
				$intMonth = $arrDate[1];
				$intYear = $arrDate[2];

				$intHour = $arrDate[3];
				$intMinute = $arrDate[4];
				$intSecond = $arrDate[5];
			
			
				if (checkdate($intMonth, $intDay, $intYear)){
					$esito = true;
				}else{
					$esito = false;
				}
				
			}else{ //errore sulla data non meglio precisato (e che non dovrebbe verificarsi, una volta arrivati qua)
				$esito = false;
			}


		}elseif(preg_match($pattern_1b, $dataTime) == 1 && ($opt == -1 || $opt == 0)){ //controlliamo se per caso e' gia' nel formato corretto (aaaa-mm-gg hh:ii:ss)
				
			//facciamo il replace di tutti i separatori, cosi' e' piu' semplice individuare gli elementi
			$dataTime = str_replace(" ", "/", $dataTime);
			$dataTime = str_replace(":", "/", $dataTime);
			$dataTime = str_replace("-", "/", $dataTime);

			//invertiamo anno e giorno rispetto a prima

			$arrDate = explode("/", $dataTime); // break up date by slash

			if(count($arrDate) == 6){

				$intYear = $arrDate[0];
				$intMonth = $arrDate[1];
				$intDay = $arrDate[2];

				$intHour = $arrDate[3];
				$intMinute = $arrDate[4];
				$intSecond = $arrDate[5];

				if (checkdate($intMonth, $intDay, $intYear)){
					$esito = true;
				}else{
					$esito = false;
				}

			}else{ //errore sulla data non meglio precisato (e che non dovrebbe verificarsi, una volta arrivati qua)
				$esito = false;
			}
			
			
		}elseif(preg_match($pattern_2a, $dataTime) == 1 && ($opt == -1 || $opt == 1)){ //controlliamo il formato gg/mm/aaaa
			
			$arrDate = explode("/", $dataTime); // break up date by slash
			
			if(count($arrDate) == 3){
			
				$intDay = $arrDate[0];
				$intMonth = $arrDate[1];
				$intYear = $arrDate[2];	

				if (checkdate($intMonth, $intDay, $intYear)){
					$esito = true;
				}else{
					$esito = false;
				}
				
			}else{ //errore sulla data non meglio precisato (e che non dovrebbe verificarsi, una volta arrivati qua)
				$esito = false;
			}
			
			
		}elseif(preg_match($pattern_2b, $dataTime) == 1 && ($opt == -1 || $opt == 1)){ //controlliamo il formato aaaa-mm-gg
			
			$data = str_replace("-", "/", $dataTime);

			$arrDate = explode("/", $data); // break up date by slash

			if(count($arrDate) == 3){

				$intDay = $arrDate[2];
				$intMonth = $arrDate[1];
				$intYear = $arrDate[0];	

				if (checkdate($intMonth, $intDay, $intYear)){
					$esito = true;
				}else{
					$esito = false;
				}

			}else{ //errore sulla data non meglio precisato (e che non dovrebbe verificarsi, una volta arrivati qua)
				$esito = false;
			}
		}
				
		return $esito;
	}
	
	
	
	/**
	 * prende la data in formato mysql e la stampa al contrario (a meno che sia uguale al parametro "escape", in tal caso ritorna una stringa vuota)
	 * 
	 * @param string $data data nel formato aaaa-mm-gg
	 * @param string $escape data da escludere (ad esempio 1800-01-01)
	 * @return string data nel formato gg/mm/aaaa
	 */
	public static function stampa_data($data, $escape = ""){

		if(is_a($data, "DateTime")){			
			$data = $data->format("Y-m-d H:i:s");
		}

		if(strlen($data)==0){
			return "";
		}

		if(strlen($data)>10){
			$data = substr($data, 0, 10);
		}
		
		if($data == $escape){
			return "";
		}


		$arrDate = explode("-", $data); // break up date by slash
		if(count($arrDate)>=3){

			$intDay = $arrDate[2];
			$intMonth = $arrDate[1];
			$intYear = $arrDate[0];		

			return $intDay."/".$intMonth."/".$intYear;
		}else{
			return "";
		}

	}
	
	
	/**
	 * prende la data in formato mysql e la stampa al contrario (a meno che sia uguale al parametro "escape", in tal caso ritorna una stringa vuota)
	 * considerando anche i minuti
	 * 
	 * @param string $data data nel formato aaaa-mm-gg hh:ii:ss
	 * @param string $escape data da escludere (ad esempio 1800-01-01 00:00:00)
	 * @return string data nel formato gg/mm/aaaa hh:ii:ss
	 */
	public static function stampa_dataTime($data, $escape = ""){

		if(is_a($data, "DateTime")){			
			$data = $data->format("Y-m-d H:i:s");
		}

		if(strlen($data)==0){
			return "";
		}

		if($data == $escape){
			return "";
		}
		
		$minuti = "00:00:00";
		
		if(strlen($data)>10){
			$arr = explode(" ", $data);
			
			if(count($arr) == 2){
				$minuti = $arr[1];
				$data = $arr[0];
			}
		}
		
		
		$arrDate = explode("-", $data); // break up date by slash
		if(count($arrDate)>=3){

			$intDay = $arrDate[2];
			$intMonth = $arrDate[1];
			$intYear = $arrDate[0];		

			return $intDay."/".$intMonth."/".$intYear." ".$minuti;
		}else{
			return "";
		}

	}
	
}

?>