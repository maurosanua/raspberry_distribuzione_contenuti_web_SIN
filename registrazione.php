<?
date_default_timezone_set('Europe/Rome');
require_once('classi/master_class.php');

//echo URL_SERVER."registrazione_dispositivo";
?>
<html>
	<head>
		
	</head>
	<body style="text-align: center;">
		<img src="https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=<?=URL_SERVER."registrazione_dispositivo"?>/<?=SERIALE?>&choe=UTF-8"/>
		<br/>
		<?=URL_SERVER."registrazione_dispositivo"?>/<?=SERIALE?>
	</body>
</html>
