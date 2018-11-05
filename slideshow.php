<?php
date_default_timezone_set('Europe/Rome');
require_once('classi/master_class.php');

$conn = new DB_PDO();


$scena_id = isset($_GET["scena_id"]) && is_numeric($_GET["scena_id"]) ? trim($_GET["scena_id"]) : 0;

try{
	$scena_obj = new classe_scene($scena_id);
} catch (Exception $ex) {
	$scena_obj = new classe_scene();
}

if(!$scena_obj->exists()){
	$conn->Close();
	die("Scena Non Trovata!");
}


$arr_imm_slide_show = array();

$arr_contenuti = json_decode($scena_obj->get_contenuti(0), TRUE);

if($arr_contenuti !== NULL){

	foreach($arr_contenuti AS $contenuto){

		if(is_file(IMAGES_DIR."/".$contenuto['file'])){
			$url = IMAGES_DIR."/".$contenuto['file'];
			$arr_proprieta_immagine = getimagesize($url);
			$arr_imm_slide_show[] = array(
				'ordine' => $contenuto['ordine'],
				'tempo' => $contenuto['tempo'],
				'url' => $url,
				'larghezza' => $arr_proprieta_immagine[0],
				'altezza' => $arr_proprieta_immagine[1]
			);
		}
	}
}

$json_immagini = json_encode($arr_imm_slide_show);

//var_dump($json_immagini);
//die();

$conn->Close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script src="https://code.jquery.com/ui/1.11.4/jquery-ui.min.js"></script>

</head>
<body style="margin:0px;background-color:#000000;overflow:hidden;">

<div style="width:100%;height:100%; text-align:center;overflow:hidden;">

    <div style="width:100%; text-align:center; position:absolute;overflow:hidden;" id="contenitore">

<?php

	for($i = 0; $i < count($arr_imm_slide_show); $i++){
?>
	<img data-id="<?= $i ?>" src="<?= $arr_imm_slide_show[$i]['url'] ?>" class="immagine" style="" hidden>
<?
	}
?>   

    </div>

</div>

<input type="hidden" name="json" id="json" value='<?= $json_immagini ?>'>

</body>
<script>

	var contatore = 0;
    var contatore_prev = 0;

	$(document).ready(function () {

		//var contatore = 0;

        show();

	});

	function show() {

		var json = $('#json').val();
		
//		console.log(json);
//		return;
		
		var arr_immagini = JSON.parse(json);

		var tot_immagini = arr_immagini.length;

		//console.log('Contatore prima: '+contatore);

		var w = window.innerWidth;
		var h = window.innerHeight;

		// console.log('larghezza f:'+w);
		// console.log('altezza a:'+h);
		// console.log(arr_immagini[contatore]['url']);


        $('.immagine[data-id='+(contatore_prev)+']').fadeOut(500, function () {

            $('.immagine[data-id='+contatore+']').css('max-width', w+'px');
            $('.immagine[data-id='+contatore+']').css('max-height', h+'px');


            if(arr_immagini[contatore]['larghezza'] > w || arr_immagini[contatore]['altezza'] > h){

                //togliere css
                $("body").css("overflow","hidden");
                $('#contenitore').css('top', '0%');
                $('#contenitore').css('margin-top', '0px');

            }else{

                //aggiungere css
                $('#contenitore').css('top', '50%');
                var margin_top = '-'+arr_immagini[contatore]['altezza'] / 2+'px';
                $('#contenitore').css('margin-top', margin_top);
            }



            //$('.immagine[data-id='+contatore+']').attr('src', arr_immagini[contatore]['url']);
            setTimeout("show()",arr_immagini[contatore]['tempo']*1000);
            $('.immagine[data-id='+contatore+']').fadeIn(500);

            contatore_prev = contatore;
			contatore++;

			//console.log('Contatore dopo: '+contatore);

			if(contatore >= tot_immagini){
				contatore = 0;
				//console.log('Contatore a zero: '+contatore);
			}
        });
	}
</script>
</html>