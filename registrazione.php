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
		<div class=row>
			<div> 
				<img src="img/logo.png" style="padding-top: 20px; padding-bottom: 20px">
			</div>
		</div>
			<div>
				<h1>Registrazione nuovo dispositivo 4</h1>
			<br><p>Inquadra il codice QR o digita l'url sottostante per attivare questo dispositivo.<br>Se non visualizzi il QR code verifica la connessione a internet.</p>
			</div>
						
				<img src="https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=<?=URL_SERVER."registrazione_dispositivo"?>?k=<?=$k?>&choe=UTF-8"/>
		<br/>
		<?=URL_SERVER."registrazione_dispositivo"?>?k=<?=$k?>
	</body>
</html>