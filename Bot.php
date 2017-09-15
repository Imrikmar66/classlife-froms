<?php 
class Bot {

    private $webHook = "";
    private $message = "";

    function __construct( $webHook ){
        $this->webHook = $webHook;
    }

    function createMessage(){
        $this->message = "start";
    }

    function addLine( $msg ){
        if( $this->message == "start" )
            $this->message = $msg;
        else
            $this->message .= "\n".$msg;

    }

    function send() {
        if( $this->message == "" )
            throw new Exception("Pas de message préparé. Essayer createMessage() avant d'utiliser send()");
        else{
            $this->sendMessage( $this->message );
            $this->message = "";
        }
    }

    function sendData( Array $datas ){
        
        $json_datas = json_encode( $datas );

        $req = curl_init( $this->webHook );
        curl_setopt($req, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
        curl_setopt($req, CURLOPT_POSTFIELDS, $json_datas);                                                                  
        curl_setopt($req, CURLOPT_RETURNTRANSFER, true);                                                                      
        curl_setopt($req, CURLOPT_HTTPHEADER, array(                                                                          
            'Content-Type: application/json',                                                                                
            'Content-Length: ' . strlen($json_datas))                                                                       
        );                                                                                                                   
        
        return curl_exec( $req );

    }

    function sendMessage( $msg ){
        $this->sendData( [
            "text" => $msg
        ] );
    }
}