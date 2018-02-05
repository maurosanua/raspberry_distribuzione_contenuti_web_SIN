<?php
/**
 * Description of classe_ldap
 *
 * @author fabio
 */
class classe_ldap {
	
	public static $versione = "1.0";
	public static $changelog = "
1.0	--	Versione base della classe per interagire con Active Directory
";
	
	
	private $AD_Server = "";
	private $domain = "";
	private $LDAP_conn = null;
    private $user = "";
    private $bind = null;
            
	function __construct($server = "", $dominio = "") {
		
		if(isset($server) && strlen($server) > 0){$this->AD_Server = $server;} else {$this->AD_Server = AD_SERVER;}
		if(isset($dominio) && strlen($dominio) > 0){$this->domain = $dominio;} else {$this->domain = AD_DOMAIN;}
		
		$this->LDAP_conn = ldap_connect($this->AD_Server);
		
	}
	
	
	/**
	 * La funziona prova a fare login su Active Directory;
	 * in caso di errore termina l'esecuzione della pagina
	 * 
	 * @param string $user
	 * @param string $password
	 */
	public function check_login($user, $password){
		
		try{
			$LDAP_bind = @ldap_bind($this->LDAP_conn, $this->domain."\\".$user, $password);
		}catch (Exception $e){
			die();
		}
		
		if($LDAP_bind===false){
			die();
		}		
	}
	
	/**
	 * La funziona prova a fare login su Active Directory e ne ritorna l'esito
	 * 
	 * @param string $user
	 * @param string $password
	 * @return boolean esito dell'autenticazione
	 */
	public function check_login_bool($user, $password){
		
        $this->user = $user;
        
		try{
			$LDAP_bind = @ldap_bind($this->LDAP_conn, $this->domain."\\".$user, $password);
		}catch (Exception $e){
			return false;
		}
		
		if($LDAP_bind !== false){
            
            $this->bind=$LDAP_bind;
             
             
            //questo funziona             
            //$accounts_searchResult = ldap_search( $this->LDAP_conn, "ou=casaj,dc=sinergo,dc=it", "memberof=CN=casaj,OU=casaj,DC=sinergo,DC=it" );
//             
//             $accounts_searchResult = ldap_search( $this->LDAP_conn, "ou=casaj,dc=sinergo,dc=it", "sAMAccountName=$user" );
//             
//             $entry = ldap_get_entries($this->LDAP_conn,$accounts_searchResult);
//             
//             //var_dump($entry);
//             $contatore = count($entry);
//             
//             
//             if ($contatore>1){
//                 return true;
//             }
//             $contatore = count($entry[0]['memberof'])-1;
//             
//             for($i=0;$i<$contatore;$i++){
//                 echo $entry[0]['memberof'][$i]."<br>";
////                foreach ($entry[$i]['cn'] as $gruppo){
////                    echo $gruppo."<br>";
////                }
//             }




			return true;
		}
		
		return false;		
	}
    
    
    
    public function check_group($gruppo_search){
        
        

		
		if($this->bind){
            
            
             
             
            //questo funziona             
            //$accounts_searchResult = ldap_search( $this->LDAP_conn, "ou=casaj,dc=sinergo,dc=it", "memberof=CN=casaj,OU=casaj,DC=sinergo,DC=it" );

             $accounts_searchResult = ldap_search( $this->LDAP_conn, "ou=sin,dc=sinergo,dc=it", "(&(sAMAccountName=$this->user)(memberof=CN=$gruppo_search,OU=sin,DC=sinergo,DC=it))" );
             
             $entry = ldap_get_entries($this->LDAP_conn,$accounts_searchResult);
             
            // var_dump($entry);
             $contatore = count($entry);

             
             if ($contatore>1){
                 
                 return true;
             }

			return false;
		}
		
		return false;	        
        
    }        
        
    




    public function get_user_groups(){
        
        $elenco_gruppi= array();

		
		if($this->bind){
            
            
             
             $gruppo_search = "antifurto";
            //questo funziona             
            //$accounts_searchResult = ldap_search( $this->LDAP_conn, "ou=casaj,dc=sinergo,dc=it", "memberof=CN=casaj,OU=casaj,DC=sinergo,DC=it" );
             
             $accounts_searchResult = ldap_search( $this->LDAP_conn, "ou=sin,dc=sinergo,dc=it", "sAMAccountName=$this->user" );
             
             $entry = ldap_get_entries($this->LDAP_conn,$accounts_searchResult);
             
            // var_dump($entry);
             $contatore = count($entry);

             
             if ($contatore>1){
                 
                 return $entry[0]['memberof'];
             }
//             $contatore = count($entry[0]['memberof'])-1;
//             
//             for($i=0;$i<$contatore;$i++){
//                 echo $entry[0]['memberof'][$i]."<br>";
////                foreach ($entry[$i]['cn'] as $gruppo){
////                    echo $gruppo."<br>";
////                }
//             }




			return $elenco_gruppi;
		}
		
		return $elenco_gruppi;	        
        
    }
    
    
    
    function get_pid(){
        return 1;
    }
	
}

?>