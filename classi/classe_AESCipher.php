<?php
class classe_AESCipher {
    private $key;
    private $mode = "cbc";
    private $cipher = MCRYPT_RIJNDAEL_128;
    private $openssl_cipher = "AES-256-CBC";
    private $BS = 16; //questo non puo' cambiare per ora



    public function __construct($my_key){
        $this->key = $my_key;
    }

    public function encrypt($msg){
        return $this->openssl_encrypt($msg);
    }

    public function decrypt($msg){
        return $this->openssl_decrypt($msg);
    }

    public function mcrypt_decrypt($msg){
        $data = base64_decode($msg);
        $iv= substr($data,0,$this->BS);
        $mex= substr($data,$this->BS);
        $out = mcrypt_decrypt($this->cipher,$this->key,$mex,$this->mode,$iv);
        $temp = ord(substr($out,strlen($out)-1));
        $out = substr($out,0,strlen($out)-$temp);
        return $out;
    }

    public function mcrypt_encrypt($msg){
        if($this->BS>0){
            $complement = $this->BS-strlen($msg)%$this->BS;
        }else{
            $complement = 0;
        }
        for($i=0;$i<$complement;$i++){
            $msg.=chr($complement);
        }

        $wasItSecure = false;
        $count= 0;
        while(!$wasItSecure){
            $iv = openssl_random_pseudo_bytes($this->BS, $wasItSecure);
            $count++;
            if($count>100){
                die();
            }
        }
        $out = mcrypt_encrypt($this->cipher,$this->key,$msg,$this->mode,$iv);
        
        $out = base64_encode($iv.$out);
        return $out;
    }

    public function openssl_decrypt($msg) {
        $data = base64_decode($msg);
        $iv= substr($data,0,$this->BS);
        $mex= substr($data,$this->BS);
        $output = openssl_decrypt($mex, $this->openssl_cipher, $this->key, true, $iv);
        return $output;
    }

    public function openssl_encrypt($msg){
        $wasItSecure = false;
        $count= 0;
        while(!$wasItSecure){
            $iv = openssl_random_pseudo_bytes($this->BS, $wasItSecure);
            $count++;
            if($count>100){
                die();
            }
        }
        $out  = openssl_encrypt($msg, $this->openssl_cipher, $this->key, true, $iv);
        $out = base64_encode($iv.$out);
        return $out;
    }

}
?>