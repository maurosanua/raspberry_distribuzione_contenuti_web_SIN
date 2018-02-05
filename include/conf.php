<?php

/*	DATABASE
-------------------------------------------------------------------------------------------------------------------------------- */
//define("DB_TYPE",		"MSSQL");
define("DB_TYPE",		"MySQL");
define("DB_HOST",		"172.16.30.4");
define("DB_NAME",		"ggate_raspberry_sviluppo");
define("DB_USER",		"ggate_rasp");
define("DB_PASSWORD",	"skhe3olxzmclerurww");


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