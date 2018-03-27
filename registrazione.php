<?
date_default_timezone_set('Europe/Rome');

require_once('classi/master_class.php');

require_once('classi/classe_AESCipher.php');
//echo URL_SERVER."registrazione_dispositivo";


/*
$cipher = new classe_AESCipher(SERIAL_NUMBER_KEY);

$info = json_encode(['serial_number'=>SERIALE]);

$k = urlencode($cipher->encrypt($info));
*/
$k = SERIALE;

?>
<html>
	<head>
		
	</head>
	<body style="text-align: center;">
		<img src="https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=<?=URL_SERVER."registrazione_dispositivo"?>?k=<?=$k?>&choe=UTF-8"/>
		<br/>
		<?=URL_SERVER."registrazione_dispositivo"?>?k=<?=$k?>
	</body>
</html>
