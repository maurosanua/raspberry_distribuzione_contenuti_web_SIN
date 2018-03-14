<?php
date_default_timezone_set('Europe/Rome');
require_once('classi/master_class.php');

$conn = new DB_PDO();


$scena_id = isset($_GET["scena_id"])?$_GET["scena_id"]:0;

$scena_obj = new classe_scene($scena_id);

$contenuto = $scena_obj->get_link(0);

$conn->Close();
?>
<html>
	<head>
		
	</head>
	<body style="margin: 0px;">
		<iframe class="videoContainer__video" width="1920" height="1080" src="http://www.youtube.com/embed/<?=$contenuto?>?modestbranding=1&autoplay=1&controls=0&fs=0&loop=1&rel=0&showinfo=0&disablekb=1&playlist=IsBInsOj8TY" frameborder="0"></iframe>

	</body>
</html>
