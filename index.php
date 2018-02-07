<html>
	<head>
		<title>Rasp-url</title>
	</head>
	<body style="margin: 0px;overflow: hidden;">
<iframe id="iframe1" src="https://app.innovafarmacia.it/sites/mastersumo/monitor/pfizer/thermacare" style="width:100%;overflow:hidden;overflow-y: scroll;"></iframe> 




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
	$("#iframe1").css("height",altezza);

});
 
var url_code = 0;
 
function aggiorna_url(){
	$.ajax({
		url: "script/get_url.php",
		method: "POST",
		
		dataType: "json",
		success: function (json_risposta) {
			if (json_risposta.status === "OK"){
				if(json_risposta.code != url_code){
					url_code = json_risposta.code;
					change_url(json_risposta.url);
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
	aggiorna_url()
	setTimeout(function(){ aggiorna_loop(); }, 3000);
}
 
aggiorna_loop();



function change_url(url){
	$('#iframe1').fadeOut(500,function(){
		$('#iframe1').attr('src',url ).load(function(){
			$(this).fadeIn(1000);    
		});
	});
}
 
 </script>
 
	</body>
</html>
