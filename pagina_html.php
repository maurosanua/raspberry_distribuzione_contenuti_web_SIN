<?php
date_default_timezone_set('Europe/Rome');
require_once('classi/master_class.php');

$conn = new DB_PDO();


$scena_id = isset($_GET["scena_id"])?$_GET["scena_id"]:0;

$scena_obj = new classe_scene($scena_id);

$contenuto = $scena_obj->get_campo_html(0);

$conn->Close();
?>
<html>
	<head>
		
	</head>
	<body>
		<?=$contenuto?>
	</body>
</html>