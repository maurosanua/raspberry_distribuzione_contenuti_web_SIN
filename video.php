<?php
date_default_timezone_set('Europe/Rome');
require_once('classi/master_class.php');

$conn = new DB_PDO();


$scena_id = isset($_GET["scena_id"])?$_GET["scena_id"]:0;

$scena_obj = new classe_scene($scena_id);

$contenuto = $scena_obj->get_contenuti(0);

$conn->Close();
?>
<html>
	<head>
		
	</head>
	<body style="margin: 0px;">
		<video width="100%" autoplay loop>
			<source src="contents/video/<?=$contenuto?>" type="video/mp4">

		  Your browser does not support the video tag.
		  </video>
	</body>
</html>
