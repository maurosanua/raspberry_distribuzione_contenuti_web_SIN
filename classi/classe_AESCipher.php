<?php
class classe_AESCipher {
    private $key;
    private $mode = "cbc";
    private $cipher = MCRYPT_RIJNDAEL_128;
    private $BS = 16;



    public function __construct($my_key){
        $this->key = $my_key;
    }


    public function decrypt($msg){
        $data = base64_decode($msg);
        $iv= substr($data,0,16);
        $mex= substr($data,16);
        $out = mcrypt_decrypt($this->cipher,$this->key,$mex,$this->mode,$iv);
        $temp = ord(substr($out,strlen($out)-1));
        $out = substr($out,0,strlen($out)-$temp);
        return $out;
    }

    public function encrypt($msg){

        $complement = $this->BS-strlen($msg)%$this->BS;
        for($i=0;$i<$complement;$i++){
            $msg.=chr($complement);
        }

        $wasItSecure = false;
        $count= 0;
        while(!$wasItSecure){
            $iv = openssl_random_pseudo_bytes(16, $wasItSecure);
            $count++;
            if($count>100){
                die();
            }
        }
        $out = mcrypt_encrypt($this->cipher,$this->key,$msg,$this->mode,$iv);
        
        $out = base64_encode($iv.$out);
        return $out;
    }

    
}
?>