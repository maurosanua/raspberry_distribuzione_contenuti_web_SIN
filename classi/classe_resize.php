<?php
class resize{
	
	public static $versione = "1.0";
	public static $changelog = "
1.0	--	Classe per ridimensionare automaticamente le immagini. Recuperata dal web e introdotta senza modifiche alla versione attuale.
	";
	
	
	var $urlimage = "";
	var $maxX = 100;
	var $maxY = 100;
	var $fisso = 1;
	var $latofisso = "XY";
	var $folder = "public/";
	var $newName = "";
	//valorimax
	var $valorimax = 1; 
	//valorimax -> 0/1 ->vale solo per latofisso=XY
	//se 1 valori max di X Y, se no valori minimi X Y.
	
	
	function controlladati(){
		$this->errore = array();
		$this->Estensione = "";
		$this->originalX = 0;
		$this->originalY = 0;
		$this->originalName = "";
		$this->newX = 0;
		$this->newY = 0;
		
		if($this->urlimage==""){
			array_push($this->errore,"Scegliere un file da ridimensionare");
		}elseif(!file_exists($this->urlimage) || !is_file($this->urlimage)){
			array_push($this->errore,"Il file selezionato non esiste");
		}
		if(!is_numeric($this->maxX) || !is_numeric($this->maxY) || $this->maxX<0 || $this->maxY<0){
			array_push($this->errore,"L'altezza e la larghezza dell'immagine devono essere numerici");
		}
		if(!file_exists($this->folder)){
			array_push($this->errore,"La cartella di destinazione non esiste o non e' scrivibile");
		}
		if($this->fisso!=0 && $this->fisso!=1){
			array_push($this->errore,"La variabile di dimensione fissa deve essere 0 o 1");
		}
		if($this->latofisso!="XY" && $this->latofisso!="X" && $this->latofisso!="Y"){
			array_push($this->errore,"La variabile di lato fisso devono essere X o Y o XY");
		}
		if(count($this->errore)>0){
			return false;
		}else{
			return true;
		}
	}
	
	function go(){
		if($this->controlladati()){
			$filename = basename($this->urlimage);
			$this->originalName = $filename;
			if($this->newName==""){$this->newName=$filename;}
			$this->Estensione = strtolower(substr($filename, strrpos($filename, "."), strlen($filename)-strrpos($filename, ".")));
			if($this->Estensione==".jpeg" || $this->Estensione==".jpg"){
				$handle_immagine = imagecreatefromjpeg($this->urlimage);
			}elseif($this->Estensione==".gif"){
				$handle_immagine = imagecreatefromgif($this->urlimage);
			}elseif($this->Estensione==".png"){
				$handle_immagine = imagecreatefrompng($this->urlimage);
			}else{
				array_push($this->errore,"Formato immagine non valido");
				return null;
			}
			$handle_immagine_adattata=$this->adatta($handle_immagine);
            
           
			imagejpeg($handle_immagine_adattata, $this->folder.$this->newName, 100);

			chmod($this->folder.$this->newName,0777);
			unset($handle_immagine);
			unset($handle_immagine_adattata);
		}
	}
	
	function adatta($handle_immagine){
		$this->originalX=imagesx($handle_immagine);
		$this->originalY=imagesy($handle_immagine);
		if($this->fisso==1){
			$this->newX=$this->maxX;
			$this->newY=$this->maxY;
		}else{
			if($this->latofisso=="XY"){
				if($this->valorimax==1){
					if ($this->originalX/$this->originalY>$this->maxX/$this->maxY) {
						$this->newX=$this->maxX;
						$this->newY=($this->originalY/$this->originalX)*$this->maxX;
					} else {
						$this->newX=($this->originalX/$this->originalY)*$this->maxY;
						$this->newY=$this->maxY;
					}
				}else{
					if ($this->originalX/$this->originalY>$this->maxX/$this->maxY) {
						$this->newX=($this->originalX/$this->originalY)*$this->maxY;
						$this->newY=$this->maxY;
					} else {
						$this->newX=$this->maxX;
						$this->newY=($this->originalY/$this->originalX)*$this->maxX;
					}
				}
			}elseif($this->latofisso=="X"){
				$this->newX=$this->maxX;
				$this->newY=($this->originalY/$this->originalX)*$this->maxX;
			}elseif($this->latofisso=="Y"){
				$this->newX=($this->originalX/$this->originalY)*$this->maxY;
				$this->newY=$this->maxY;
			}else{
				if ($this->originalX/$this->originalY>$this->maxX/$this->maxY) {
					$this->newX=$this->maxX;
					$this->newY=($this->originalY/$this->originalX)*$this->maxX;
				} else {
					$this->newX=($this->originalX/$this->originalY)*$this->maxY;
					$this->newY=$this->maxY;
				}
			}
		}
		$tmp_immagine = imagecreatetruecolor($this->newX, $this->newY);
		$handle_immagine_adattata = imagecopyresampled($tmp_immagine, $handle_immagine, 0, 0, 0, 0, $this->newX, $this->newY, $this->originalX, $this->originalY);
		return $tmp_immagine;
		
	}

}
?>