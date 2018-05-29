<?php

/*	DATABASE
-------------------------------------------------------------------------------------------------------------------------------- */
//define("DB_TYPE",		"MSSQL");
define("DB_TYPE",		"MySQL");
define("DB_HOST",		"host");
define("DB_NAME",		"db_name");
define("DB_USER",		"db_user");
define("DB_PASSWORD",	"db_password");


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


define("URL_RASPBERRY","http://raspberry_url.it");
define("URL_SERVER","http://palinsesto.it");

/*  PARAMETRI SISTEMA OPERATIVO IN USO
-------------------------------------------------------------------------------------------------------------------------------- */
define("SISTEMA", "linux");
?>