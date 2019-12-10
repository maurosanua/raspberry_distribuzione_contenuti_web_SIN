<?
date_default_timezone_set('Europe/Rome');
require_once('classi/master_class.php');

//inizializza db conn
$conn = new DB_PDO();


$sql = "SELECT * FROM dispositivi where numero_serie = ?";
$dati_query = array(SERIALE);
$arr = $conn->query_risultati($sql,$dati_query);


$larghezza = 1920;
$altezza = 1080;

$x_offset = 0;
$y_offset = 0;

if(count($arr)>0){
	
	if(strlen($arr[0]["iframe_w"])>0){
		$larghezza = $arr[0]["iframe_w"];
	}
	
	if(strlen($arr[0]["iframe_h"])>0){
		$altezza = $arr[0]["iframe_h"];
	}

	if(strlen($arr[0]["iframe_x"])>0){
		$x_offset = $arr[0]["iframe_x"];
	}

	if(strlen($arr[0]["iframe_y"])>0){
		$y_offset = $arr[0]["iframe_y"];
	}
	
}
?><html>
	<head>
		<title>Palinsesto Facile</title>
	</head>
	<body style="margin: 0px;overflow: hidden;background-color:#000000">
		<iframe id="iframe1" src="blank.php" data-id="" style="width:<?=$larghezza?>px;height:<?=$altezza?>px;overflow:hidden;overflow-y: scroll; position: absolute; top:<?=$y_offset?>px;left:<?=$x_offset?>px" frameBorder="0" allow="autoplay;fullscreen"></iframe> 




 <script src="./jquery.min.js"></script>
 <script>
 
$(document).ready(function() {
	var altezza = $( window ).height()+"px";
	
	
});
 
var url_code = 0;
 
function aggiorna_url(){
	$.ajax({
		url: "script/get_url.php?date="+new Date(),
		method: "POST",
		
		dataType: "json",
		success: function (json_risposta) {
			console.log("PalinsestoFacileSanityCheck");
			console.log((new Date).toISOString()+JSON.stringify(json_risposta));
			if (json_risposta.status === "OK"){

				if(json_risposta.code != url_code){
					
					url_code = json_risposta.code;
					change_url(json_risposta.url, url_code);
				}
			}else{
			}
		},
		error: function(XMLHttpRequest, textStatus, errorThrown) { 
			//messaggio o gestione dell’errore
			console.log("Status: " + textStatus); 
			console.log("Error: " + errorThrown); 
		 }
	});

}
 

function aggiorna_loop(){
	aggiorna_url();
	setTimeout(function(){ aggiorna_loop(); }, 1000);
}
 
aggiorna_loop();



function change_url(url, code){
	$('#iframe1').fadeOut(500,function(){
		$('#iframe1').attr('src',url );
		console.log("url changed to ",$('#iframe1').attr('src'))
		$('#iframe1').off("load").load(function(){
			$(this).fadeIn(1000);    
		});
	});
}
 
 </script>
 
	</body>
</html>
