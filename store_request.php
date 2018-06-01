<?
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('Europe/Rome');
require_once('classi/master_class.php');
init_sessione();

$conn = new DB_PDO();

$data = file_get_contents('php://input');
file_put_contents('../request_data/request.txt', "\r\n-----------------------------------------------", FILE_APPEND);
file_put_contents('../request_data/request.txt', "\r\nIP: ".(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : "---")." @ ".date("Y-m-d H:i:s")."\r\n", FILE_APPEND);
file_put_contents('../request_data/request.txt', "\r\nGET:\r\n", FILE_APPEND);
file_put_contents('../request_data/request.txt', json_encode($_GET), FILE_APPEND);
file_put_contents('../request_data/request.txt', "\r\nPOST:\r\n", FILE_APPEND);
file_put_contents('../request_data/request.txt', $data, FILE_APPEND);

$arr_persone = json_decode($data,true);

$sql = "SELECT id from log_eventi_rpi where disappearance_datetime is null";
$arr_res = $conn->query_risultati($sql);
$arr_log = array();
foreach($arr_res as $id){
    $arr_log[] = new classe_log_eventi_rpi($id[0]);
}

if(isset($arr_persone["Audience"]) && is_array($arr_persone["Audience"])){
    foreach($arr_persone["Audience"] as $persona){
        $trovato = false;
        foreach($arr_log as $log_evento){
            if($persona["ID"]==$log_evento->get_camera_id(0)){
                unset($log_evento);
                $trovato = true;
                break;
            }
        }
        if(!$trovato){
            $nuovo_evento = new classe_log_eventi_rpi();
            $nuovo_evento->set_data_evento(substr($arr_persone["PostDateTime"],0,10)." ".substr($arr_persone["PostDateTime"],11));
            $nuovo_evento->set_genere($persona["Gender"]);
            $nuovo_evento->set_eta($persona["AgeGroup"]);
            $nuovo_evento->set_etnia($persona["Race"]);
            $nuovo_evento->set_disappearance_datetime(null);
            $nuovo_evento->set_processato(0);
            $nuovo_evento->set_camera_id($persona["ID"]);
            $nuovo_evento->set_appearance_datetime(substr($persona["AppearanceDateTime"],0,10)." ".substr($persona["AppearanceDateTime"],11));
            $nuovo_evento->salva(false);
        }
    }
}

foreach($arr_log as $log_evento){
    $log_evento->set_disappearance_datetime(date("Y-m-d H:i:s"));
    $log_evento->salva(false);
}
echo "ok";


