<?
date_default_timezone_set('Europe/Rome');
require_once('classi/master_class.php');

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
		<title>Rasp-url</title>
	</head>
	<body style="margin: 0px;overflow: hidden;">
		<iframe id="iframe1" src="blanck.php" data-id="" style="width:<?=$larghezza?>px;height:<?=$altezza?>px;overflow:hidden;overflow-y: scroll; position: absolute; top:<?=$y_offset?>px;left:<?=$x_offset?>px" frameBorder="0" allow="autoplay;fullscreen"></iframe> 




 <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
 <script>
 
  $("#bottone").click(function(e) {
      e.preventDefault();
      var src = "https://app.innovafarmacia.it//sites/mastersumo/monitor/pfizer/viagra";

      $('#iframe1').fadeOut(1000,function(){
          $('#iframe1').attr('src',src ).load(function(){
              $(this).fadeIn(1000);    
          });
      });

 });
 
$(document).ready(function() {
	var altezza = $( window ).height()+"px";
	
	
});
 
var url_code = 0;
 
function aggiorna_url(){
	$.ajax({
		url: "script/get_url.php",
		method: "POST",
		
		dataType: "json",
		success: function (json_risposta) {
			console.log(json_risposta);
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
		$('#iframe1').attr('src',url ).load(function(){
			$(this).fadeIn(1000);    
		});
	});
}
 
 </script>
 
	</body>
</html>
