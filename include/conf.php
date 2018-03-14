<?php

/*	DATABASE
-------------------------------------------------------------------------------------------------------------------------------- */
//define("DB_TYPE",		"MSSQL");
define("DB_TYPE",		"MySQL");
define("DB_HOST",		"172.16.30.63");
define("DB_NAME",		"palinsesto");
define("DB_USER",		"root");
define("DB_PASSWORD",		"jsmSinoxDB.1");

define("URL_RASPBERRY","http://l116z.sinergo.it/raspberry");
define("URL_SERVER","http://shire.sinergo.it/palinsesto/");

/*	PARAMETRI DI DEBUG
-------------------------------------------------------------------------------------------------------------------------------- */
define("DEBUG", true);
define("JSON_DEBUG", true);


/*	PARAMETRI DI CONTROLLO
-------------------------------------------------------------------------------------------------------------------------------- */
define("DB_WRITE_DISABLED", false);


/*	PARAMETRI LDAP
-------------------------------------------------------------------------------------------------------------------------------- */
define("LOGIN_AD", false); //serve per dire alla classe_user se fare o meno il login su AD
define("AD_SERVER", "172.16.30.12");
define("AD_DOMAIN", "sinergo");

?>
