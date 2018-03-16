<?php

/*	PARAMETRI VARI
-------------------------------------------------------------------------------------------------------------------------------- */
define("COOKIE_NAME", "milanoFilmFestival_cookie");
define("COOKIE_SALT", "stringa_per_generare_milanoFilmFestival_cookie");

define("HASH_TYPE", "sha256");



define("URL", "localhost");
define("URL_COMPLETO", "http://localhost/");
define("MAIL_SEGNALAZIONI", "info@jsmservice.eu");
define("NOME_SITO", "Template Website");

define("LARGHEZZA_IMM_PICCOLA", '265');
define("ALTEZZA_IMM_PICCOLA", '170');

define("LARGHEZZA_IMM_MIDDLE", '550');
define("ALTEZZA_IMM_MIDDLE", '365');

define("LARGHEZZA_IMM_GRANDE", '835');
define("ALTEZZA_IMM_GRANDE", '453');


$estensioni_ammesse = array(".jpg", ".png", ".jpeg");

define("PATTERN_FILENAME", "/[^a-zA-Z0-9_\(\)-]/s"); //pattern per escludere i caratteri non supportati

if(is_dir("imgs/sfondi/pagine/")){
	define("SFONDI_PAGINE", "imgs/sfondi/pagine/");
	
}elseif(is_dir("../imgs/sfondi/pagine/")){
	define("SFONDI_PAGINE", "../imgs/sfondi/pagine/");
	
}elseif(is_dir("../../imgs/sfondi/pagine/")){
	define("SFONDI_PAGINE", "../../imgs/sfondi/pagine/");
	
}elseif(is_dir("../../../imgs/sfondi/pagine/")){
	define("SFONDI_PAGINE", "../../../imgs/sfondi/pagine/");	

}elseif(is_dir("../../../../imgs/sfondi/pagine/")){
	define("SFONDI_PAGINE", "../../../../imgs/sfondi/pagine/");	

}else{
	define("SFONDI_PAGINE", "dir_not_found");
}


if(is_dir("imgs/logo/")){
	define("LOGO", "imgs/logo/");
	
}elseif(is_dir("../imgs/logo/")){
	define("LOGO", "../imgs/logo/");
	
}elseif(is_dir("../../imgs/logo/")){
	define("LOGO", "../../imgs/logo/");
	
}elseif(is_dir("../../../imgs/logo/")){
	define("LOGO", "../../../imgs/logo/");	

}elseif(is_dir("../../../../imgs/logo/")){
	define("LOGO", "../../../../imgs/logo/");	

}else{
	define("LOGO", "dir_not_found");
}


if(is_dir("imgs/logo2/")){
	define("LOGO2", "imgs/logo2/");
	
}elseif(is_dir("../imgs/logo2/")){
	define("LOGO2", "../imgs/logo2/");
	
}elseif(is_dir("../../imgs/logo2/")){
	define("LOGO2", "../../imgs/logo2/");
	
}elseif(is_dir("../../../imgs/logo2/")){
	define("LOGO2", "../../../imgs/logo2/");	

}elseif(is_dir("../../../../imgs/logo2/")){
	define("LOGO2", "../../../../imgs/logo2/");	

}else{
	define("LOGO2", "dir_not_found");
}



if(is_dir("upload/images/")){
	define("IMAGES", "upload/images/");
	
}elseif(is_dir("../upload/images/")){
	define("IMAGES", "../upload/images/");
	
}elseif(is_dir("../../upload/images/")){
	define("IMAGES", "../../upload/images/");
	
}elseif(is_dir("../../../upload/images/")){
	define("IMAGES", "../../../upload/images/");	

}elseif(is_dir("../../../../upload/images/")){
	define("IMAGES", "../../../../upload/images/");	

}else{
	define("IMAGES", "dir_not_found");
}


if(is_dir("pdf/")){
	define("PDF", "pdf/");
	
}elseif(is_dir("../pdf/")){
	define("PDF", "../pdf/");
	
}elseif(is_dir("../../pdf/")){
	define("PDF", "../../pdf/");
	
}elseif(is_dir("../../../pdf/")){
	define("PDF", "../../../pdf/");	


}else{
	define("PDF", "dir_not_found");
}


define("IMAGES_DIR", "contents/images");
define("VIDEO_DIR", "contents/video");

define('SERIAL_NUMBER_KEY','jsm_serial_key');
define ("SERIALE", "00000000ae9d38d5");
?>