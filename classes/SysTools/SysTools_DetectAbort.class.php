<?php
class SysTools_DetectAbort {
    static $instance=null;
    static $usingHeartBeat=false;
    
    private function __construct($additional_headers=array(),$shutdown_function=null) {
        if(headers_sent())
            throw new Exception(__METHOD__.' - Las cabeceras ya fueron enviadas. DetectAbort debe ser utilizado antes de enviar cabeceras');
        SysTools_DetectAbort::$usingHeartBeat=true;
        foreach($additional_headers as $h) {
            header($h);
        }
        header('Transfer-Encoding: chunked');
        ob_flush();
        flush();
        ob_start();
        if(is_null($shutdown_function)) {
            register_shutdown_function(array(&$this,'shutdown'));
        }else{
            register_shutdown_function($shutdown_function);
        }
    }
    
    static function beginDetectAbort($additional_headers=array(),$shutdown_function=null) {
        if(is_null(SysTools_DetectAbort::$instance)) {
            SysTools_DetectAbort::$instance=new SysTools_DetectAbort($additional_headers);
        }
        return SysTools_DetectAbort::instance;
    }
    
    function shutdown() {
        if(connection_aborted())
            die();
        $buffer=ob_get_clean();
        echo dechex(strlen($buffer))."\r\n".$buffer."\r\n";
        echo '0'."\r\n\r\n";
        ob_flush();
        flush();
    }


    function heartbeat() {
        $buffer=ob_get_clean();
        if($buffer!='')
            throw new Exception(__METHOD__.' - Mientras se usa HeartBeat, no se puede hacer ningun tipo de salida');
        echo '0'."\r\n";
        ob_flush();
        flush();
    }
    
    static function usingHeartBeat() {
        return SysTools_DetectAbort::$usingHeartBeat;
    }
    
    
}